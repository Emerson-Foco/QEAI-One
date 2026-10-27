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

    document.querySelectorAll('[data-board]').forEach(function (board) {
      var dragged = null;

      board.querySelectorAll('[data-board-card]').forEach(function (card) {
        card.addEventListener('dragstart', function (event) {
          if (event.target.closest('select, input, button, a')) {
            event.preventDefault();
            return;
          }
          dragged = card;
          card.classList.add('is-dragging');
          if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.leadId || '');
          }
        });

        card.addEventListener('dragend', function () {
          card.classList.remove('is-dragging');
          board.querySelectorAll('.is-drop-target').forEach(function (column) {
            column.classList.remove('is-drop-target');
          });
          dragged = null;
        });
      });

      board.querySelectorAll('[data-board-column]').forEach(function (column) {
        column.addEventListener('dragover', function (event) {
          event.preventDefault();
          if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
          column.classList.add('is-drop-target');
        });

        column.addEventListener('dragleave', function () {
          column.classList.remove('is-drop-target');
        });

        column.addEventListener('drop', function (event) {
          event.preventDefault();
          column.classList.remove('is-drop-target');
          if (!dragged) return;

          var form = dragged.querySelector('form');
          var select = form ? form.querySelector('select') : null;
          if (!form || !select) return;

          select.value = column.dataset.value || '';
          form.submit();
        });
      });
    });
  });
})();
