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

function epl_save_settings() {
    if (isset($_POST['epl_save_settings'])) {
        if (!wp_verify_nonce($_POST['epl_nonce'], 'epl_save_settings')) return;
        update_option('epl_paystack_secret', sanitize_text_field($_POST['epl_paystack_secret']));
        update_option('epl_paystack_public', sanitize_text_field($_POST['epl_paystack_public']));
    }
}

add_action( 'admin_menu', 'epl_register_settings_page' );
add_action( 'admin_init', 'epl_save_settings' );