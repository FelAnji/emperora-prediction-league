<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action ('rest_api_init', 'epl_register_payment_routes');

function epl_register_payment_routes() {
    register_rest_route ('emperora/v1', '/buy', [
        'methods' => 'POST',
        'callback' => 'epl_buy_credits',
        'permission_callback' => fn() => is_user_logged_in()
    ]);

    register_rest_route ('emperora/v1', '/webhook', [
        'methods' => 'POST',
        'callback' => 'epl_handle_webhook',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route ('emperora/v1', '/balance', [
        'methods' => 'GET',
        'callback' => 'epl_get_balance_endpoint',
        'permission_callback' => fn() => is_user_logged_in()
    ]);
}

function epl_get_balance_endpoint ($request) {
    $user_id = get_current_user_id();
    $balance = epl_get_balance($user_id);

    return rest_ensure_response($balance);
}

function epl_buy_credits($request) {
    $user_id = get_current_user_id();
    $email = wp_get_current_user()->user_email;
    $amount = $request['amount'];

    $response = wp_remote_post('https://api.paystack.co/transaction/initialize', [
    'headers' => [
        'Authorization' => 'Bearer ' . EPL_PAYSTACK_SECRET_KEY,
        'Content-Type'  => 'application/json',
    ],
    'body' => json_encode([
            'email'  => $email,
            'amount' => $amount * 100, // Paystack uses kobo, so multiply by 100
            'metadata' => [
                'credits' => $request['amount']
            ]
        ]),
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('paystack_error', 'Payment initialisation failed', ['status' => 500]);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    return rest_ensure_response($body);
}

function epl_handle_webhook($request) {
    // 1. Get the raw request body
    $body = $request->get_body();

    // 2. Verify the signature
    $paystack_signature = $request->get_header('x-paystack-signature');
    $expected_signature = hash_hmac('sha512', $body, EPL_PAYSTACK_SECRET_KEY);

    if ($paystack_signature !== $expected_signature) {
        return new WP_Error('invalid_signature', 'Unauthorized', ['status' => 403]);
    }

    // 3. Decode the event
    $event = json_decode($body, true);

    // 4. Only handle successful payments
    if ($event['event'] !== 'charge.success') {
        return rest_ensure_response(['message' => 'Event ignored']);
    }

    // 5. Get the user by email
    $email = $event['data']['customer']['email'];
    $user = get_user_by('email', $email);

    if (!$user) {
        return new WP_Error('user_not_found', 'User not found', ['status' => 404]);
    }

    // 6. Add credits to the user
    $amount = $event['data']['metadata']['credits'];
    epl_add_credits($user->ID, $amount);

    return rest_ensure_response(['success' => true]);
}