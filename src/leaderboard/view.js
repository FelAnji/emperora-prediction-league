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
});