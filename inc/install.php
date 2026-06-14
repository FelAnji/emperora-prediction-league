<?php

function emperora_create_payment_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Entries table - which user entered which round
    $entries_table = $wpdb->prefix . 'emperora_entries';
    $entries_sql = "CREATE TABLE $entries_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        round_id BIGINT UNSIGNED NOT NULL,
        amount_paid INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_entry (user_id, round_id),
        KEY round_id (round_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $entries_sql );
}

function emperora_run_migrations() {
    $current_version = get_option('emperora_db_version', '0');

    if (version_compare($current_version, '1.1', '<')) {
        global $wpdb;
        $wpdb->query("ALTER TABLE {$wpdb->prefix}emperora_entries CHANGE credits_spent amount_paid INT NOT NULL");
        update_option('emperora_db_version', '1.1');
    }
}
add_action('plugins_loaded', 'emperora_run_migrations');