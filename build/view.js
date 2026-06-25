import * as __WEBPACK_EXTERNAL_MODULE__wordpress_interactivity_8e89b257__ from "@wordpress/interactivity";
/******/ var __webpack_modules__ = ({

/***/ "@wordpress/interactivity"
/*!*******************************************!*\
  !*** external "@wordpress/interactivity" ***!
  \*******************************************/
(module) {

module.exports = __WEBPACK_EXTERNAL_MODULE__wordpress_interactivity_8e89b257__;

/***/ }

/******/ });
/************************************************************************/
/******/ // The module cache
/******/ var __webpack_module_cache__ = {};
/******/ 
/******/ // The require function
/******/ function __webpack_require__(moduleId) {
/******/ 	// Check if module is in cache
/******/ 	var cachedModule = __webpack_module_cache__[moduleId];
/******/ 	if (cachedModule !== undefined) {
/******/ 		return cachedModule.exports;
/******/ 	}
/******/ 	// Check if module exists (development only)
/******/ 	if (__webpack_modules__[moduleId] === undefined) {
/******/ 		var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 		e.code = 'MODULE_NOT_FOUND';
/******/ 		throw e;
/******/ 	}
/******/ 	// Create a new module (and put it into the cache)
/******/ 	var module = __webpack_module_cache__[moduleId] = {
/******/ 		// no module.id needed
/******/ 		// no module.loaded needed
/******/ 		exports: {}
/******/ 	};
/******/ 
/******/ 	// Execute the module function
/******/ 	__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 
/******/ 	// Return the exports of the module
/******/ 	return module.exports;
/******/ }
/******/ 
/************************************************************************/
/******/ /* webpack/runtime/make namespace object */
/******/ (() => {
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = (exports) => {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/ })();
/******/ 
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*********************!*\
  !*** ./src/view.js ***!
  \*********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/interactivity */ "@wordpress/interactivity");
/**
 * WordPress dependencies
 */

const {
  state,
  actions
} = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.store)('emperora', {
  actions: {
    togglePrediction() {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      context.isOpen = !context.isOpen;
      context.buttonLabel = context.isOpen ? 'Save Outcome' : 'Predict Outcome';
    },
    handleSelection(event) {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      context.isPredictScore = event.target.value === 'predict_score';
      context.selectedWinner = event.target.value;
    },
    setScoreHome(event) {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      context.scoreHome = parseInt(event.target.value);
    },
    setScoreAway(event) {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      context.scoreAway = parseInt(event.target.value);
    },
    async submitPrediction() {
      console.log('submitPrediction fired');
      if (state.balance == 0) {
        alert("You do not have sufficient credit to play");
        return;
      }
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      try {
        const body = {
          match_id: context.matchID,
          predicted_winner: context.selectedWinner
        };
        if (context.isPredictScore) {
          body.predicted_score_home = context.scoreHome;
          body.predicted_score_away = context.scoreAway;
        }
        const response = await fetch('/wp-json/emperora/v1/predict', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': context.nonce
          },
          body: JSON.stringify(body)
        });
        const data = await response.json();
        if (data.success) {
          context.showToast = true;
          context.toastMessage = '✅ Prediction saved successfully! Click on Save Outcome';
          setTimeout(() => {
            context.showToast = false;
          }, 3000);
        }
      } catch (error) {
        console.error('Prediction failed:', error);
      }
    },
    async enterRound() {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      if (!context.entryAmount || context.entryAmount < context.minEntryFee) {
        context.showToast = true;
        context.toastMessage = `❌ Minimum entry is ₦${context.minEntryFee}`;
        setTimeout(() => {
          context.showToast = false;
        }, 3000);
        return;
      }
      try {
        const response = await fetch('/wp-json/emperora/v1/enter-round', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': context.nonce
          },
          body: JSON.stringify({
            round_id: context.roundID,
            amount: context.entryAmount,
            callback_url: window.location.href
          })
        });
        const data = await response.json();
        console.error('Full response:', data);
        const url = data?.data?.authorization_url;
        if (url) {
          window.location.href = url;
        } else {
          context.showToast = true;
          context.toastMessage = '❌ Could not initialize payment. Try again.';
          setTimeout(() => {
            context.showToast = false;
          }, 3000);
        }
      } catch (error) {
        console.error('Entry failed:', error);
        context.showToast = true;
        context.toastMessage = '❌ Something went wrong.';
        setTimeout(() => {
          context.showToast = false;
        }, 3000);
      }
    },
    setEntryAmount(event) {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      context.entryAmount = parseInt(event.target.value);
    }
  },
  callbacks: {
    async loadPrediction() {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      const response = await fetch(`/wp-json/emperora/v1/prediction/${context.matchID}`, {
        headers: {
          'X-WP-Nonce': context.nonce
        }
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
})();


//# sourceMappingURL=view.js.map