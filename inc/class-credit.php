<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function epl_get_balance($user_id) {
    global $wpdb;

    $credits = $wpdb->get_var($wpdb->prepare(
        "SELECT balance FROM {$wpdb->prefix}epl_credits WHERE user_id = %d",
        $user_id
    ));
    return $credits ?: 0;

}

function epl_add_credits($user_id, $amount) {
    global $wpdb;

    $balance = epl_get_balance($user_id);

    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}epl_credits WHERE user_id = %d",
        $user_id
    ));
    if ($existing) {
        $wpdb -> update (
            $wpdb->prefix . 'epl_credits',
            [
                'balance' => $balance + $amount,
            ],
            [
                'user_id' => $user_id
            ]
        );
    } else {
        $wpdb -> insert (
            $wpdb->prefix . 'epl_credits', 
            [
                'user_id' => $user_id,
                'balance' => $amount
            ]
        );
    }
}

function epl_deduct_credit($user_id) {
    global $wpdb;

    $balance =  epl_get_balance($user_id);

    if ($balance > 0) {
        $wpdb -> update (
            $wpdb->prefix . 'epl_credits', 
            [
                'balance' => $balance - 1
            ],
            [
                'user_id' => $user_id,
            ]
        );
        return true;
    }
    return false;
}