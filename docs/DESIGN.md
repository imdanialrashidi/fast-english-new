# Fast English — Design Contract

Source: scope §17 (UI/UX), §6 (routes/nav), §7 (flows), §13 (player/progress). Scope §17.1 palette/typography values are **proposals, not owner approval**.

## Owner direction (confirmed)

- Calm, professional, adult, readable, editorial; visual focus on text, lesson image, and playback (§17.1).
- Mobile-first, single-column on mobile; reading measure ~680px on desktop (§17.1).
- Persian RTL chrome, English content LTR (scope §17.3).
- No admin-dashboard look in the learner surface (§17.1).
- No second frontend; learner styling is Tailwind 4.1+ with Flux Free only where needed + bespoke Blade (§2).

## Proposed defaults (NOT owner-approved — reversible)

The following are the scope's §17.1 working proposals. They are defaults for scaffolding and first rendered review, not brand approval:

| Semantic token | Proposed value (§17.1) | Status |
|---|---|---|
| canvas | `#F8FAFC` | PROPOSED |
| surface | `#FFFFFF` | PROPOSED |
| text | `#0F172A` | PROPOSED |
| muted-text | `#475569` | PROPOSED |
| border | `#E2E8F0` | PROPOSED |
| primary | `#1D4ED8` | PROPOSED |
| primary-hover | `#1E40AF` | PROPOSED |
| on-primary | `#FFFFFF` | PROPOSED |
| success | `#15803D` | PROPOSED |
| danger | `#B91C1C` | PROPOSED |

- Typography (PROPOSED): Vazirmatn self-hosted for Persian (license recorded before use); legible system font for English. English body 18px / ~1.75 line-height; main UI controls ≥16px.
- Restraint (binding): no new arbitrary colors, decorative shadows/gradients/glows, or permanent animation. Levels indicated by text + limited tokens — no six loud level colors (§17.1).
- Proposed mobile destinations (PROPOSED, §6): «مطالب» / «ذخیره‌شده‌ها» / «حساب». Placement, payment, settings are sub-paths; admin is not in learner nav.
- Canonical code token source: `resources/css/app.css` (semantic tokens + learner styles). Since S1 the code tokens own the resolved values: `:root` variables `--fe-canvas`, `--fe-surface`, `--fe-text`, `--fe-muted-text`, `--fe-border`, `--fe-primary`, `--fe-primary-hover`, `--fe-on-primary`, `--fe-success`, `--fe-danger`, plus `--fe-font-fa` (self-hosted Vazirmatn variable, `public/fonts/vazirmatn/`, OFL license beside the files) and `--fe-font-en` (system stack). The table above keeps the proposed values and PROPOSED status (unchanged, still not owner approval). Never maintain a second competing palette.
- S1 measured contrast (computed WCAG relative luminance on the actual pairs, 2026-10-08; pairs are the proposals, not approvals): text/canvas 17.06:1, primary/surface 6.70:1, on-primary/primary 6.70:1, success/surface 5.02:1, danger/surface 6.47:1, muted-text/canvas 7.24:1. Every pair measures at or above 4.5:1 for normal text.

## Direction

- Visual thesis: a quiet reading room — text first, one restrained player bar, supporting imagery kept small.
- Signature element: the persistent lesson player + level selector pair (proposed; to be judged on rendered evidence, not screenshots count).
- Aesthetic risk / restraint: deliberately plain; novelty never overrides contrast, focus, touch, or RTL/LTR correctness.
- Must never look generic: no interchangeable card-grid marketing look; no dashboard KPI widgets in learner surfaces.

## Composition and responsiveness

- List page (§17.2): page title, level selector, low-noise search, a "continue" section only when valid progress exists, then newest topics. One card/row per topic (six versions never become six results). Card: fixed-ratio image, title, available levels, estimated time. No badge piles, internal metadata, tables, or finance KPIs.
- Saved page reuses the list pattern (§17.2). Account shows subscription + current request state with one clear CTA.
- Lesson page order (§17.3): title/bounded image → level selector → player → body → key words. No oversized card per paragraph.
- Reader + mini-player must not overlap keyboard, bottom nav, or safe-area; end-of-content padding fits the fixed controls. Speed + bookmark sit in a fixed, understandable spot; controls are not icon-only without names.
- Viewports: 360 / 390 / 430 / 768 / 1440 reviewed; reflow at 320 CSS px for the main path (§17.4). Mobile recomposition, not shrinkage.
- RTL/LTR: chrome `lang=fa dir=rtl`; English body `lang=en dir=ltr` (§17.3). Long FA/EN text and 200% zoom must not break structure.

## Components and states

Required journey states with real data in QA (§17.4): loading, empty, error/retry, locked (premium without access), pending, rejected, expired. Error keeps safe form values; success never shown before server confirmation (§7.4). Color alone never signals error/status.

## Accessibility targets (scope §17.4 — functional target)

- WCAG 2.2 AA on main paths as a working target; full conformance claimed only after a sufficient audit.
- Normal-text contrast ≥ 4.5:1, large text ≥ 3:1 — measured on real pairs, not assumed.
- Touch target design goal 44×44 CSS px; seek usable by keyboard as well as drag.
- Visible focus, logical keyboard order, labels, control names, field-associated errors, status announcements.
- Reduced motion honored; no autoplay. Modals hold and correctly return focus.
- First UI proof gate: list + reader on mobile with real content, inspected as rendered images before full UI build (§17.4).

## Quality budgets

- Accessibility: above. Performance: LCP ≤ 2.5s, INP ≤ 200ms, CLS ≤ 0.1 at p75 unless stricter product budgets are accepted; pre-release lab budget + production RUM/rollout proof required before field claims.
- Reader payload: audio `preload=metadata`; never auto-download all list audio; admin JS/CSS never loaded in learner bundle (scope §20.2).

## Screen acceptance (proof plan; evidence in QUALITY.md)

| Flow | Critical states | Viewports/locales | Visual proof |
|---|---|---|---|
| List + saved | loading, empty, error, locked | 360–430, 768, 1440; fa-RTL | Rendered images inspected (AC-03, AC-18) |
| Lesson reader + player | level switch, missing level, network error | mobile first + desktop measure; en-LTR body | Rendered + browser interaction (AC-04, AC-05, AC-18) |
| Account/subscribe/payment | pending, rejected, expired | mobile | Rendered + server proof (AC-10/11/14, AC-18) |

## Decisions intentionally deferred

Final brand/logo, exact palette approval, font license confirmation, dark mode (out of v1), multi-locale UI (out of v1).

## S3 library note (2026-10-08 — no owner choices; proposals unchanged)

No explicit user style/color choice was given for S3, so nothing is recorded as owner-approved: the §17.1 palette stays PROPOSED and the canonical token source stays `resources/css/app.css` (`:root --fe-*`). The library reuses those tokens only — a test (`tests/Feature/S3DesignTest.php`) fails the build if any color literal appears outside the `:root` block.

- Composition: page title, GET filter form (level select, category select, title search — each with a visible `<label>`), result count, then one card per topic; empty state with a reset link; pagination (12/page) sharing the 44px target rule. Single column on mobile, 2 columns from 640px, 3 from 1024px (recomposition, not shrinkage).
- Card: fixed 16/9 cover (`aspect-ratio: 16/9`, `object-fit: cover`, lazy), English title (`lang=en dir=ltr`), available levels as text, approximate minutes. Cover-less topics show a restrained FE monogram block in canvas/muted tones — no stock imagery.
- Reader additions: bounded cover (`max-height: 15rem`) and the active lesson's glossary only (word LTR bold, Persian meaning, optional LTR example), all escaped.
- Re-measured contrast on the real pairs (computed WCAG luminance, 2026-10-08): text/canvas 17.06:1, muted-text/canvas 7.24:1, text/surface 17.85:1, muted-text/surface 7.58:1, primary/surface 6.70:1, on-primary/primary 6.70:1, success/surface 5.02:1, danger/surface 6.47:1 — every pair ≥ 4.5:1.
- Studio pass: library at 360/390/320 plus desktop 1280 and the reader glossary inspected as rendered pixels (test-results/browser-shots/s3-*.png); quiet reading-room thesis holds; fixture covers are labelled PIL placeholders, not art direction.

## S4 returning-user note (2026-10-08 — no owner choices; proposals unchanged)

No explicit user style/color choice was given for S4, so nothing is recorded as owner-approved: the §17.1 palette stays PROPOSED and the canonical token source stays `resources/css/app.css` (`:root --fe-*`). No new colors or text pairs were introduced — `tests/Feature/S3DesignTest.php` still passes on the same eight pairs (all ≥ 4.5:1).

- Thesis kept: quiet reading room — text first, one restrained player bar, supporting imagery kept small. Signature: the persistent lesson player + level selector pair, now with a mini-player line (`در حال پخش: topic (level)`) in the same bar.
- Top nav: three destinations (مطالب / ذخیره‌شده‌ها / حساب), each a 44 px target, reusing text/border/surface tokens only.
- Saved reuses the library card component (`library/_card.blade.php`) with the single fixed 16/9 media container (`.fe-card-media`); reader keeps the bounded cover + active-lesson glossary only.
- Player bar grows by one mini-player line; end-of-content padding raised 19 rem → 22 rem so the last glossary/body row clears the fixed bar when scrolled to the bottom (proven at 360/390).
- Studio pass: saved + reader at 360/390 plus glossary at 390 inspected as rendered pixels (test-results/browser-shots/s4-*.png); restraint holds, no badge piles or dashboard look.

## S7 placement note (2026-10-09 — no owner choices; proposals unchanged)

No explicit user style/color choice was given for S7, so nothing is recorded as owner-approved: the §17.1 palette stays PROPOSED and the canonical token source stays `resources/css/app.css` (`:root --fe-*`). Placement reuses learner tokens only — question cards, answer options, result panel, and the neutral "در دست آماده‌سازی" state share `.fe-card`/`.fe-btn`/`.fe-filters` styling with no new colors or text pairs. `tests/Feature/S3DesignTest.php` still guards the eight measured pairs (all ≥ 4.5:1). Thesis kept: quiet reading room; signature stays the persistent player + level selector pair — placement adds no second signature, only a restrained question/result sheet in the same measure.

## S8 public note (2026-10-09 — no owner choices; proposals unchanged)

No explicit user style/color choice was given for S8, so nothing is recorded as owner-approved: the §17.1 palette stays PROPOSED and the canonical token source stays `resources/css/app.css` (`:root --fe-*`). Landing, trust, download, reset, and staff-2FA pages reuse learner tokens only (`.fe-card`/`.fe-btn`/`.fe-field` plus small var-only additions: `.fe-hero`, `.fe-draft-badge`, `.fe-blocked`, `.fe-meta-table`, `.fe-footer`, `.fe-prose`). `tests/Feature/S3DesignTest.php` still passes (same eight pairs, all ≥ 4.5:1; no hex literal outside `:root`). Thesis kept: quiet reading room; signature unchanged — the landing is a short single-measure sheet (promise → sample → how-it-works → plans → install → FAQ) with one restrained DRAFT badge, no marketing hero art, no price claims.
