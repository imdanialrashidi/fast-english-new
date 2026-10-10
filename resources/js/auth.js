/**
 * Auth forms: password visibility toggles + submit loading state.
 * Progressive enhancement only — forms submit normally without JS.
 */
(function () {
  function bind(scope) {
    scope.querySelectorAll('[data-fe-password-toggle]').forEach((btn) => {
      if (btn.dataset.feBound === '1') return;
      btn.dataset.feBound = '1';
      btn.addEventListener('click', () => {
        const input = document.getElementById(btn.getAttribute('aria-controls'));
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', String(show));
        btn.setAttribute('aria-label', show ? 'پنهان کردن رمز عبور' : 'نمایش رمز عبور');
        const iconShow = btn.querySelector('[data-fe-icon-show]');
        const iconHide = btn.querySelector('[data-fe-icon-hide]');
        if (iconShow && iconHide) {
          iconShow.hidden = !show;
          iconHide.hidden = show;
        }
      });
    });

    scope.querySelectorAll('form[data-fe-auth-form]').forEach((form) => {
      if (form.dataset.feBound === '1') return;
      form.dataset.feBound = '1';
      form.addEventListener('submit', () => {
        const btn = form.querySelector('[data-fe-submit]');
        if (!btn || btn.disabled) return;
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        const label = btn.querySelector('[data-fe-submit-label]');
        if (label) {
          label.textContent = 'در حال بررسی…';
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => bind(document), { once: true });
  } else {
    bind(document);
  }
  document.addEventListener('livewire:navigated', () => bind(document));
})();
