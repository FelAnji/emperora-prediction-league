/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions } = store('emperora', {
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

            if (state.balance == 0) {
                alert("You do not have sufficient credit to play")
                return;
            }

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

                if (data.success) {
                    state.balance--;
                }
            } catch (error) {
                console.error('Prediction failed:', error);
            }
        },
        async buyCredits() {
            const context = getContext();
            console.log('context:', context);
            try {
                const response = await fetch('/wp-json/emperora/v1/buy', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': context.nonce,
                    },
                    body: JSON.stringify({ amount: 5 })
                });
                console.log('response status:', response.status);
                const data = await response.json();
                console.log('data:', data);
                window.location.href = data.data.authorization_url;
            } catch (error) {
                console.error('Payment failed:', error);
            }
        }
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