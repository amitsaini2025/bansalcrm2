# CRM Bugs Audit

**Date:** 2026-07-26  
**Last deep review:** 2026-07-26 (code-verified; false positives retracted; wording corrected)  
**Scope:** Full CRM audit by area (Clients, Leads, Partners, Agents, Applications, Invoices/Receipts, Email/Messaging, Documents, Followups, Actions, Staff/Roles/Teams/Branches, Reports, Admin Console, Auth/CRM Access, Ongoing Sheet, Notifications, Shared Frontend/Config, SMS Webhooks).  
**Status:** Audit doc; some Agent fixes applied (A-1, A-2, A-3, A-4).  
**Stack note:** Laravel **13.x** (route parameters bind **by position** after DI, not by PHP parameter name).

Severity: **Critical** (crash / data corruption / money wrong / security) · **High** (major feature broken or serious auth hole) · **Medium** (incorrect behavior) · **Low** (edge case / UX / maintenance risk)

### Deep review changelog (2026-07-26)

| ID | Change |
|----|--------|
| **C-6** | **Retracted** — `$slug` still receives `{type}` via positional binding on Laravel 13 |
| **C-1 / C-7+** | Clarified: login (`auth:admin`) exists; missing piece is **visibility / canEditClient** |
| **C-12** | Corrected: merge `is_deleted=1` **is** excluded by `whereNull`; real mismatch is `is_deleted=0` handling |
| **INV-1** | Corrected: `else` overwrites **type 1 only**, not type 2 |
| **APP-3** | Split: ~1470 request-controlled SQLi; ~1575 same antipattern but ID from DB |
| **P-3 / P-11** | Removed false “ungrouped OR” on partner Invoice tab (OR **is** grouped); fee-join inflation remains |
| **FE-4** | Narrowed residual: default Tom Select templates already emit HTML; risk is custom plain-text `render` |
| **N-1** | Split: initial header badge OK; AJAX poll returns total not unseen |
| **CA-2** | Downgraded practical risk — grant create/approve always sets `ends_at` |
| **S-1** | Added exact StaffController arg + double mismatch (values vs keys; string vs module id) |
| **ACT-1** | Noted `FIXES_APPLIED_assigned_by_me.md` did **not** fix XSS |
| **NEW** | SMS webhooks unauthenticated; Audit logs login-only (same class as Reports) |
| **A-1** | **FIXED** — Added `use Maatwebsite\Excel\Facades\Excel;` in `AgentController` |
| **A-2** | **FIXED** — Individual import POST handler, route, and form |
| **A-3** | **FIXED** — Null-check after `Agent::find` on edit |

---

## 1. Clients

### Critical

#### C-1. Client merge with no visibility check or transaction
- **Files:** `app/Http/Controllers/Admin/Client/ClientMergeController.php` (~25–157); `routes/clients.php` (~219)
- **What happens:** Any **logged-in** staff (`auth:admin`) can POST `merge_from` / `merge_into`, re-point activities/notes/applications/documents/invoices, and soft-delete one admin row. No `canEditClient` / `StaffClientVisibility` check. No `DB::transaction` — partial merge on failure.
- **Reproduce:** As restricted staff, POST `/merge_records` with two arbitrary client IDs.
- **Root cause:** Merge moved from legacy without authorization or transactional safety.
- **Review note:** Not “unauthenticated” — login middleware is present.

#### C-2. Document endpoints lack client visibility checks (IDOR)
- **Files:** `app/Http/Controllers/Admin/Client/ClientDocumentController.php` — `uploaddocument` (~1673), `deletedocs` (~1846), `download_document` (~697), `preview_document` (~727)
- **What happens:** Upload/delete/download/preview operate on any `client_id` / document without verifying staff may access that client. `ClientAuthorization` may be imported but is unused on these paths.
- **Reproduce:** Staff A (not allocated to client B) POSTs upload or GETs download for client B’s document.
- **Root cause:** No `canViewClient` / `canEditClient` on document endpoints.

#### C-3. Missing controller methods for registered routes
- **Files:** `routes/clients.php` (111–112); `app/Http/Controllers/Admin/Client/ClientApplicationController.php` (methods absent)
- **What happens:** `GET /convertapplication` and `GET /deleteservices` resolve to methods that **do not exist** → 500 / “method does not exist”.
- **Reproduce:** Hit those URLs from client detail application UI (if wired).
- **Root cause:** Routes registered during refactor; methods never migrated. Controller only has `saveapplication` + `getapplicationlists`.

#### C-4. Legacy phone verification validates against non-existent `clients` table
- **Files:** `app/Http/Controllers/Admin/Client/PhoneVerificationController.php` (~26–27, 45–46)
- **What happens:** `sendCodeLegacy` / `verifyCodeLegacy` use `exists:clients,id`. Records live in `admins` — validation fails; legacy verify modal cannot send/check codes.
- **Reproduce:** Use legacy verify flow from client edit with `client_id` = admins.id.
- **Root cause:** Schema name not updated after leads→admins migration.

### High

#### C-5. Client status/rating AJAX is a no-op (targets removed column)
- **Files:** `public/js/pages/admin/client-detail/client-status.js` (~45–55); `ClientController.php` `updateclientstatus` (~964–993); migration `2026_02_10_120000_drop_marked_columns_from_admins_table.php` (drops `rating`)
- **What happens:** UI sends `rating`; controller never reads request fields, saves unchanged row, logs fake “updated client status”, returns success. Column no longer exists anyway.
- **Reproduce:** Click status/rating on client detail → success toast, no DB change.

#### C-6. ~~Client/Lead type toggle broken (route param not bound)~~ — **RETRACTED**
- **Original claim:** Route `{type}` vs method param `$slug` → `$slug` stays null → type cleared.
- **Why wrong:** On Laravel 13, after `Request` DI, remaining route params are passed **by position** (`array_values`). For `changetype(Request $request, $id, $slug)`, `{id}` → `$id` and `{type}` → `$slug`. Toggle works; rename is clarity-only.
- **Evidence:** `routes/clients.php` (~80); `ClientController.php` `changetype` (~1215); live positional bind behaviour.

#### C-7. Notes CRUD without client visibility checks (IDOR)
- **Files:** `ClientNoteController.php` — `createnote`, `getnotedetail`, `viewnotedetail`, `deletenote`, `pinnote`, `getnotes`
- **What happens:** Any **logged-in** staff can create/read/delete/pin notes for any `client_id` / `note_id` (login auth present; no `canViewClient` / `canEditClient`).

#### C-8. Email verification & contact fetch bypass allocation (IDOR)
- **Files:** `ClientMessagingController.php` — `updateemailverified`, `emailVerify`, `fetchClientContactNo`
- **What happens:** Update verification, send verify email, or fetch phones for any `client_id` without visibility check.

#### C-9. Client actions/tasks without visibility checks (IDOR)
- **Files:** `ClientActionController.php` — `actionstore`, `reassignactionstore`, `updateaction`, etc.
- **What happens:** Create/reassign tasks and activity logs for any decoded `client_id`.

#### C-10. Activity log & “not picked call” without authorization (IDOR + SMS)
- **Files:** `ClientActivityController.php` — `notpickedcall`, `deleteactivitylog`, `pinactivitylog`
- **What happens:** Send SMS / update `not_picked_call` / delete/pin activity for any admin ID.

#### C-11. Application create/list without authorization; fragile partner_branch parse
- **Files:** `ClientApplicationController.php` — `saveapplication`, `getapplicationlists`
- **What happens:** Create applications for any `client_id`; `explode('_', partner_branch)` can undefined-index; missing workflow stage → null deref on `$workflowstage->name`.

#### C-12. Soft-delete filter semantics inconsistent with LeadController
- **Files:** `app/Traits/ClientQueries.php` (`whereNull('is_deleted')` only); `ClientMergeController` sets `is_deleted => 1`; LeadController uses `whereNull OR = 0`
- **Corrected behaviour:**
  - Merge sets `is_deleted = 1` → **excluded** by `whereNull` (merged rows do **not** still appear solely because of `=1`).
  - Real bug: ClientQueries also excludes rows with `is_deleted = 0` (treated as “deleted” incorrectly), while LeadController keeps them. Permanent-delete / timestamp semantics may still diverge.
- **Root cause:** Inconsistent `is_deleted` null/`0`/`1`/timestamp conventions across modules.

#### C-13. Clients index includes leads (no default type filter)
- **Files:** `ClientQueries.php` `getBaseClientQuery()`; `clients/index.blade.php` shows type badge
- **What happens:** Active leads appear on Clients list unless user filters Type manually.

#### C-14. Phone OTP endpoints lack allocation checks
- **Files:** `PhoneVerificationController.php` — `sendOTP`, `verifyOTP`, `getStatus`
- **What happens:** Any staff with a `client_phone_id` can OTP-verify phones for clients they cannot otherwise access.

### Medium

#### C-15. Note delete never writes activity log (type comparison bug)
- **File:** `ClientNoteController.php` (~154): `if($data == 'client')` compares **object** to string — always false.

#### C-16. `getonlyclientrecipients` omits Tom Select `text` field & doesn’t filter clients-only
- **File:** `ClientController.php` (~1048–1075) — returns `{name, email, …}` without `text`; includes leads.

#### C-17. `change_assignee` silently no-ops when assignee not sent as array
- **File:** `ClientController.php` (~1100–1116) — scalar assignee skipped; still returns success.

#### C-18. Client import duplicate phone check inefficient / incomplete
- **File:** `ClientImportService.php` (~72–135) — loads all phones per country; can miss `admins.phone` vs `client_phones` mismatch.

#### C-19. Client import may assign wrong `user_id` on phone rows
- **File:** `ClientImportService.php` (~277) — `'user_id' => Auth::id()` may not match staff FK semantics.

#### C-20. `leaddetail` legacy migration creates duplicate admin rows (race)
- **File:** `ClientController.php` (~837–877) — lazy create on GET without lock/upsert.

#### C-21. `clientdetail` does not enforce `type=client`
- **File:** `ClientController.php` (~734–776) — lead IDs open on `/clients/detail/{id}`.

#### C-22. Archived list may include archived leads
- **File:** `ClientQueries.php` `getArchivedClientQuery()` — no `type='client'` filter.

#### C-23. Manual email verify reloads page on any AJAX success
- **File:** `session-handlers.js` (~61–63) — weak error handling; reload even on non-success JSON.

#### C-24. Duplicate `save_tag` route registration
- **Files:** `routes/web.php` (~541); `routes/clients.php` (~84)

### Low

#### C-25. `checkclientexist` no rate limiting / enumeration hardening
#### C-26. `address_auto_populate` permanently disabled but still routed
#### C-27. Client index hardcoded `URL::to` (APP_URL host mismatch risk)

---

## 2. Leads

### Critical

#### L-1. “Convert To Client” runs debug migration script, not conversion
- **Files:** `leads/index.blade.php` (~180); `routes/web.php` (~258); `LeadController.php` `convertoClient` (~363–382)
- **What happens:** Menu action loops migrated leads and **echoes HTML lead IDs** — no conversion, no redirect, no `converted` flag.
- **Reproduce:** Leads index → Options → Convert To Client.
- **Root cause:** Leftover one-off migration endpoint exposed as user-facing action.

### High

#### L-2. Lead assign without visibility check (IDOR)
- **File:** `LeadController.php` `assign` (~165–192)

#### L-3. Lead phone OTP without allocation checks
- **Files:** `PhoneVerificationController.php` (~120–164); `PhoneVerificationService.php`

#### L-4. Lead phone “Verify” button hidden for new admin-only leads (`lead_id` null)
- **File:** `clients/detail.blade.php` (~351–357) — Verify only when `$conVal->lead_id` non-empty; new leads from `LeadController::store` have `lead_id = null`.

#### L-5. Lead list does not filter `is_archived`
- **File:** `LeadController.php` (~49–51) — archived leads still appear on `/leads`.

#### L-6. Lead uniqueness AJAX / store ignore `client_phones`
- **Files:** `is_contactno_unique` (~400–411); store validation `unique:admins,phone` only.

#### L-7. Lead list phone filter only searches `admins.phone`
- **File:** `buildLeadListQuery` (~124–128)

### Medium

#### L-8. Lead status display wrong for numeric statuses
- **Files:** `leads/index.blade.php` (~153); create form uses string statuses — integer `0`/`1` show wrong labels.

#### L-9. Lead create multi-assign UI only saves first assignee
- **File:** `createAdminFromRequestData` (~261–264) uses `assign_to[0]` only.

#### L-10. `leaddetail` auto-migration side effect on GET
#### L-11. Duplicate `leads.detail` route registration (`web.php` + `clients.php`)
#### L-12. Lead import inherits client import duplicate-check gaps
#### L-13. `convertoClient` route outside auth group (relies on controller middleware only)

---

## 3. Partners

### Critical

#### P-1. Gross Claim (type 2) due amount wrong in Accounts tab
- **Files:** `PartnersController.php` `formatPartnerAccountsTabRow` (~1336–1341, 1367–1369)
- **What happens:** Type-2 due computed as `netamount - amount_rec`, but payment storage uses `coom_amt - amount_rec`. Make Payment modal gets wrong `data-dueamount`.
- **Contrast:** `InvoiceController` payment store uses correct formulas (~399–405).

### High

#### P-2. Net Claim (type 1) due ignores prior payments
- **File:** `PartnersController.php` (~1336–1337) — `$totaldue = $total_fee - $coom_amt` without subtracting `$amount_rec`. Payment store uses `$feepaid - $amount_rec`.

#### P-3. Partner Invoice tab summary totals inflated
- **File:** `partners/detail.blade.php` (~934–957)
- **What happens:** `leftJoin` all `application_fee_options` rows without “latest fee only” (`MAX(id)`) constraint → multiple fee snapshots per application multiply Total Projected Fee / Commission totals.
- **Review note:** Stage ORs **are** wrapped in `where(function …)` — do **not** blame ungrouped OR here. Other partner student queries correctly use latest-fee logic.

#### P-4. Student tab export ignores table search
- **File:** `public/js/pages/admin/partner-detail/datatable-handlers.js` (~441–461) — uses client `api.search()` but Student tab is **serverSide**; export dumps unfiltered data.

#### P-5. Student tab pagination shows fake counts when count query fails
- **Files:** `PartnersController.php` `estimatePartnerStudentTabCounts` / `getStudentTabCount`; JS treats `estimated: true` as success — silent wrong N.

### Medium

#### P-6. `URL::to` / hardcoded S3 URL host mismatch risk (print/download)
#### P-7. Partner student invoice ID generation race (`MAX(invoice_id)+1` without lock)
#### P-8. Accounts tab export search uses `id ILIKE` (fragile)
#### P-9. Cached staff dropdown stale for 1 hour (`partner_detail_staff_assignees_v2`)
#### P-10. Column visibility toggle resets after DataTables redraw

#### ~~P-11. Partner invoice tab stage filter uses ungrouped OR~~ — **RETRACTED**
- Stage filter in Invoice tab summary **is** grouped (see P-3). Removed as duplicate/false claim.

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

#### APP-1. Finalize page is a copy of Overdue page
- **File:** `resources/views/Admin/applications/finalize.blade.php` — `@section('title', 'Applications overdue')` and `<h4>All Overdue Applications</h4>`.

#### APP-2. `updatestage` crashes at last workflow stage
- **File:** `ApplicationsController.php` (~128–169) — no guard when `$workflowstage` / `$nextid` null → `$nextid->name` throws.

#### APP-3. Application checklist upload SQL injection / raw SQL antipattern
- **File:** `ApplicationsController.php`
  - **~1470 (Critical):** `$application_id = $request->application_id` interpolated raw into `DB::select("… application_id = '$application_id'")` — attacker-controlled.
  - **~1575 (Medium):** Same string interpolation, but `$application_id` comes from `$appdoc->application_id` (DB integer after `note_id` lookup) — unsafe pattern, not classic request SQLi.
- **Fix direction (not applied):** cast `(int)` or use parameter bindings.

### High

#### APP-4. Finalize list logic contradicts filters / business intent
- **Files:** `ApplicationsController.php` (~2426–2472); `finalize.blade.php` (~86–95)
- **What happens:** Base query requires `status = 2` (Discontinued) AND specific COE stages. Status filter other than 2 → impossible AND → empty results. Completed (status=1) apps never appear.

#### APP-5. Multiple handlers null-deref on missing application
- **Methods:** `completestage`, `discontinue_application`, `revert_application`, `updateintake`, `getapplicationslogs`, `exportapplicationpdf`, `getapplicationdetail`

#### APP-6. `discontinue_application` / `revert_application` report success on failure
- **Files:** (~617–621, 702–704) — `$saved === false` still `'status' => true` with “Please try again”.

#### APP-7. Application activity log email button uses undefined `$fetchedData`
- **File:** `getapplicationslogs` (~286) — `data-email="{{@$fetchedData->email}}"` but `$fetchedData` never loaded.

#### APP-8. `getInvoice` null application dereference
- **File:** `InvoiceController.php` (~68–72)

### Medium

#### APP-9. `updatebackstage` logs wrong stage field (`$workflowstage->stage` vs `name`)
#### APP-10. List pages show wrong total counts (count before filters)
#### APP-11. `updateintake` reports success on failed save
#### APP-12. Partner detail stage AJAX sends `client_id: partnerId` (dead/misleading param)

---

## 6. Invoices / Receipts

### Critical

#### INV-1. Client invoice list (`getinvoices`) due calculation broken
- **File:** `InvoiceController.php` (~507–513)
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

### High

#### INV-2. `getinvoices` loads workflow by wrong ID
- **File:** `InvoiceController.php` (~488–490) — after loading Application, still does `Workflow::where('id', $invoicelist->application_id)` (application ID as workflow ID). Should use `$applicationdata->workflow`.

#### INV-3. Commission report duplicates rows + ungrouped stage OR footgun
- **File:** `ClientReceiptController.php` `getcommissionreport` (~737–745)
- **What happens:**
  - `leftJoin application_fee_options` without latest-fee filter → duplicate students / inflated columns.
  - Stage filter is `where(…).orWhere(…).orWhere(…)` **without** a grouping closure. Currently those are the only filters (so results happen to match stages), but any future `where partner_id = …` etc. added alongside will leak wrong rows.
- **Contrast:** Partner Invoice tab (P-3) **does** group its OR correctly.

### Medium

#### INV-4. `validate_receipt` returns empty/misleading JSON when no IDs
#### INV-5. Multi-line receipt save only updates `trans_no` on last insert
#### INV-6. `saveaccountreport` null deref on validate flag race
#### INV-7. Paid invoice export fee formula may not match list UI
#### INV-8. Client receipt `printUrl` via `URL::to` (APP_URL mismatch)

### Low

#### INV-9. `invoicepaymentstore` can insert empty payment rows

---

## 7. Email / Messaging (CRM compose, SES, Elite, Outlook)

### Critical

#### E-1. College compose crashes on send
- **Files:** `AdminController.php` `sendmail` (~1716–1728); `email-handlers.js` (~362)
- **What happens:** College To entry uses email address as Tom Select `id` (`buildClientRecipientEntry(cEmail, cName, cEmail, 'College')`). Loop does `Admin::where('id', $l)` → null → fatal on `$client->first_name`.
- **Reproduce:** Client detail → Applications → Compose email to college → Send.
- **Note:** `resolveRecipientsToEmails()` already accepts `@` addresses for storage; the send loop does not.

#### E-2. Multi-recipient compose sends only to first recipient
- **File:** `AdminController.php` (~1819–1826) — after first successful send, **returns immediately** inside the foreach. Remaining recipients never get mail.

### High

#### E-3. Multi-recipient template placeholders wrong after first recipient
- **File:** `AdminController.php` (~1719–1738) — `$subject`/`$message` mutated in-place without resetting from originals.

#### E-4. Google review email: undefined `$response` when already sent
- **File:** `ClientMessagingController.php` (~232–272) — `echo json_encode($response)` when already sent → notice / invalid JSON.

#### E-5. SMS `sendmsg` backend missing
- **Files:** Controller docblock lists `sendmsg`; JS opens `#sendmsgmodal` / form `sendmsg`; **no method or route** found.

#### E-6. Elite inbound webhook open when secret unset
- **File:** `EliteEmailController.php` `assertInboundSecret` (~385–398); `config/crm.php` default `env('EDUCATION_ELITE_INBOUND_SECRET', '')`
- **What happens:** Empty secret → early `return` (no auth); anyone can inject `elite_emails`.

### Medium

#### E-7. Checklist attachments duplicated on multi-recipient send
#### E-8. From dropdown ignores API `default_from`
- **Files:** `email-from-ses-script.blade.php`; `OutlookController::senders` — docs say auto-select; frontend only restores `prev`.
#### E-9. `SesSenderService` no longer merges SES API identities (doc drift)
#### E-10. `uploadmail` — no validation, weak persistence
#### E-11. Elite inbound saves non-Elite mail anyway (“Don't reject — save anyway”)
#### E-12. S3 archival silent skip when `client_id` missing (sent email won’t appear in Email tab)
#### E-13. Recipient HTML XSS in `buildRecipientHtml` (unescaped name/email)

### Low

#### E-14. Google review still uses placeholder SMTP body string
#### E-15. Email modal destroys Tom Select on every close (re-init races)
#### E-16. Elite `sent()` query uses invalid Eloquent arity (`where(..., 'and')`)
#### E-17. `EmailService::configureMailerForEmail(null)` does not configure mailer
#### E-18. Archived S3 HTML snapshot stores unsanitized body

---

## 8. Documents

### High

#### D-1. `uploaddocument` still uses local storage, not S3
- **File:** `ClientDocumentController.php` (~1673–1814) — `public/img/documents/` vs `uploadalldocument` S3 path → inconsistent preview/download.

#### D-2. `uploadalldocument` continues after S3 failure
- **File:** (~1446–1457) — logs warning, still saves DB row → UI shows doc that 404s.

#### D-3. `download_document` / preview — no authorization (any authenticated admin + arbitrary `filelink`)
- **File:** (~697–787) — accepts arbitrary S3 URL and presigns if object exists.

#### D-4. Public signing: Python failure silently copies unsigned PDF
- **File:** `PublicDocumentController.php` (~411–437) — on Python error, copies unsigned PDF, marks signed, generates tamper hash from unsigned copy.

### Medium

#### D-5. `deletealldocs` S3 key reconstruction fragile → orphan objects
#### D-6. Public signing: activity/notifications skip when only `client_id` set (not `documentable_*`)
#### D-7. Public signing: S3 download disables SSL verification (`verify_peer => false`)
#### D-8. Legacy preview uses `asset()` on full S3 URL → broken double URL

### Low

#### D-9. Drag-drop click always opens file picker (no target filtering)
#### D-10. `UploadChecklistController` incomplete (no edit/delete; missing files break compose)

---

## 9. Followups / Calendar

### High

#### F-1. Consultant slugs hardcoded — new DB consultants broken
- **Files:** `FollowupController.php` (~35–40, 384–389, 632, 708); `FollowupCalendarBlockTimingController.php` (~90)
- **What happens:** Fixed slug lists (ankit/rakshita/jaspreet/syed). Consultants added via calendar settings are not wired into calendar routes / reschedule / block APIs.

### Medium

#### F-2. Calendar shows completed/cancelled on same footing as open (no status filter in query)
#### F-3. Reassign consultant blocked when note `status != 0`

### Low

#### F-4. Activity log sync for consultant change is best-effort string match (last 40 rows)

---

## 10. Actions / Tasks

### High

#### ACT-1. Assigned-by-me XSS / broken HTML via unescaped description
- **File:** `resources/views/Admin/action/assigned_by_me.blade.php` (~112–117) — `data-content="'.$full_description.'"` and raw `echo $list->description` without `e()`.
- **Review note:** `FIXES_APPLIED_assigned_by_me.md` fixed popover ID scoping / complete flow — **not** XSS escaping. Residual XSS still live.

### Medium

#### ACT-2. Action DataTable popover: `data-noteid` holds full description HTML (attribute size/escaping)
- **Files:** `ActionController.php` (~824, 901); `action/index.blade.php` (~629)
#### ACT-3. Assigned-by-me misleading `data-noteid` (still description text) + global-selector fallbacks remain
#### ACT-4. Bootstrap 3 popover hide API (`inState`) on BS5 — flaky hide/reopen
#### ACT-5. `destroyCompleted` / `destroyByMe` no null check on `Note::find`

### Low

#### ACT-6. `markComplete` ignores route-model `Note $note` (overwrites from request id)
#### ACT-7. `markIncomplete` inconsistent JSON response style
#### ACT-8. Followup actions hidden until assign date (easy to misread as “not created”)
#### ACT-9. Appointment endpoints still registered but return 404

---

## 11. Staff / Roles / Teams / Branches

### Critical

#### S-1. `checkAuthorizationAction` incompatible with Staff Role UI
- **Files:** `Controller.php` (~236–256); `StaffController` / `StaffroleController`
- **What happens (double mismatch):**
  1. Roles UI stores `module_access` as JSON object keys → values like `{"3":"on","20":"on"}`.
  2. StaffController calls `$this->checkAuthorizationAction('user_management', …)`.
  3. Check does `in_array($controller, $decoded)` on **values** (`"on"`), never keys, and never maps `'user_management'` → module id **3**.
  4. Result: every **non–role-1** user is treated unauthorized for Staff create/edit and Staff Role CRUD (role 1 bypasses).
- **Contrast:** `ClientAuthorization` correctly uses `array_key_exists($moduleId, …)`.

### High

#### S-2. Staff list/view/AJAX/timezone lack module authorization
- **File:** `StaffController.php` — `active`/`inactive`/`view`/`savezone`/`getassigneeajax`/`getAssigneeList` — IDOR on timezone (`savezone` updates any `user_id`).

#### S-3. Teams & Branches authorization commented out
- **Files:** `TeamController.php` (~36–41); `BranchesController.php` (~34–39) — any logged-in staff can create/edit.

### Medium

#### S-4. StaffRole store/edit validation disabled + null-unsafe edit
#### S-5. Team/Branch edit null dereference on bad id
#### S-6. `getAssigneeList` HTML injection (office names into `<option>` unescaped)

### Low

#### S-7. Staff URL encoding inconsistency + widespread `URL::to`

---

## 12. Reports

### High

#### R-1. No authorization on ReportController
- **File:** `ReportController.php`; routes `web.php` (~527–539) — any `auth:admin` user can hit report endpoints regardless of module flags (UI modules 62–69 unused here).

#### R-4. Audit logs index/export login-only (same class as R-1)
- **Files:** `AuditLogController.php` (constructor `auth:admin` only); `routes/web.php` (~523–524) `/audit-logs`, `/audit-logs/export`
- **What happens:** Any logged-in staff can view/export staff login logs; no module/super-admin gate.

### Medium

#### R-2. Office-visit report wrong totals / page math
- **File:** `ReportController.php` (~122–136) — `paginate(5)` then `count($lists)` (page size); offset multiplier uses 20 while page size is 5.

### Low

#### R-3. Thin report endpoints (`visaexpires`, `actionCalendar`, `agreementexpires`) — login only

---

## 13. Admin Console

### Critical

#### AC-1. Destructive Admin Console ops: `auth:admin` only
- **Files:** `routes/adminconsole.php`; `RecentlyModifiedClientsController.php` — `toggleArchive`, `bulkArchive`, `deleteDocument`, S3 upload/delete — no role/module/super-admin gate (route group and controller middleware both only `auth:admin`).

### High

#### AC-2. `bulkArchive` leaks debug payload to client + logs PII
- **File:** `RecentlyModifiedClientsController.php` (~1543–1624) — response includes `debug.all_admins_with_ids`.

#### AC-3. `to_date` exclusive of most of the end day
- **File:** (~150–155) — `created_at <= $toDate` as `Y-m-d` → compared as midnight; rest of end day excluded.

### Medium

#### AC-4. Default `doc_storage = 'local'` hides non-local clients
#### AC-5. Case-sensitive search on PostgreSQL (`LIKE` vs `ILIKE` elsewhere)
#### AC-6. Duplicate rows when max activity timestamps collide

### Low

#### AC-7. Misleading `TESTING ONLY` comment above active delete path

---

## 14. Auth / CRM Access

### High

#### CA-1. Quick grant POST does not re-check cross-access eligibility
- **Files:** `AccessGrantController.php` `quick` (~358–402); `CrmAccessService.php` (~102–108)
- **What happens:** Staff with `quick_access_enabled` can POST grant for any `admins.id` without the eligibility check used by `supervisor()`.

### Medium

#### CA-3. Notification URLs depend on `APP_URL` (`CrmAccessService` uses `url('/crm/access/...')`)

### Low

#### CA-2. Active grants require `ends_at` not null — **low practical risk**
- **File:** `StaffClientVisibility.php` (~212–213) — `whereNotNull('cag.ends_at')->where('cag.ends_at', '>', $now)`
- **Review note:** Quick grant + approve paths **always set** `ends_at`. Pending grants with null `ends_at` are correctly excluded until approved. Only hurts manually inserted open-ended rows. Downgraded from High.

---

## 15. Ongoing Sheet

### High

#### OS-1. Mutating endpoints lack client visibility checks
- **File:** `OngoingSheetController.php` — `updateReference`, `storeSheetComment`, `updateChecklistStatus` (can set status/stage), `storePhoneReminder`
- **What happens:** List uses `StaffClientVisibility`; mutations accept any `application_id` / `clientId`.

### Medium

#### OS-2. Insights “clients seen” / load not visibility-scoped the same way as list

### Low

#### OS-3. Session filter persistence surprises (hard to clear without `clear_filters`)

---

## 16. Notifications

### High

#### N-1. Bell count poll returns total notifications, not unseen
- **Files:** `AdminController.php` `fetchnotification` (~123–140) — `receiver_status = 0` filter **commented**; returns total as `unseen_notification`; `legacy-init.js` treats value as unseen → badge can stay high / never clear when count is 0.
- **Review note:** Initial header badge in `header.blade.php` **does** filter `receiver_status = 0`. Dual path: first paint OK, poll wrong.

### Medium

#### N-2. `fetchmessages` auto-marks first unseen as seen on poll (even if toast fails)
#### N-3. In-person waiting count is global for all roles (role branch commented)
#### N-4. Office-visit toast HTML not escaped (`layouts/admin.blade.php` ~517–521) — XSS risk

### Low

#### N-5. Bell click hard-codes `/all-notifications` (ignores subdirectory / `site_url`)

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

#### FE-5. `recipient-select.js` XSS in HTML builder path (`buildRecipientHtml`)
- Confirmed: `wrapTomSelectLabel` escapes; `buildRecipientHtml` still concatenates `name`/`email`/`status` raw when `repo.html` / entry builder path is used.

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
3. **Broken/missing endpoints** — Convert lead, convert/delete application routes, SMS sendmsg, finalize view copy. (Agent Excel import **A-1/A-2** fixed.)
4. **Email send loop** — College compose crash; multi-recipient early return; placeholder mutation.
5. **APP_URL / URL::to** — Absolute URLs break when browser host ≠ `APP_URL`.
6. **SQL injection** — Raw string interpolation in application checklist upload count (request path).
7. **Document integrity** — Signing fallback copies unsigned PDF; download accepts arbitrary S3 links.
8. **Unauthenticated ingress** — Elite inbound when secret empty; SMS webhooks without signature checks.

---

## Suggested fix priority (do not implement in this pass)

1. APP-3 (~1470) SQL injection; E-1/E-2 email send; INV-1 + P-1 invoice due math  
2. C-1/C-2/C-7–C-11 IDOR; D-3 document download auth; AC-1 Admin Console gates; S-1 role auth mismatch  
3. L-1 convert-to-client; C-3 missing application routes; APP-1/APP-2 finalize + stage crash  *(A-1 agent import — **FIXED**)*  
4. D-4 signing unsigned PDF; E-6 Elite webhook auth; SMS-1 webhook signatures; FE-1 APP_DEBUG default  
5. F-1 followup consultant hardcoding; FE-5 recipient XSS; N-1 notification poll badge  

---

## Retracted / corrected claims (quick index)

| ID | Verdict |
|----|---------|
| C-6 | **False positive** — Laravel 13 positional bind |
| C-12 (original “merged still appear”) | **Wrong for `is_deleted=1`** — see corrected wording |
| P-11 | **Retracted** — OR is grouped on partner Invoice tab |
| INV-1 “overwrites type 1 & 2” | **Partial** — overwrites type 1 only |
| FE-4 “generic initTomSelect still vulnerable” | **Overstated** — defaults already emit HTML |
| CA-2 as High | **Downgraded** — grants always get `ends_at` in current paths |

---

*End of audit. Generated 2026-07-26. Deep-reviewed 2026-07-26 against codebase (Laravel 13). No application code fixes in this document pass.*
