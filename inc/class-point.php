<?php

add_action('rest_after_insert_epl_match', 'epl_calculate_points', 10, 1);

function epl_calculate_points($post) {
     global $wpdb;

    $post_id = $post->ID;

    $all_meta = get_post_meta($post_id);
    error_log(print_r($all_meta, true));

    $match_status = get_post_meta($post_id, 'match_status', true);
    
    if ($match_status !== 'completed') return; 

        $match_id = $post_id;
        $home_score = get_post_meta($post_id, 'home_score', true);
        $away_score = get_post_meta($post_id, 'away_score', true);

        error_log("home_score: $home_score | away_score: $away_score");

        $predictions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}epl_predictions WHERE match_id = %d",
            $match_id
        ));

        foreach ($predictions as $prediction) {
            $points=0;

            if ($prediction->predicted_winner === 'home' && $home_score > $away_score) {
                $points = 3;
            }
            elseif ($prediction->predicted_winner === 'away' && $home_score < $away_score) {
                $points = 3;
            }
            elseif ($prediction->predicted_winner === 'draw' && $home_score == $away_score) {
                $points = 5;
            }
            elseif ($prediction->predicted_winner === 'predict_score' 
                && $prediction->predicted_score_home == $home_score 
                && $prediction->predicted_score_away == $away_score) {
                $points = 10;
            }
            else {
                $points = 0;
            }

            error_log("User: {$prediction->predicted_winner} | Points awarded: $points"); // ← confirm points

            $wpdb->update (
                $wpdb->prefix . 'epl_predictions', [
                    'points_earned' => $points,
                ],
                [
                    'id' => $prediction->id,
                ]
            );
        }
}