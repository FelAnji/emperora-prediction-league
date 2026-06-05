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

    register_rest_route('emperora/v1', '/enter-round', [
        'methods'             => 'POST',
        'callback'            => 'epl_enter_round',
        'permission_callback' => fn() => is_user_logged_in()
    ]);
}

function epl_enter_round($request) {
    $user_id  = get_current_user_id();
    $email    = wp_get_current_user()->user_email;
    $round_id = intval($request['round_id']);
    $amount   = intval($request['amount']);

    global $wpdb;

    // Get the round's entry fee
    $round = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}epl_rounds WHERE id = %d",
        $round_id
    ));

    if (!$round) {
        return new WP_Error('invalid_round', 'Round not found', ['status' => 404]);
    }

    if ($amount < $round->entry_fee) {
        return new WP_Error('invalid_amount', 'Amount is below the minimum entry fee', ['status' => 400]);
    }

    // Check if user already entered
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}epl_round_entries WHERE user_id = %d AND round_id = %d",
        $user_id, $round_id
    ));

    if ($existing) {
        return new WP_Error('already_entered', 'You have already entered this round', ['status' => 400]);
    }

    // Initialise Paystack payment
    $response = wp_remote_post('https://api.paystack.co/transaction/initialize', [
        'headers' => [
            'Authorization' => 'Bearer ' . EPL_PAYSTACK_SECRET_KEY,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'email'        => $email,
            'amount'       => $amount * 100, // kobo
            'metadata'     => [
                'user_id'   => $user_id,
                'round_id'  => $round_id,
                'amount'    => $amount,
                'type'      => 'round_entry'
            ]
        ]),
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('paystack_error', 'Payment initialisation failed', ['status' => 500]);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return rest_ensure_response($body);
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

    if (!$amount || intval($amount) < 12) {
        return new WP_Error('invalid_amount', 'Minimum purchase is 12 credits', ['status' => 400]);
    }

    $match_id = $request['match_id'];

    $callback_url = add_query_arg([
        'epl_payment' => 'verify',
        'user_id'     => $user_id,
        'match_id' => $match_id
    ], get_permalink($match_id));
    error_log('Callback URL: ' . $callback_url);

    $response = wp_remote_post('https://api.paystack.co/transaction/initialize', [
    'headers' => [
        'Authorization' => 'Bearer ' . EPL_PAYSTACK_SECRET_KEY,
        'Content-Type'  => 'application/json',
    ],
    'body' => json_encode([
            'email'  => $email,
            'amount' => $amount * 25 * 100, // Paystack uses kobo, so multiply by 100
            'callback_url' => $callback_url, 
            'metadata' => [
                'user_id' => $user_id,  
                'credits' => $request['amount']
            ]
        ]),
    ]);

    if (is_wp_error($response)) {
        error_log('Paystack error: ' . $response->get_error_message());
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
    $metadata = $event['data']['metadata'];
    $user_id  = intval($metadata['user_id']);
    $amount   = intval($metadata['credits']);

    if (!$user_id) {
        return new WP_Error('user_not_found', 'User not found', ['status' => 404]);
    }

    $type = $metadata['type'] ?? 'credits';

    if ($type === 'round_entry') {
        $round_id   = intval($metadata['round_id']);
        $amount_paid = intval($metadata['amount']);

        $wpdb->insert(
            $wpdb->prefix . 'epl_round_entries',
            [
                'user_id'    => $user_id,
                'round_id'   => $round_id,
                'amount_paid' => $amount_paid,
            ]
        );
    } else {
        $amount = $metadata['credits'];
        epl_add_credits($user_id, $amount);
    }

    return rest_ensure_response(['success' => true]);
}