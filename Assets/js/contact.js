document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('contactForm');
    if (!form) return;
    let submitting = false;
    form.addEventListener('submit', event => {
        if (submitting) { event.preventDefault(); return; }
        if (!form.checkValidity()) return;
        submitting = true;
        const button = form.querySelector('[type="submit"]');
        if (button) { button.disabled = true; button.querySelector('span').textContent = 'Sending inquiry…'; }
    });
});
