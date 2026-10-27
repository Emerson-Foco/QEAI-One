/* QEAI One — interações leves (sem dependências) */
(function () {
  'use strict';

  function onReady(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  onReady(function () {
    var body = document.body;

    document.querySelectorAll('[data-nav-toggle]').forEach(function (button) {
      button.addEventListener('click', function () {
        body.classList.toggle('nav-open');
      });
    });

    document.querySelectorAll('[data-nav-close]').forEach(function (button) {
      button.addEventListener('click', function () {
        body.classList.remove('nav-open');
      });
    });

    document.querySelectorAll('[data-nav-overlay]').forEach(function (overlay) {
      overlay.addEventListener('click', function () {
        body.classList.remove('nav-open');
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        body.classList.remove('nav-open');
        document.querySelectorAll('details[open]').forEach(function (details) {
          if (details.classList.contains('user-dropdown')) details.open = false;
        });
      }
    });

    document.querySelectorAll('details.user-dropdown').forEach(function (details) {
      document.addEventListener('click', function (event) {
        if (!details.contains(event.target)) details.open = false;
      });
    });

    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
      button.addEventListener('click', function () {
        var input = button.closest('.auth-input').querySelector('input');
        if (!input) return;
        var showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        button.setAttribute('aria-label', showing ? 'Mostrar senha' : 'Ocultar senha');
        button.classList.toggle('is-active', !showing);
      });
    });

    document.querySelectorAll('.alert').forEach(function (alert) {
      if (!alert.classList.contains('ok')) return;
      window.setTimeout(function () {
        alert.style.transition = 'opacity .35s ease, transform .35s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-4px)';
        window.setTimeout(function () { alert.remove(); }, 360);
      }, 7000);
    });
  });
})();
