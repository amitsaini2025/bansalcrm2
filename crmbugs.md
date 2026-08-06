# CRM Bugs Audit

**Date:** 2026-07-26  
**Last deep review:** 2026-07-26 (code-verified; false positives retracted; wording corrected)  
**Scope:** Full CRM audit by area (Clients, Leads, Partners, Agents, Applications, Invoices/Receipts, Email/Messaging, Documents, Followups, Actions, Staff/Roles/Teams/Branches, Reports, Admin Console, Auth/CRM Access, Ongoing Sheet, Notifications, Shared Frontend/Config, SMS Webhooks).  
**Status:** Audit doc; some fixes applied (A-1–A-4, R-1, R-2, R-3 partial, R-4, F-1–F-4, **L-1–L-13** (L-6 retracted), OS-1, OS-2, OS-3, N-1, N-2, N-3, N-4, N-5, E-1–E-5, E-7, E-8, E-10–E-18, **APP-1–APP-12**, **C-1–C-21**, **C-22**, **C-23**, **C-24**, **C-25**, **C-26**, **C-27**, **CA-1–CA-3**).  
**Stack note:** Laravel **13.x** (route parameters bind **by position** after DI, not by PHP parameter name).

Severity: **Critical** (crash / data corruption / money wrong / security) · **High** (major feature broken or serious auth hole) · **Medium** (incorrect behavior) · **Low** (edge case / UX / maintenance risk)

### Deep review changelog (2026-07-26)

| ID | Change |
|----|--------|
| **C-6** | **FIXED** (retracted claim) — no code change; Laravel 13 binds `{type}` → `$slug` by position; Client/Lead toggle works |
| **C-1 / C-7+** | Clarified: login (`auth:admin`) exists; missing piece is **visibility / canEditClient** |
| **C-12** | Corrected: merge `is_deleted=1` **is** excluded by `whereNull`; real mismatch is `is_deleted=0` handling |
| **C-15** | **FIXED** — `deletenote` logs activity when `$data->type == 'client'` (was comparing Note object to string) |
| **C-16** | **FIXED** — `getonlyclientrecipients`: `type=client` filter + Tom Select `text`; keep name/email/status/id/cid |
| **C-17** | **FIXED** — `change_assignee` normalizes array/scalar/empty/comma-separated; same storage + notifications |
| **C-22** | **FIXED** (Option A) — shared `/archived` kept for clients+leads; Type filter, typed detail links, restore labels |
| **C-23** | **FIXED** — manual email verify reloads only on `status: true`; reverts checkbox on fail/error |
| **C-24** | **FIXED** — removed duplicate `POST /save_tag` from `web.php`; kept `clients.php` + `clients.save_tag` |
| **C-25** | **FIXED** — `GET /checkclientexist` throttled `60,1`; still returns plain `1`/`0` |
| **C-26** | **FIXED** — removed dead `address_auto_populate` route + method; Places autocomplete unchanged |
| **C-27** | **FIXED** — clients index uses relative `route(..., false)` instead of `URL::to` |
| **C-1** | **FIXED** — merge requires `canEditClient` on both IDs; transactional reassign then soft-delete |
| **C-5** | **FIXED** — removed dead client rating status AJAX (route, no-op controller, orphan handlers); `admins.rating` already dropped |
| **C-7** | **FIXED** — note create/read/list/delete/pin + app note view gated with `canViewClient`/`canEditClient` |
| **C-8** | **FIXED** — `updateemailverified` / `emailVerify` / `fetchClientContactNo` gated with `canEditClient`/`canViewClient` |
| **C-9** | **FIXED** — action create/reassign/update/personal/retag/schedule/app-stage gated with `canEditClient` |
| **C-10** | **FIXED** — activity list/delete/pin + not-picked SMS gated with `canViewClient`/`canEditClient` |
| **C-2** | **FIXED** — document upload/delete/rename/download/preview/pdf gate on `canViewClient`/`canEditClient` |
| **C-3** | **FIXED** — removed orphan `GET /convertapplication` + `GET /deleteservices` routes (no callers; methods never existed) |
| **C-4** | **FIXED** — legacy phone verify `client_id` rule: `exists:clients,id` → `exists:admins,id` |
| **INV-1** | Corrected: `else` overwrites **type 1 only**, not type 2 |
| **APP-3** | **FIXED** — Split was correct; checklist counts now use bound `?` + `(int)` (`ApplicationsController`) |
| **APP-1** | **FIXED** — Finalize title/heading “finalized” (not overdue copy) |
| **APP-2** | **FIXED** — `updatestage` guards missing app / stage / next stage |
| **APP-4** | **FIXED** — Finalize status filter replaces hard-coded default (default still Discontinued); stage list = COE set |
| **APP-5** | **FIXED** — Null app guards on complete/discontinue/revert/intake/logs/detail/PDF |
| **APP-6** | **FIXED** — discontinue/revert failure returns `status: false` |
| **APP-7** | **FIXED** — `getapplicationslogs` loads client as `$fetchedData` for email attrs |
| **APP-8** | **FIXED** — `getInvoice` redirects when application missing |
| **APP-9** | **FIXED** — `updatebackstage` activity log uses `$workflowstage->name` |
| **APP-10** | **FIXED** — All/Overdue `$totalData` counted after filters (Finalized already) |
| **APP-11** | **FIXED** — `updateintake` failure returns `status: false` |
| **APP-12** | **FIXED** — Partner stage AJAX sends application `id` only (dropped `client_id: partnerId`) |
| **P-3 / P-11** | Removed false “ungrouped OR” on partner Invoice tab (OR **is** grouped); fee-join inflation remains |
| **FE-4** | Narrowed residual: default Tom Select templates already emit HTML; risk is custom plain-text `render` |
| **N-1** | **FIXED** — `fetchnotification` counts `receiver_status = 0` only; `legacy-init.js` always syncs badge (clears at 0) |
| **N-2** | **FIXED** — `fetchmessages` no longer marks `seen` on poll; client marks after toast via `/notifications/mark-toast-seen` |
| **N-3** | **FIXED** — In Person waiting badge scoped: admin + reception see all; others see own assignee queue (list pages unchanged) |
| **N-4** | **FIXED** — Office-visit toast text escaped + safe same-origin URL for View Details (`admin.blade.php`) |
| **N-5** | **FIXED** — Bell click uses `site_url + '/all-notifications'` (subdirectory-safe) |
| **CA-1** | **FIXED** — `quick()` uses same `ensureStaffMayOpenCrossAccessOrSupervisorEligible` as supervisor/requestForm |
| **CA-2** | **FIXED** — active grants in force when `ends_at` null or future (visibility + hasActiveGrant + dup quick) |
| **CA-3** | **FIXED** — access notifications store root-relative `route(..., false)` not `url()` / APP_URL |
| **S-1** | Added exact StaffController arg + double mismatch (values vs keys; string vs module id) |
| **ACT-1** | **FIXED** — Assigned-by-me (+ completed) note column: strip tags + Utf8Helper escape; Read more popover `html=false` |
| **ACT-2** | **FIXED** — Action popover description uses `data-description` (not misnamed `data-noteid`); JS falls back to legacy attr; `data-taskid` still the real id |
| **ACT-3** | **FIXED** — Assigned-by-me prefill/submit scoped to button popover tip (`getTipElement` / `closest('.popover')`); removed document-global `#id` fallbacks |
| **ACT-4** | **FIXED** — Dropped BS3 `inState` hide hack; Action + client-detail use BS5 `Popover.hide()` / jQuery `popover('hide')` |
| **ACT-5** | **FIXED** — `destroyByMe` / `destroyCompleted` (+ `destroyToMe`) null-check `Note::find`; redirect with error flash |
| **ACT-6** | **FIXED** — `markComplete(Request only)`; loads note from body `id` (no unused route-model `$note`) |
| **ACT-7** | **FIXED** — `markIncomplete` uses `response()->json` + HTTP statuses; clients accept string or object |
| **ACT-8** | **FIXED** — Create toast explains Action visibility; Action lists offer “Include scheduled follow-ups” (default off) |
| **ACT-9** | **FIXED** — Removed dead appointment stubs from ActionController + leftover Action-page modal/JS; live Action routes unchanged |
| **NEW** | SMS webhooks unauthenticated; Audit logs login-only (same class as Reports) |
| **A-1** | **FIXED** — Added `use Maatwebsite\Excel\Facades\Excel;` in `AgentController` |
| **A-2** | **FIXED** — Individual import POST handler, route, and form |
| **A-3** | **FIXED** — Null-check after `Agent::find` on edit |
| **R-3** | **FIXED** (safe partial) — `visaexpires` / `agreementexpires` gated to role 1|12; `actionCalendar` left login-only (data already scoped) |
| **R-2** | **FIXED** — `noofpersonofficevisit`: `$totalData` via `$lists->total()`; Sno offset via `$lists->firstItem()` (matches `paginate(5)`) |
| **R-4** | **FIXED** — `AuditLogController` index/export gated to super admin (role == 1); matches Staff Login Log sidebar |
| **R-1** | **FIXED** — `ReportController` gates match sidebar: role 1|12 + modules 62–65; date-wise report role 1; actionCalendar left login-only |
| **F-4** | **FIXED** — Scheduled follow-up activity sync: subject + full title match (no last-40 window; no `note_id` column) |
| **F-2** | **FIXED** — Calendar Show filter defaults to open (confirmed); Completed/Cancelled/No show/All remain available |
| **F-3** | **FIXED** — Calendar disables reassign/reschedule when note not open; server open-only rule unchanged |
| **F-1** | **FIXED** — Consultants from followup_consultants (routes/labels/blocked-times/nav); legacy four-slug maps kept |
| **L-5** | **FIXED** — Lead list + export base query filters `is_archived = 0`; archived type badge on `/archived` |
| **L-6** | **Retracted** — uniqueness must stay on `admins.phone` only; `client_phones` holds related contacts (e.g. sister) shared across people |
| **L-7** | **FIXED** — Lead list phone (+ email) filter also matches `client_phones` / `client_emails` via subquery; client list filters aligned |
| **L-8** | **FIXED** — Lead list status labels via `Helper::formatLeadStatusDisplay` (strings + legacy numeric IDs; display-only) |
| **L-9** | **FIXED** — Lead create multi-assign saves all `assign_to[]` (comma-separated like clients; office from first) |
| **L-11** | **FIXED** — Removed duplicate `leads.detail` from `web.php`; single registration in `clients.php` under `auth:admin` |
| **E-1** | **FIXED** — `sendmail` accepts free-form `@` recipients (college compose); does not call Admin on email id |
| **E-2** | **FIXED** — `sendmail` no longer returns on first success; all To recipients get mail (fail-fast on error unchanged) |
| **E-3** | **FIXED** — subject/message reset from originals each recipient |
| **E-4** | **FIXED** — `isgreviewmailsent` always sets `$response` (already-sent default before send branch) |
| **E-5** | **FIXED** — legacy `.sendmsg` rewired to `#sendSmsModal` / Admin Console SMS; no new sendmsg backend |
| **E-7** | **FIXED** — checklist `$array['files']` reset each recipient |
| **E-8** | **FIXED** — From dropdown auto-selects API `default_from` when no previous value |
| **E-10** | **FIXED** — `uploadmail` validates from/to/subject/message/client_id; keeps Email-tab flags |
| **E-11** | **FIXED** — Elite inbound 422 when resolved To/From are non-Elite; ambiguous empty parse still saves |
| **E-12** | **FIXED** — sendmail resolves entity client_id (form/app/invoice/numeric To); S3 skip log; only mark archived on true |
| **E-13** | **FIXED** — `buildRecipientHtml` escapes name/email/status; badge class uses raw status |
| **E-14** | **FIXED** — removed Google review test SMTP `body` placeholder (blade unused) |
| **E-15** | **FIXED** — email modal clears To/CC Tom Select on hide instead of destroy (no re-init race) |
| **E-16** | **FIXED** (cosmetic) — `where(..., 'and')` was valid Laravel; simplified to `where(col, val)` |
| **E-17** | **FIXED** — `resolveFromEmail()` + PHPDoc; `configureMailerForEmail` BC alias (never configured mailer) |
| **E-18** | **FIXED** — CRM sent S3 HTML snapshot sanitizes body + CSP; Email v2 read iframe sandboxed |

---

## 1. Clients

### Critical

#### C-1. ~~Client merge with no visibility check or transaction~~ — **FIXED**
- **Files:** `app/Http/Controllers/Admin/Client/ClientMergeController.php`; `routes/clients.php`
- **What happens:** Any **logged-in** staff (`auth:admin`) can POST `merge_from` / `merge_into`, re-point activities/notes/applications/documents/invoices, and soft-delete one admin row. No `canEditClient` / `StaffClientVisibility` check. No `DB::transaction` — partial merge on failure.
- **Reproduce:** As restricted staff, POST `/merge_records` with two arbitrary client IDs.
- **Root cause:** Merge moved from legacy without authorization or transactional safety.
- **Review note:** Not “unauthenticated” — login middleware is present.
- **Fix:** `canEditClient` on both source and target; reassign related tables then soft-delete inside `DB::transaction`; invalid/same/archived IDs return `status:false`. Route keys and table set unchanged.

#### C-2. ~~Document endpoints lack client visibility checks (IDOR)~~ — **FIXED**
- **Files:** `app/Http/Controllers/Admin/Client/ClientDocumentController.php` — `uploaddocument`, `deletedocs`, `renamedoc`, `download_document`, `preview_document`, `downloadpdf`
- **What happens:** Upload/delete/download/preview operate on any `client_id` / document without verifying staff may access that client. `ClientAuthorization` may be imported but is unused on these paths.
- **Reproduce:** Staff A (not allocated to client B) POSTs upload or GETs download for client B’s document.
- **Root cause:** No `canViewClient` / `canEditClient` on document endpoints.
- **Fix:** Shared helpers `assertCanAccessDocumentClient` / `resolveAccessibleDocumentClientForJson` use existing `ClientAuthorization` (allocation + grants). Applied on download/preview path, upload, rename, delete, and PDF. S3/filelink checks and public document routes unchanged.

#### C-3. ~~Missing controller methods for registered routes~~ — **FIXED**
- **Files:** `routes/clients.php`; `app/Http/Controllers/Admin/Client/ClientApplicationController.php`
- **What happens:** `GET /convertapplication` and `GET /deleteservices` resolve to methods that **do not exist** → 500 / “method does not exist”.
- **Reproduce:** Hit those URLs from client detail application UI (if wired).
- **Root cause:** Routes registered during refactor; methods never migrated. Controller only has `saveapplication` + `getapplicationlists`.
- **Fix:** Removed orphan routes (no blade/JS callers; methods never existed). Live app routes `saveapplication` / `getapplicationlists` / `savetoapplication` unchanged.

#### C-4. ~~Legacy phone verification validates against non-existent `clients` table~~ — **FIXED**
- **Files:** `app/Http/Controllers/Admin/Client/PhoneVerificationController.php` (`sendCodeLegacy`, `verifyCodeLegacy`)
- **What happens:** `sendCodeLegacy` / `verifyCodeLegacy` use `exists:clients,id`. Records live in `admins` — validation fails; legacy verify modal cannot send/check codes.
- **Reproduce:** Use legacy verify flow from client edit with `client_id` = admins.id.
- **Root cause:** Schema name not updated after leads→admins migration.
- **Fix:** Both methods validate `client_id` with `exists:admins,id`. Routes, UI, phone lookup, and modern OTP endpoints unchanged.

### High

#### C-5. ~~Client status/rating AJAX is a no-op (targets removed column)~~ — **FIXED**
- **Files:** `public/js/pages/admin/client-detail/client-status.js`; `ClientController.php` `updateclientstatus`; migration `2026_02_10_120000_drop_marked_columns_from_admins_table.php` (drops `rating`)
- **What happens:** UI sends `rating`; controller never reads request fields, saves unchanged row, logs fake “updated client status”, returns success. Column no longer exists anyway.
- **Reproduce:** Click status/rating on client detail → success toast, no DB change.
- **Fix:** Removed dead path — route `/change-client-status`, `updateclientstatus`, `client-status.js` (+ entry import), orphan handlers on partner/product/agent/staff, and unused URL config keys. Did not re-add `admins.rating`.

#### C-6. ~~Client/Lead type toggle broken (route param not bound)~~ — **FIXED** (retracted — not a real bug)
- **Original claim:** Route `{type}` vs method param `$slug` → `$slug` stays null → type cleared.
- **Why wrong:** On Laravel 13, after `Request` DI, remaining route params are passed **by position** (`array_values`). For `changetype(Request $request, $id, $slug)`, `{id}` → `$id` and `{type}` → `$slug`. Toggle works; rename is clarity-only.
- **Evidence:** `routes/clients.php` (`/clients/changetype/{id}/{type}`); `ClientController.php` `changetype`; live positional bind behaviour.
- **Fix:** None required — closed as false positive. Optional rename `$slug` → `$type` only if desired for readability.

#### C-7. ~~Notes CRUD without client visibility checks (IDOR)~~ — **FIXED**
- **Files:** `ClientNoteController.php` — `createnote`, `getnotedetail`, `viewnotedetail`, `viewapplicationnote`, `deletenote`, `pinnote`, `getnotes`
- **What happens:** Any **logged-in** staff can create/read/delete/pin notes for any `client_id` / `note_id` (login auth present; no `canViewClient` / `canEditClient`).
- **Fix:** `ClientAuthorization` + `resolveAccessibleNoteClient` (allocation/grants same as client detail). View on list/detail/app-note; edit on create/update/delete/pin. Update also requires edit on existing note’s client. Routes and note payload shape unchanged.

#### C-8. ~~Email verification & contact fetch bypass allocation (IDOR)~~ — **FIXED**
- **Files:** `ClientMessagingController.php` — `updateemailverified`, `emailVerify`, `fetchClientContactNo`
- **What happens:** Update verification, send verify email, or fetch phones for any `client_id` without visibility check.
- **Fix:** `ClientAuthorization` + `resolveAccessibleMessagingClient`. Edit on verify flag/email send; view on contact fetch. Public `emailVerifyToken` / `thankyou` unchanged.

#### C-9. ~~Client actions/tasks without visibility checks (IDOR)~~ — **FIXED**
- **Files:** `ClientActionController.php` — `actionstore`, `reassignactionstore`, `updateaction`, `personalaction`, `retagaction`, `actionstore_application`, `scheduleFollowupStore`
- **What happens:** Create/reassign tasks and activity logs for any decoded `client_id`.
- **Fix:** `ClientAuthorization` + resolve encoded/raw client id then `canEditClient`. Reassign/update also check existing note’s client. Personal actions with no client unchanged. Slot-list endpoint unchanged (no client id).

#### C-10. ~~Activity log & “not picked call” without authorization (IDOR + SMS)~~ — **FIXED**
- **Files:** `ClientActivityController.php` — `notpickedcall`, `deleteactivitylog`, `pinactivitylog`, `activities`
- **What happens:** Send SMS / update `not_picked_call` / delete/pin activity for any admin ID.
- **Fix:** `ClientAuthorization` + `resolveAccessibleActivityClient`. Edit on not-picked/SMS and delete/pin (via activity `client_id`); view on activity list. SMS/query behaviour unchanged for allowed staff.

#### C-11. ~~Application create/list without authorization; fragile partner_branch parse~~ — **FIXED**
- **Files:** `ClientApplicationController.php` — `saveapplication`, `getapplicationlists`
- **What happens:** Create applications for any `client_id`; `explode('_', partner_branch)` can undefined-index; missing workflow stage → null deref on `$workflowstage->name`.
- **Fix:** `ClientAuthorization` + `resolveAccessibleApplicationClient` (edit on create, view on list — same visibility as notes/activity). `parsePartnerBranch` validates `branchId_partnerId` and branch↔partner; missing workflow stages return a JSON error instead of crashing. Success payload / UI format unchanged for allowed staff.

#### C-12. ~~Soft-delete filter semantics inconsistent with LeadController~~ — **FIXED**
- **Files:** `app/Traits/ClientQueries.php` (`whereNull('is_deleted')` only); `ClientMergeController` sets `is_deleted => 1`; LeadController uses `whereNull OR = 0`
- **Corrected behaviour:**
  - Merge sets `is_deleted = 1` → **excluded** by `whereNull` (merged rows do **not** still appear solely because of `=1`).
  - Real bug: ClientQueries also excludes rows with `is_deleted = 0` (treated as “deleted” incorrectly), while LeadController keeps them. Permanent-delete / timestamp semantics may still diverge.
- **Root cause:** Inconsistent `is_deleted` null/`0`/`1`/timestamp conventions across modules.
- **Fix:** `getBaseClientQuery` / `getArchivedClientQuery` now use `whereNull('is_deleted')->orWhere('is_deleted', 0)` (same as LeadController / SearchService). Still excludes merge (`1`) and permanent-delete timestamps.

#### C-13. ~~Clients index includes leads (no default type filter)~~ — **FIXED**
- **Files:** `ClientQueries.php` `getBaseClientQuery()`; `clients/index.blade.php` shows type badge
- **What happens:** Active leads appear on Clients list unless user filters Type manually.
- **Fix:** `applyClientFilters` defaults to `type = client` when Type is empty; explicit Type=Lead/Client still works. Index counts after filters (matches list/export). Filter UI preselects Client.

#### C-14. ~~Phone OTP endpoints lack allocation checks~~ — **FIXED**
- **Files:** `PhoneVerificationController.php` — `sendOTP`, `verifyOTP`, `getStatus`
- **What happens:** Any staff with a `client_phone_id` can OTP-verify phones for clients they cannot otherwise access.
- **Fix:** `resolveAccessibleClientPhone` / `denyUnlessCanAccessClientId` use `StaffClientVisibility::canAccessAdminRecord` (same as lead OTP / client detail). Wired on `sendOTP`, `verifyOTP`, `resendOTP`, `getStatus`, and legacy send/check-code. OTP service + success JSON unchanged for allowed staff.

### Medium

#### ~~C-15. Note delete never writes activity log (type comparison bug)~~ — **FIXED**
- **File:** `ClientNoteController.php` `deletenote`
- **What happens:** `if($data == 'client')` compared Note **object** to string — always false; delete succeeded but no activity log.
- **Fix:** Select includes `type`; condition is `$data && $data->type == 'client'` (same rule as `createnote`). Delete + success JSON unchanged for non-client notes.

#### ~~C-16. `getonlyclientrecipients` omits Tom Select `text` field & doesn’t filter clients-only~~ — **FIXED**
- **File:** `ClientController.php` `getonlyclientrecipients`
- **What happens:** Returned `{name, email, …}` without Tom Select `text`; query had no `type=client` so leads appeared.
- **Fix:** Filter `type = client`; add `text` while keeping `name`/`email`/`status`/`id`/`cid`; empty `q` returns `{"items":[]}`. Staff visibility + search fields unchanged.

#### ~~C-17. `change_assignee` silently no-ops when assignee not sent as array~~ — **FIXED**
- **File:** `ClientController.php` `change_assignee`
- **What happens:** Only applied assignee when value was an array; scalar/`''` left `assignee` unchanged but returned success.
- **Fix:** Normalize to id list when `assignee`/`assinee` present (array, scalar, empty clear, comma-separated). Storage still one id / comma list / `""`; notify only when ≥1 assignee; missing keys leave field unchanged.

#### C-18. ~~Client import duplicate phone check inefficient / incomplete~~ — **FIXED**
- **File:** `ClientImportService.php` (~72–135) — loads all phones per country; can miss `admins.phone` vs `client_phones` mismatch.
- **Fix:** `findExistingClientByPhone` uses narrow LIKE/suffix candidate queries (no full-table loads). One `isSameLogicalPhone` rule: digit match + country when stored has a code (else phone-only like legacy `admins.phone`). Still covers `client_phones` and primary `admins.phone`. Skip-duplicate messages/flow unchanged.
#### C-19. ~~Client import may assign wrong `user_id` on phone rows~~ — **FIXED**
- **File:** `ClientImportService.php` (~277) — `'user_id' => Auth::id()` may not match staff FK semantics.
- **Fix:** `resolveImportingStaff()` uses `Auth::guard('admin')->user()` as `Staff`; `client_phones.user_id` = that staff id (null only if no admin staff session). Office fallback + activity `created_by` default use the same staff identity. Payload phones/contacts unchanged.

#### C-20. ~~`leaddetail` legacy migration creates duplicate admin rows (race)~~ — **FIXED**
- **File:** `ClientController.php` (~837–877) — lazy create on GET without lock/upsert.
- **Fix:** `migrateLegacyLeadToAdminIfNeeded()` runs in a DB transaction with `leads` `lockForUpdate`, rechecks `admins.lead_id`, then creates only if still missing. Same field mapping / detail view / auth as before.

#### C-21. ~~`clientdetail` does not enforce `type=client`~~ — **FIXED**
- **File:** `ClientController.php` (~734–776) — lead IDs open on `/clients/detail/{id}`.
- **Fix:** If record `type` is `lead`, redirect to `leads.detail` / `leads.detail.application` (same encoded id, tab, applicationId, query string). Non-lead records (client / legacy) unchanged; auth still enforced on the lead detail path.

#### ~~C-22. Archived list may include archived leads~~ — **FIXED** (Option A — intentional shared list)
- **File:** `archived/index.blade.php` (query still shared via `getArchivedClientQuery`)
- **What happens:** `/archived` listed archived clients **and** leads with no Type filter and no typed detail links.
- **Fix (Option A, keeps L-5):** List stays shared for clients+leads. Added Type filter (All/Client/Lead); name links to `/clients/detail` vs `/leads/detail` by type; type badge; restore label “Move to clients” / “Restore to leads”. Did **not** force `type=client` on the query.

#### ~~C-23. Manual email verify reloads page on any AJAX success~~ — **FIXED**
- **File:** `session-handlers.js` (manual email/phone verified checkbox)
- **What happens:** AJAX `success` always `location.reload()` on HTTP 200, ignoring JSON `status: false` and with no `error` handler.
- **Fix:** Parse response; reload only when `status` is true; on false/parse/HTTP error revert checkbox + toast/alert. Endpoint/payload unchanged; Vite assets rebuilt.

#### ~~C-24. Duplicate `save_tag` route registration~~ — **FIXED**
- **Files:** `routes/web.php` (removed); `routes/clients.php` (kept)
- **What happens:** `POST /save_tag` registered twice — unnamed in `web.php` (first match) and named `clients.save_tag` under `auth:admin` in `clients.php`.
- **Fix:** Removed duplicate from `web.php`. Single route remains: `POST /save_tag` → `ClientController::save_tag`, name `clients.save_tag`, `auth:admin`. Form path `/save_tag` unchanged.

### Low

#### ~~C-25. `checkclientexist` no rate limiting / enumeration hardening~~ — **FIXED**
- **Files:** `routes/web.php`; `ClientController.php` `checkclientexist`
- **What happens:** Authenticated staff could hammer `GET /checkclientexist` (email/phone/client_id → `1`/`0`) with no throttle.
- **Fix:** Route middleware `throttle:60,1` (same family as client search). Response still plain `1`/`0` for create/edit JS; empty `vl` returns `0`. Auth still on controller.

#### ~~C-26. `address_auto_populate` permanently disabled but still routed~~ — **FIXED**
- **Files:** `routes/clients.php` (removed); `ClientController.php` (method removed)
- **What happens:** Stub always returned “Geocoding feature disabled”; no live callers (UI uses Places `/address/search` + details).
- **Fix:** Removed route + dead method. Address autocomplete `/address/search` and `/address/details` unchanged.

#### ~~C-27. Client index hardcoded `URL::to` (APP_URL host mismatch risk)~~ — **FIXED**
- **File:** `resources/views/Admin/clients/index.blade.php`
- **What happens:** Links/AJAX used `URL::to(...)` absolute URLs from `APP_URL`, which can miss when browser host differs.
- **Fix:** Named routes with `$absolute = false` (root-relative path) for tabs, filter, detail/export/agent, sendmail, merge, templates, recipients, CSV export. Paths unchanged; host follows the open tab.

---

## 2. Leads

**Section status:** **L-1–L-13 closed** — L-1–L-5, L-7–L-13 **FIXED**; L-6 **RETRACTED** (by design, not a defect).

### Critical

#### ~~L-1. “Convert To Client” runs debug migration script, not conversion~~ — **FIXED**
- **Files:** `leads/index.blade.php`; `routes/web.php`; `LeadController.php` `convertoClient`
- **What happens:** Menu action looped migrated leads and **echoed HTML lead IDs** — no conversion, no redirect, no `converted` flag.
- **Reproduce:** Leads index → Options → Convert To Client.
- **Root cause:** Leftover one-off migration endpoint exposed as user-facing action.
- **Fix:** `convertoClient` now converts **one** lead from the URL id (raw or encoded; resolve by `lead_id` then `admins.id`). Gate with `StaffClientVisibility::canAccessAdminRecord`. Sets `type=client`, `converted=1`, `converted_date` (if column). Redirect to client detail (same type switch idea as `clients.changetype`). Idempotent if already client. Menu/route unchanged. Migration dump/timestamp sync removed.

### High

#### ~~L-2. Lead assign without visibility check (IDOR)~~ — **FIXED**
- **File:** `LeadController.php` `assign`
- **What happens:** Any authenticated admin who posted a lead id could reassign it without allocation check (list/detail were scoped; assign was not).
- **Fix:** After resolving the lead, require `StaffClientVisibility::canAccessAdminRecord` (same rules as list/detail: strict off = allow all; exempt roles; active grants; assignee/creator allocation). Denied → unauthorized flash; assign payload logic unchanged.

#### ~~L-3. Lead phone OTP without allocation checks~~ — **FIXED**
- **Files:** `PhoneVerificationController.php` (lead OTP methods)
- **What happens:** Any authenticated admin with a numeric `lead_id` could send/verify OTP or read status without allocation.
- **Fix:** Shared `resolveAccessibleLeadAdmin` on send/verify/resend/status — resolve lead then `StaffClientVisibility::canAccessAdminRecord` (same as list/detail). Denied → 403 JSON; missing → 404. OTP service / AU / cooldowns unchanged. Client OTP (C-14) unchanged.

#### ~~L-4. Lead phone “Verify” button hidden for new admin-only leads (`lead_id` null)~~ — **FIXED**
- **File:** `clients/detail.blade.php` (phone Verify link)
- **What happens:** Verify only when `$conVal->lead_id` non-empty; new leads from `LeadController::store` have `lead_id = null` so button never rendered.
- **Fix:** For primary lead contact, resolve `Admin` by `admin_id` (`admins.id`) first, then fall back to migrated `lead_id`. Still requires `needsVerification()`; `data-lead-id` remains `admins.id`. Client phone UI unchanged.

#### ~~L-5. Lead list does not filter `is_archived`~~ — **FIXED**
- **File:** `LeadController.php` (`index` / `exportList` base query)
- **What happens:** archived leads still appeared on `/leads`.
- **Fix:** Base query includes `where('is_archived', 0)` (list + CSV export). Archived leads remain on `/archived` (type badge shows lead/client).

#### ~~L-6. Lead uniqueness AJAX / store ignore `client_phones`~~ — **RETRACTED**
- **Original claim:** `is_contactno_unique` / store only check `admins.phone`; should also unique-check `client_phones`.
- **Why wrong (by design):** `client_phones` stores multi/related contact numbers (e.g. sister of Lead 1). That same person can later be created as Lead 2 with the same number. Enforcing uniqueness on `client_phones` would block valid lead creates.
- **Correct behavior:** Keep uniqueness on primary `admins.phone` + existing AJAX only; do **not** apply uniqueness across `client_phones`.

#### ~~L-7. Lead list phone filter only searches `admins.phone`~~ — **FIXED**
- **File:** `LeadController.php` `buildLeadListQuery` (phone + email; list + export)
- **What happens:** `/leads` phone (and email) filters only checked primary `admins` columns; related rows in `client_phones` / `client_emails` were missed.
- **Fix:** Match primary `admins.phone` / `admins.email` **or** related `client_phones.client_phone` / `client_emails.client_email` via `orWhereIn` subquery (no join so counts/paginate/export stay correct). Client list `/clients` filters updated the same way in `ClientQueries::applyClientFilters`.

### Medium

#### ~~L-8. Lead status display wrong for numeric statuses~~ — **FIXED**
- **Files:** `leads/index.blade.php`; `Helper::formatLeadStatusDisplay`
- **What happens:** list treated non-string statuses poorly — `0`/`"0"` → “Not Contacted”, other ints → `—` or raw `1`; create uses string labels.
- **Fix:** Display-only mapping — modern strings unchanged; legacy IDs `0`→Not Contacted, `1`→Create Proposal, `11`–`14`→Undecided/Lost/Won/Ready to Pay (from pre-refactor counters); unmapped numeric IDs show as digits (not blank). Stored `admins.status` not rewritten.

#### ~~L-9. Lead create multi-assign UI only saves first assignee~~ — **FIXED**
- **File:** `LeadController.php` `createAdminFromRequestData`
- **What happens:** create form multi-select `assign_to[]` was accepted, but only `assign_to[0]` was written to `admins.assignee`.
- **Fix:** Same as clients — multiple ids → comma-separated string; single id → one value; `office_id` still from the first selected staff only.

#### ~~L-10. `leaddetail` auto-migration side effect on GET~~ — **FIXED**
- **File:** `ClientController.php` `leaddetail`
- **What happens:** GET `/leads/detail/{id}` could INSERT a new `admins` row when the id only existed in the legacy `leads` table (`migrateLegacyLeadToAdminIfNeeded`).
- **Fix:** Lead detail is read-only. Resolve existing `admins` by `id` / `lead_id` only; no create on GET. Residual unmigrated rows require offline `MigrateLeadsToAdminsCommand`. Removed dead GET-migrate helper. Normal admin-only / already-migrated leads unchanged.

#### ~~L-11. Duplicate `leads.detail` route registration (`web.php` + `clients.php`)~~ — **FIXED**
- **What happens:** same `GET /leads/detail/{id}/{tab?}` + name `leads.detail` registered twice (`web.php` outside `auth:admin` group; `clients.php` inside).
- **Fix:** Removed duplicate from `web.php`. Kept `leads.detail` (+ `leads.detail.application`) only in `clients.php` under `auth:admin`.
#### ~~L-12. Lead import inherits client import duplicate-check gaps~~ — **FIXED**
- **Files:** `LeadController.php` `import`; `ClientImportService.php`
- **What happens:** Lead import forces `type=lead` then calls `ClientImportService::importClient`, so client-side duplicate-check gaps (C-18) also applied to leads; skip/success messages always said “Client”.
- **Fix:** Keep one shared importer (no lead fork). Phone/email skip-duplicates use C-18 rules (`admins.email` + `findExistingClientByPhone` over `admins.phone` / `client_phones`). Type-aware messages (“Lead” / “Client”). Lead imports set `converted=0` so they appear on leads index. Client import path unchanged aside from message label when type=client.

#### ~~L-13. `convertoClient` route outside auth group (relies on controller middleware only)~~ — **FIXED**
- **File:** `routes/web.php` (leads block)
- **What happens:** `/leads/convert/{id?}` (and sibling lead routes in `web.php`) sat outside the `auth:admin` group; auth only on `LeadController` constructor.
- **Fix:** Wrapped all web.php lead routes (index, export, create, store, assign, import, convert) in `middleware(['auth:admin'])` with unchanged paths/names. Named convert `leads.convert`. Controller middleware kept. Guests get login redirect at route level.

---

## 3. Partners

### Critical

#### ~~P-1. Gross Claim (type 2) due amount wrong in Accounts tab~~ — **FIXED**
- **Files:** `PartnersController.php` `formatPartnerAccountsTabRow`
- **What happens:** Type-2 due computed as `netamount - amount_rec`, but payment storage uses `coom_amt - amount_rec`. Make Payment modal gets wrong `data-dueamount`.
- **Fix:** Uses `Invoice::computeOutstandingDue(...)` (same as payment store / InvoiceController).

### High

#### ~~P-2. Net Claim (type 1) due ignores prior payments~~ — **FIXED**
- **File:** `PartnersController.php` `formatPartnerAccountsTabRow`
- **What happens:** `$totaldue = $total_fee - $coom_amt` without subtracting `$amount_rec`.
- **Fix:** Same `Invoice::computeOutstandingDue` path as P-1 (includes `$amount_rec`).

#### ~~P-3. Partner Invoice tab summary totals inflated~~ — **FIXED**
- **File:** `partners/detail.blade.php` (~934–957)
- **What happens:** `leftJoin` all `application_fee_options` rows without “latest fee only” (`MAX(id)`) constraint → multiple fee snapshots per application multiply Total Projected Fee / Commission totals.
- **Review note:** Stage ORs **are** wrapped in `where(function …)` — do **not** blame ungrouped OR here. Other partner student queries correctly use latest-fee logic.
- **Fix:** Invoice summary constrains `application_fee_options` to latest row only (`id = MAX(id)` per `app_id`), same rule as partner student tab totals. Stage filter and invoice/payment sums unchanged.

#### ~~P-4. Student tab export ignores table search~~ — **FIXED**
- **File:** `public/js/pages/admin/partner-detail/datatable-handlers.js`
- **What happens:** Export path could miss server-side search filter.
- **Fix:** `resolveStudentSearchValue()` (`api.search()` + ajax params fallback) wired into export, totals, and count requests; server already applied `search` on export.

#### ~~P-5. Student tab pagination shows fake counts when count query fails~~ — **FIXED**
- **Files:** `PartnersController.php` `getStudentTabCount` (+ estimate); `datatable-handlers.js`
- **What happens:** JS treated `estimated: true` as exact success totals.
- **Fix:** JS ignores count responses with `estimated: true` (does not overwrite pagination N with invented totals). Estimate endpoint still returned for graceful degrade.

### Medium

#### ~~P-6. `URL::to` / hardcoded S3 URL host mismatch risk (print/download)~~ — **FIXED**
- **Files:** `Helper.php` `s3ObjectUrl`; `partners/detail.blade.php`; `PartnersController.php` (awsUrl responses + `s3Url()`)
- **What happens:** Hand-built `https://{bucket}.s3.{region}.amazonaws.com/` could disagree with `AWS_URL` / s3 disk config for partner docs; print used `URL::to` (app host, not S3).
- **Fix:** Shared `Helper::s3ObjectUrl()` (disk/`AWS_URL`, pass-through full URLs); partner preview/download and invoice doc URLs use it. Print routes left as `URL::to` (app PDF, not S3).

#### ~~P-7. Partner student invoice ID generation race (`MAX(invoice_id)+1` without lock)~~ — **FIXED**
- **Fix:** New creates only — `withNextPartnerStudentInvoiceId` lock (PG advisory / MySQL GET_LOCK) + max+1, then insert in same transaction for types 1/2/3. Existing rows not rewritten.
#### ~~P-8. Accounts tab export search uses `id ILIKE` (fragile)~~ — **FIXED**
- **Fix:** Shared `applyPartnerAccountsTabSearch` — exact numeric id + `CAST(id AS TEXT) ILIKE` + workflow name; used by Accounts table + export.
#### ~~P-9. Cached staff dropdown stale for 1 hour (`partner_detail_staff_assignees_v2`)~~ — **FIXED**
- **Fix:** Cache key `partner_detail_staff_assignees_v3`, TTL 300s (5 min) on Partner Detail.
#### ~~P-10. Column visibility toggle resets after DataTables redraw~~ — **FIXED**
- **Fix:** Student tab uses DataTables `column().visible()` from checkbox state (1-based map preserved); re-applies on `draw`. Removed fragile jQuery `nth-child` handlers from `legacy-init.js` (partner student only).

#### ~~P-11. Partner invoice tab stage filter uses ungrouped OR~~ — **RETRACTED**
- Stage filter in Invoice tab summary **is** grouped (see P-3). Not a bug — no code change. Closed as false/duplicate claim; real fee inflation was **P-3** (fixed).

---

## 4. Agents

### Critical

#### ~~A-1. Business agent import crashes~~ — **FIXED**
- **File:** `AgentController.php` (~301–307) — `Excel::import(...)` with **no** `use Maatwebsite\Excel\Facades\Excel` and no `Excel` alias in `config/app.php` → fatal “Class Excel not found”. Package is in `composer.json` but FQCN/`use` still required.
- **Reproduce:** Agents → Import Business → upload CSV → 500.
- **Fix:** Added `use Maatwebsite\Excel\Facades\Excel;` in `AgentController`.

### High

#### ~~A-2. Individual import is non-functional~~ — **FIXED**
- **File:** `AgentController.php` `individualimport` (~313–315) — returns view only; no POST handler.
- **Fix:** Mirrored business import — POST handler with `Excel::import`, POST route, and form open/close with hidden `struture=Individual`. Also added missing POST route for business import (form already posted there).

#### ~~A-3. Agent edit null dereference~~ — **FIXED**
- **File:** `AgentController.php` (~164–196) — `Agent::find` not null-checked before property writes.
- **Fix:** After `Agent::find`, return redirect back with “Agent not found” if null; successful edit path unchanged.

### Medium

#### ~~A-4. `savepartner` disabled but UI may still expose it (“feature disabled” hard failure)~~ — **FIXED**
- **Fix:** Removed Representing Partners tab/pane from agent detail, Connect Partner modal, `POST /agents/savepartner` route, and `savepartner()` method. (`5388792b`)

---

## 5. Applications

### Critical

#### ~~APP-1. Finalize page is a copy of Overdue page~~ — **FIXED**
- **File:** `resources/views/Admin/applications/finalize.blade.php`
- **Was:** `@section('title', 'Applications overdue')` and `<h4>All Overdue Applications</h4>`.
- **Fix:** Title `Applications finalized`; heading `All Finalized Applications`.

#### ~~APP-2. `updatestage` crashes at last workflow stage~~ — **FIXED**
- **File:** `ApplicationsController.php` `updatestage`
- **Was:** no guard when `$workflowstage` / `$nextid` null → `$nextid->name` throws.
- **Fix:** Early JSON failure for missing application, current stage not found, or already at last stage; happy path unchanged.

#### ~~APP-3. Application checklist upload SQL injection / raw SQL antipattern~~ — **FIXED**
- **File:** `ApplicationsController.php`
  - **Was (Critical):** `$request->application_id` interpolated raw into `DB::select("… application_id = '$application_id'")`.
  - **Was (Medium):** Same pattern with `$appdoc->application_id`.
- **Fix:** `(int)` cast + bound parameter `WHERE application_id = ?` on upload and delete count paths. Response shape unchanged.

### High

#### ~~APP-4. Finalize list logic contradicts filters / business intent~~ — **FIXED**
- **Files:** `ApplicationsController.php` `finalizeApplicationList`; `finalize.blade.php`
- **Was:** Base query always `status = 2` AND status filter stacked (empty set for other statuses).
- **Fix:** Default remains Discontinued (`status = 2`) when no status filter; selected status **replaces** default (no AND stack). Stage dropdown fixed to COE finalize stages. Status selected attrs use strict string checks. `$totalData` after filters.

#### ~~APP-5. Multiple handlers null-deref on missing application~~ — **FIXED**
- **Methods:** `completestage`, `discontinue_application`, `revert_application`, `updateintake`, `getapplicationslogs`, `exportapplicationpdf`, `getapplicationdetail`
- **Fix:** Not-found guards — JSON `status: false` for AJAX; HTML message for logs/detail panels; redirect flash for PDF export.

#### ~~APP-6. `discontinue_application` / `revert_application` report success on failure~~ — **FIXED**
- **Was:** `$saved === false` still `'status' => true` with “Please try again”.
- **Fix:** Failure branches return `'status' => false` (same as `refund_application`).

#### ~~APP-7. Application activity log email button uses undefined `$fetchedData`~~ — **FIXED**
- **File:** `getapplicationslogs`
- **Was:** `data-email` / `data-name` used `$fetchedData` never loaded.
- **Fix:** `$fetchedData = Admin::find($fetchData->client_id)` after app load (same as `getapplicationdetail`).

#### ~~APP-8. `getInvoice` null application dereference~~ — **FIXED**
- **File:** `InvoiceController.php` `getInvoice`
- **Fix:** After loading application (type ≠ 3), redirect back with “Application not found for this invoice.” if null (mirrors client guard).

### Medium

#### ~~APP-9. `updatebackstage` logs wrong stage field~~ — **FIXED**
- **Was:** `$obj->stage = $workflowstage->stage` (column does not exist on `WorkflowStage`).
- **Fix:** `$obj->stage = $workflowstage->name` (matches `updatestage`).

#### ~~APP-10. List pages show wrong total counts (count before filters)~~ — **FIXED**
- **Was:** `$totalData` counted before partner/assignee/stage/status filters on All / Overdue.
- **Fix:** Count after filters on `index` and `overdueApplicationList` (Finalized already counted after filters).

#### ~~APP-11. `updateintake` reports success on failed save~~ — **FIXED**
- **Was:** `$saved === false` still `'status' => true`.
- **Fix:** Failure branch `'status' => false`.

#### ~~APP-12. Partner detail stage AJAX sends `client_id: partnerId`~~ — **FIXED**
- **File:** `public/js/pages/admin/partner-detail/application-handlers.js`
- **Was:** Next/back stage and log reload sent partner id as `client_id` / `clientid` (ignored by server; wrong type).
- **Fix:** Request data is application `id` only.

---

## 6. Invoices / Receipts

### Critical

#### ~~INV-1. Client invoice list (`getinvoices`) due calculation broken~~ — **FIXED**
- **File:** `InvoiceController.php` (~507–513) [historical bug]
```php
if ($invoicelist->type == 1) {
    $totaldue = $total_fee - $coom_amt;        // missing -$amount_rec
}
if ($invoicelist->type == 2) {
    $totaldue = $netamount - $amount_rec;      // wrong base (should be coom_amt)
} else {
    $totaldue = $netamount - $amount_rec;      // else bound to 2nd if only → overwrites TYPE 1
}
```
- **Corrected analysis:**
  - Type **1**: first block sets due, then `else` of second `if` **overwrites** it → `netamount - amount_rec` (wrong).
  - Type **2**: second `if` true → else skipped; still wrong base vs store (`coom_amt - amount_rec`).
  - Other types: else applies.
- **Reproduce:** Client detail → invoices → Make Payment due wrong for Net/Gross claims.
- **Contrast:** Correct formulas in `invoicepaymentstore` (~399–405).
- **Fix:** Shared `Invoice::computeOutstandingDue()` used by `getinvoices`, client Accounts tab, unpaid/paid lists — type 2 = `comm - paid`, type 3 = `net - paid`, else (type 1) = `(total_fee - comm) - paid`.

### High

#### ~~INV-2. `getinvoices` loads workflow by wrong ID~~ — **FIXED**
- **File:** `InvoiceController.php` (~488–490) — after loading Application, still does `Workflow::where('id', $invoicelist->application_id)` (application ID as workflow ID). Should use `$applicationdata->workflow`.
- **Fix:** Controller `getinvoices` now uses `@$applicationdata->workflow` (and `@$applicationdata->partner_id`). Type 3 unchanged.
- **Note:** Initial Accounts tab render in `clients/detail.blade.php` (~1763) still has the old lookup until that copy is updated; AJAX `/get-invoices` path is correct.

#### ~~INV-3. Commission report duplicates rows + ungrouped stage OR footgun~~ — **FIXED**
- **File:** `ClientReceiptController.php` `getcommissionreport` (~737–745)
- **What happens:**
  - `leftJoin application_fee_options` without latest-fee filter → duplicate students / inflated columns.
  - Stage filter is `where(…).orWhere(…).orWhere(…)` **without** a grouping closure. Currently those are the only filters (so results happen to match stages), but any future `where partner_id = …` etc. added alongside will leak wrong rows.
- **Contrast:** Partner Invoice tab (P-3) **does** group its OR correctly.
- **Fix:** Latest fee-row join via `MAX(id)` subquery (mysql/pgsql); stage OR wrapped in `where(function …)`. Also fixed page 500: blade now uses `route('clients.getcommissionreport')` instead of missing `admin.commissionreportlist`.

### Medium

#### ~~INV-4. `validate_receipt` returns empty/misleading JSON when no IDs~~ — **FIXED**
- **Fix:** Empty/missing `clickedReceiptIds` now returns `{status:false, message, record_data:[], clickedIds:[]}`.

#### ~~INV-5. Multi-line receipt save only updates `trans_no` on last insert~~ — **FIXED**
- **Fix:** Each insert now sets `trans_no`/`receipt_id` and returns per-line `id`/`trans_no` in `requestData`; UI uses per-row ids for print/edit/refund.
#### ~~INV-6. `saveaccountreport` null deref on validate flag race~~ — **FIXED**
- **Fix:** `validate_receipt` response uses `?? 0` when receipt row missing on add/edit.

#### ~~INV-7. Paid invoice export fee formula may not match list UI~~ — **FIXED**
- **Fix:** Shared `Invoice::sumLineFeeTotals()` used by paid list UI and export (`feepaid = total_fee - (comm + tax + bonus)`).
#### ~~INV-8. Client receipt `printUrl` via `URL::to` (APP_URL mismatch)~~ — **FIXED**
- **Fix:** Add/edit/refund `printUrl` now relative `/clients/printpreview/{id}` (matches current host).

### Low

#### ~~INV-9. `invoicepaymentstore` can insert empty payment rows~~ — **FIXED**
- **Fix:** Only positive numeric payment lines are summed/saved; empty lines skipped; over-due and no-valid-line paths return without insert.

---

## 7. Email / Messaging (CRM compose, SES, Elite, Outlook)

### Critical

#### ~~E-1. College compose crashes on send~~ — **FIXED**
- **Files:** `AdminController.php` `sendmail`; `email-handlers.js` (college To id = email)
- **Was:** Loop used `Admin::where('id', $l)` for college email → null → fatal on `$client->first_name`.
- **Fix:** If recipient contains `@`, send to that address with a stub recipient object (same idea as `resolveRecipientsToEmails`). ID → model path unchanged for client/partner/agent. Invalid null recipient returns error JSON instead of crash. `client_id` never set from email string (uses form client_id / application / numeric To). (2026-08-05)

#### ~~E-2. Multi-recipient compose sends only to first recipient~~ — **FIXED**
- **File:** `AdminController.php` `sendmail` (recipient foreach)
- **Was:** After first successful send, **returns immediately** inside the foreach. Remaining recipients never get mail.
- **Fix:** Success response deferred until after the loop; all `email_to` recipients are attempted. Fail-fast on first send exception unchanged. Invoice temp unlink moved after loop. (2026-08-05)

### High

#### ~~E-3. Multi-recipient template placeholders wrong after first recipient~~ — **FIXED**
- **File:** `AdminController.php` `sendmail`
- **Was:** `$subject`/`$message` mutated in-place without resetting from originals.
- **Fix:** Keep `$subjectOriginal` / `$messageOriginal`; reset each loop iteration before placeholders. (same E-2 pass, 2026-08-05)

#### ~~E-4. Google review email: undefined `$response` when already sent~~ — **FIXED**
- **File:** `ClientMessagingController.php` `isgreviewmailsent`
- **Was:** `echo json_encode($response)` when already sent → notice / invalid JSON.
- **Fix:** Default `$response` (`status: true`, already-sent message) before the send `if`; first-time send branches still overwrite as before. (2026-08-05)

#### ~~E-5. SMS `sendmsg` backend missing~~ — **FIXED**
- **Was:** Docblock listed `sendmsg`; JS opened missing `#sendmsgmodal` / form `sendmsg`; no method or route.
- **Fix:** No new backend. Legacy `.sendmsg` opens live `#sendSmsModal` via `window.openClientSendSmsModal` (phones/templates + Admin Console `features.sms.send`). Dead form-validation POST branch routes to same modal. Toolbar `.send-sms-btn` unchanged. (2026-08-05)

#### E-6. Elite inbound webhook open when secret unset
- **File:** `EliteEmailController.php` `assertInboundSecret` (~385–398); `config/crm.php` default `env('EDUCATION_ELITE_INBOUND_SECRET', '')`
- **What happens:** Empty secret → early `return` (no auth); anyone can inject `elite_emails`.

### Medium

#### ~~E-7. Checklist attachments duplicated on multi-recipient send~~ — **FIXED**
- **Fix:** `$array['files'] = []` each recipient iteration in `sendmail` (same E-2 pass, 2026-08-05).
#### ~~E-8. From dropdown ignores API `default_from`~~ — **FIXED**
- **Files:** `email-from-ses-script.blade.php`; `OutlookController::senders`
- **Was:** Frontend only restored `prev`; never used `default_from`.
- **Fix:** After loading senders, keep `prev` if still valid; otherwise select `data.default_from` when present in the list. (2026-08-05)
#### E-9. `SesSenderService` no longer merges SES API identities (doc drift)
#### ~~E-10. `uploadmail` — no validation, weak persistence~~ — **FIXED**
- **File:** `ClientMessagingController::uploadmail`
- **Was:** Raw `$request->all()` save; missing server validation; weak typed client_id.
- **Fix:** Validate required from/to/subject/message + client_id exists on admins; trim email fields; keep `mail_type`/`conversion_type`/`mail_body_type` for Email tab compatibility. Same redirect success/error. (2026-08-05)
#### ~~E-11. Elite inbound saves non-Elite mail anyway (“Don't reject — save anyway”)~~ — **FIXED**
- **File:** `EliteEmailController::persistInbound`
- **Was:** Non-Elite To/From logged as rejected then `$eliteTo = true` and saved.
- **Fix:** If any To/From was resolved and none are Elite → JSON 422 (not stored). If no addresses parsed → still save (parse-gap safety). Elite To or From unchanged. (2026-08-05)
#### ~~E-12. S3 archival silent skip when `client_id` missing (sent email won’t appear in Email tab)~~ — **FIXED**
- **Files:** `AdminController::sendmail`; `CrmSentEmailS3Service::storeToS3`
- **Was:** Missing/invalid `client_id` → S3 skip with thin log; compose often left entity id unset (college free email as To-only).
- **Fix:** Resolve entity id from client_id → application → invoice/receipt client → first numeric To; default `type=client` when omitted; richer skip log; only set `$s3Stored` when `storeToS3` returns true. Send path unchanged on skip. (2026-08-05)
#### ~~E-13. Recipient HTML XSS in `buildRecipientHtml` (unescaped name/email)~~ — **FIXED**
- **File:** `public/js/common/recipient-select.js`
- **Was:** `name`/`email`/`status` concatenated raw into Tom Select option HTML.
- **Fix:** Escape text via `escapeHtml`; badge class from raw status before escape. (2026-08-05)

### Low

#### ~~E-14. Google review still uses placeholder SMTP body string~~ — **FIXED**
- **File:** `ClientMessagingController::isgreviewmailsent`
- **Was:** `'body' => 'This is for testing email using smtp'` dead test payload.
- **Fix:** Removed unused `body` key; keep `firstname` / `reviewLink` for `emails.googlereview`. View unchanged. (2026-08-05)
#### ~~E-15. Email modal destroys Tom Select on every close (re-init races)~~ — **FIXED**
- **Files:** `email-handlers.js`; `recipient-select.js`
- **Was:** `hidden.bs.modal` called `RecipientSelect.destroy` → re-init races on reopen.
- **Fix:** `RecipientSelect.clear()` resets values/options; modal hide uses clear; keep instance for next `shown` apply/setData. Destroy kept as fallback if `clear` missing. (2026-08-05)
#### ~~E-16. Elite `sent()` query uses invalid Eloquent arity (`where(..., 'and')`)~~ — **FIXED** (cosmetic / not a runtime bug)
- **Was:** Claimed invalid arity; 4th arg is Laravel `$boolean` (default `'and'`).
- **Fix:** `sent()` / `drafts()` use `where('mail_type', 1)` and `where('admin_id', $adminId)` — same SQL. (2026-08-05)
#### ~~E-17. `EmailService::configureMailerForEmail(null)` does not configure mailer~~ — **FIXED**
- **File:** `app/Services/EmailService.php`
- **Was:** Name implied mailer config; method only resolved From; `null` → env / first active from_emails.
- **Fix:** Added `resolveFromEmail()` with accurate PHPDoc; `configureMailerForEmail` delegates (BC). No send-path behavior change. (2026-08-05)
#### ~~E-18. Archived S3 HTML snapshot stores unsanitized body~~ — **FIXED**
- **Files:** `CrmSentEmailS3Service::buildEmailHtml`; `emails_v2` read iframe
- **Was:** Message HTML injected raw into S3 snapshot (headers only escaped).
- **Fix:** Before archive, strip script/iframe/object/embed/form, `on*` handlers, javascript/vbscript URLs; add CSP meta. Live SES body unchanged. Email tab body iframe `sandbox="allow-same-origin"` (no scripts). Older S3 files still benefit from iframe sandbox when open in-app. (2026-08-05)

---

## 8. Documents

### High

#### ~~D-1. `uploaddocument` still uses local storage, not S3~~ — **FIXED**
- **File:** `ClientDocumentController.php` — `uploaddocument`, dual preview HTML, `deletedocs`, `downloadpdf` guard; compose dual-read in `AdminController` + `detail.blade.php`
- **Fix:** New uploads use S3 (`myfile` URL + `myfile_key`) with fail-closed on S3 error; list/preview/delete dual-read S3 vs legacy `img/documents/`; PDF convert only for legacy local images; compose attach/send uses remote when key/URL present so education/migration S3 rows keep working.
#### ~~D-2. `uploadalldocument` continues after S3 failure~~ — **FIXED**
- **File:** `ClientDocumentController.php` — `uploadalldocument` (+ bulk upload same fail-closed pattern); `document-upload.js` error toast
- **Fix:** On S3 put failure, return `status: false`, do not write `myfile` / `myfile_key`; checklist row stays uploadable. Success path unchanged.

#### ~~D-3. `download_document` / preview — no authorization (any authenticated admin + arbitrary `filelink`)~~ — **FIXED**
- **File:** `ClientDocumentController.php` — `download_document`, `preview_document`, `preview_document_view` + `authorizeDocumentFileAccess`
- **Fix:** Presign only when `filelink` matches a `documents` row (`myfile` / `signed_doc_link` / resolved S3 key / optional `document_id`). Existing UI keeps using `filelink` only; arbitrary S3 URLs without a CRM row get 403.

#### ~~D-4. Public signing: Python failure silently copies unsigned PDF~~ — **FIXED**
- **File:** `PublicDocumentController.php` — public `submitSignatures` / add_signatures
- **Fix:** On Python HTTP/body/connection failure, missing/empty output, or output identical to source PDF: leave signer pending, no signed status/hash/_signed row; return error (503/AJAX or redirect) so client can retry. Success path unchanged when Python stamps the file.

### Medium

#### ~~D-5. `deletealldocs` S3 key reconstruction fragile → orphan objects~~ — **FIXED**
- **File:** `ClientDocumentController.php` — `deletealldocs` + shared `resolveS3KeyFromFileUrl` / multi-candidate S3 delete
- **Fix:** Resolve key like preview (strip bucket prefix); fallbacks: full key from `myfile`, `{client_unique_id}/{doc_type}/{myfile_key}`, legacy segments; S3 miss only logs — DB delete + response still succeed.
#### ~~D-6. Public signing: activity/notifications skip when only `client_id` set (not `documentable_*`)~~ — **FIXED**
- **File:** `PublicDocumentController.php` — `createSignatureNotifications`
- **Fix:** Resolve client from `documentable_*` (Admin) first; fall back to `client_id`. Activity + notification when client found; signing flow unchanged on failure.
#### ~~D-7. Public signing: S3 download disables SSL verification (`verify_peer => false`)~~ — **FIXED**
- **File:** `PublicDocumentController.php` — `downloadS3FileToTemp`
- **Fix:** Prefer `Storage::disk('s3')->get` → presigned verified HTTP → verified HTTPS; insecure SSL only when `APP_ENV=local` and `ALLOW_INSECURE_S3_DOWNLOAD=true`.
#### ~~D-8. Legacy preview uses `asset()` on full S3 URL → broken double URL~~ — **FIXED**
- **Files:** `Helper::documentFileUrl` / `documentFileUrlAttr`; client/partner document HTML; `document-handlers.js` normalize
- **Fix:** Absolute http(s) URLs passed through; relative paths still use `asset()`; JS unwraps accidental `https://app/https://…` if any remain.

### Low

#### ~~D-9. Drag-drop click always opens file picker (no target filtering)~~ — **FIXED**
- **Files:** `blade-inline.js`, `partner-detail/bulk-upload.js`, `drag-drop-handlers.js` (+ Vite rebuild)
- **Fix:** Click opens file picker only on empty dropzone/`#ddArea` area; skips interactive children; client bulk uses scoped `$(this).find('.bulk-upload-file-input')`.
#### ~~D-10. `UploadChecklistController` incomplete (no edit/delete; missing files break compose)~~ — **FIXED**
- **Files:** `UploadChecklistController.php`; `UploadChecklist` model; `AdminController` compose + `deleteAction`; `uploadchecklist/index|edit`; routes
- **Fix:** Edit/update (+ optional file replace); delete via existing `deleteAction` with disk cleanup; compose skips missing `public/checklists/*` files and logs a warning; list shows Missing when file absent.


---

## 9. Followups / Calendar

### High

#### ~~F-1. Consultant slugs hardcoded — new DB consultants broken~~ — **FIXED**
- **Files:** `FollowupController.php`; `routes/web.php`; `FollowupCalendarBlockTiming*`; `left-side-bar.blade.php`; `FollowupConsultant.php`
- **Fix:** Resolve calendars/labels/titles/redirect validation from `followup_consultants`; keep built-in four-slug label/title maps and legacy “Calendar” titles. Sidebar + blocked-times options load active DB consultants.

### Medium

#### ~~F-2. Calendar shows completed/cancelled on same footing as open (no status filter in query)~~ — **FIXED**
- **File:** `resources/views/Admin/followups/calendar.blade.php` (+ `FollowupController` calendar load still includes all statuses for filter options).
- **Fix:** Calendar **Show** filter defaults to **Open (confirmed)**; Completed / Cancelled / No show / All preserve access to closed follow-ups. No API/migration change.
#### ~~F-3. Reassign consultant blocked when note `status != 0`~~ — **FIXED**
- **File:** `resources/views/Admin/followups/calendar.blade.php`
- **Fix:** UI matches open-only API — reassign/reschedule disabled for closed notes with hint to mark confirmed first; server `status === 0` rule kept. Change-status still available.

### Low

#### ~~F-4. Activity log sync for consultant change is best-effort string match (last 40 rows)~~ — **FIXED**
- **File:** `FollowupController.php` — `findScheduledFollowupActivityLogId` / consultant + date sync.
- **Fix:** Target only `Scheduled follow-up (%` rows; match full note title in description; no last-40 cap. Soft-fail if no match (reassign/reschedule still succeed). No `note_id` column.

---

## 10. Actions / Tasks

### High

#### ~~ACT-1. Assigned-by-me XSS / broken HTML via unescaped description~~ — **FIXED**
- **Files:** `resources/views/Admin/action/assigned_by_me.blade.php` (note column); also `completed.blade.php` (same pattern)
- **What happens:** `echo $list->description` and unescaped `data-content` allowed stored HTML/script into staff UI; quotes could break attributes.
- **Fix:** Display uses `strip_tags` + `Utf8Helper::sanitizeForHtml` / `sanitizeForHtmlAttribute`; long notes use Read more with `data-bs-html="false"`. Update/Reassign still prefill via Blade-escaped `data-description`. Assign-to-me already used `{{ }}`.

### Medium

#### ~~ACT-2. Action DataTable popover: `data-noteid` holds full description HTML (attribute size/escaping)~~ — **FIXED**
- **Files:** `ActionController.php` (~824, 901); `action/index.blade.php`; also `assigned_by_me.blade.php`, `completed.blade.php` (same pattern)
- **What happens:** Update/Reassign buttons put full description in misnamed `data-noteid`; real note id was `data-taskid`. Confusing DOM, large attributes.
- **Fix:** Description moved to `data-description` (still attribute-escaped via `Utf8Helper` / Blade `{{ }}`); `data-taskid` unchanged for `#assign_note_id` / submit; JS prefills note field from `data-description` with legacy `data-noteid` fallback.
#### ~~ACT-3. Assigned-by-me global-selector fallbacks remain (popover field scoping)~~ — **FIXED**
- **File:** `resources/views/Admin/action/assigned_by_me.blade.php`
- **What happens:** Update/Reassign prefill used `$('.popover:visible').last()` then global `$('#assignnote')` / `$('#assign_note_id')`; submit fell back to `$(document)`. Duplicate row ids → wrong row fields / empty `note_id`.
- **Fix:** Prefill resolves that button’s Bootstrap tip (`getTipElement` + short retry; toast if tip missing). No document-wide `#id` writes. Submit uses `closest('.popover')` (or active trigger tip); aborts with toast if form not found. Payloads/URLs unchanged.
#### ~~ACT-4. Bootstrap 3 popover hide API (`inState`) on BS5 — flaky hide/reopen~~ — **FIXED**
- **Files:** `action/index.blade.php`, `assigned_by_me.blade.php`, `assign_to_me.blade.php`; `public/js/pages/admin/client-detail/assignments.js`
- **What happens:** Success handlers ran BS3.3.6 `inState.click = false` after `popover('hide')`. BS5 has no `inState` → hide/reopen could stick or need extra clicks.
- **Fix:** Replaced with BS5-safe hide: `bootstrap.Popover.getInstance(el).hide()` with jQuery `popover('hide')` fallback. Same callers (`[data-role=popover]`); no init/AJAX/submit changes.
#### ~~ACT-5. `destroyCompleted` / `destroyByMe` no null check on `Note::find`~~ — **FIXED**
- **File:** `ActionController.php` — `destroyByMe`, `destroyCompleted` (also `destroyToMe` same crash pattern)
- **What happens:** `Note::find($note_id)` then immediate property access; missing/stale id → null dereference / 500. Sibling `destroy` already returned JSON 404.
- **Fix:** If note missing, redirect back to list with `error` flash (`Activity not found` / `Action not found`). Success path (`is_action = 0`, ActivitiesLog, success flash) unchanged. Save-failure on by-me/completed now also redirects with an error message.

### Low

#### ~~ACT-6. `markComplete` ignores route-model `Note $note` (overwrites from request id)~~ — **FIXED**
- **File:** `ActionController.php` `markComplete`; route `POST /action/task-complete` (no `{note}`)
- **What happens:** Signature had unused `Note $note`; body reloaded via `$request->input('id')` and overwrote `$note`.
- **Fix (Option A):** `markComplete(Request $request)` only; still resolves note from request `id` + existing validation/JSON. Clients and payload unchanged.
#### ~~ACT-7. `markIncomplete` inconsistent JSON response style~~ — **FIXED**
- **Files:** `ActionController.php` `markIncomplete`; `assigned_by_me` / `completed` / `assign_to_me` incomplete AJAX success handlers
- **What happens:** Used `echo json_encode` with HTTP 200 for errors; unused `Note $note`; unlike `markComplete`’s `response()->json` + status codes. Callers only `$.parseJSON(response)`.
- **Fix:** `markIncomplete(Request $request)` returns `response()->json` (400/404/200/400/500); same body `id` + success shape `{status,message}`; ActivitiesLog path unchanged. Clients parse string or already-parsed object then reload.
#### ~~ACT-8. Followup actions hidden until assign date (easy to misread as “not created”)~~ — **FIXED**
- **Files:** `ActionController` filter helper; Action index / assigned-by-me / assign-to-me; `ClientActionController::scheduleFollowupStore`
- **What happens:** Followups with future `action_assign_date` stayed in DB but were filtered out of Action lists until due day → looked like create failed.
- **Fix (A + B-toggle):** (A) Schedule success message for future dates notes when they appear on Action and the toggle. (B) Default filter unchanged; optional `include_scheduled_followups` (checkbox) shows future Followups with a **Scheduled** badge. Paginated lists keep query via `appends($_GET)`.
#### ~~ACT-9. Appointment endpoints still registered but return 404~~ — **FIXED**
- **Files:** `ActionController.php`; Action index / assigned-by-me / assign-to-me / completed blades
- **What happens:** After appointments → followups, controller still had permanent-404 stubs (`create`/`store`/`show`/`edit`/`update`, `assignedetail`, `update_appointment_*`) and blades still had modal JS calling unregistered appointment URLs.
- **Fix:** Deleted unused appointment stubs; kept all live destroy/complete/list methods. Removed dead `#openassigneview` modal + handlers. Did not change Action/followup routes or client action stores. `/appointments` redirects to followups left alone.

---

## 11. Staff / Roles / Teams / Branches

**Status: S-1 through S-7 — all FIXED** (auth helper, staff list/IDOR, teams/branches gates, role validation, null-safe edit, assignee XSS, staff named routes).

### Critical

#### ~~S-1. `checkAuthorizationAction` incompatible with Staff Role UI~~ — **FIXED**
- **Files:** `Controller.php` (`checkAuthorizationAction`); `StaffController` / `StaffroleController` call sites
- **What happens (double mismatch):**
  1. Roles UI stores `module_access` as JSON object keys → values like `{"3":"on","20":"on"}`.
  2. StaffController called `$this->checkAuthorizationAction('user_management', …)`.
  3. Check did `in_array($controller, $decoded)` on **values** (`"on"`), never keys, and never mapped `'user_management'` → module id **3**.
  4. Result: every **non–role-1** user was treated unauthorized for Staff create/edit and Staff Role CRUD (role 1 bypasses).
- **Fix:** `checkAuthorizationAction` now matches Roles UI / `ClientAuthorization`: decode as assoc array, `array_key_exists` on module ids. Slug map `user_management`→**3**, `user_role`→**6**; numeric id pass-through; role `== 1` still always allowed. Return semantics unchanged (truthy = deny). Unmapped slugs (e.g. `api_key`) stay deny for non–role-1. Legacy list-of-slug formats still accepted as fallback. Call sites untouched.

### High

#### ~~S-2. Staff list/view/AJAX/timezone lack module authorization~~ — **FIXED**
- **File:** `StaffController.php` — `active`/`inactive`/`view`/`savezone`/`getassigneeajax`/`getAssigneeList` — IDOR on timezone (`savezone` updates any `user_id`).
- **Fix:** `active`/`inactive` require role 1 or Roles UI module **3** (manage) or **4** (view list/details). `view` same for other profiles; **own** profile still allowed without 3/4 (self timezone / deep links). `savezone` only **self**, role 1, or module **3**, plus target-exists null-safe. `getassigneeajax` / `getAssigneeList` intentionally remain `auth:admin` only so Actions/Applications assign UIs keep working for staff without 3/4.

#### ~~S-3. Teams & Branches authorization commented out~~ — **FIXED**
- **Files:** `TeamController.php`; `BranchesController.php` — any logged-in staff could create/edit.
- **Fix:** Branch list/create/store/edit require role 1 or Roles UI module **1** (create/edit offices; matches Admin Console Branches menu). Branch **`view` / `viewclient`** stay `auth:admin` only so staff/client/office-visit office deep links keep working. Team index/store/edit require role 1 or module **4** (matches Admin Console Teams under Staff). Replaces dead commented `$check` stubs. Null-safe `find` on branch/team edit POST (related to S-5) included on those save paths only.

### Medium

#### ~~S-4. StaffRole store/edit validation disabled + null-unsafe edit~~ — **FIXED**
- **File:** `StaffroleController.php` `store` / `edit`
- **What happens:** Validate rules commented out / empty (stale `usertype` field); empty or garbage roles could save; edit POST used `StaffRole::find` with no null check → 500 on bad id; `json_encode(null)` when no modules selected.
- **Fix:** Validate current form fields (`name` required max 255, `description` nullable, `module_access` nullable array, edit also requires `id`). Null-safe find → friendly redirect if role missing. Encode modules as JSON object keys or `[]` if none (no unique on name so legacy duplicates still editable). Auth unchanged (module 6 / `user_role`).

#### ~~S-5. Team/Branch edit null dereference on bad id~~ — **FIXED**
- **Files:** `TeamController.php` / `BranchesController.php` `edit` POST
- **What happens:** `$obj = Team::find` / `Branch::find` then `$obj->…` with no null check → 500 on missing/bad `id`.
- **Fix:** Null check after `find` with friendly “Not Exist” redirect (landed with S-3). POST also validates `id` as `required|integer` before load. GET edit still uses `exists()` (already safe). Hidden form `id` fields unchanged.

#### ~~S-6. `getAssigneeList` HTML injection (office names into `<option>` unescaped)~~ — **FIXED**
- **File:** `StaffController.php` `getAssigneeList`
- **What happens:** Built `<option>` HTML with raw first/last name and `office_name`; Action popovers inject via `.html(obj.message)` → stored XSS if names contain markup.
- **Fix:** Escape option **label** with `e()`; cast staff id to `(int)` for `value`. Response shape unchanged (`status` + `message` array of option HTML strings) so Action blades / TomSelect keep working. `getassigneeajax` left as JSON (separate path).

### Low

#### ~~S-7. Staff URL encoding inconsistency + widespread `URL::to`~~ — **FIXED**
- **What happens:** Edit used `base64_encode(convert_uuencode(...))` + `decodeString`; view used plain numeric id; staff blades mixed hard-coded `URL::to('/staff/…')` with `route()`.
- **Fix (approach A — no deep-link breaks):** Keep **view = raw id**, **edit = encoded** intentionally. Staff index: `route('staff.create|active|inactive|view|edit')`; office link `route('branch.userview')`. Named `staff.savezone` for timezone form. Left non-staff `URL::to` on staff view (clients/email/app AJAX) unchanged — out of scope. Controllers unchanged for id handling.

---

## 12. Reports

### High

#### ~~R-1. No authorization on ReportController~~ — **FIXED**
- **File:** `ReportController.php`; routes `web.php` — any `auth:admin` user could hit report endpoints regardless of module flags (UI modules 62–65).
- **Fix:** Server gates match left-side-bar: role 1|12 + `StaffRole` module **62** (client/application), **63** (invoice), **64** (office check-in), **65** (sale forecast); date-wise office visit role `== 1` only; visa/agreement role 1|12 (R-3). `actionCalendar` intentionally remains login-only (data scoped in view).

#### ~~R-4. Audit logs index/export login-only (same class as R-1)~~ — **FIXED**
- **Files:** `AuditLogController.php` (constructor `auth:admin` only); `routes/web.php` `/audit-logs`, `/audit-logs/export`
- **What happens:** Any logged-in staff could view/export staff login logs; no module/super-admin gate. Menu (Staff Login Log) is role 1 only.
- **Fix:** `ensureSuperAdminAccess()` on `index` and `exportCsv` — abort 403 unless role `== 1` (loose compare for string/int). Filter/export behavior unchanged for super admin.

### Medium

#### ~~R-2. Office-visit report wrong totals / page math~~ — **FIXED**
- **File:** `ReportController.php` `noofpersonofficevisit` — `paginate(5)` then `count($lists)` (page size only); Sno offset used `* 20` while page size is 5 → wrong Sno on page 2+.
- **Fix:** `$totalData = $lists->total()`; Sno base `$i = ($lists->firstItem() ?? 1) - 1` so serials match pagination.

### Low

#### ~~R-3. Thin report endpoints (`visaexpires`, `actionCalendar`, `agreementexpires`) — login only~~ — **FIXED**
- **File:** `ReportController.php` — `visaexpires`, `actionCalendar`, `agreementexpires` returned views under `auth:admin` only.
- **What happens:** Any logged-in staff could open those URLs; menu only showed visa/agreement under Reports (role 1|12).
- **Fix:** `ensureReportsRoleAccess()` aborts 403 unless role is 1 or 12 on `visaexpires` and `agreementexpires` (matches Reports sidebar). `actionCalendar` intentionally remains login-only — view already scopes non–role-1 to `assigned_to` self; sidebar link is commented out.

---

## 13. Admin Console

### Critical

#### ~~AC-1. Destructive Admin Console ops: `auth:admin` only~~ — **FIXED**
- **Files:** `routes/adminconsole.php`; `RecentlyModifiedClientsController.php` — `toggleArchive`, `bulkArchive`, `deleteDocument`, S3 upload/delete — no role/module/super-admin gate (route group and controller middleware both only `auth:admin`).
- **What happens:** Any logged-in staff could archive clients / delete or move docs via direct POST even though Admin Console UI is super-admin only.
- **Fix:** `ensureSuperAdminAccess()` (role `== 1`, loose compare) on every public method in `RecentlyModifiedClientsController` only — page + AJAX + destructive ops. Other `adminconsole` routes unchanged. Archive paths also require target `role = 7` (client).

### High

#### ~~AC-2. `bulkArchive` leaks debug payload to client + logs PII~~ — **FIXED**
- **File:** `RecentlyModifiedClientsController.php` (~1543–1624) — response includes `debug.all_admins_with_ids`.
- **What happens:** Success/error JSON exposed debug with admin names; `\Log::info` dumped request input + names.
- **Fix:** Removed debug keys from responses, debug Log calls, and the extra `all_admins_with_ids` query. Response retains `success`, `message`, `archived_count` only.

#### ~~AC-3. `to_date` exclusive of most of the end day~~ — **FIXED**
- **File:** (~150–155) — `created_at <= $toDate` as `Y-m-d` → compared as midnight; rest of end day excluded.
- **What happens:** End date filter treated `Y-m-d` as start-of-day, so almost all activity on the end day was excluded from list + storage tab counts.
- **Fix:** Shared `activityDateEndInclusive()` uses end-of-day (`Y-m-d 23:59:59`) for `to_date` in `index` and `getStorageTabCounts`. `from_date` and UI date format unchanged.

### Medium

#### ~~AC-4. Default `doc_storage = 'local'` hides non-local clients~~ — **FIXED**
- **What happens:** Empty `doc_storage` was forced to `local`, so first load (and filter "All") only showed local-only clients; AWS/both/none hidden.
- **Fix:** Removed the force-default; `''` means no storage filter (All). Explicit `doc_storage=local|aws|both|none` tabs/dropdown values unchanged.
#### ~~AC-5. Case-sensitive search on PostgreSQL (`LIKE` vs `ILIKE` elsewhere)~~ — **FIXED**
- **What happens:** Search and activity-type filters used `LIKE` (case-sensitive on PostgreSQL); staff/partners already use `ILIKE`.
- **Fix:** Switched name/email/phone/`client_id` search and activity subject/description filters to `ILIKE` in `index` and `getStorageTabCounts`. Same wildcards; UI/params unchanged.
#### ~~AC-6. Duplicate rows when max activity timestamps collide~~ — **FIXED**
- **What happens:** Joining only on `MAX(created_at)` returned multiple activity rows per client when timestamps tied → duplicate list rows and inflated storage counts.
- **Fix:** Shared `latestActivityPerClientSubquery()` picks one log per client (`MAX(created_at)`, then `MAX(id)` on ties). Used in `index` and `getStorageTabCounts`.

### Low

#### ~~AC-7. Misleading `TESTING ONLY` comment above active delete path~~ — **FIXED**
- **What happens:** Stale comment above `$document->delete()` implied the hard delete was temporary/disabled; the call was always live.
- **Fix:** Replaced with an accurate short comment; delete path behavior unchanged.

---

## 14. Auth / CRM Access

### High

#### ~~CA-1. Quick grant POST does not re-check cross-access eligibility~~ — **FIXED**
- **Files:** `AccessGrantController.php` `quick`; `CrmAccessService.php` `requestQuickGrant`
- **What happens:** Staff with `quick_access_enabled` could POST grant for any `admins.id` without the eligibility check used by `supervisor()`.
- **Fix:** `quick()` now calls the same `ensureStaffMayOpenCrossAccessOrSupervisorEligible()` as `requestForm` / `supervisor()` before `requestQuickGrant`. Service still only checks flag/reason/dup/office (no `canRequestCrossAccessGrant` hard block — preserves assignee audit grants).

### Medium

#### ~~CA-3. Notification URLs depend on `APP_URL` (`CrmAccessService` uses `url('/crm/access/...')`)~~ — **FIXED**
- **Files:** `CrmAccessService.php` `notifyApproversOfPendingGrant`, `notifyRequesterGrantProcessed`
- **What happens:** Access-request notifications stored absolute URLs from `url()`, so links could miss when browser host ≠ `APP_URL`.
- **Fix:** Store root-relative paths via `route('crm.access.queue|my-grants', [], false)`. Destinations unchanged; host follows the open tab.

### Low

#### ~~CA-2. Active grants require `ends_at` not null~~ — **FIXED**
- **Files:** `StaffClientVisibility.php` `restrictAdminsQueryForStaff`; `CrmAccessService.php` `hasActiveGrant`, `hasDuplicateActiveQuickGrant`
- **What happens:** Visibility only counted `status=active` grants with non-null future `ends_at`, so open-ended rows (`ends_at` null) never granted access.
- **Review note:** Product quick/approve paths always set `ends_at`. Pending keeps null until approved (still excluded via `status`).
- **Fix:** Treat active as in force when `ends_at` is null **or** `ends_at > now`. Expire job unchanged (still only expires past non-null `ends_at`).

---

## 15. Ongoing Sheet

### High

#### OS-1. Mutating endpoints lack client visibility checks — **FIXED**
- **File:** `OngoingSheetController.php` — `updateReference`, `storeSheetComment`, `updateChecklistStatus`, `storePhoneReminder`
- **What happened:** List used `StaffClientVisibility`; mutations accepted any `application_id` / `clientId`.
- **Fix:** Shared `denyUnlessVisibleClient()` uses `StaffClientVisibility::canAccessAdminRecord` before mutations; 403 JSON when denied. Exempt / allocated access unchanged for rows staff can already see.

### Medium

#### OS-2. Insights “clients seen” / load not visibility-scoped the same way as list — **FIXED**
- **Fix:** `sheetsInsights` scopes check-in “clients seen” and per-assignee “load” with `StaffClientVisibility` (same rules as `$appBase` / sheet list). Exempt / non-strict users unchanged.

### Low

#### OS-3. Session filter persistence surprises (hard to clear without `clear_filters`) — **FIXED**
- **Fix:** Session restore still keeps filters on return, but bare sheet visits now **redirect** to a URL that includes the restored filters so the address bar matches applied filters. Clear/Reset still use `clear_filters` and wipe session. See `OngoingSheetController::index` + restore redirect helpers.

---

## 16. Notifications

### High

#### ~~N-1. Bell count poll returns total notifications, not unseen~~ — **FIXED**
- **Was:** `AdminController.php` `fetchnotification` — `receiver_status = 0` filter commented; returned total as `unseen_notification`; `legacy-init.js` only updated badge when count `> 0` (never cleared).
- **Fix:** Poll count matches header: `receiver_id` + `receiver_status = 0`; badge always synced (empty when 0).

### Medium

#### ~~N-2. `fetchmessages` auto-marks first unseen as seen on poll (even if toast fails)~~ — **FIXED**
- **Was:** GET poll set `seen = 1` before toast; toast failure still consumed notification.
- **Fix:** `fetchmessages` returns `{id, message}` without marking; client POSTs `/notifications/mark-toast-seen` only after `iziToast.show`.
#### ~~N-3. In-person waiting count is global for all roles (role branch commented)~~ — **FIXED**
- **Was:** Sidebar + `fetchInPersonWaitingCount` always counted all `status=0` (role branch commented).
- **Fix:** Shared `CheckinLog::waitingCountForUser()` — global for role 1 and `reception_user_id`; others filter `user_id`. Waiting list page still shows full queue (no feature break).
#### ~~N-4. Office-visit toast HTML not escaped (`layouts/admin.blade.php` ~517–521) — XSS risk~~ — **FIXED**
- **Was:** Dynamic toast fields concatenated into `innerHTML` without escaping.
- **Fix:** `escapeHtml()` on text fields; `safeNotificationUrl()` allows only same-origin/relative paths (fallback waiting page). Buttons/actions unchanged.

### Low

#### ~~N-5. Bell click hard-codes `/all-notifications` (ignores subdirectory / `site_url`)~~ — **FIXED**
- **Was:** `window.location = "/all-notifications"` always hit site root.
- **Fix:** Navigate with `(site_url || '') + '/all-notifications'` in `legacy-init.js`.

---

## 17. Shared Frontend / Config

### Critical

#### FE-1. `APP_DEBUG` defaults to `true`
- **File:** `config/app.php` (~42) — `'debug' => env('APP_DEBUG', true)` — missing env exposes stack traces / sensitive data.
- **Review note:** No `.env.example` in repo to force a safe default; Laravel convention is default `false`.

### High

#### FE-2. `APP_URL` default `http://localhost` + widespread `URL::to` / `site_url`
- Affects staff pages, modern search AJAX, notification links, CRM access URLs, print/receipt links across many areas.
- **Related prior fixes:** Some detail edit pencils already moved to relative `route(..., false)`; many list/dropdown links still use `URL::to`.

#### FE-3. `scripts/fix-icon-migration-bugs.cjs` is a mutator, not a safe reporter
- Re-running can overwrite healthy blades (e.g. elite inbox) from an old git rev even when “healthy.”

### Medium

#### FE-4. Tom Select `querySelector` crash for names like `"New ."` — **mostly mitigated**
- **Fixed:** `tomselect-init.js` `normalizeTemplateOutput` / `wrapPlainTextForTomSelect` when legacy `templateResult` / `templateSelection` are mapped; RecipientSelect uses `wrapTomSelectLabel`.
- **Default path safe:** Tom Select 2.4.x built-in `option`/`item` templates already return HTML containing `<`, so plain `initTomSelect(el)` / `initModalTomSelects` are **not** generally vulnerable to `"New ."`.
- **Residual risk:** Custom `render` callbacks that return **plain text without `<`** and bypass `normalizeTemplateOutput` (e.g. some popover paths). Original Compose Email crash was this class of bug.

#### ~~FE-5. `recipient-select.js` XSS in HTML builder path (`buildRecipientHtml`)~~ — **FIXED** (same as E-13)
- Confirmed fixed: `buildRecipientHtml` now escapes name/email/status.

#### FE-6. `modern-search.js` depends on global `site_url` (wrong host if unset/mismatched)

### Low

#### FE-7. `tomselect-init.js` AJAX requires jQuery (empty results without `$.ajax`)
#### FE-8. Recipient URL fallback root-relative `/clients/get-recipients` (breaks in subdirectory)
#### FE-9. Invoice report client link missing `/` in path  
- **File:** `resources/views/Admin/reports/invoice.blade.php` (~100) — `URL::to('/clients/detail'.base64_encode(...))` missing slash between `detail` and id.

---

## 18. SMS Webhooks (new)

### High

#### SMS-1. Provider webhooks have no signature / shared-secret verification
- **Files:** `routes/sms.php` (~15–19) — `webhooks/sms/*` under `web` middleware only (no auth); `SmsWebhookController.php`
- **What happens:** Twilio/Cellcast status endpoints update `SmsLog` by `provider_message_id` with **no** Twilio signature validation or shared secret. Anyone who can guess/obtain a provider message id can spoof delivery status (or DoS update loops). Incoming handlers currently only log + return OK.
- **Reproduce:** POST `/webhooks/sms/twilio/status` with arbitrary `MessageSid` + `MessageStatus`.

---

## Cross-cutting themes (for prioritization)

1. **Authorization / IDOR** — Many Client/* AJAX controllers, documents, notes, actions, ongoing sheet mutations, reports, audit logs, teams/branches, Admin Console destructive ops lack visibility/module checks.
2. **Money math** — Invoice due formulas diverge between list UI (`getinvoices`, partner Accounts tab) and payment store; fee joins inflate partner/commission totals.
3. **Broken/missing endpoints** — Convert lead, convert/delete application routes. (Agent Excel import **A-1/A-2** fixed; SMS sendmsg legacy path **E-5 FIXED** — rewired to `#sendSmsModal`; finalize view copy **APP-1 FIXED**.)
4. **Email send loop** — *(E-1 college crash, E-2 multi-recipient early return, E-3 placeholders, E-7 checklist dup — **FIXED**)*
5. **APP_URL / URL::to** — Absolute URLs break when browser host ≠ `APP_URL`.
6. **SQL injection** — *(APP-3 application checklist upload count — **FIXED** via parameter binding.)*
7. **Document integrity** — Signing fallback copies unsigned PDF; download accepts arbitrary S3 links.
8. **Unauthenticated ingress** — Elite inbound when secret empty; SMS webhooks without signature checks.

---

## Suggested fix priority (do not implement in this pass)

1. INV-1 + P-1 invoice due math  *(APP-3 SQLi, E-1 college / E-2 multi-To email send — **FIXED**)*  

2. C-1/C-2/C-7–C-11 IDOR; D-3 document download auth; AC-1 Admin Console gates; S-1 role auth mismatch  
3. L-1 convert-to-client; C-3 missing application routes  *(A-1 agent import, APP-1/APP-2 finalize + stage crash — **FIXED**)*  
4. D-4 signing unsigned PDF; E-6 Elite webhook auth; SMS-1 webhook signatures; FE-1 APP_DEBUG default  
5. FE-5 recipient XSS *(F-1 followup consultants, N-1 notification poll badge — **FIXED**)*  

---

## Retracted / corrected claims (quick index)

| ID | Verdict |
|----|---------|
| C-6 | **False positive** — Laravel 13 positional bind |
| C-12 (original “merged still appear”) | **Wrong for `is_deleted=1`** — see corrected wording |
| P-11 | **Retracted** — OR is grouped on partner Invoice tab |
| INV-1 “overwrites type 1 & 2” | **Partial** — overwrites type 1 only |
| FE-4 “generic initTomSelect still vulnerable” | **Overstated** — defaults already emit HTML |
| CA-1–CA-3 | **FIXED** — quick eligibility gate; open-ended `ends_at`; root-relative notif URLs |
| CA-2 as High | **Was downgraded** then **FIXED** — open-ended `ends_at` null now counts as active |

---

*End of audit. Generated 2026-07-26. Deep-reviewed 2026-07-26 against codebase (Laravel 13). No application code fixes in this document pass.*
