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
        'scoreAway' => null
		]); ?>

        data-wp-init="callbacks.loadPrediction"
>
    <span>
        <h1>
            <?php echo esc_html($home_team); ?> vs <?php echo esc_html($away_team); ?>
        </h1>
    </span>

    <span>
        <p data-wp-text="state.balance">Balance</p>
        <button data-wp-on--click="actions.buyCredits">Buy Credits</button>
    </span>

    <button
        data-wp-on--click="actions.togglePrediction"
    	data-wp-text="context.buttonLabel"
    ></button>

    <div data-wp-bind--hidden="!context.isOpen">
        <label for="predict">Predict Outcome:</label>
        <select data-wp-on--change="actions.handleSelection" data-wp-bind--value="context.selectedWinner">
            <option value=""></option>
            <option value="home"><?php echo esc_html($home_team); ?> win</option>
            <option value="away"><?php echo esc_html($away_team); ?> win</option>
            <option value="draw">Draw</option>
            <option value="predict_score">Predict Score</option>
        </select>

        <button data-wp-on--click="actions.submitPrediction">Submit</button>
    </div>
	
	<div data-wp-bind--hidden="!context.isPredictScore">
		<span> 
			<?php echo $home_team ?>
			<input type="number" data-wp-bind--value="context.scoreHome" data-wp-on--input="actions.setScoreHome">
		</span>
		-
		<span>
			<input type="number" data-wp-bind--value="context.scoreAway" data-wp-on--input="actions.setScoreAway">
			<?php echo $away_team ?>
		</span>
	</div>
	
</div>

