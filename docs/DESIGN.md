# Fast English — Design Contract

Source: scope §17 (UI/UX), §6 (routes/nav), §7 (flows), §13 (player/progress). Scope §17.1 palette/typography values are **proposals, not owner approval**.

## Owner direction (confirmed)

- Calm, professional, adult, readable, editorial; visual focus on text, lesson image, and playback (§17.1).
- Mobile-first, single-column on mobile; reading measure ~680px on desktop (§17.1).
- Persian RTL chrome, English content LTR (scope §17.3).
- No admin-dashboard look in the learner surface (§17.1).
- No second frontend; learner styling is Tailwind 4.1+ with Flux Free only where needed + bespoke Blade (§2).

## Owner instruction 2026-10-09 (authoritative per scope §0 — save-only, no UI built this turn)

The product and landing must look polished, distinctive, and professional, comparable to leading consumer audio and learning apps. Dark mode is required alongside light mode. The palette must be distinctive. Every published topic must show a real cover image, not an empty placeholder.

## Owner-approved amendments 2026-10-09 (supersede cited scope text; reason recorded)

1. Dark mode into v1 (was scope §4 excluded). Full second theme alongside light. Light is the default; user can choose system, light, or dark. Reason: owner requires dark alongside light for parity with leading audio/learning apps.
2. Scope §17.1 restraint relaxed, narrowly. Gradients and glow allowed only in cover art and the landing hero. Motion allowed only for state feedback and transitions, and must respect `prefers-reduced-motion`. Shadows limited to named elevation tokens. Reason: owner requires polished/distinctive finish; prior blanket ban blocks cover/hero craft.
3. Navigation to mobile bottom tab bar: Library, Saved, Account; mini-player above it with safe-area padding. Supersedes top-bar placement. Scope §17.3 already assumes bottom nav/mini-player/safe-area. Reason: owner polish + audio-app pattern on mobile.
4. Covers for every published topic. Default is a generated abstract cover, rendered server-side with GD, deterministic from the topic slug, with no text baked into the image. Staff may upload licensed photos; each upload needs a license record: source, license name, date checked, usage notes. No image fetched from a third-party API at runtime. Reason: owner requires no empty placeholder; provenance must be auditable without runtime dependency.
5. Amber replaces the navy/blue identity from scope §17.1 (primary `#1D4ED8`, primary-hover `#1E40AF`) with the Night Studio amber scale (dark `#F5A524`, light `#B45309`, sepia `#92400E`); navy/blue is no longer the identity. Owner-approved on 2026-10-09 by owner direction. Reason: owner direction requires a distinctive polished palette with a warm amber signal for play/progress.
6. Soft Day light theme (owner Softly inspiration, 2026-10-09) replaces the Night Studio light table and the §17.1 proposal values in code for learner app surfaces: canvas `#FDFCF8`, surface `#FFFFFF`, text `#292524`, muted `#78716C`, coral primary `#FFB7B2`/hover `#FF9E99` with dark label `#292524`, divider `#E7E5E4`, input-boundary `#78716C`, lavender tint `#EFEDF4`; sage `#E8EFE8` recorded for landing. Performance-heavy effects explicitly excluded (no grain overlay, no backdrop-blur, no permanent/floating animation, no giant blurred blobs). Landing markup untouched. Owner-approved on 2026-10-09 by owner direction. Reason: owner wants the Softly look in the app without the perf cost.

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

## Direction history

- Quiet reading room (S1–S8 working default): text first, one restrained player bar, supporting imagery kept small. SUPERSEDED 2026-10-09 by the owner instruction above; retained here as history. S3/S4/S7/S8 notes below were judged under that thesis.

## Direction — Night Studio (SUPERSEDED 2026-10-09 for the app light theme by the owner-approved Soft Day direction below; dark/sepia tables retained as unapproved history — see open decisions)

- Visual thesis: a night studio — deep ink-blue dark canvas with a warm amber signal for play/progress, editorial reading surfaces in light/sepia; audio controls feel like studio hardware, text stays calm.
- Signature element (proposed): the mini-player + bottom tab bar pair with an amber progress signal, plus deterministic abstract cover art per topic (GD, slug-seeded, no baked text).
- Intentional restraint: one amber accent for primary/progress, one teal for success, one coral for danger; gradients/glow only in cover art and landing hero; motion only for state feedback/transitions with `prefers-reduced-motion` honored; shadows only via named elevation tokens.
- Must never look generic: no interchangeable card-grid marketing look; no dashboard KPI widgets; no stock-photo filler; no ad-hoc colors in components.
- Audience/surface jobs: Persian-RTL learners picking a short English article at a level, reading + listening, resuming later; landing promises one sample, how-it-works, plans, install, FAQ.
- Composition: single column mobile; reading measure ~680px desktop; list = title + level selector + low-noise search + continue-when-valid + newest topics, one card/row per topic (16/9 cover, title, levels, minutes); lesson order title/bounded image → level selector → player → body → key words; account = subscription + current request + one CTA.
- Responsive transformation: bottom tabs + mini-player with safe-area padding (`env(safe-area-inset-bottom)`); end-of-content padding clears fixed controls; reflow at 320 CSS px; review at 360/390/1280 (see D2–D5).
- Motion: no autoplay, no permanent animation; transitions ≤200ms for press/focus/player state only; reduced-motion disables all.
- Content voice: direct Persian chrome, LTR English body; error keeps safe values; success only after server confirmation; color never the sole signal.
- Code mapping (proposed, NOT implemented this turn): `resources/css/app.css` owns tokens as `@theme` variables plus a `[data-theme]` custom variant with per-theme definitions (`dark`, `light`, `sepia`); components use only those variables; no hex literal outside the token block (extends the existing S3DesignTest rule to all three themes).
- Typography (PROPOSED): Persian UI Vazirmatn self-hosted (existing `public/fonts/vazirmatn/`, OFL beside files); English display sans Sora self-hosted under OFL (license file to be recorded at `public/fonts/sora/OFL.txt` before use); English reading serif Newsreader self-hosted under OFL (license at `public/fonts/newsreader/OFL.txt` before use). Roles: Vazirmatn = Persian chrome/labels; Sora = English headings/hero/player numerals; Newsreader = English lesson body/glossary examples. Fallbacks: system-ui stacks. English body 18px/1.75; controls ≥16px.

### Night Studio token tables (every hex PROPOSED until owner approval; ratios measured 2026-10-09 by computed WCAG relative luminance, same method as S1/S3)

Dark — deep ink-blue (design-origin theme; runtime default stays light per amendment 1):

| Token | Hex (PROPOSED) | Role |
|---|---|---|
| canvas | `#0D1526` (PROPOSED) | app background |
| surface | `#172033` (PROPOSED) | cards/player/bars |
| text | `#F2EFE9` (PROPOSED) | body |
| muted-text | `#B8C0D1` (PROPOSED) | secondary |
| border | `#2A3A55` (PROPOSED) | dividers only, decorative |
| input-border | `#7A8BB0` (PROPOSED) | input and checkbox boundaries |
| primary | `#F5A524` (PROPOSED) | amber fill/progress |
| primary-hover | `#F7B84B` (PROPOSED) | hover/pressed fill |
| on-primary | `#1A1206` (PROPOSED) | label on amber |
| success | `#2DD4BF` (PROPOSED) | teal |
| danger | `#FB7185` (PROPOSED) | restrained coral |
| elevation-1 | `0 1px 2px rgb(0 0 0 / 0.45)` (PROPOSED) | shadow only |
| elevation-2 | `0 8px 28px rgb(0 0 0 / 0.45)` (PROPOSED) | shadow only |

Light — warm paper (runtime default):

| Token | Hex (PROPOSED) | Role |
|---|---|---|
| canvas | `#FAF6EF` (PROPOSED) | app background |
| surface | `#FFFDF8` (PROPOSED) | cards/player/bars |
| text | `#1B1E2A` (PROPOSED) | body |
| muted-text | `#5B6372` (PROPOSED) | secondary |
| border | `#E7DFD2` (PROPOSED) | dividers only, decorative |
| input-border | `#776B5B` (PROPOSED) | input and checkbox boundaries |
| primary | `#B45309` (PROPOSED) | amber-700 fill (darkened for contrast) |
| primary-hover | `#92400E` (PROPOSED) | hover fill |
| on-primary | `#FFFFFF` (PROPOSED) | label on amber |
| success | `#0F766E` (PROPOSED) | teal-700 |
| danger | `#BE123C` (PROPOSED) | rose-700 |
| elevation-1 | `0 1px 2px rgb(59 46 31 / 0.12)` (PROPOSED) | shadow only |
| elevation-2 | `0 12px 32px rgb(59 46 31 / 0.14)` (PROPOSED) | shadow only |

Sepia — reader option (proposed):

| Token | Hex (PROPOSED) | Role |
|---|---|---|
| canvas | `#F5EBD3` (PROPOSED) | reader background |
| surface | `#FFF8E7` (PROPOSED) | reader cards/bars |
| text | `#3B2E1F` (PROPOSED) | body |
| muted-text | `#6B5D4D` (PROPOSED) | secondary |
| border | `#E3D5B8` (PROPOSED) | dividers only, decorative |
| input-border | `#76644F` (PROPOSED) | input and checkbox boundaries |
| primary | `#92400E` (PROPOSED) | amber fill |
| primary-hover | `#78350F` (PROPOSED) | hover fill |
| on-primary | `#FFF8E7` (PROPOSED) | label on amber |
| success | `#0F766E` (PROPOSED) | teal |
| danger | `#9F1239` (PROPOSED) | deep rose |
| elevation-1 | `0 1px 2px rgb(59 46 31 / 0.12)` (PROPOSED) | shadow only |
| elevation-2 | `0 12px 32px rgb(59 46 31 / 0.14)` (PROPOSED) | shadow only |

### Measured contrast (computed ratios; body ≥4.5:1 PASS, controls/focus/input-boundaries ≥3:1 PASS; decorative dividers exempt, never sole indicators)

Dark (`#0D1526` canvas / `#172033` surface): text/canvas 15.88:1, text/surface 14.18:1, muted/canvas 9.98:1, muted/surface 8.91:1, on-primary/primary 9.08:1, on-primary/hover 10.51:1, success/surface 8.74:1, success/canvas 9.79:1, danger/surface 6.04:1, danger/canvas 6.77:1, primary/canvas 8.93:1 (focus/non-text), primary/surface 7.97:1, input-border `#7A8BB0`/surface 4.76:1 (control boundary). Border `#2A3A55`/canvas 1.59:1 — dividers only, decorative, never the sole control indicator; focus uses primary (≥7:1).

Light (`#FAF6EF` canvas / `#FFFDF8` surface): text/canvas 15.40:1, text/surface 16.32:1, muted/canvas 5.61:1, muted/surface 5.95:1, on-primary/primary 5.02:1, on-primary/hover 7.09:1, success/surface 5.38:1, success/canvas 5.08:1, danger/surface 6.18:1, danger/canvas 5.83:1, primary-as-text/surface 4.94:1, primary-as-text/canvas 4.66:1, primary-focus/surface 4.94:1, input-border `#776B5B`/surface 5.12:1 (control boundary). Border `#E7DFD2`/canvas 1.23:1 — dividers only, decorative; focus uses primary (≥4.6:1).

Sepia (`#F5EBD3` canvas / `#FFF8E7` surface): text/canvas 11.09:1, text/surface 12.42:1, muted/canvas 5.37:1, muted/surface 6.01:1, on-primary/primary 6.70:1, on-primary/hover 8.57:1, success/surface 5.17:1, success/canvas 4.61:1, danger/surface 7.57:1, danger/canvas 6.76:1, primary-as-text/surface 6.70:1, primary-as-text/canvas 5.98:1, input-border `#76644F`/surface 5.35:1 (control boundary). Border `#E3D5B8`/canvas 1.22:1 — dividers only, decorative; focus uses primary (≥5.9:1).

No failing pair waived: all body-text pairs ≥4.5:1, all control/focus/input-boundary pairs ≥3:1 on the listed fills; decorative divider borders are exempt and never sole indicators. Anti-template check: ink-blue + warm-paper + sepia triple with amber-progress/mini-player/abstract-cover signature is product-specific; rejected the generic slate-blue + Inter-default marketing look.

## Direction — Soft Day (owner-approved light theme 2026-10-09; implemented in `resources/css/app.css` this turn)

- Visual thesis: a soft daylight reading room — warm paper canvas, white pill surfaces, one coral signal for primary actions and progress; calm Persian chrome, friendly but adult.
- Signature element: the floating pill topnav (solid surface, soft shadow, no blur) with dark pill CTA, coral primary buttons, and lavender monogram covers on cream.
- Intentional restraint: one coral accent for primary/progress, retained teal/rose for status text only (always with text/icon, never color-alone); soft shadow only via `--fe-elev-1`; transitions ≤200ms for press/focus only; `prefers-reduced-motion` disables all.
- Explicitly excluded by owner order (perf cost): SVG grain overlay, `backdrop-blur` nav, permanent/floating blob animations, giant blurred background blobs.
- Must never look generic: no navy-SaaS blue, no dashboard widgets, no stock-photo filler, no ad-hoc colors outside the table below.
- Palette (every hex owner-approved 2026-10-09; code owns values in `resources/css/app.css :root`):

| Token | Hex (APPROVED 2026-10-09) | Role |
|---|---|---|
| canvas | `#FDFCF8` | app background |
| surface | `#FFFFFF` | cards/bars/inputs |
| text | `#292524` | body |
| muted-text | `#78716C` | secondary (4.67:1 on canvas — thin but PASS, recorded) |
| border | `#E7E5E4` | dividers only, decorative |
| input-border | `#78716C` | input/checkbox/radio boundaries (4.80:1 on surface) |
| primary | `#FFB7B2` | coral fill/progress (dark label 9.15:1) |
| primary-hover | `#FF9E99` | hover/pressed fill (dark label 7.66:1) |
| on-primary | `#292524` | label on coral |
| tint-lavender | `#EFEDF4` | cover-placeholder bg (dark monogram 13.07:1; never muted text) |
| success | `#15803D` | retained status text (5.02:1) |
| danger | `#B91C1C` | retained status text (6.47:1) |
| elev-1 | `0 4px 20px -2px rgb(0 0 0 / 0.05)` | soft shadow only |

- Landing record (APPROVED for future landing work; landing markup untouched this turn): same canvas/surface/text/muted/coral tokens plus sage `#E8EFE8` and lavender `#EFEDF4` screen/tint backgrounds with dark `#292524` text.
- Measured contrast, light (computed WCAG relative luminance, same method as S1/S3, 2026-10-09): text/canvas 14.78:1, text/surface 15.17:1, muted/canvas 4.67:1, muted/surface 4.80:1, on-primary/primary 9.15:1, on-primary/hover 7.66:1, success/surface 5.02:1, danger/surface 6.47:1, input-border/surface 4.80:1 (control boundary), text/primary 9.15:1 (dark focus ring on accent), text/surface 15.17:1 (dark focus ring), text/lavender 13.07:1. Coral `#FFB7B2`/surface 1.66:1 — coral is NEVER body text or a lone boundary; it is fill-with-dark-label only. No failing pair waived.
- Shape language: cards/filters/empty/cover blocks 1.5rem; buttons/inputs/pills/nav links 999px; player bar top corners 1.25rem; floating pill topnav with 1rem side margins (solid surface, soft shadow).
- Focus: 3px dark ring (`--fe-text`) — ≥9:1 on surface, canvas, and accent; never coral-on-white.
- Typography (unchanged this turn): Vazirmatn Persian + system English stay; Outfit/Reenie Beanie deferred (Latin-only glyphs, extra downloads — open decision).
- Motion: explicit no-motion direction beyond ≤200ms press/focus feedback; the existing reduced-motion kill-switch stays.
- Anti-template check: cream + coral + lavender + pill-nav + monogram-cover signature is product-specific; rejected both the generic navy-SaaS look and the superseded ink-blue Night Studio.

## Direction — Editorial Ivory (owner-approved 2026-10-09; reference image is the primary visual direction)

- Visual thesis: a premium editorial learning product — the elegance of a digital magazine with the usability of a modern learning app. Generous whitespace, clear hierarchy, editorial headlines, consistent image crops, readable measures, precise alignment.
- Signature element: the immersive reader — title/cover/level/audio/English text/vocabulary as one designed reading experience — plus the editorial landing hero and the «امروز» continue-learning moment.
- Intentional restraint: one blue for primary actions/active states, one amber as a limited accent (progress, today ring, small highlights); off-white surfaces, subtle separators, controlled borders, restrained shadows; no rainbow badges, glowing borders, decorative charts, oversized empty dashboard sections, or rows of indistinguishable icon-and-text boxes. Every visual element has a clear purpose.
- Palette (hexes are the reference-derived proposals from the owner request; semantic roles APPROVED 2026-10-09; code owns resolved values in `resources/css/app.css :root`):

| Code token | Value | Semantic role |
|---|---|---|
| `--fe-canvas` | `#F7F5EF` | warm ivory app background |
| `--fe-surface` | `#FFFDF8` | clean off-white cards/bars/inputs |
| `--fe-text` | `#172238` | deep navy body text |
| `--fe-muted-text` | `#5B6372` | secondary text (must measure ≥4.5:1 on canvas) |
| `--fe-border` | `#E7DFD2` | subtle separators/dividers only |
| `--fe-primary` | `#4263EB` | blue primary actions + active states |
| `--fe-primary-hover` | `#364FC7` | pressed/hover fill |
| `--fe-on-primary` | `#FFFFFF` | label on blue |
| `--fe-accent` | `#E9AC52` | warm amber accent: progress, today ring, small highlights (never body text alone) |
| `--fe-success` | `#0F766E` | status text (with text/icon, never color-alone) |
| `--fe-danger` | `#BE123C` | status text (with text/icon, never color-alone) |
| `--fe-elev-1/2` | named tokens only | restrained shadows |

- Mapping: reference canvas→`--fe-canvas`, primary text→`--fe-text`, primary actions→`--fe-primary`, limited accent→`--fe-accent`. Supersedes the Soft Day coral/ink tokens for learner + landing surfaces (soft-day table retained above as history). Focus ring uses `--fe-primary` (4.90:1 on surface — PASS as a ≥3:1 control indicator). Amber `#E9AC52` is fill-with-navy-label only (7.93:1); blue `#4263EB` is fill-with-white-label only (4.98:1); neither is body text on surface. No failing pair waived.
- Measured contrast (computed WCAG relative luminance, same method as S1/S3, 2026-10-09, locked by `tests/Feature/S3DesignTest.php`): text/canvas 14.57:1, muted/canvas 5.55:1, text/surface 15.62:1, muted/surface 5.95:1, on-primary/primary 4.98:1, on-primary/hover 6.77:1, on-accent/accent 7.93:1, text/lavender 13.68:1, success/surface 5.38:1, danger/surface 6.18:1; controls: input-border/surface 5.12:1, primary/surface 4.90:1.
- Typography: Vazirmatn for Persian (existing self-hosted `public/fonts/vazirmatn/`, OFL beside files); Inter for English (self-hosted under OFL with license recorded, fallback system stack when unavailable). English reader body 18–19px / ~1.8 line-height, measure ~680px, well-separated paragraphs; Persian chrome per existing scale.
- Layout: mobile-first single column; desktop uses a restrained sidebar (Today/Discover/Words/Account) with a strong content area, same IA as the mobile four-tab bottom bar («امروز»/«کشف»/«واژه‌ها»/«حساب»); saved lessons reachable from Discover/Account; landing/auth/staff stay distinct; no admin navigation for students.
- Required screens: (A) landing with hero + value proposition + start action + editorial image + method + lesson selection + workflow + benefits + real-data plans + FAQ + public sample; (B) «امروز» greeting + Continue Learning + today plan + goal progress + due vocab + recommendations, next action first; (C) «کشف مطالب» editorial library with imagery/titles/metadata/search/pagination/level-category filters, one row per topic, level→version-or-unavailable; (D) immersive reader (title/cover/level/player/LTR text/vocab controls, loading/audio-error/locked/missing-level/empty-vocab states, mobile safe-area clearance); (E) vocabulary notebook + focused Again/Hard/Good/Easy review; (F) calm account/subscription/settings; (G) desktop sidebar layout, not a stretched mobile page.
- Motion: short purposeful transitions (≤200ms) for navigation/interaction state only; `prefers-reduced-motion` disables all; no autoplay.
- Anti-template check: ivory + navy + blue/amber + editorial hero + reader-first composition is product-specific; rejected generic SaaS card walls and dashboard KPI looks.

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

Final brand/logo, multi-locale UI (out of v1). Palette/typography above stay PROPOSED until measured approval.

## Open owner decisions (need explicit approval; nothing here blocks save-only turn)

- Palette confirmation: approve or revise every hex in the dark/light/sepia tables.
- Dark companion for Soft Day: Night Studio dark is superseded with no replacement yet; approve a Soft-Day dark palette or defer dark past v1 (amendment 1 still requires dark in v1 — needs a decision).
- Display fonts: Outfit/Reenie Beanie are Latin-only and add downloads; Vazirmatn + system EN stay until owner approves self-hosted OFL fonts with licenses recorded.
- App icons: still navy PIL placeholders; approve a Soft Day icon set before release.
- Sepia mode: confirm the reader-only sepia option ships in v1 or stays deferred.
- Source of licensed photos: owner names the supplier(s) for staff-upload covers; uploads require the license record (source, license name, date checked, usage notes) before publish.

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
