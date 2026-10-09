# Fast English — Product Contract

Source baseline: `docs/FAST_ENGLISH_BUILD_SCOPE_FA.md` v1.0 (2026-10-07). It is the authority after the latest explicit owner instruction. Section numbers below refer to that scope (`§`). Do not reconstruct scope from memory; read the scope file for detail.

Authority order: latest explicit owner instruction → accepted slice contract → scope + recorded fresh decisions → repository docs. Conflicting behaviors are never both planned as valid (see conflict log in the active exec plan).

## Product outcome

Mobile-first Persian-RTL web product where learners pick a short English article at a CEFR level, read it, and listen to the matching audio (scope §1). Brand working name: Fast English; final brand, domain ownership, prices, bank destination, legal text, SMTP, signing identity, hosting, staff roster, and content production are launch-only inputs (§25) and block only their own launch boundary. Development continues with clearly labelled fixtures.

Reference domain in scope documents: `fastenglishpodcast.com` — ownership/access to be verified before launch (§25). No product code exists in this checkout; this is a fresh rebuild.

## Scoped requirements by ID (scope §3)

| ID | Requirement | Observable output |
|---|---|---|
| PUB-01 | Public intro | Value proposition, real sample, plans, install, support |
| PUB-02 | Trust pages | About, cooperation, FAQ, terms, privacy with real approved text |
| AUTH-01 | Account | Sign-up (name/email/password), login, logout |
| AUTH-02 | Recovery + session | Reset via real SMTP; valid session after refresh |
| LIB-01 | Topic list | Newest topics, one result per topic, pagination |
| LIB-02 | Find content | Level/category filter + simple title search |
| READ-01 | Lesson page | Level selector, English body, audio of the same lesson version |
| READ-02 | Key words | Small per-lesson glossary, no dictionary service |
| MEDIA-01 | Player | Play/pause, seek, ±10s, speed |
| MEDIA-02 | Continue playback | In-layout navigation without unwanted stop; recoverable network error |
| PROG-01 | Progress | Resume position + completed mark, independent per lesson version |
| SAVE-01 | Saved | Topic bookmark + removal |
| LEVEL-01 | Preferred level | Explicit default-level change, independent of placement |
| PLACE-01 | Placement test | 20 questions, server marking, informal suggestion (optional) |
| PAY-01 | Payment request | Plan/amount/destination snapshot before transfer; private receipt |
| PAY-02 | Payment status | Status view, rejection reason, new request |
| PAY-03 | Staff review | Approve/reject with history and transactional effect |
| SUB-01 | Access | Active subscription, renewal, expiry, controlled revoke |
| ADM-01 | Content ops | Topic, per-level lesson, image, audio, preview, publish |
| ADM-02 | Business ops | Plans, pay destination, support info, payment queue, users |
| MOB-01 | PWA | Install, icons, offline fallback without data leakage |
| MOB-02 | APK | Signed TWA release, asset links, download page |
| OPS-01 | Production ops | HTTPS, logs, essential monitoring, backup + restore |
| QA-01 | Delivery | Real-flow evidence and security/payment/mobile boundaries |

Core journeys (§1, §7): (1) see a real sample before paying; (2) pick topic + level; (3) read text + hear the same version; (4) see a small set of key words; (5) resume from saved position. Revenue path: account → plan → transfer info → off-app card transfer → receipt → staff review → subscription active.

## Level semantics (binding rule)

A level is a **content attribute, not an access permission** (scope §8). All published A1–C2 versions are allowed to an eligible subscriber. Level values are fixed: `A1, A2, B1, B2, C1, C2`; at most one lesson per (topic, level). Missing level shows "این سطح هنوز آماده نیست" with available levels — no silent substitution (§7.2). Browsing a level never silently changes `preferred_level`; changing it requires explicit settings/placement-accept action (§12, §15). Placement is optional and never gates signup, sample, or subscriber use (§12).

## Access and money rules (summary; owners in ARCHITECTURE.md)

- Eligibility (server-computed, §8): `eligible = user exists AND disabled_at IS NULL AND subscription.revoked_at IS NULL AND starts_at <= now_utc < expires_at`. Derived labels (active/expired/pending/rejected) are display-only; no parallel `account_status` on users.
- Public sample allowed only when topic AND lesson are published AND `is_public_sample=true` (§8). Premium body/glossary/audio never sent to ineligible clients, including Livewire payloads (§7.1).
- Money is integer toman; no float, no ambiguous rial/toman conversion (§8). Plan durations are exact days, not calendar months. Timestamps stored UTC, displayed Asia/Tehran.
- Payment snapshot is taken **before** transfer from the active plan/destination (plan name, price, days, card number, holder, bank) and is immutable for that request (§10.2). Client supplies only plan ID.
- One open (`awaiting_receipt`/`pending`) request per user, enforced by partial unique index + server rule (§10.1, §14). Pending grants no new access.
- Approval is one shared transactional action with fixed lock order (User → PaymentRequest → Subscription), idempotent replay, and `subscription_events.source_payment_request_id` unique (§11). Staff cannot approve their own request. Reject needs a public reason; cancelled/rejected history is preserved; resubmit creates a new request.

## Non-goals (scope §4 — binding)

Bank gateway/bank API, auto/OCR receipt verification, SMS OTP, native iOS, store publishing, offline download of paid content, push, AI tutor, speaking, runtime AI/TTS, flashcards/SRS, full lesson translation, word-by-word highlight, public import pipeline, chart dashboard, streak/gamification, algorithmic recommender, coupons, affiliates, multi-teacher, multi-locale UI, dark mode. No standalone stats page (progress lives on lesson + continue surfaces). Cooperation/support start as info pages/links; no internal CRM/ticketing. Items enter only via explicit scope change + new acceptance criteria.

## Measurement and operations

- Activation: a user with no in-person help views the sample, submits a payment request, and uses content after approval (§1).
- Staff independence: staff publishes a lesson and reviews a receipt without code edits (§1).
- Invariants: audio/text/progress never mixed across levels or users; duplicate/concurrent requests never double-apply subscription effect (§1).
- Budgets/targets: list paginated at 12 topics/page, no N+1, `preload=metadata`, no admin assets in learner bundle, real baseline measured in S0 (§20.2). Accessibility target WCAG 2.2 AA on main paths (functional target; full conformance claim needs an audit — see DESIGN.md).
- Telemetry: no product analytics dashboard in v1 (§4). Required ops signals: availability, 5xx, job/backup failure, disk space, pending-payment age (§19.2). No secrets, receipts, paid content, answer keys, or tokens in logs (§19.2).

## Open product decisions (pointers, not approvals)

All of §25 remains owner/Domain input at its launch boundary: stack confirmation, nav/placement confirmation, staff model, brand/logo/domain, package ID + signing owner, plans/prices, bank destination, snapshot-validity policy, staff SLA text, email/SMTP, placement questions + cut scores, pilot content (proposed 5 topics / ≥12 lessons covering all six levels + two-level sample), palette/fonts/assets, refund/privacy/retention policy, VPS/storage/backup destination, support channel, budget/deadline. Proposed defaults are labelled as proposals in DESIGN.md/PLAN.md and never presented as owner approval.
