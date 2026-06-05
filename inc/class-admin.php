<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('EPL_PAYSTACK_SECRET_KEY', get_option('epl_paystack_secret'));
define('EPL_PAYSTACK_PUBLIC_KEY', get_option('epl_paystack_public'));

function epl_register_settings_page() {
    add_menu_page(
        'EmperorA PL',      
        'EmperorA PL',      
        'manage_options',  
        'emperora-pl',      
        'epl_render_settings_page',        
        'dashicons-table-col-after',
        30                 
    );
}

function epl_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Emperora Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('epl_save_settings', 'epl_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th>Paystack Secret Key</th>
                    <td>
                        <input 
                            type="text" 
                            name="epl_paystack_secret" 
                            value="<?php echo esc_attr(get_option('epl_paystack_secret')); ?>"
                        />
                    </td>
                </tr>
                <tr>
                    <th>Paystack Public Key</th>
                    <td>
                        <input 
                            type="text" 
                            name="epl_paystack_public" 
                            value="<?php echo esc_attr(get_option('epl_paystack_public')); ?>"
                        />
                    </td>
                </tr>
            </table>
            <input type="submit" name="epl_save_settings" value="Save Settings" class="button button-primary"/>
        </form>
    </div>
    <?php
}

function epl_render_seasons_page() {
    global $wpdb;

    if ( isset($_POST['epl_toggle_season']) ) {
        if ( ! wp_verify_nonce($_POST['epl_season_nonce'], 'epl_add_season') ) return;

        $toggle_id     = intval($_POST['toggle_season_id']);
        $toggle_status = sanitize_text_field($_POST['toggle_status']);
        $new_status    = $toggle_status === 'active' ? 'completed' : 'active';

        $wpdb->update(
            $wpdb->prefix . 'epl_seasons',
            [ 'status' => $new_status ],
            [ 'id'     => $toggle_id  ]
        );

        $wpdb->update(
            $wpdb->prefix . 'epl_rounds',
            [ 'status'    => $new_status ],  // data
            [ 'season_id' => $toggle_id  ]   // where
        );

    }

    if ( isset($_POST['epl_add_round']) ) {
        if ( ! wp_verify_nonce($_POST['epl_round_nonce'], 'epl_add_round') ) return;

        $wpdb->insert(
            $wpdb->prefix . 'epl_rounds',
            [
                'season_id'  => intval($_POST['round_season_id']),
                'title'      => sanitize_text_field($_POST['round_title']),
                'start_date' => sanitize_text_field($_POST['round_start']),
                'end_date'   => sanitize_text_field($_POST['round_end']),
                'status'     => 'active',
                'entry_fee' => intval($_POST['round_entry_fee'])
            ]
        );

    }

    if ( isset ($_POST['epl_add_season']) ) {
        if ( ! wp_verify_nonce($_POST['epl_season_nonce'], 'epl_add_season') ) return;

        $wpdb->insert(
            $wpdb->prefix . 'epl_seasons',
            [
                'title' => sanitize_text_field($_POST['season_title']),
                'start_date' => sanitize_text_field($_POST['season_start']),
                'end_date'   => sanitize_text_field($_POST['season_end']),
                'status'     => 'active',
            ]
        );
    }

    $seasons = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}epl_seasons ORDER BY created_at DESC");
    $rounds = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}epl_rounds ORDER BY created_at DESC");
    ?>

     <div class="wrap">
        <h1>Seasons & Rounds</h1>

        <h2>Add Season</h2>
        <form method="post">
            <?php wp_nonce_field('epl_add_season', 'epl_season_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th>Title</th>
                    <td><input type="text" name="season_title" required /></td>
                </tr>
                <tr>
                    <th>Start Date</th>
                    <td><input type="date" name="season_start" /></td>
                </tr>
                <tr>
                    <th>End Date</th>
                    <td><input type="date" name="season_end" /></td>
                </tr>
            </table>
            <input type="submit" name="epl_add_season" value="Add Season" class="button button-primary" />
        </form>
        
        <h2>Existing Seasons</h2>
        <?php if ( empty($seasons) ) : ?>
            <p>No seasons yet.</p>
        <?php else : ?>
            <table class="widefat striped" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($seasons as $season) : ?>
                        <tr>
                            <td><?php echo $season->id; ?></td>
                            <td><?php echo esc_html($season->title); ?></td>
                            <td><?php echo esc_html($season->status); ?></td>
                            <td><?php echo esc_html($season->start_date); ?></td>
                            <td><?php echo esc_html($season->end_date); ?></td> 
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field('epl_add_season', 'epl_season_nonce'); ?>
                                    <input type="hidden" name="toggle_season_id" value="<?php echo $season->id; ?>" />
                                    <input type="hidden" name="toggle_status" value="<?php echo $season->status; ?>" />
                                    <input type="submit" name="epl_toggle_season" 
                                        value="<?php echo $season->status === 'active' ? 'Mark Completed' : 'Mark Active'; ?>" 
                                        class="button" />
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Add Round</h2>
        <form action="" method="post">
            <?php wp_nonce_field('epl_add_round', 'epl_round_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th>Season</th>
                    <td>
                        <select name="round_season_id">
                            <?php foreach ($seasons as $season) : ?>
                                <option value="<?php echo $season->id; ?>">
                                    <?php echo esc_html($season->title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Title</th>
                    <td><input type="text" name="round_title" required /></td>
                </tr>
                <tr>
                    <th>Start Date</th>
                    <td><input type="date" name="round_start" /></td>
                </tr>
                <tr>
                    <th>Entry Fee</th>
                    <td><input type="number" name="round_entry_fee" required></td>
                </tr>
                <tr>
                    <th>End Date</th>
                    <td><input type="date" name="round_end" /></td>
                </tr>
                
            </table>
            <input type="submit" name="epl_add_round" value="Add Round" class="button button-primary" />
        </form>

        <h2>Existing Rounds</h2>
        <table class="widefat striped" style="max-width: 600px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Season</th>
                    <th>Status</th>
                    <th>Start</th>
                    <th>End</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rounds)) : ?>
                    <p>No rounds yet.</p>
                <?php else : ?>
                    <?php foreach ($rounds as $round) : ?>
                        <tr>
                            <td><?php echo $round->id ?></td>
                            <td><?php echo esc_html($round->title) ?></td>
                            <td><?php echo esc_html($round->season_id) ?></td>
                            <td><?php echo esc_html($round->status) ?></td>
                            <td><?php echo esc_html($round->start_date) ?></td>
                            <td><?php echo esc_html($round->end_date) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function epl_save_settings() {
    if (isset($_POST['epl_save_settings'])) {
        if (!wp_verify_nonce($_POST['epl_nonce'], 'epl_save_settings')) return;
        update_option('epl_paystack_secret', sanitize_text_field($_POST['epl_paystack_secret']));
        update_option('epl_paystack_public', sanitize_text_field($_POST['epl_paystack_public']));
    }
}

function epl_register_seasons_page() {
    add_submenu_page(
        'emperora-pl',           // parent menu slug
        'Seasons & Rounds',      // page title
        'Seasons & Rounds',      // menu title
        'manage_options',        // capability
        'epl-seasons',           // menu slug
        'epl_render_seasons_page' // callback
    );
}

add_action( 'admin_menu', 'epl_register_settings_page' );
add_action('admin_menu', 'epl_register_seasons_page');
add_action( 'admin_init', 'epl_save_settings' );