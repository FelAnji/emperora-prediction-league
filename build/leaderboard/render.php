<?php 

global $wpdb;

// Handle Paystack redirect back to match page
if (!empty($_GET['reference']) && is_user_logged_in()) {
    $reference = sanitize_text_field($_GET['reference']);

    $verify_response = wp_remote_get(rest_url('emperora/v1/verify-entry?reference=' . $reference), [
        'headers' => [
            'Cookie' => $_SERVER['HTTP_COOKIE'] ?? '',
        ],
    ]);

    // Redirect cleanly to remove ?reference= from the URL
    $clean_url = remove_query_arg(['reference'], get_permalink());
    wp_redirect($clean_url);
    exit;
}

// --- Defaults ---
$season_id = isset($_GET['season_id']) ? intval($_GET['season_id']) :
    $wpdb->get_var("SELECT id FROM {$wpdb->prefix}epl_seasons WHERE status='active' LIMIT 1");

// Fallback if no active season
if (!$season_id) {
    $season_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}epl_seasons ORDER BY created_at DESC LIMIT 1");
}

$selected_season = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}epl_seasons WHERE id = %d", $season_id
));

$is_completed_season = $selected_season && $selected_season->status === 'completed';

// Only resolve round_id for active seasons
$round_id = null;
if (!$is_completed_season) {
    $round_id = isset($_GET['round_id']) ? intval($_GET['round_id']) :
        $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}epl_rounds 
             WHERE season_id = %d 
             ORDER BY (status = 'active') DESC, created_at ASC 
             LIMIT 1",
            $season_id
        ));
}

// --- Indicator helper ---
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

// --- Build leaderboard query ---
// Predictions don't have season_id/round_id directly.
// We join through matches → rounds to filter correctly.
if ($is_completed_season) {
    // Sum all predictions across every round in this season
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT p.user_id, COUNT(*) as total_games, SUM(p.points_earned) as total_points
         FROM {$wpdb->prefix}epl_predictions p
         JOIN {$wpdb->prefix}epl_rounds r ON p.round_id = r.id
         WHERE r.season_id = %d
         GROUP BY p.user_id
         ORDER BY total_points DESC",
        $season_id
    ));
} elseif ($round_id) {
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, COUNT(*) as total_games, SUM(points_earned) as total_points
         FROM {$wpdb->prefix}epl_predictions
         WHERE round_id = %d
         GROUP BY user_id
         ORDER BY total_points DESC",
        $round_id
    ));
} else {
    $results = [];
}

// --- Prize pool (always based on the viewed round, or skip for completed seasons) ---
$active_round = null;
$prize_pool   = 0;
$prizes       = ['first' => 0, 'second' => 0, 'third' => 0, 'platform' => 0];

if (!$is_completed_season && $round_id) {
    $active_round = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}epl_rounds WHERE id = %d", $round_id
    ));
    if ($active_round) {
        $prize_pool = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount_paid) FROM {$wpdb->prefix}emperora_entries WHERE round_id = %d",
            $active_round->id
        ));
        $prizes = [
            'first'    => $prize_pool * 0.40,
            'second'   => $prize_pool * 0.25,
            'third'    => $prize_pool * 0.20,
            'platform' => $prize_pool * 0.15,
        ];
    }
}

// --- Fetch seasons and rounds for dropdowns ---
$seasons      = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}epl_seasons ORDER BY created_at ASC");
$all_rounds   = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}epl_rounds ORDER BY season_id ASC, created_at ASC");

// Group rounds by season for JS
$rounds_by_season = [];
foreach ($all_rounds as $r) {
    $rounds_by_season[$r->season_id][] = ['id' => $r->id, 'title' => $r->title];
}
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

    <form method="get" class="epl-filter-form">
        <select name="season_id" id="epl-season-select">
            <?php foreach ($seasons as $season) : ?>
                <option value="<?php echo $season->id; ?>" <?php selected($season_id, $season->id); ?>>
                    <?php echo esc_html($season->title); ?>
                    <?php if ($season->status === 'completed') echo ' ✓'; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="round_id" id="epl-round-select"
            <?php echo $is_completed_season ? 'style="display:none"' : ''; ?>>
        </select>

        <button type="submit" class="epl-btn epl-btn--sm">Filter</button>
    </form>

    <?php if (empty($results)) : ?>
        <div class="epl-empty">
            <p>No predictions yet. Be the first to play!</p>
        </div>
    <?php else : ?>
        <?php if ($is_completed_season) : ?>
            <p class="epl-season-note">Showing all results for <?php echo esc_html($selected_season->title); ?></p>
        <?php endif; ?>

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
                    $rank       = $index + 1;
                    $rank_class = $rank <= 3 ? "epl-rank-{$rank}" : '';

                    $last_5 = $wpdb->get_results($wpdb->prepare(
                        "SELECT p.predicted_winner, p.predicted_score_home, p.predicted_score_away, p.points_earned,
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

                    $display_name = mb_strimwidth(get_userdata($row->user_id)->display_name, 0, 12, '...');

                    $is_paid = false;
                    if ($active_round) {
                        $is_paid = (bool) $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM {$wpdb->prefix}emperora_entries
                             WHERE user_id = %d AND round_id = %d",
                            $row->user_id, $active_round->id
                        ));
                    }
                ?>
                    <tr class="<?php echo $rank_class; ?>">
                        <td><span class="epl-rank-num"><?php echo $rank; ?></span></td>
                        <td>
                            <?php if ($is_paid) : ?>
                                <a href="#" class="epl-paid-player"><?php echo $display_name; ?></a>
                            <?php else : ?>
                                <?php echo $display_name; ?>
                            <?php endif; ?>
                        </td>
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

        <?php if ($prize_pool > 0) : ?>
            <div class="epl-prize-pool">
                <p class="epl-prize-pool-total">🏆 Prize Pool: ₦<?php echo number_format($prize_pool); ?></p>
                <div class="epl-prize-breakdown">
                    <span>1st: ₦<?php echo number_format($prizes['first']); ?></span>
                    <span>2nd: ₦<?php echo number_format($prizes['second']); ?></span>
                    <span>3rd: ₦<?php echo number_format($prizes['third']); ?></span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
(function() {
    const roundsBySeasonJson = <?php echo json_encode($rounds_by_season); ?>;
    const currentRoundId     = <?php echo json_encode($round_id); ?>;
    const seasonSelect       = document.getElementById('epl-season-select');
    const roundSelect        = document.getElementById('epl-round-select');

    function populateRounds(seasonId, selectedRoundId) {
        const rounds = roundsBySeasonJson[seasonId] || [];
        roundSelect.innerHTML = '';

        if (rounds.length === 0) {
            roundSelect.style.display = 'none';
            return;
        }

        rounds.forEach(function(r) {
            const opt      = document.createElement('option');
            opt.value      = r.id;
            opt.textContent = r.title;
            if (selectedRoundId && r.id == selectedRoundId) {
                opt.selected = true;
            }
            roundSelect.appendChild(opt);
        });

        // Hide round dropdown for completed seasons
        const selectedOption = seasonSelect.options[seasonSelect.selectedIndex];
        const isCompleted    = selectedOption.textContent.includes('✓');
        roundSelect.style.display = isCompleted ? 'none' : 'inline-block';
    }

    // On load — populate with current season's rounds
    populateRounds(seasonSelect.value, currentRoundId);

    // On season change — repopulate rounds, reset round selection
    seasonSelect.addEventListener('change', function() {
        populateRounds(this.value, null);
    });
})();
</script>