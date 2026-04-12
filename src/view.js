/**
 * WordPress dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

store('emperora', {
    actions: {
        togglePrediction() {
            const context = getContext();
            context.isOpen = !context.isOpen;
            context.buttonLabel = context.isOpen ? 'Save Outcome' : 'Predict Outcome';
        },
        handleSelection(event) {
            const context = getContext();
            context.isPredictScore = event.target.value === 'predict_score';
            context.selectedWinner = event.target.value;
        },
        setScoreHome(event) {
            const context = getContext();
            context.scoreHome = parseInt(event.target.value);
        },
        setScoreAway(event) {
            const context = getContext();
            context.scoreAway = parseInt(event.target.value);
        },
        async submitPrediction() {
            console.log('submitPrediction fired');
            const context = getContext();
            try {
                const body = {
                    match_id: context.matchID,
                    predicted_winner: context.selectedWinner,
                };

                if (context.isPredictScore) {
                    body.predicted_score_home = context.scoreHome;
                    body.predicted_score_away = context.scoreAway;
                }

                const response = await fetch('/wp-json/emperora/v1/predict', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': context.nonce,
                    },
                    body: JSON.stringify(body),
                });

                const data = await response.json();
                console.log(data);
            } catch (error) {
                console.error('Prediction failed:', error);
            }
        },
    },

    callbacks: {
        async loadPrediction() {
            const context = getContext();
            const response = await fetch(`/wp-json/emperora/v1/prediction/${context.matchID}`, {
                headers: {
                    'X-WP-Nonce': context.nonce,
                },
            });
            const data = await response.json();
            console.log('existing prediction:', data);

            if (data.id) {
                context.selectedWinner = data.predicted_winner;
                context.scoreHome = data.predicted_score_home;
                context.scoreAway = data.predicted_score_away;

                context.buttonLabel = 'Edit Prediction';
            }
        }
    }
});

// const { state } = store( 'epl-block', {
// 	state: {
// 		get themeText() {
// 			return state.isDark ? state.darkText : state.lightText;
// 		},
// 	},
// 	actions: {
// 		toggleOpen() {
// 			const context = getContext();
// 			context.isOpen = ! context.isOpen;
// 		},
// 		toggleTheme() {
// 			state.isDark = ! state.isDark;
// 		},
// 	},
// 	callbacks: {
// 		logIsOpen: () => {
// 			const { isOpen } = getContext();
// 			// Log the value of `isOpen` each time it changes.
// 			console.log( `Is open: ${ isOpen }` );
// 		},
// 	},
// } );
