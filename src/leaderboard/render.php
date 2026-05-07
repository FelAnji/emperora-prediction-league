<?php 

if (!function_exists('epl_get_prediction_indicator')) {
    function epl_get_prediction_indicator($prediction) {
        $letter = match($prediction->predicted_winner) {
        'predict_score' => 'S',
        'draw'          => 'D',
        'home', 'away'  => 'W',
        default         => '?'
    };

    if ($prediction->match_status !== 'completed') {
        $colour = 'grey';
    } elseif ($prediction->points_earned > 0) {
        $colour = 'green';
    } else {
        $colour = 'red';
    }

    return ['letter' => $letter, 'colour' => $colour];
    }
}

global $wpdb;

$results = $wpdb->get_results(
    "SELECT user_id, COUNT(*) as total_games, SUM(points_earned) as total_points
    FROM {$wpdb->prefix}epl_predictions
    GROUP BY user_id
    ORDER BY total_points DESC" 
);

?>

<div <?php echo get_block_wrapper_attributes(); ?>>
    <div class="epl-leaderboard-header">
        <h2 class="epl-leaderboard-title">EmperorA PL Leaderboard</h2>
        <button class="epl-rules-btn">Rules</button>
    </div>

    <div class="epl-rules-overlay">
        <div class="epl-rules-modal">
            <h3 class="epl-rules-title">How points are earned</h3>
            <ul class="epl-rules-list">
                <li><span class="epl-rules-pts">3pts</span> Correct result</li>
                <li><span class="epl-rules-pts">5pts</span> Correct draw prediction</li>
                <li><span class="epl-rules-pts">10pts</span> Exact score prediction</li>
            </ul>
            <button class="epl-rules-close">Close</button>
        </div>
    </div>

    <?php if (empty($results)) : ?>
        <div class="epl-empty">
            <p>No predictions yet. Be the first to play!</p>
        </div>
    <?php else : ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Player</th>
                    <th>G</th>
                    <th>Pts</th>
                    <th>Last 5</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $index => $row) : 
                    $rank = $index + 1;
                    $rank_class = $rank <= 3 ? "epl-rank-{$rank}" : '';

                    $last_5 = $wpdb->get_results($wpdb->prepare(
                        "SELECT p.predicted_winner, p.predicted_score_home, p.predicted_score_away, p.points_earned, m.post_status,
                                pm.meta_value AS match_status
                        FROM {$wpdb->prefix}epl_predictions p
                        JOIN {$wpdb->prefix}posts m ON p.match_id = m.ID
                        JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = m.ID AND pm.meta_key = 'match_status'
                        WHERE p.user_id = %d
                        ORDER BY p.created_at DESC
                        LIMIT 5",
                        $row->user_id
                    ));

                    $last_5 = array_reverse($last_5);
                ?>
                    <tr class="<?php echo $rank_class; ?>">
                        <td><span class="epl-rank-num"><?php echo $rank; ?></span></td>
                        <td><?php echo mb_strimwidth(get_userdata($row->user_id)->display_name, 0, 12, '...'); ?></td>
                        <td><?php echo $row->total_games; ?></td>
                        
                        <td class="epl-pts"><?php echo $row->total_points; ?></td>    
                        <td class="epl-last-5">
                            <?php foreach ($last_5 as $prediction) : ?>
                                <?php $indicator = epl_get_prediction_indicator($prediction); ?>
                                <span class="epl-indicator--<?= $indicator['colour'] ?>">
                                    <?= $indicator['letter'] ?>
                                </span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>