<?php 

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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $index => $row) : 
                    $rank = $index + 1;
                    $rank_class = $rank <= 3 ? "epl-rank-{$rank}" : '';
                ?>
                    <tr class="<?php echo $rank_class; ?>">
                        <td><span class="epl-rank-num"><?php echo $rank; ?></span></td>
                        <td><?php echo get_userdata($row->user_id)->user_login; ?></td>
                        <td><?php echo $row->total_games; ?></td>
                        <td class="epl-pts"><?php echo $row->total_points; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>