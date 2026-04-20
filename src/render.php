

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

        wp_interactivity_state('emperora', [
            'isOpen' => false,
            'homeTeam' => $home_team,
            'awayTeam' => $away_team,

            'isPredictScore' => false,

            'balance' => epl_get_balance(get_current_user_id()),
            'paystackPublicKey' => EPL_PAYSTACK_PUBLIC_KEY
        ]);
        ?>

        <?php echo wp_create_nonce('wp_rest'); ?>

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

                'creditAmount' => 12
                ]); ?>

                data-wp-init="callbacks.loadPrediction"
        >

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

            <span class="epl-balance-row">
                <div>
                    <p class="epl-balance-label">Your balance:</p>
                    <p class="epl-balance-value" data-wp-text="state.balance"></p>
                </div>        
                    <input 
                        type="number" 
                        min="12" 
                        placeholder="Min 12 credits"
                        data-wp-bind--value="context.creditAmount"
                        data-wp-on--input="actions.setCreditAmount"
                    />
                    <button class="epl-btn epl-btn--sm" data-wp-on--click="actions.buyCredits">Buy Credits</button>
            </span>

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
            
        </div>

<?php endif; ?>



