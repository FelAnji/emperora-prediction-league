<?php 

    function register_routes () {
        register_rest_route('emperora/v1', '/predict', [
            'methods' => 'POST',
            'callback' => 'save_prediction',
            'permission_callback' => fn() => is_user_logged_in()
        ]);

        register_rest_route('emperora/v1', '/prediction/(?P<match_id>\d+)', [
            'methods' => 'GET',
            'callback' => 'get_prediction',
            'permission_callback' => fn() => is_user_logged_in()
        ]);
    }

    function get_prediction ($request) {
        global $wpdb;

        $user_id = get_current_user_id();
        $match_id = $request['match_id'];

        $prediction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}epl_predictions WHERE match_id = %d AND user_id = %d",
            $match_id, $user_id
        ));

        return rest_ensure_response($prediction ?: (object)[]);
    }

    function save_prediction ($request) {
        global $wpdb;

        $user_id = get_current_user_id();
        $match_id = intval($request['match_id']);
        $predicted_winner = sanitize_text_field($request['predicted_winner']);

        $score_home = isset($request['predicted_score_home']) ? intval($request['predicted_score_home']) : null; 
        $score_away = isset($request['predicted_score_away']) ? intval($request['predicted_score_away']) : null;

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}epl_predictions WHERE match_id = %d AND user_id = %d",
            $match_id, $user_id
        )); 
        if ($existing) {
            $wpdb -> update(
                $wpdb->prefix . 'epl_predictions',
                [
                    'predicted_winner' => $predicted_winner, 
                    'predicted_score_home' => $score_home, 
                    'predicted_score_away' => $score_away
                ], // data to update
                [
                    'id' => $existing
                ]  // where condition
            );
        } else {
            $wpdb -> insert(
                $wpdb->prefix . 'epl_predictions', [
                    'match_id' => $match_id, 
                    'user_id' => $user_id, 
                    'predicted_winner' => $predicted_winner, 
                    'predicted_score_home' => $score_home, 
                    'predicted_score_away' => $score_away
                ]
            );
            epl_deduct_credit($user_id);
        }

        return rest_ensure_response(['success' => true]);
    }

add_action ('rest_api_init', 'register_routes');
