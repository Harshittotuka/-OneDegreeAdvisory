/*
 * Student portal sign-in pages: the show/hide password buttons, and a gentle
 * tilt of the globe scene that follows the pointer. The form submit, the
 * hand-off screen and the toasts are crm.js's.
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-sp-reveal]').forEach(function (button) {
        var input = document.getElementById(button.getAttribute('data-sp-reveal'));
        if (!input) return;
        button.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            input.focus();
        });
    });

    // Put the password back to hidden before the form goes, so the browser
    // never offers to remember it as plain text.
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('[data-sp-reveal]').forEach(function (button) {
                var input = document.getElementById(button.getAttribute('data-sp-reveal'));
                if (input) input.type = 'password';
            });
        }, true);
    });

    var visual = document.querySelector('[data-sp-visual]');
    var scene = document.querySelector('[data-sp-parallax]');
    var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var fine = window.matchMedia('(pointer: fine)').matches;
    if (!visual || !scene || still || !fine) return;

    var frame = 0;
    visual.addEventListener('pointermove', function (event) {
        if (frame) return;
        frame = requestAnimationFrame(function () {
            frame = 0;
            var box = visual.getBoundingClientRect();
            var x = (event.clientX - box.left) / box.width - 0.5;
            var y = (event.clientY - box.top) / box.height - 0.5;
            scene.style.transform = 'translate(calc(-50% + ' + (x * 18).toFixed(1) + 'px), calc(-50% + ' + (y * 14).toFixed(1) + 'px))';
        });
    });
    visual.addEventListener('pointerleave', function () { scene.style.transform = ''; });
});
