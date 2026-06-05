<?php
/**
 * Plugin Name:       Emperora Prediction League
 * Description:       An interactive block with the Interactivity API.
 * Version:           0.1.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       emperora-prediction-league
 *
 * @package           create-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once plugin_dir_path(__FILE__) . 'inc/class-rest-api.php';
require_once plugin_dir_path(__FILE__) . 'inc/class-point.php';
require_once plugin_dir_path(__FILE__) . 'inc/install.php';
require_once plugin_dir_path(__FILE__) . 'inc/class-credit.php';
require_once plugin_dir_path(__FILE__) . 'inc/class-payment.php';
require_once plugin_dir_path(__FILE__) . 'inc/class-admin.php';
require_once plugin_dir_path(__FILE__) . 'inc/class-login.php';

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */

//Creation of custom DB Table
function epl_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Predictions table
    $predictions = $wpdb->prefix . 'epl_predictions';
    $sql1 = "CREATE TABLE $predictions (
        id                   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        match_id             BIGINT(20) UNSIGNED NOT NULL,
        user_id              BIGINT(20) UNSIGNED NOT NULL,
        season_id            BIGINT(20) UNSIGNED DEFAULT NULL,
        round_id             BIGINT(20) UNSIGNED DEFAULT NULL,
        predicted_winner     VARCHAR(20) NOT NULL,
        predicted_score_home TINYINT UNSIGNED DEFAULT NULL,
        predicted_score_away TINYINT UNSIGNED DEFAULT NULL,
        points_earned        TINYINT UNSIGNED DEFAULT 0,
        created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY match_user (match_id, user_id)
    ) $charset_collate;";

    // Seasons table
    $seasons = $wpdb->prefix . 'epl_seasons';
    $sql2 = "CREATE TABLE $seasons (
        id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        title      VARCHAR(100) NOT NULL,
        status     ENUM('active', 'completed') NOT NULL DEFAULT 'active',
        start_date DATE DEFAULT NULL,
        end_date   DATE DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Rounds table
    $rounds = $wpdb->prefix . 'epl_rounds';
    $sql3 = "CREATE TABLE $rounds (
        id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        season_id  BIGINT(20) UNSIGNED NOT NULL,
        title      VARCHAR(100) NOT NULL,
        status     ENUM('active', 'completed') NOT NULL DEFAULT 'active',
        entry_fee INT NOT NULL DEFAULT 0,
        start_date DATE DEFAULT NULL,
        end_date   DATE DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY season_id (season_id)
    ) $charset_collate;";

    $round_entries = $wpdb->prefix . 'epl_round_entries';
    $sql4 = "CREATE TABLE $round_entries (
        id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id    BIGINT(20) UNSIGNED NOT NULL,
        round_id   BIGINT(20) UNSIGNED NOT NULL,
        amount_paid INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_entry (user_id, round_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql1 );
    dbDelta( $sql2 );
    dbDelta( $sql3 );
    dbDelta( $sql4 );

    update_option( 'epl_db_version', '1.2' );
}

register_activation_hook( __FILE__, 'epl_create_tables' );
register_activation_hook( __FILE__, 'emperora_create_payment_tables' );

add_action( 'plugins_loaded', function() {
    if ( get_option('epl_db_version') !== '1.2' ) {
        epl_create_tables();
    }
});

function create_block_emperora_prediction_league_block_init() {
	register_block_type_from_metadata( __DIR__ . '/build' );
  register_block_type_from_metadata( __DIR__ . '/build/leaderboard' );
}
add_action( 'init', 'create_block_emperora_prediction_league_block_init' );

function match_post_type () {
    register_post_type('epl_match', [ 
        'label' => 'Matches', 
        'public' => true, 
        'show_ui' => true, 
        'show_in_rest'  => true,
        'menu_icon'     => 'dashicons-tickets-alt',
		'supports'     => [ 'title', 'editor', 'custom-fields' ],
      'has_archive'  => true,
    ]);
}


function match_meta() {
  register_post_meta('epl_match', 'home_team', [
    'single' => true,
    'type' => 'string',
    'show_in_rest' => true,
    'auth_callback' => fn() => current_user_can('edit_posts'),
  ]);

  register_post_meta('epl_match', 'away_team', [
    'single' => true,
    'type' => 'string',
    'show_in_rest' => true,
    'auth_callback' => fn() => current_user_can('edit_posts'),
  ]);

  register_post_meta('epl_match', 'home_score', [
    'single' => true,
    'type' => 'number',
    'show_in_rest' => true,
    'auth_callback' => fn() => current_user_can('edit_posts'),
  ]);

  register_post_meta('epl_match', 'away_score', [
    'single' => true,
    'type' => 'number',
    'show_in_rest' => true,
    'auth_callback' => fn() => current_user_can('edit_posts'),
  ]);

  register_post_meta('epl_match', 'match_status', [
        'single'        => true,
        'type'          => 'string',   // 'upcoming' | 'completed'
        'show_in_rest'  => true,
        'default'       => 'upcoming',
        'auth_callback' => fn() => current_user_can('edit_posts'),
  ]);

  register_post_meta('epl_match', 'round_id', [
    'single' => true,
    'type' => 'integer',
    'show_in_rest' => true,
    'auth_callback' => fn() => current_user_can('edit_posts')
  ]);

  register_post_meta('epl_match', 'season_id', [
    'single' => true,
    'type' => 'integer',
    'show_in_rest' => true,
    'auth_callback' => fn() => current_user_can('edit_posts')
  ]);
}

add_action('init', 'match_post_type');
add_action('init', 'match_meta');
