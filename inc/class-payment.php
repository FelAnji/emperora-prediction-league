<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action ('rest_api_init', 'epl_register_payment_routes');

function epl_register_payment_routes() {

    register_rest_route ('emperora/v1', '/webhook', [
        'methods' => 'POST',
        'callback' => 'epl_handle_webhook',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('emperora/v1', '/enter-round', [
        'methods'             => 'POST',
        'callback'            => 'epl_enter_round',
        'permission_callback' => fn() => is_user_logged_in()
    ]);

    register_rest_route('emperora/v1', '/test', [
        'methods' => 'POST',
        'callback' => fn() => rest_ensure_response(['ok' => true]),
        'permission_callback' => fn() => is_user_logged_in()
    ]);

    register_rest_route('emperora/v1', '/verify-entry', [
        'methods'             => 'GET',
        'callback'            => 'epl_verify_entry',
        'permission_callback' => '__return_true'
    ]);
}

function epl_enter_round($request) {
    $user_id  = get_current_user_id();
    $email    = wp_get_current_user()->user_email;
    $round_id = intval($request['round_id']);
    $amount   = intval($request['amount']);
    $callback_url = !empty($request['callback_url']) 
        ? esc_url_raw($request['callback_url']) 
        : get_permalink();

    global $wpdb;

    $round = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}epl_rounds WHERE id = %d AND status = 'active'",
        $round_id
    ));

    if (!$round) {
        return new WP_Error('invalid_round', 'Round not found or closed', ['status' => 404]);
    }

    if ($amount < $round->entry_fee) {
        return new WP_Error('invalid_amount', 'Amount is below the minimum entry fee', ['status' => 400]);
    }

    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}emperora_entries WHERE user_id = %d AND round_id = %d",
        $user_id, $round_id
    ));

    if ($existing) {
        return new WP_Error('already_entered', 'You have already entered this round', ['status' => 400]);
    }

    $response = wp_remote_post('https://api.paystack.co/transaction/initialize', [
        'headers' => [
            'Authorization' => 'Bearer ' . EPL_PAYSTACK_SECRET_KEY,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'email'    => $email,
            'amount'   => $amount * 100,
            'callback_url' => $callback_url,
            'metadata' => [
                'user_id'  => $user_id,
                'round_id' => $round_id,
                'amount'   => $amount,
                'type'     => 'round_entry'
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
    $body = $request->get_body();

    $paystack_signature = $request->get_header('x-paystack-signature');
    $expected_signature = hash_hmac('sha512', $body, EPL_PAYSTACK_SECRET_KEY);

    if ($paystack_signature !== $expected_signature) {
        return new WP_Error('invalid_signature', 'Unauthorized', ['status' => 403]);
    }

    $event = json_decode($body, true);

    if ($event['event'] !== 'charge.success') {
        return rest_ensure_response(['message' => 'Event ignored']);
    }

    $metadata = $event['data']['metadata'];
    $user_id  = intval($metadata['user_id']);

    if (!$user_id) {
        return new WP_Error('user_not_found', 'User not found', ['status' => 404]);
    }

    $type = $metadata['type'] ?? '';

    if ($type === 'round_entry') {
        global $wpdb;
        $round_id    = intval($metadata['round_id']);
        $amount_paid = intval($metadata['amount']);

        $wpdb->insert(
            $wpdb->prefix . 'emperora_entries',
            [
                'user_id'     => $user_id,
                'round_id'    => $round_id,
                'amount_paid' => $amount_paid,
            ]
        );
    }

    return rest_ensure_response(['success' => true]);
}

function epl_verify_entry($request) {
    $reference = sanitize_text_field($request['reference']);

    if (!$reference) {
        return new WP_Error('missing_reference', 'No reference provided', ['status' => 400]);
    }

    $response = wp_remote_get('https://api.paystack.co/transaction/verify/' . $reference, [
        'headers' => [
            'Authorization' => 'Bearer ' . EPL_PAYSTACK_SECRET_KEY,
        ],
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('verify_failed', 'Verification request failed', ['status' => 500]);
    }

    $body  = json_decode(wp_remote_retrieve_body($response), true);
    $data  = $body['data'] ?? null;

    if (!$data || $data['status'] !== 'success') {
        return new WP_Error('payment_not_successful', 'Payment not verified', ['status' => 400]);
    }

    $metadata    = $data['metadata'];
    $user_id     = intval($metadata['user_id']);
    $round_id    = intval($metadata['round_id']);
    $amount_paid = intval($metadata['amount']);
    $type        = $metadata['type'] ?? '';

    if ($type !== 'round_entry' || !$user_id || !$round_id) {
        return new WP_Error('invalid_metadata', 'Invalid payment metadata', ['status' => 400]);
    }

    global $wpdb;

    // Avoid duplicate entry if webhook already fired
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}emperora_entries WHERE user_id = %d AND round_id = %d",
        $user_id, $round_id
    ));

    if (!$existing) {
        $wpdb->insert(
            $wpdb->prefix . 'emperora_entries',
            [
                'user_id'     => $user_id,
                'round_id'    => $round_id,
                'amount_paid' => $amount_paid,
            ]
        );
    }

    return rest_ensure_response(['success' => true, 'already_existed' => (bool) $existing]);
}