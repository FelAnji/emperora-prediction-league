<?php

function emperora_create_payment_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Wallets table - stores each user's credit balance
    $epl_credits = $wpdb->prefix . 'epl_credits';
    $credits_sql = "CREATE TABLE $epl_credits (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        balance INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";

    // Transactions table - logs every credit movement
    $epl_transactions = $wpdb->prefix . 'epl_transactions';
    $transactions_sql = "CREATE TABLE $epl_transactions (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        type ENUM('topup', 'entry_fee', 'refund') NOT NULL,
        amount INT NOT NULL,
        reference VARCHAR(100) DEFAULT NULL,
        note TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    // Contests table - each prediction contest
    $contests_table = $wpdb->prefix . 'emperora_contests';
    $contests_sql = "CREATE TABLE $contests_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        entry_fee INT NOT NULL DEFAULT 0,
        status ENUM('draft', 'open', 'closed', 'cancelled') NOT NULL DEFAULT 'draft',
        start_date DATETIME DEFAULT NULL,
        end_date DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Entries table - which user entered which contest
    $entries_table = $wpdb->prefix . 'emperora_entries';
    $entries_sql = "CREATE TABLE $entries_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        contest_id BIGINT UNSIGNED NOT NULL,
        credits_spent INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_entry (user_id, contest_id),
        KEY contest_id (contest_id)
    ) $charset_collate;";

    $epl_seasons = $wpdb->prefix . 'epl_seasons';
    $season_sql = "CREATE TABLE $epl_seasons (

        
    ) $charset_collate;";

    $epl_rounds = $wpdb->prefix . 'epl_rounds';
    $rounds_sql = "CREATE TABLE $epl_rounds (

        
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $credits_sql );
    dbDelta( $transactions_sql );
    dbDelta( $contests_sql );
    dbDelta( $entries_sql );
}