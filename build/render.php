

<?php if (!is_user_logged_in()) : ?>
    <div class="epl-login-prompt">
        <p>Please <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">log in</a> to make predictions.</p>
    </div>
<?php else : ?>
    <?php
        if (get_post_type() !== 'epl_match') return;

        $meta = get_post_meta(get_the_ID());
        $home_team = $meta['home_team'][0];
        $away_team = $meta['away_team'][0];
        $match_status = $meta['match_status'][0] ?? 'upcoming';

        $match_id = get_the_ID();
        $user_id = get_current_user_id();

        global $wpdb;

        $prediction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}epl_predictions WHERE match_id = %d AND user_id = %d",
            $match_id,
            $user_id
        ));

        $active_round = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}epl_rounds
            WHERE status = 'active'
            ORDER BY created_at DESC
            LIMIT 1"
        );

        $has_entered = false;
        if ($active_round) {
            $has_entered = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}emperora_entries
                WHERE user_id = %d AND round_id = %d",
                $user_id, $active_round->id
            ));
        }

        wp_interactivity_state('emperora', [
            'isOpen' => false,
            'homeTeam' => $home_team,
            'awayTeam' => $away_team,

            'isPredictScore' => false,

            'paystackPublicKey' => EPL_PAYSTACK_PUBLIC_KEY
        ]);
        ?>

        <div
            <?php echo get_block_wrapper_attributes(); ?>
            data-wp-interactive="emperora"
            <?php echo wp_interactivity_data_wp_context([
                'isOpen' => false, 
                'buttonLabel' => 'Predict Outcome', 
                'isPredictScore' => false,
                'matchID' => get_the_ID(), 
                'nonce' => wp_create_nonce('wp_rest'),

                'selectedWinner' => '',
                'scoreHome' => null,
                'scoreAway' => null,

                'creditAmount' => 12,

                'entryAmount' => $active_round ? $active_round->entry_fee : 0,
                'roundID' => $active_round ? $active_round->id : null,
                'hasEntered' => $has_entered,

                'showToast' => false,
                'toastMessage' => '',
                ]); ?>

                data-wp-init="callbacks.loadPrediction"
        >
            <div class="epl-toast" data-wp-bind--hidden="!context.showToast">
                <p data-wp-text="context.toastMessage"></p>
            </div>

            <div class="epl-status-wrap">
                <span class="epl-status-pill">EmperorA Prediction League</span>
            </div>

            <span>
                <h1 class="epl-match-title">
                    <?php echo esc_html($home_team); ?> 
                    <span class="epl-vs"> vs </span> 
                    <?php echo esc_html($away_team); ?>
                </h1>
            </span>

            <?php if ($active_round) : ?>
                <div class="epl-entry-banner" data-wp-bind--hidden="!context.roundID">
                    <div data-wp-bind--hidden="context.hasEntered">
                        <p class="epl-entry-text">
                            🏆 You're playing for free. Enter this round to compete for the prize pool.
                        </p>
                        <a href="#" class="epl-entry-link">Learn more about the prediction league →</a>
                        <div class="epl-entry-controls">
                            <input 
                                type="number" 
                                min="<?php echo $active_round->entry_fee; ?>"
                                placeholder="Min ₦<?php echo number_format($active_round->entry_fee); ?>"
                                data-wp-bind--value="context.entryAmount"
                                data-wp-on--input="actions.setEntryAmount"
                            />
                            <button class="epl-btn epl-btn--sm" data-wp-on--click="actions.enterRound">
                                Enter Round
                            </button>
                        </div>
                    </div>
                    <div data-wp-bind--hidden="!context.hasEntered">
                        <p class="epl-entry-paid">✓ You're entered in this round</p>
                    </div>
                </div>
            <?php endif; ?>


            <?php if ($match_status === 'upcoming') : ?>

            <button
                class="epl-btn epl-btn--full"
                data-wp-on--click="actions.togglePrediction"
                data-wp-text="context.buttonLabel"
            >Predict Outcome</button>

            <div class="epl-prediction-box" data-wp-bind--hidden="!context.isOpen">
                <label for="predict">Predict Outcome:</label>
                <select data-wp-on--change="actions.handleSelection" data-wp-bind--value="context.selectedWinner">
                    <option value=""></option>
                    <option value="home"><?php echo esc_html($home_team); ?> win</option>
                    <option value="away"><?php echo esc_html($away_team); ?> win</option>
                    <option value="draw">Draw</option>
                    <option value="predict_score">Predict Score</option>
                </select>

                <button class="epl-btn" data-wp-on--click="actions.submitPrediction">Submit Prediction</button>
            </div>
            
            <div class="epl-score-section" data-wp-bind--hidden="!context.isPredictScore">
                <p class="epl-score-label">Exact score</p>
                <div class="epl-score-row">
                    <div class="epl-score-team">
                        <?php echo esc_html($home_team); ?>
                        <input type="number" min="0"
                            data-wp-bind--value="context.scoreHome" 
                            data-wp-on--input="actions.setScoreHome">
                    </div>
                    <span class="epl-score-sep">–</span>
                    <div class="epl-score-team">
                        <?php echo esc_html($away_team); ?>
                        <input type="number" min="0"
                            data-wp-bind--value="context.scoreAway" 
                            data-wp-on--input="actions.setScoreAway">
                    </div>
                </div>
            </div>

            <?php else : ?>
                <?php
                if ($prediction) {
                    echo '<div class="epl-prediction-result">';
                    echo '<span class="epl-prediction-closed-badge">🔒 Predictions closed</span>';
                    echo '<p class="epl-prediction-label">Your prediction</p>';
                    echo '<p class="epl-prediction-value">';
                    switch ($prediction->predicted_winner) {
                        case 'home':
                            echo esc_html($home_team) . ' win';
                            break;
                        case 'away':
                            echo esc_html($away_team) . ' win';
                            break;
                        case 'predict_score':
                            echo esc_html($prediction->predicted_score_home) . ' - ' . esc_html($prediction->predicted_score_away);
                            break;
                        case 'draw':
                            echo 'Draw';
                            break;
                        default:
                            echo 'No prediction made';
                    }
                    echo '</p>';
                    echo '</div>';
                } else {
                    echo '<div class="epl-prediction-result">';
                    echo '<span class="epl-prediction-closed-badge">🔒 Predictions closed</span>';
                    echo '<p class="epl-prediction-value">You did not make a prediction for this match.</p>';
                    echo '</div>';
                } ?> 
            <?php endif; ?>
            
        </div>

<?php endif; ?>



