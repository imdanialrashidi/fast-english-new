/**
 * S4 persistent player: one HTMLAudioElement in the learner layout under
 * @persist('fe-player'), surviving Livewire in-app navigation between the
 * library, reader, saved, and account pages.
 *
 * - Single state owner (window.__fePlayer), listeners bound once for the
 *   persisted controls; page-specific buttons (bookmark, mark-read, reset)
 *   rebind on each navigation because their elements are replaced.
 * - S1 controls kept: play/pause labels, native range seek (drag +
 *   keyboard, RTL-aware), ±10 s clamped, speeds 0.75/1/1.25/1.5,
 *   no autoplay, play-rejection/buffering/network errors with retry.
 * - Mini-bar + expanded console: the bar rests collapsed (play · lesson ·
 *   compact elapsed/total · speed-cycle · expand toggle) and opens into
 *   the full seek/skip console on demand. Collapsed on first paint at
 *   every width; the toggle always wins. Playback errors
 *   auto-expand so their message + retry stay visible. Idle pages (no
 *   lesson) keep the collapsed bar with play disabled.
 * - Opening a different lesson/level stops the current audio, loads the new
 *   source, seeks to the saved position (revision-isolated), and stays
 *   paused. Same-lesson navigation keeps the current time.
 * - Logout and admin navigation stop the audio, clear its source, and rely
 *   on full navigation (forms/links without wire:navigate).
 * - Progress (authenticated only): at most every 15 s while playing, plus
 *   pause and ended. pagehide/visibilitychange are best-effort. Failures
 *   show #progress-error + #progress-retry and never claim success.
 * - Completion only from ended (natural, not seek-driven) or the explicit
 *   #mark-read button; seeking to the end never completes. Reset via
 *   #reset-progress clears completion only.
 * - Guest level preference: the chosen level (A1–C2 only) is kept in
 *   localStorage as non-sensitive UI state; no token, receipt, body, or
 *   answer key ever enters storage.
 */
(function () {
  if (window.__fePlayerBound) {
    return;
  }
  window.__fePlayerBound = true;

  const audio = document.getElementById('lesson-audio');
  if (!audio) return;

  const state = (window.__fePlayer = window.__fePlayer || {
    lessonId: null,
    revision: null,
    authenticated: false,
    lastSave: 0,
    pendingSave: null,
    suppressEndedOnce: false,
    appliedInitialFor: null,
    // R3 sentence sync: rebuilt from the DOM on every navigation.
    sentences: [],
    stopAt: null,
  });

  const STEP = 10;
  // Speed cycle order: tap steps 1 → 1.25 → 1.5 → 0.75 → 1. The rate
  // sticks across lessons (standard sticky-speed behavior).
  const SPEEDS = [1, 1.25, 1.5, 0.75];
  state.speedIdx = 0;
  const SAVE_INTERVAL_MS = 15000;
  const LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
  // Lucide play/pause glyphs (same paths as <x-icon />), swapped without
  // touching the button's accessible name handling elsewhere.
  const PLAY_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" aria-hidden="true"><polygon points="6 3 20 12 6 21 6 3"/></svg>';
  const PAUSE_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" aria-hidden="true"><rect width="4" height="16" x="6" y="4"/><rect width="4" height="16" x="14" y="4"/></svg>';

  function $(id) {
    return document.getElementById(id);
  }

  function fmt(seconds) {
    if (!Number.isFinite(seconds) || seconds < 0) return '–:––';
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${String(s).padStart(2, '0')}`;
  }

  function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
  }

  function lessonData() {
    const el = document.getElementById('fe-lesson-data');
    if (!el) return null;
    return {
      lessonId: Number(el.dataset.lessonId),
      audioUrl: el.dataset.audioUrl,
      revision: Number(el.dataset.revision),
      initialPosition: Number(el.dataset.initialPosition || 0),
      topic: el.dataset.topic || '',
      level: el.dataset.level || '',
      coverUrl: el.dataset.coverUrl || '',
      authenticated: el.dataset.authenticated === '1',
    };
  }

  function playerBar() {
    return document.querySelector('.fe-playerbar');
  }

  function mainEl() {
    return document.querySelector('.fe-player-main');
  }

  function controlsEl() {
    return document.querySelector('.fe-player-controls');
  }

  function setThumb(url) {
    const thumb = $('player-thumb');
    if (!thumb) return;
    const next = typeof url === 'string' && url !== '' ? url : '/icons/icon-192.png';
    if (thumb.getAttribute('src') !== next) thumb.setAttribute('src', next);
  }

  // The same play element lives in the collapsed mini-row and, when
  // expanded, moves into the centered transport cluster so the console
  // reads [−10s][play 64px][+10s] without a duplicate button or ID.
  function placePlay(expanded) {
    const play = $('player-play');
    const main = mainEl();
    const controls = controlsEl();
    const back = $('player-back');
    const fwd = $('player-forward');
    if (!play || !main || !controls || !back || !fwd) return;
    if (expanded) {
      if (play.parentElement !== controls) {
        controls.appendChild(back);
        controls.appendChild(play);
        controls.appendChild(fwd);
      }
    } else if (play.parentElement !== main) {
      main.prepend(play);
      controls.appendChild(back);
      controls.appendChild(fwd);
    }
  }

  function setExpanded(expanded) {
    const bar = playerBar();
    const toggle = $('player-toggle');
    const on = expanded === true;
    if (bar) bar.setAttribute('data-expanded', String(on));
    try {
      document.body.dataset.playerExpanded = String(on);
    } catch (_) {}
    placePlay(on);
    if (toggle) {
      toggle.setAttribute('aria-expanded', String(on));
      toggle.setAttribute('aria-label', on ? 'جمع‌کردن کنترل‌ها' : 'نمایش کنترل‌های بیشتر');
    }
    return on;
  }

  function isExpanded() {
    return playerBar()?.getAttribute('data-expanded') === 'true';
  }

  function showError(message) {
    const errorBox = $('player-error');
    const errorText = $('player-error-text');
    const retryBtn = $('player-retry');
    if (!errorBox || !retryBtn) return;
    if (errorText) errorText.textContent = message;
    else errorBox.textContent = message;
    errorBox.hidden = false;
    retryBtn.hidden = false;
    // Errors live in the detail console: open it so the message stays seen.
    setExpanded(true);
  }

  function clearError() {
    const errorBox = $('player-error');
    const errorText = $('player-error-text');
    const retryBtn = $('player-retry');
    if (!errorBox || !retryBtn) return;
    if (errorText) errorText.textContent = '';
    else errorBox.textContent = '';
    errorBox.hidden = true;
    retryBtn.hidden = true;
  }

  function showProgressError(message) {
    const box = $('progress-error');
    const boxText = $('progress-error-text');
    const retry = $('progress-retry');
    if (!box || !retry) return;
    if (boxText) boxText.textContent = message;
    else box.textContent = message;
    box.hidden = false;
    retry.hidden = false;
    setExpanded(true);
  }

  function clearProgressError() {
    const box = $('progress-error');
    const boxText = $('progress-error-text');
    const retry = $('progress-retry');
    if (!box || !retry) return;
    if (boxText) boxText.textContent = '';
    else box.textContent = '';
    box.hidden = true;
    retry.hidden = true;
  }

  function clampTime(t) {
    const d = audio.duration;
    if (Number.isFinite(d)) return Math.min(Math.max(t, 0), d);
    return Math.max(t, 0);
  }

  function refreshPlayLabel() {
    const playBtn = $('player-play');
    const icon = $('player-play-icon');
    if (icon) icon.innerHTML = audio.paused ? PLAY_SVG : PAUSE_SVG;
    if (playBtn) playBtn.setAttribute('aria-label', audio.paused ? 'پخش' : 'توقف');
  }

  function paintSeekFill() {
    const seek = $('player-seek');
    if (!seek) return;
    const d = audio.duration;
    let pct = 0;
    if (Number.isFinite(d) && d > 0 && Number.isFinite(audio.currentTime)) {
      pct = Math.min(100, Math.max(0, (audio.currentTime / d) * 100));
    }
    seek.style.setProperty('--fe-seek-pct', `${pct}%`);
    const fill = $('player-progress-fill');
    if (fill) fill.style.width = `${pct}%`;
  }

  function refreshSeek() {
    const seek = $('player-seek');
    const cur = $('player-current');
    const dur = $('player-duration');
    if (!seek || !cur || !dur) return;
    const d = audio.duration;
    if (Number.isFinite(d) && d > 0) {
      seek.max = String(d);
      seek.value = String(clampTime(audio.currentTime));
      dur.textContent = fmt(d);
    }
    cur.textContent = fmt(audio.currentTime);
    paintSeekFill();
  }

  function refreshSpeedLabel() {
    const label = $('player-speed-label');
    const btn = $('player-speed');
    const rate = SPEEDS[state.speedIdx] ?? 1;
    if (label) label.textContent = `${rate}×`;
    if (btn) btn.setAttribute('aria-label', `سرعت پخش: ${rate}×`);
  }

  function setMiniPlayer(topic, level) {
    const mini = $('mini-player');
    if (!mini) return;
    if (topic && level) {
      mini.textContent = `در حال پخش: ${topic} (${level})`;
    } else {
      mini.textContent = 'پخش‌کننده آماده است';
    }
  }

  function setPlayEnabled(enabled) {
    const playBtn = $('player-play');
    if (playBtn) playBtn.disabled = !enabled;
  }

  function stopAndClear(reason) {
    try {
      audio.pause();
    } catch (_) {}
    audio.removeAttribute('src');
    try {
      audio.load();
    } catch (_) {}
    state.lessonId = null;
    state.revision = null;
    state.appliedInitialFor = null;
    setMiniPlayer('', '');
    setThumb('/icons/icon-192.png');
    setPlayEnabled(false);
    refreshPlayLabel();
    refreshSeek();
    if (reason) {
      const status = $('player-status');
      if (status) status.textContent = '';
    }
  }

  async function saveProgress(position, opts = {}) {
    if (!state.authenticated || state.lessonId === null) return;
    const payload = {
      lesson_id: state.lessonId,
      audio_revision: state.revision,
      position_seconds: Math.max(0, Number(position)),
    };
    if (!Number.isFinite(payload.position_seconds)) return;
    try {
      const res = await fetch('/app/progress', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
      });
      if (res.status === 409) {
        // Stale revision: adopt the server's current position, never apply
        // old progress to the new audio.
        const data = await res.json().catch(() => null);
        if (data && typeof data.position_seconds === 'number') {
          audio.currentTime = clampTime(data.position_seconds);
        }
        if (!opts.silent) showProgressError('نسخه صوت تغییر کرده است. موقعیت جدید بارگذاری شد.');
        return;
      }
      if (!res.ok) {
        throw new Error(`save failed: ${res.status}`);
      }
      clearProgressError();
      state.lastSave = Date.now();
      state.pendingSave = null;
    } catch (_) {
      state.pendingSave = payload;
      if (!opts.silent) {
        showProgressError('ذخیره پیشرفت ناموفق بود. اتصال را بررسی کنید و دوباره تلاش کنید.');
      }
    }
  }

  function maybePeriodicSave() {
    if (audio.paused) return;
    if (!state.authenticated || state.lessonId === null) return;
    if (Date.now() - state.lastSave < SAVE_INTERVAL_MS) return;
    saveProgress(audio.currentTime, { silent: true });
  }

  async function completeLesson() {
    if (!state.authenticated || state.lessonId === null) return;
    try {
      const res = await fetch('/app/progress/complete', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          lesson_id: state.lessonId,
          audio_revision: state.revision,
        }),
      });
      if (!res.ok) throw new Error(`complete failed: ${res.status}`);
      clearProgressError();
      const markBtn = $('mark-read');
      if (markBtn) markBtn.hidden = true;
      let resetBtn = $('reset-progress');
      if (!resetBtn) {
        resetBtn = document.createElement('button');
        resetBtn.id = 'reset-progress';
        resetBtn.className = 'fe-btn';
        resetBtn.type = 'button';
        resetBtn.textContent = 'شروع دوباره';
        resetBtn.dataset.lessonId = String(state.lessonId);
        markBtn?.after(resetBtn);
        bindResetButton(resetBtn);
      } else {
        resetBtn.hidden = false;
      }
    } catch (_) {
      showProgressError('ثبت تکمیل ناموفق بود. دوباره تلاش کنید.');
    }
  }

  function bindResetButton(btn) {
    if (!btn || btn.dataset.feBound === '1') return;
    btn.dataset.feBound = '1';
    btn.addEventListener('click', async () => {
      const lessonId = Number(btn.dataset.lessonId || state.lessonId);
      if (!Number.isFinite(lessonId)) return;
      try {
        const res = await fetch('/app/progress/reset', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ lesson_id: lessonId }),
        });
        if (!res.ok) throw new Error(`reset failed: ${res.status}`);
        clearProgressError();
        btn.hidden = true;
        const markBtn = $('mark-read');
        if (markBtn) markBtn.hidden = false;
      } catch (_) {
        showProgressError('بازنشانی ناموفق بود. دوباره تلاش کنید.');
      }
    });
  }

  // --- R3 sentence-level sync + R4 vocabulary actions (rebound per page). ---

  function rebuildSentences() {
    const nodes = document.querySelectorAll('.fe-sentence');
    state.sentences = Array.from(nodes).map((el) => ({
      el,
      start: Number(el.dataset.start),
      end: Number(el.dataset.end),
    }));
    nodes.forEach((el) => {
      if (el.dataset.feSentenceBound === '1') return;
      el.dataset.feSentenceBound = '1';
      el.addEventListener('click', () => {
        const start = Number(el.dataset.start);
        const end = Number(el.dataset.end);
        if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) return;
        clearError();
        // Play exactly this sentence's interval, then stop.
        state.suppressEndedOnce = true;
        state.stopAt = end;
        try { audio.currentTime = clampTime(start); } catch (_) {}
        refreshSeek();
        const p = audio.play();
        if (p && typeof p.catch === 'function') {
          p.catch(() => {
            state.stopAt = null;
            showError('پخش آغاز نشد. اتصال را بررسی کنید و دوباره تلاش کنید.');
          });
        }
      });
    });
  }

  function refreshSentence(t) {
    if (!state.sentences || state.sentences.length === 0) return;
    if (!Number.isFinite(t)) return;
    let active = null;
    for (const s of state.sentences) {
      if (t >= s.start && t < s.end) { active = s; break; }
    }
    let changed = false;
    for (const s of state.sentences) {
      const isActive = s === active;
      if ((s.el.getAttribute('aria-current') === 'true') !== isActive) {
        s.el.setAttribute('aria-current', String(isActive));
        changed = true;
      }
    }
    if (changed && active !== null && !audio.paused) {
      try {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        active.el.scrollIntoView({ block: 'nearest' });
      } catch (_) {}
    }
  }

  function bindVocabButtons() {
    document.querySelectorAll('.fe-vocab-save').forEach((btn) => {
      if (btn.dataset.feBound === '1') return;
      btn.dataset.feBound = '1';
      btn.addEventListener('click', async () => {
        const status = document.getElementById('vocab-status');
        try {
          const res = await fetch('/app/words', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'X-CSRF-TOKEN': csrf(),
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
              word: btn.dataset.word,
              meaning_fa: btn.dataset.meaning || null,
              example_en: btn.dataset.example || null,
              lesson_id: Number(btn.dataset.lessonId) || null,
            }),
          });
          const data = await res.json().catch(() => null);
          if (res.status === 201) {
            btn.textContent = 'ذخیره شد';
            btn.disabled = true;
            if (status) status.textContent = '';
          } else {
            if (status) status.textContent = (data && data.message) || 'ذخیره واژه ناموفق بود.';
          }
        } catch (_) {
          if (status) status.textContent = 'ذخیره واژه ناموفق بود. اتصال را بررسی کنید.';
        }
      });
    });
  }

  function bindWordsPage() {
    document.querySelectorAll('.fe-word-remove').forEach((btn) => {
      if (btn.dataset.feBound === '1') return;
      btn.dataset.feBound = '1';
      btn.addEventListener('click', async () => {
        const status = document.getElementById('words-status');
        try {
          const res = await fetch(`/app/words/${Number(btn.dataset.wordId)}`, {
            method: 'DELETE',
            headers: {
              Accept: 'application/json',
              'X-CSRF-TOKEN': csrf(),
              'X-Requested-With': 'XMLHttpRequest',
            },
          });
          if (!res.ok) throw new Error(`remove failed: ${res.status}`);
          const row = btn.closest('.fe-vocab-row');
          row?.remove();
          if (status) status.textContent = '';
          if (document.querySelectorAll('.fe-vocab-row').length === 0) {
            window.location.reload();
          }
        } catch (_) {
          if (status) status.textContent = 'حذف واژه ناموفق بود. دوباره تلاش کنید.';
        }
      });
    });

    document.querySelectorAll('.fe-word-edit').forEach((btn) => {
      if (btn.dataset.feBound === '1') return;
      btn.dataset.feBound = '1';
      btn.addEventListener('click', () => {
        const row = btn.closest('.fe-vocab-row');
        const form = row?.querySelector('.fe-word-form');
        if (!form) return;
        const open = form.hasAttribute('hidden');
        if (open) {
          form.removeAttribute('hidden');
          btn.setAttribute('aria-expanded', 'true');
          form.querySelector('input')?.focus();
        } else {
          form.setAttribute('hidden', '');
          btn.setAttribute('aria-expanded', 'false');
          btn.focus();
        }
      });
    });

    document.querySelectorAll('.fe-word-form').forEach((form) => {
      if (form.dataset.feBound === '1') return;
      form.dataset.feBound = '1';
      form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const row = form.closest('.fe-vocab-row');
        const formStatus = form.querySelector('.fe-word-form-status');
        const meaning = form.querySelector('input[name="meaning_fa"]')?.value ?? '';
        const example = form.querySelector('input[name="example_en"]')?.value ?? '';
        try {
          const res = await fetch(`/app/words/${Number(form.dataset.wordId)}`, {
            method: 'PATCH',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'X-CSRF-TOKEN': csrf(),
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ meaning_fa: meaning, example_en: example }),
          });
          const data = await res.json().catch(() => null);
          if (!res.ok) throw new Error((data && data.message) || `edit failed: ${res.status}`);
          if (row) {
            const main = row.querySelector('.fe-vocab-main');
            const meta = row.querySelector('.fe-vocab-meta');
            row.querySelector('.fe-vocab-meaning')?.remove();
            row.querySelector('.fe-vocab-example')?.remove();
            if (data && data.meaning_fa) {
              const p = document.createElement('p');
              p.className = 'fe-vocab-meaning';
              p.setAttribute('dir', 'auto');
              p.textContent = data.meaning_fa;
              if (meta) meta.before(p);
              else main?.append(p);
            }
            if (data && data.example_en) {
              const p = document.createElement('p');
              p.className = 'fe-vocab-example';
              p.setAttribute('lang', 'en');
              p.setAttribute('dir', 'ltr');
              p.textContent = data.example_en;
              if (meta) meta.before(p);
              else main?.append(p);
            }
          }
          if (formStatus) formStatus.textContent = 'ذخیره شد.';
        } catch (_) {
          if (formStatus) formStatus.textContent = 'ذخیره تغییرات ناموفق بود. دوباره تلاش کنید.';
        }
      });
    });

    document.querySelectorAll('.fe-word-known').forEach((btn) => {
      if (btn.dataset.feBound === '1') return;
      btn.dataset.feBound = '1';
      btn.addEventListener('click', async () => {
        const status = document.getElementById('words-status');
        const target = btn.dataset.targetStatus || 'known';
        try {
          const res = await fetch(`/app/words/${Number(btn.dataset.wordId)}`, {
            method: 'PATCH',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'X-CSRF-TOKEN': csrf(),
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ status: target }),
          });
          if (!res.ok) throw new Error(`status failed: ${res.status}`);
          window.location.reload();
        } catch (_) {
          if (status) status.textContent = 'تغییر وضعیت ناموفق بود. دوباره تلاش کنید.';
        }
      });
    });

    const showBtn = document.getElementById('flash-show');
    if (showBtn && showBtn.dataset.feBound !== '1') {
      showBtn.dataset.feBound = '1';
      showBtn.addEventListener('click', () => {
        document.getElementById('flash-meaning')?.removeAttribute('hidden');
        document.getElementById('flash-grades')?.removeAttribute('hidden');
        document.getElementById('flash-grades-hint')?.removeAttribute('hidden');
        showBtn.hidden = true;
        document.querySelector('.fe-grade')?.focus();
      });
    }

    const gradeKeys = { 1: 'again', 2: 'hard', 3: 'good', 4: 'easy' };
    const reviewKeyHandler = (event) => {
      const grades = document.getElementById('flash-grades');
      if (!grades || grades.hasAttribute('hidden')) return;
      if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) return;
      const fa = { '۱': '1', '۲': '2', '۳': '3', '۴': '4' }[event.key] || event.key;
      const grade = gradeKeys[fa];
      if (!grade) return;
      const btn = grades.querySelector(`.fe-grade[data-grade="${grade}"]`);
      if (btn) {
        event.preventDefault();
        btn.click();
      }
    };
    if (!window.__feReviewKeys && document.querySelector('.fe-flash')) {
      window.__feReviewKeys = true;
      document.addEventListener('keydown', reviewKeyHandler);
    }

    document.querySelectorAll('.fe-grade').forEach((btn) => {
      if (btn.dataset.feBound === '1') return;
      btn.dataset.feBound = '1';
      btn.addEventListener('click', async () => {
        const status = document.getElementById('grade-status');
        try {
          const res = await fetch(`/app/words/${Number(btn.dataset.wordId)}/grade`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'X-CSRF-TOKEN': csrf(),
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ grade: btn.dataset.grade }),
          });
          if (!res.ok) throw new Error(`grade failed: ${res.status}`);
          window.location.reload();
        } catch (_) {
          if (status) status.textContent = 'ثبت نتیجه ناموفق بود. دوباره تلاش کنید.';
        }
      });
    });
  }

  function bindPageButtons() {
    rebuildSentences();
    bindVocabButtons();
    bindWordsPage();
    const bookmarkBtn = $('bookmark-toggle');
    if (bookmarkBtn && bookmarkBtn.dataset.feBound !== '1') {
      bookmarkBtn.dataset.feBound = '1';
      bookmarkBtn.addEventListener('click', async () => {
        const topicId = Number(bookmarkBtn.dataset.topicId);
        const isBookmarked = bookmarkBtn.dataset.bookmarked === '1';
        const status = $('bookmark-status');
        try {
          const res = isBookmarked
            ? await fetch(`/app/bookmarks/${topicId}`, {
                method: 'DELETE',
                headers: {
                  Accept: 'application/json',
                  'X-CSRF-TOKEN': csrf(),
                  'X-Requested-With': 'XMLHttpRequest',
                },
              })
            : await fetch('/app/bookmarks', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  Accept: 'application/json',
                  'X-CSRF-TOKEN': csrf(),
                  'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ topic_id: topicId }),
              });
          if (!res.ok) throw new Error(`bookmark failed: ${res.status}`);
          const next = !isBookmarked;
          bookmarkBtn.dataset.bookmarked = next ? '1' : '0';
          bookmarkBtn.setAttribute('aria-pressed', String(next));
          const label = bookmarkBtn.querySelector('span');
          if (label) label.textContent = next ? 'حذف از ذخیره‌شده‌ها' : 'ذخیره برای بعد';
          else bookmarkBtn.textContent = next ? 'حذف از ذخیره‌شده‌ها' : 'ذخیره برای بعد';
          if (status) status.textContent = '';
        } catch (_) {
          if (status) status.textContent = 'ذخیره‌سازی ناموفق بود. دوباره تلاش کنید.';
        }
      });
    }

    const markBtn = $('mark-read');
    if (markBtn && markBtn.dataset.feBound !== '1') {
      markBtn.dataset.feBound = '1';
      markBtn.addEventListener('click', () => {
        completeLesson();
      });
    }

    const resetBtn = $('reset-progress');
    if (resetBtn) bindResetButton(resetBtn);

    // Guest level preference: keep only the level code (A1–C2), never any
    // token, receipt, body text, or answer key.
    document.querySelectorAll('a.fe-level-link').forEach((link) => {
      if (link.dataset.feLevelBound === '1') return;
      link.dataset.feLevelBound = '1';
      link.addEventListener('click', () => {
        const level = (link.textContent || '').trim().toUpperCase();
        if (LEVELS.includes(level)) {
          try {
            localStorage.setItem('fe-guest-level', level);
          } catch (_) {}
        }
      });
    });

    // Library filter: for guests with a stored level and no explicit query,
    // preselect the stored level. Authenticated users rely on preferred_level
    // server-side; localStorage never overrides an explicit URL.
    const filterLevel = document.getElementById('filter-level');
    if (filterLevel && !new URLSearchParams(window.location.search).has('level')) {
      try {
        const stored = (localStorage.getItem('fe-guest-level') || '').toUpperCase();
        if (LEVELS.includes(stored)) {
          const option = filterLevel.querySelector(`option[value="${stored}"]`);
          if (option) filterLevel.value = stored;
        }
      } catch (_) {}
    }
  }

  function syncLesson() {
    bindPageButtons();
    state.stopAt = null;
    const data = lessonData();
    if (!data) {
      // Non-reader page (library, saved, account): keep playing.
      // An idle bar (no source yet) keeps play disabled.
      if (state.lessonId === null) setPlayEnabled(false);
      return;
    }
    state.authenticated = data.authenticated;
    setPlayEnabled(true);

    if (state.lessonId !== null && state.lessonId !== data.lessonId) {
      // A different lesson or level: stop the current audio first so the
      // old sound never continues under the new text (S1 rule, kept).
      try {
        audio.pause();
      } catch (_) {}
    }

    if (state.lessonId !== data.lessonId || state.revision !== data.revision) {
      state.lessonId = data.lessonId;
      state.revision = data.revision;
      state.lastSave = 0;
      state.pendingSave = null;
      state.appliedInitialFor = null;
      clearError();
      clearProgressError();
      setMiniPlayer(data.topic, data.level);
      setThumb(data.coverUrl);
      audio.src = data.audioUrl;
      try {
        audio.load();
      } catch (_) {}
      // Seek to the saved position once metadata arrives; never autoplay.
      const initial = Math.max(0, Number(data.initialPosition) || 0);
      const applyInitial = () => {
        if (state.appliedInitialFor === `${data.lessonId}:${data.revision}`) return;
        state.appliedInitialFor = `${data.lessonId}:${data.revision}`;
        try {
          audio.currentTime = clampTime(initial);
        } catch (_) {}
        refreshSeek();
      };
      if (Number.isFinite(audio.duration) && audio.duration > 0) {
        applyInitial();
      } else {
        audio.addEventListener('loadedmetadata', applyInitial, { once: true });
      }
      refreshPlayLabel();
      refreshSeek();
      return;
    }

    // Same lesson and revision (e.g., back navigation): keep the time.
    state.authenticated = data.authenticated;
    setMiniPlayer(data.topic, data.level);
    setThumb(data.coverUrl);
  }

  // --- Persisted controls: bound once. ---

  function bindPersisted() {
    const playBtn = $('player-play');
    const backBtn = $('player-back');
    const fwdBtn = $('player-forward');
    const seek = $('player-seek');
    const status = $('player-status');
    const toggle = $('player-toggle');

    // The bar rests collapsed on first paint everywhere (phones and
    // desktop alike — deterministic for users and proofs); the toggle
    // always wins afterwards, and errors auto-expand.
    if (toggle && !toggle.dataset.feBound) {
      toggle.dataset.feBound = '1';
      setExpanded(false);
      toggle.addEventListener('click', () => {
        setExpanded(!isExpanded());
      });
    }

    playBtn?.addEventListener('click', () => {
      clearError();
      if (audio.paused) {
        const p = audio.play();
        if (p && typeof p.catch === 'function') {
          p.catch(() => showError('پخش آغاز نشد. اتصال را بررسی کنید و دوباره تلاش کنید.'));
        }
      } else {
        audio.pause();
      }
    });

    backBtn?.addEventListener('click', () => {
      audio.currentTime = clampTime(audio.currentTime - STEP);
    });

    fwdBtn?.addEventListener('click', () => {
      audio.currentTime = clampTime(audio.currentTime + STEP);
    });

    seek?.addEventListener('input', () => {
      clearError();
      // A seek-driven jump to the end must never count as completion.
      state.suppressEndedOnce = true;
      audio.currentTime = clampTime(Number(seek.value));
      refreshSeek();
    });

    const speedBtn = $('player-speed');
    if (speedBtn && !speedBtn.dataset.feBound) {
      speedBtn.dataset.feBound = '1';
      speedBtn.addEventListener('click', () => {
        state.speedIdx = ((state.speedIdx ?? 0) + 1) % SPEEDS.length;
        audio.playbackRate = SPEEDS[state.speedIdx];
        refreshSpeedLabel();
      });
    }
    refreshSpeedLabel();

    $('player-retry')?.addEventListener('click', () => {
      clearError();
      if (status) status.textContent = 'در حال تلاش دوباره…';
      audio.load();
    });

    $('progress-retry')?.addEventListener('click', () => {
      if (state.pendingSave) {
        const pos = state.pendingSave.position_seconds;
        state.pendingSave = null;
        saveProgress(pos);
      } else {
        saveProgress(audio.currentTime);
      }
    });

    audio.addEventListener('play', () => {
      refreshPlayLabel();
      if (status) status.textContent = '';
    });
    audio.addEventListener('pause', () => {
      refreshPlayLabel();
      // Save on pause (authenticated only); failures show retryable UI.
      if (state.authenticated && state.lessonId !== null) {
        saveProgress(audio.currentTime, { silent: false });
      }
    });
    audio.addEventListener('timeupdate', () => {
      refreshSeek();
      maybePeriodicSave();
      // R3: stop at the end of a sentence selected for isolated playback.
      if (state.stopAt !== null && Number.isFinite(audio.currentTime) && audio.currentTime >= state.stopAt) {
        state.stopAt = null;
        try { audio.pause(); } catch (_) {}
      }
      refreshSentence(audio.currentTime);
    });
    audio.addEventListener('loadedmetadata', refreshSeek);
    audio.addEventListener('waiting', () => {
      if (status) status.textContent = 'در حال بارگذاری صوت…';
    });
    audio.addEventListener('playing', () => {
      if (status) status.textContent = '';
    });
    audio.addEventListener('canplay', () => {
      if (status && status.textContent === 'در حال تلاش دوباره…') status.textContent = '';
    });
    audio.addEventListener('error', () => {
      showError('خطا در دریافت صوت. اتصال را بررسی کنید و دوباره تلاش کنید.');
    });
    audio.addEventListener('ended', () => {
      refreshPlayLabel();
      refreshSeek();
      if (state.suppressEndedOnce) {
        state.suppressEndedOnce = false;
        return;
      }
      if (state.authenticated && state.lessonId !== null) {
        saveProgress(audio.duration || audio.currentTime, { silent: true }).then(() => completeLesson());
      }
    });

    // Best-effort only: never relied upon for durability.
    const bestEffort = () => {
      if (!audio.paused && state.authenticated && state.lessonId !== null) {
        try {
          fetch('/app/progress', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({
              lesson_id: state.lessonId,
              audio_revision: state.revision,
              position_seconds: audio.currentTime,
            }),
            keepalive: true,
          }).catch(() => {});
        } catch (_) {}
      }
    };
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') bestEffort();
    });
    window.addEventListener('pagehide', bestEffort);

    // Logout and admin leave the learner layout via full navigation: stop
    // the audio and clear its source first so nothing lingers in bfcache.
    document.addEventListener('submit', (event) => {
      const form = event.target;
      if (form instanceof HTMLFormElement && form.hasAttribute('data-fe-logout')) {
        stopAndClear();
      } else if (form instanceof HTMLFormElement && /\/logout\b/.test(form.action)) {
        stopAndClear();
      }
    });
    document.addEventListener('click', (event) => {
      const anchor = event.target instanceof Element ? event.target.closest('a[href]') : null;
      if (!anchor) return;
      const href = anchor.getAttribute('href') || '';
      if (href.startsWith('/admin') || href === '/logout') {
        stopAndClear();
      }
    });

    document.addEventListener('livewire:navigated', () => {
      syncLesson();
    });

    refreshPlayLabel();
    refreshSeek();
  }

  bindPersisted();
  syncLesson();
})();
