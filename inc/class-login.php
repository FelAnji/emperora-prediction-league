<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function epl_login_styles() {
    ?>
    <style>
        body.login {
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        body.login div#login {
            background: #fff;
            border-radius: 12px;
            border: 0.5px solid rgba(0, 0, 0, 0.15);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: none;
        }

        /* Logo */
        body.login h1 a {
            background-image: url('<?php echo esc_url(get_site_icon_url(80)); ?>');            
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            width: 80px;
            height: 80px;
            display: block;
            margin: 0 auto 1rem;
        }

        /* Form fields */
        body.login form {
            border: none;
            box-shadow: none;
            padding: 0;
            background: none;
        }

        body.login form .input,
        body.login input[type="text"],
        body.login input[type="password"] {
            border: 0.5px solid rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            width: 100%;
            box-shadow: none;
            margin-bottom: 0.5rem;
        }

        body.login form .input:focus,
        body.login input[type="text"]:focus,
        body.login input[type="password"]:focus {
            border-color: #FF2800;
            outline: none;
            box-shadow: none;
        }

        body.login label {
            font-size: 13px;
            color: #666;
            font-weight: 500;
        }

        /* Submit button */
        body.login .button-primary {
            background: #FF2800;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 500;
            color: #fff;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.15s;
            box-shadow: none;
            text-shadow: none;
        }

        body.login .button-primary:hover,
        body.login .button-primary:focus {
            background: #cc2000;
            border: none;
            box-shadow: none;
        }

        /* Links */
        body.login #nav a,
        body.login #backtoblog a {
            color: #FF2800;
            font-size: 13px;
        }

        body.login #nav a:hover,
        body.login #backtoblog a:hover {
            color: #cc2000;
        }

        /* Error messages */
        body.login #login_error {
            border-left: 4px solid #FF2800;
            border-radius: 8px;
            font-size: 13px;
        }

        /* Remove default WP login styles */
        body.login form .forgetmenot {
            font-size: 13px;
            color: #666;
        }

        @media (max-width: 480px) {
            body.login div#login {
                margin: 1rem;
                padding: 1.5rem;
            }
        }

        /* Registration page */
        body.login.wp-core-ui input[type="tel"] {
            border: 0.5px solid rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            width: 100%;
            box-shadow: none;
            margin-bottom: 0.5rem;
        }

        body.login.wp-core-ui input[type="tel"]:focus {
            border-color: #FF2800;
            outline: none;
            box-shadow: none;
        }
    </style>
    <?php
}

function epl_login_logo_url() {
    return home_url();
}

function epl_login_logo_url_title() {
    return get_bloginfo('name');
}

add_action('login_enqueue_scripts', 'epl_login_styles');
add_filter('login_headerurl', 'epl_login_logo_url');
add_filter('login_headertext', 'epl_login_logo_url_title');

// Add extra fields to registration form
function epl_register_extra_fields() {
    ?>
    <p>
        <label for="full_name">Full Name<br>
            <input 
                type="text" 
                id="full_name" 
                name="full_name" 
                class="input" 
                value="<?php echo isset($_POST['full_name']) ? esc_attr($_POST['full_name']) : ''; ?>" 
                placeholder="Your full name"
            />
        </label>
    </p>
    <p>
        <label for="phone_number">Phone Number<br>
            <input 
                type="tel" 
                id="phone_number" 
                name="phone_number" 
                class="input" 
                value="<?php echo isset($_POST['phone_number']) ? esc_attr($_POST['phone_number']) : ''; ?>" 
                placeholder="Your phone number"
            />
        </label>
    </p>
    <?php
}

// Validate extra fields
function epl_validate_extra_fields($errors, $sanitized_user_login, $user_email) {
    if (empty($_POST['full_name'])) {
        $errors->add('full_name_error', '<strong>Error:</strong> Please enter your full name.');
    }
    if (empty($_POST['phone_number'])) {
        $errors->add('phone_number_error', '<strong>Error:</strong> Please enter your phone number.');
    }
    return $errors;
}

// Save extra fields after registration
function epl_save_extra_fields($user_id) {
    if (!empty($_POST['full_name'])) {
        update_user_meta($user_id, 'full_name', sanitize_text_field($_POST['full_name']));
    }
    if (!empty($_POST['phone_number'])) {
        update_user_meta($user_id, 'phone_number', sanitize_text_field($_POST['phone_number']));
    }
}

function epl_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('subscriber', $user->roles)) {
            return home_url('/epl_match/');
        }
    }
    return $redirect_to;
}


add_action('register_form', 'epl_register_extra_fields');
add_filter('registration_errors', 'epl_validate_extra_fields', 10, 3);
add_action('user_register', 'epl_save_extra_fields');
add_filter('login_redirect', 'epl_login_redirect', 10, 3);