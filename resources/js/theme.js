/**
 * Appearance theme: system (default) / light / dark.
 * The inline pre-paint snippet in the layouts sets data-theme before first
 * paint; this module keeps it consistent afterwards: OS changes while on
 * system, the Settings radio group, Livewire navigations, and the
 * theme-color meta. The preference is non-sensitive UI state in
 * localStorage only (fe-theme); nothing else is stored here.
 */
(function () {
  const KEY = 'fe-theme';
  const VALID = ['system', 'light', 'dark'];

  function stored() {
    try {
      const raw = (localStorage.getItem(KEY) || 'system').toLowerCase();
      return VALID.includes(raw) ? raw : 'system';
    } catch (_) {
      return 'system';
    }
  }

  function systemDark() {
    try {
      return window.matchMedia('(prefers-color-scheme: dark)').matches;
    } catch (_) {
      return false;
    }
  }

  function resolved(mode) {
    if (mode === 'light') return 'light';
    if (mode === 'dark') return 'dark';
    return systemDark() ? 'dark' : 'light';
  }

  function apply(mode) {
    const theme = resolved(mode || stored());
    document.documentElement.setAttribute('data-theme', theme);
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', theme === 'dark' ? '#05090a' : '#f5f9fa');
    return theme;
  }

  function persist(mode) {
    try {
      if (mode === 'system') localStorage.removeItem(KEY);
      else localStorage.setItem(KEY, mode);
    } catch (_) {}
  }

  window.__feTheme = { apply, stored, resolved };

  apply(stored());

  try {
    const mq = window.matchMedia('(prefers-color-scheme: dark)');
    const onChange = () => {
      if (stored() === 'system') apply('system');
    };
    if (typeof mq.addEventListener === 'function') mq.addEventListener('change', onChange);
    else if (typeof mq.addListener === 'function') mq.addListener(onChange);
  } catch (_) {}

  function bindAppearance() {
    document.querySelectorAll('input[name="appearance"]').forEach((radio) => {
      if (radio.dataset.feThemeBound === '1') return;
      radio.dataset.feThemeBound = '1';
      radio.addEventListener('change', () => {
        if (!radio.checked) return;
        const mode = VALID.includes(radio.value) ? radio.value : 'system';
        persist(mode);
        apply(mode);
        const status = document.getElementById('appearance-status');
        if (status) {
          status.textContent =
            mode === 'system'
              ? 'حالت سیستم فعال شد؛ ظاهر با دستگاه هماهنگ می‌شود.'
              : mode === 'light'
                ? 'حالت روشن فعال شد.'
                : 'حالت تیره فعال شد.';
        }
      });
    });
    // Reflect the stored choice whenever the settings block renders.
    const current = stored();
    document.querySelectorAll('input[name="appearance"]').forEach((radio) => {
      radio.checked = radio.value === current || (current === 'system' && radio.value === 'system');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindAppearance, { once: true });
  } else {
    bindAppearance();
  }
  document.addEventListener('livewire:navigated', () => {
    apply(stored());
    bindAppearance();
  });
})();
