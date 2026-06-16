/******/ (() => { // webpackBootstrap
/*!*********************************!*\
  !*** ./src/leaderboard/view.js ***!
  \*********************************/
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.querySelector('.epl-rules-btn');
  const overlay = document.querySelector('.epl-rules-overlay');
  const close = document.querySelector('.epl-rules-close');
  btn.addEventListener('click', () => {
    overlay.classList.add('is-open');
  });
  close.addEventListener('click', () => {
    overlay.classList.remove('is-open');
  });
  const seasonSelect = document.querySelector('select[name="season_id"]');
  const roundSelect = document.querySelector('select[name="round_id"]');
  seasonSelect.addEventListener('change', async () => {
    const seasonId = seasonSelect.value;
    const response = await fetch(`/wp-json/emperora/v1/rounds?season_id=${seasonId}`);
    const rounds = await response.json();
    roundSelect.innerHTML = '';
    rounds.forEach(round => {
      const option = document.createElement('option');
      option.value = round.id;
      option.textContent = round.title;
      roundSelect.appendChild(option);
    });
    setTimeout(() => {
      roundSelect.value = roundSelect.dataset.selected;
    }, 0);
  });
  seasonSelect.dispatchEvent(new Event('change'));
});
/******/ })()
;
//# sourceMappingURL=view.js.map