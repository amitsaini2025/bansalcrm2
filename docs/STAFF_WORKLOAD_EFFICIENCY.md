# Staff workload and efficiency

Spec for a **My day** view in **bansalcrm2** (education admissions CRM). **Individual staff only** — no team table, no manager “view as,” no ranking of colleagues.

This is **not** a copy of migrationmanager2’s matter/visa workload. The unit of work here is **students** (clients/leads), **applications** (admissions files to colleges), and **colleges** (partners). Staff chase both people and institutes.

Related tables and writers live across client detail, partners, applications, sheets, actions, follow-ups, email/SMS, and office visits. Prefer querying the source rows below; do not invent new `activity_type` values without writing them at the source.

---

## Agreed product brief

Efficiency and workload are built from **what staff did on student, application, and college records**. **Hours in CRM** are shown prominently as **context** (reuse existing login/session stats). They are **not** the score. **Duration on calls/in-person is out** (no timer on notes).

### Domain model (keep in mind)

| Entity | Table / shape | Role in workload |
|--------|---------------|------------------|
| Student | `admins` (`type` client or lead) | Person contacted; allocation via `user_id` + `assignee` |
| Application | `applications` | Admissions **file**: partner, product, workflow, stage, fees, COE path |
| College | `partners` | Institute staff call/email for admissions; notes/emails use `type = partner` |
| Office visit | `checkin_logs` | Front-desk queue — **not** proof of a consult |
| Follow-up | `notes` with `task_group = Followup` | Scheduled diary — **not** Layer A contact |

A student can have **many applications**. One Call note on the student is **one contact**. Moving three application stages is **three files of throughput**, still one person “worked on” for unique-student counts. Caseload must count **applications on their plate**, not only student rows. A counsellor can own the student while another staff owns the application (`applications.user_id`).

### Contact (two audiences)

**Spoke to / met** stays on file notes (`is_action = 0`). Types are stored in **`notes.title`** (not `task_group`):

| Audience | `notes.type` | `notes.client_id` | Titles that count as live contact |
|----------|--------------|-------------------|-----------------------------------|
| Student | `client` (or lead path using same note UI) | `admins.id` | `Call`, `In-Person` |
| College | `partner` | `partners.id` | `Call`, `In-Person` |

Keep student and college contact **separate**. Do not merge unique counts. Optional: Email-title notes count as “emailed (note)” under throughput/comms, not spoke-to.

**Student-from-college note** (`PartnersController::addstudentnote`): note is saved on the **student** (`type = client`); `activities_logs` is written on the **partner** (`task_group = partner`, subject like “added a note for {student}”). Credit **college contact** from that flow (they spoke to the institute). Do **not** also increment student spoke-to unless a student Call/In-Person note exists as the consult. Drill-down may show which student the college call was about.

### What else to count (besides Call / In-Person notes)

Count **unique people / unique applications / unique colleges** first, event count second.

| Count | Why | Source |
|-------|-----|--------|
| Documents (student / app / partner) | Files they worked on | `documents` (`created_by` / `user_id`); activity subjects |
| Email / SMS | Communication | `emails.user_id`; `sms_logs.sender_id`; activity `email` / `sms` |
| Application stage moves | Admissions progress — strong for processors | `application_activities_logs` (`type = stage`, `user_id`) |
| Checklist / app docs | Admissions pipeline | checklist send/verify; docs with application linkage |
| Lead converted | Outcome | Who converted (`LeadController` / `admins.converted`) — not only assigned-lead stats |
| Financial | Accounts work | receipts / invoices / refunds + related activity |
| Profile / file upkeep | Real work, easy to inflate | `activities_logs` generic / subject parse |
| Actions completed | Assigned work finished | `notes` (`is_action = 1`, completed) — **not** Action `task_group = Call` as a consult |
| Partner / college work | Institute chase | partner notes, partner emails, partner docs, partner actions |
| Started application | Pipeline start | activity subject / application create by staff |

### Show separately — not “met / spoke”

- Follow-ups (diary / booked vs outcome) — consultant calendars may use **slugs**, not `staff.id` 1:1
- Office visit / front-desk check-in (`checkin_logs`)
- **Hours in CRM** — top strip; reuse dashboard login/session (`DashboardService::getLoginStatistics`, `sessions`, `staff_login_logs`). Context, not a score
- **Call not picked** — student flag + SMS; **not** spoke-to; small counter so chase work is visible
- Sheets (Ongoing, Checklist, COE Issued & Enrolled, Discontinue, Refund) — **views of applications**, not extra credit for opening the sheet

### Do not count

- Login duration as the **main** score (still **show** hours as context)
- Note **updates** as new contact (only **created** Call / In-Person)
- The duplicate `activities_logs` row when a student note is saved (`added a note` / `updated a note`) — audit copy; do not double with Layer A
- Action `task_group = Call` as a phone consult
- Appointment / follow-up meeting type as Layer A contact
- “Call not picked” (client flag + SMS) as spoke-to
- Check-in / session start as “met” (student or college) without an In-Person **note**
- Inbound email as spoke-to or outbound credit
- Ghost credit: being on `assignee` or owning the application does not give contact/throughput unless that staff logged it
- Active CRM access grants (`client_access_grants`) as caseload ownership
- Ranking this staff against colleagues, or inventing a team roll-up on this page

### Shared allocation

| Layer | Rule |
|-------|------|
| Contact / throughput | Only the staff who **wrote** the note / activity / doc / email / stage |
| Caseload (students) | `admins.user_id` **or** id in comma-separated `admins.assignee` — dedupe same student |
| Caseload (applications) | `applications.user_id` — split by stage workload class when available |
| Caseload (actions) | `notes.assigned_to` only — do not fan out to all student assignees |

No “ghost credit”: being co-assignee on a student does not increment spoke-to, docs, or stages unless that person logged it. Drill-down can show co-assignees (“shared with …”). Do not rank co-assignees against each other on one student/application.

### How it fits together

| Layer | Source of truth |
|-------|-----------------|
| Hours in CRM | `sessions` + `staff_login_logs` / `DashboardService::getLoginStatistics` — **header**, not a score |
| Spoke to / met students | `notes` (`is_action = 0`, `type` client path, `title` Call or In-Person) — **actor** |
| Spoke to / met colleges | `notes` (`is_action = 0`, `type = partner`, `title` Call or In-Person) — **actor**; student-from-partner flow → college contact |
| Worked on today | Distinct students + applications + colleges on staff writes |
| Still on their plate | Student allocation + applications they own + open actions; **split apps active / waiting / closed** by stage class; **quiet / inactive** by this staff’s last work |

### Workflow stages: active vs waiting vs closed

`workflow_stages` has **no** idle/active flag today (`w_id` = workflow; application stores **stage name** string on `applications.stage`). Add an explicit classification per stage row (Admin Console), not a hardcoded name list.

| Class | Meaning for workload | Typical education examples (hints only — admins set the real list) |
|-------|----------------------|---------------------------------------------------------------------|
| **active** | Staff (or the firm) is expected to do something next | Checklist chase we own, drafting, follow-up with student *we* must chase |
| **waiting** (idle) | Ball is not with us: waiting on student, college, COE processing external, etc. | Awaiting docs, awaiting partner, Coe processing (if treated as waiting) |
| **closed** | Out of working caseload | Discontinued, Coe Cancelled, Refund final, enrolled-and-done if you treat that as closed |

**Caseload** = count distinct **applications** on this staff, split **active / waiting / closed / unknown**. A large waiting pile is **not** low efficiency. A large **active** pile with no notes/docs/stages today is overload or neglect.

**Quiet / inactive** (this staff’s last work) is **not** the same as waiting. An application can be waiting on the college and still **inactive** for this staff if they have not logged work in 14+ days.

**Throughput** = stage *moves* they logged (`application_activities_logs`, actor = `user_id`). Do not treat “sitting in waiting” as a stage move.

Leads / students with **no** application: put in a **No application** bucket for open plate, not “waiting.”

Default for unclassified stages: **`unknown`** (or treat as active) until an admin classifies them. Do not guess from sheet names alone without admin override.

Known finalize-stage name set in code today (hints only): `Coe processing`, `Coe issued`, `Refund`, `Coe Cancelled` — still require `workload_class`, do not hardcode closed/waiting from this list alone.

See §6.1 for schema and rules. Quiet / inactive bands are §6.2 (v1 even if stage class is not live yet).

### Personal view is user-based (not role-based)

**Every logged-in staff member sees only their own My day**, using `Auth::guard('admin')->id()` (staff). Consultant, processor, accounts, front desk — **same page, same tiles, their data**. Do not hide Call tiles from processors or college tiles from counsellors. If they logged a Call or emailed a college, it shows; if not, counts are zero.

Role does **not** change whose numbers you see. There is **no Team today** and **no** manager view of another staff’s My day on this page.

Do **not** copy the dashboard behaviour where role `1` sees everyone’s recent `activities_logs`. My day is always **me**. Ignore `staff_id` query params from other users.

### Further ideas (agreed)

Not all of this is v1. Items marked **phase 2** wait until My day exists. Quiet/inactive (7 / 14 days) and the hours strip **are v1**.

| Idea | Layer | When |
|------|--------|------|
| **Aging (days in stage)** | Caseload | Long **waiting** is normal. Long **active** with no work by anyone on the application is a later flag. Phase 2. Quiet/inactive by **this staff** is already v1 (§6.2). |
| **Waiting → active** (and reverse) | Throughput | Stage move that unsticks a file. Optional tag; do not treat sitting in waiting as work. |
| **Inbound vs outbound** | Comms | College/student emailing you ≠ you calling them. Do **not** fold inbound into spoke-to. Phase 2 if at all. |
| **Due today / overdue** | Caseload | Open actions + follow-ups already in the CRM. Strip next to active/waiting. |
| **Compare to yourself** | UI | This week vs last week for **the same staff**. Do not rank colleagues. |
| **Leave / part-time** | Fairness | Empty days after leave must not look like a bad day. If leave is not in the CRM, do not interpret zeros — including a long session hours strip with empty contact. |
| **Visit without note** | Quality | Office visit completed / attended without In-Person note — warning only. |
| **Partner agreements / commission** | Throughput | Optional later; do not inflate v1 with agreement edits. |

---

## Reading the day (and how to improve)

Own heading on purpose: this is how people should **read** My day, not a hidden pitfall.

### Contact can be zero on a busy day

If staff only **update profiles**, **move application stages**, or tick **“call not picked,”** Layer A (spoke to / met) stays **empty**. That is expected. Those actions are **not** Call / In-Person notes.

The page must **not** look idle when contact is zero. Layer B (worked on, stage moves, profile/other, docs, emails) and Layer C (open plate, quiet/inactive) stay visible. High hours + empty contact is a **logging or workload** signal, not “they did nothing.”

### Call not picked is chase, not a consult

Show a small **Call not picked** count (students they flagged / SMS’d today) **beside** contact, never inside spoke-to. Same for office visits: diary, not “met.”

### How to improve (product / UI)

- Keep **hours**, **contact**, and **throughput** visually separate so “no calls logged” is not read as “did nothing.”
- After a **stage move** or **call not picked**, nudge a Call / In-Person note (optional, not blocking).
- Quiet hint when **Others / Attention** notes dominate Call / In-Person for that staff today — data quality, not a score.
- Quiet (7–13 days) and inactive (14+ days) lists are the prompt to touch allocated students / owned applications — not a ranking.
- Do not add a single “efficiency %” from hours + note counts.

---

## 1. Goal

Answer, for **this staff member** and a date range (default: **today**, app timezone **Australia/Melbourne**):

1. How long have they been **in the CRM** today (session / last seen) — **context**, not the score?
2. Which **students** did they speak to or meet (unique)? Which were **new** (first Call / In-Person by them / by the firm)?
3. Which **colleges** did they speak to or meet (unique)?
4. Which **students / applications / colleges** are they working on (touched today + still assigned)?
5. What **other CRM work** did they do (docs, email, SMS, stages, money, actions, checklist)?
6. What is still **open** on their plate (allocated students, owned applications by stage class, quiet/inactive, overdue actions / follow-ups)?

Hours answer (1) must not replace (2)–(6).

---

## 2. Three layers (keep them separate)

| Layer | Question | Do not mix with |
|-------|----------|-----------------|
| **Hours** | How long in CRM (session / last seen)? | Contact or throughput score |
| **A. Contact** | Who did they actually talk to / sit with (student **or** college)? | Page views, profile edits, follow-up booked, check-in, call not picked |
| **B. Throughput** | What work did they complete on student / application / college records? | Login duration as the score |
| **C. Caseload** | What is assigned or unfinished (including quiet / inactive)? | Today’s activity volume |

A person can have high caseload and low throughput (blocked on college/student). A person can have high profile-edit counts and zero contact (busy in the file, no consult). A person can have high college Call counts and few student Calls (admissions chase day) — both are real work; show both. A person can have a long session and zero Call notes — show hours **and** throughput; do not treat hours as the win.

---

## 3. Attribution rules

Credit the **staff who performed the write**, not the student’s assigned counsellor — unless the metric is explicitly “assigned to me”.

| Store | Staff column | Use for |
|-------|----------------|---------|
| `notes` (file notes) | `user_id` | Contact (Call / In-Person / Email titles) for student and partner |
| `notes` (actions) | `assigned_to` (open/completed), `user_id` (who assigned) | Caseload vs assigner activity |
| `activities_logs` | `created_by` | Throughput **if** `created_by` is staff. Relation `createdBy` points at `Admin` — do not trust that relation for staff; join `staff` |
| `application_activities_logs` | `user_id` | Application stage / app notes / related comments |
| `emails` | `user_id` | Staff-sent / uploaded mail (`type` client/lead/partner) |
| `sms_logs` | `sender_id` | SMS sent by staff |
| `documents` | `created_by` (prefer) / `user_id` | Files they added (`type` client/partner/application as applicable) |
| `admins` | `user_id`, `assignee` | Student **allocation** — caseload only |
| `applications` | `user_id` | Application **owner** — caseload; stage moves still need actor log |
| `checkin_logs` | `user_id` = **assignee** (not always reception) | Walk-in queue, not “met” |
| `staff_login_logs` | `user_id` | Hours / presence (header) |
| `sessions` | `user_id` | Online / last activity / session length |

### Exclude from staff credit

- `activities_logs` with `created_by` null
- Inbound-only email rows if they cannot be attributed as staff-sent
- System / import noise if subject is clearly bulk import (optional filter later)
- Login events as client/college **work** (they feed the hours strip only)

Timezone: use `config('app.timezone')` (**Australia/Melbourne**). `whereDate` / range queries must use that timezone, not UTC-naive “today”.

---

## 4. Layer A — Contact (notes are canonical)

File notes live in `notes` with `is_action = 0`. Contact type is **`title`**:

| UI type | `notes.title` | Counts as |
|---------|---------------|-----------|
| Call | `Call` | **Spoke to** (student or college by `type`) |
| In-Person | `In-Person` | **Met in person** |
| Email | `Email` | Emailed (optional; not “spoke to”) |
| Others | `Others` | Not contact |
| Attention | `Attention` | Not contact |

**Important:** `notes.task_group` is for **actions** (Call, Checklist, Review, Query, Urgent, Personal Task) and **follow-ups** (`Followup`). Never use action `task_group = Call` as Layer A.

Creating a **student** note also writes `activities_logs` (`subject` `added a note` / `updated a note`). That row is an **audit copy**.

**Count the `notes` row, not the activity row.** Counting both doubles every student contact.

Partner notes created via `/create-note` with `vtype = partner` set `notes.type = partner`. Today that path may **not** write `activities_logs` for pure partner notes — still count the **note**. Partner activity rows from `addstudentnote` are for college feed / throughput linkage, not a second spoke-to.

### Queries (today, one staff)

**Students spoken to / met:**

```
notes
  WHERE user_id = :staffId
    AND is_action = 0
    AND type IN ('client', 'lead')   -- confirm lead notes type in data; UI often uses client note path on admins.id
    AND created_at BETWEEN :start AND :end
    AND title IN ('Call', 'In-Person')   -- or one of them
```

**Colleges spoken to / met:**

```
notes
  WHERE user_id = :staffId
    AND is_action = 0
    AND type = 'partner'
    AND created_at BETWEEN :start AND :end
    AND title IN ('Call', 'In-Person')
```

Metrics:

| Metric | Definition |
|--------|------------|
| `spoke_to_students_count` | Distinct student ids with a **created** Call note in range |
| `met_students_count` | Distinct students with a **created** In-Person note |
| `contacted_students_live_count` | Distinct students with Call **or** In-Person |
| `spoke_to_colleges_count` | Distinct partner ids with a **created** Call note |
| `met_colleges_count` | Distinct partners with In-Person |
| `contacted_colleges_live_count` | Distinct partners with Call **or** In-Person |
| `new_to_staff_student_*` | First Call or In-Person note **by this staff** on that student (ever before range start: none) |
| `new_to_firm_student_*` | First Call or In-Person note **by any staff** on that student |

Use **create** time, not `updated_at`. Editing a Call note is **not** a new contact.

### Do not use for Layer A

- Follow-up calendar / meeting type — scheduled, not proof of conversation
- Actions with `is_action = 1` and `task_group = 'Call'`
- Loose `activities_logs.subject ILIKE '%call%'` — too many false positives (`ClientDetailActivities` “calls” filter). Match `notes.title` exactly
- Front-desk check-in — still require In-Person **note** to count as “met”
- “Call not picked” bit / SMS — separate counter only

### Drill-down list

For each unique student: name, client/lead, note type, `created_at`, snippet, link to client detail. Show co-assignees (`user_id` / `assignee`).

For each unique college: partner name, note type, time, snippet, link to partner detail. If the note/activity names a student, show that link (student-from-partner flow).

---

## 5. Layer B — Throughput

Primary sources:

1. **`activities_logs`** — `created_by = :staffId`, `created_at` in range (student and partner `client_id` values — partner ids are **not** `admins.id`; resolve names via `partners` when `task_group = partner` or note type implies partner)
2. **`application_activities_logs`** — `user_id = :staffId` (stage moves and app-side comments)
3. Secondary tables when the feed is incomplete: `emails`, `sms_logs`, `documents`, `notes`

### 5.1 Deduplicate against Layer A

- Contact metrics use `notes` (student and college).
- Throughput may count one “note” event per person/college per day **or** skip student audit subjects `added a note` / `updated a note` / `deleted a note` so Contact and Worked-on do not double-display the same save.

Recommended: **Contact widgets** = notes. **Activity breakdown** = feed + application logs **except** those note-audit copies, **or** show notes only in Contact and keep a full feed in “Worked on”.

### 5.2 What to count

Always group **unique student id**, **unique application id**, **unique partner id**, and event counts.

| Source | Meaning | Count? |
|--------|---------|--------|
| Student notes (all titles) | File notes | Unique students in “worked on”; Call/In-Person already in Layer A |
| Partner notes | College file notes | Unique colleges; Call/In-Person in Layer A college tiles |
| `activities_logs` document / upload / verify | Files | Exclude non-staff actors |
| `emails` + `activity` email | Comms | Prefer `emails.user_id`; separate inbound later |
| `sms_logs` | Outbound SMS | Prefer `sender_id` + sent status |
| `application_activities_logs` stage | **Outcome for processors** | Actor = `user_id` |
| Receipt / invoice / refund activity | Accounts | Strong for accounts staff |
| Actions completed | `notes.is_action = 1`, completed in range | Prefer notes table over messy feed subjects |
| Lead conversion | Converted today by this staff | Actor of conversion action |
| Started application | New application by staff | Unique applications |
| Profile / other activity | File upkeep | Throughput; **not** contact |

Action feed rows (subjects messy): prefer querying **`notes` where `is_action = 1`** for completed/assigned metrics (`status`, `updated_at` or completion timestamp — verify how completion is stored).

### 5.3 Suggested throughput tiles

**Worked on today**

- Distinct **students** from notes + `activities_logs` on student ids
- Distinct **applications** from `application_activities_logs` (+ docs/receipts tied to application)
- Distinct **colleges** from partner notes + partner emails/docs + partner activity rows

Breakdown (event counts **and** unique entities):

1. Student notes (all types)  
2. College notes (all types)  
3. Documents  
4. Emails (`emails` by type)  
5. SMS  
6. Application stage moves  
7. Financial  
8. Profile/other activity  
9. Actions completed  
10. Lead conversions / applications started  

**Files they are working on** — documents uploaded today by staff, or unfinished docs on today’s touched students/applications — not every historical file on allocated students.

### 5.4 Secondary tables

| Table | When to use |
|-------|-------------|
| `emails` | Staff mail; filter `type` for partner vs student |
| `sms_logs` | `sender_id`, `sent_at` / `created_at` |
| `documents` | Uploads without a feed row; `type` partner vs client |
| `notes` | Canonical notes and actions |
| `application_activities_logs` | Stage and application timeline — **required**; not in client `activities_logs` |
| Sheets | Do **not** use sheet page views as activity |

Do **not** scan HTTP logs or “opened client detail” for efficiency.

---

## 6. Layer C — Caseload (open work)

Not “today”, except “overdue as of today” and **quiet / inactive** as of today. **Caseload is where shared assignment lives.**

| Metric | Source |
|--------|--------|
| Allocated students | Distinct `admins` where `user_id = staff` **or** staff id in `assignee` (CSV). Exclude deleted/archived as existing list rules do |
| Owned applications | Distinct `applications` where `user_id = staff`. Split by stage class: **active / waiting / closed / unknown**. Deduplicate |
| Quiet / inactive | Allocated students and owned applications with no **qualifying work by this staff** in 7–13 days (quiet) or 14+ days (inactive). See §6.2 |
| No-application students | Allocated students with zero open applications — separate bucket |
| Open actions | `notes.is_action = 1`, `assigned_to = staff`, not completed — person-specific |
| Overdue actions | Same + `action_assign_date` &lt; start of today |
| Open Call actions | `task_group = 'Call'` — **queue**, not Layer A |
| Open partner actions | `type = partner`, assigned to staff |
| Open / overdue follow-ups | `task_group = Followup`, assigned / calendar rules already in FollowupController |

Temporary `client_access_grants`: **access**, not ownership — do not add to caseload totals.

Existing dashboard / reports under-count shared `assignee` and ignore college contact. Workload caseload must use `user_id` + `assignee` + `applications.user_id`.

### 6.1 Stage workload class (active / waiting / closed)

There is **no** `workload_class` on `workflow_stages` today. Sheet names and finalize stage lists are operational views, not workload class. Do not reuse them blindly.

**Schema (new):** on `workflow_stages`, e.g. `workload_class` string: `active` | `waiting` | `closed` | `unknown` (default `unknown`). Classify **per stage row** (each workflow has its own stages; the same label can be active in one workflow and waiting in another). Match applications via `applications.workflow` ↔ `workflow_stages.w_id` and `applications.stage` ↔ stage `name` (existing pattern in `ApplicationsController`).

**Admin Console:** on workflow edit, a dropdown per stage. Optional one-off backfill by name *hints* (contains “await”, “waiting”, “coe processing”, “refund”, “cancelled”, “discontinu”) — admins must override. Do not hardcode `Coe issued` as closed without agreement.

**How My day uses it:**

| Tile / list | Rule |
|-------------|------|
| Active applications | Owned apps whose current stage class is `active` and status still open |
| Waiting applications | Class `waiting` — waiting on student / college / external. **Not** a performance fail |
| Closed | `closed` class **or** discontinued / final statuses — exclude from working totals |
| Unknown | Unclassified — show a count so admins finish setup |
| Days in stage (phase 2) | Prefer last `application_activities_logs` stage timestamp |
| Stage moves today | Still Layer B (`user_id`). Optionally tag `waiting→active` / `active→waiting` |

**Do not:** treat waiting as “this staff is lazy”; treat every finalize stage as closed without classification.

Quiet / inactive tiles can ship **before** `workload_class` is filled in.

### 6.2 Quiet (7–13 days) and inactive (14+ days)

By **this staff’s** last **qualifying work** on that student or owned application — **not** last page view, **not** a grant, **not** “call not picked” alone, **not** another staff’s note.

**Qualifying work:** notes they created (`notes.user_id`); `activities_logs.created_by`; `application_activities_logs.user_id`; documents they added (`created_by` / `user_id`); emails they sent (`emails.user_id`); SMS they sent (`sms_logs.sender_id`). Use the latest of those timestamps in app timezone.

| Band | Meaning |
|------|---------|
| **Quiet** | Last qualifying work by this staff was **7–13 days** ago (inclusive of day 7, exclusive of day 14) |
| **Inactive** | Last qualifying work by this staff was **14+ days** ago, **or never** (allocated / owned, no qualifying work on record) |

Counts: distinct allocated **students** in each band; distinct owned **applications** in each band. A waiting-stage application can still be quiet/inactive if **this staff** has not logged work — different meaning from workflow **waiting**.

Closed / discontinued applications: exclude from quiet/inactive working lists (same as closed caseload).

Do not score empty days after leave if leave is not in the CRM.

### Shared-file rules (implement exactly)

1. **Actor, not assignee**, for Layers A and B. If a co-assignee logs a Call note, only they get `spoke_to`.
2. **All allocated staff** get the student in Layer C; application owner gets the application in Layer C.
3. **Same staff** as `user_id` and in `assignee` → one student caseload row.
4. **Do not** rank this staff against colleagues on this page. No team roll-up.
5. **Actions** stay person-specific (`assigned_to`).
6. **New to me vs new to firm** is unchanged for students.
7. **Quiet / inactive** uses **this staff’s** last qualifying work only (§6.2). Not the same as workflow **waiting**.
8. College Call about a student (student-from-partner) → college contact only for Layer A unless a separate student Call note exists.

---

## 7. Hours in CRM (prominent header)

Reuse what the dashboard already has. Do **not** rebuild login tracking. Put this **at the top of My day**, not a footer.

| Signal | Source |
|--------|--------|
| Current session / in CRM since | `DashboardService::getLoginStatistics` (`current_login_*`, `current_session_duration_*`) |
| Last seen / last activity | `sessions.last_activity`; inactivity vs 5-minute window already in that service |
| Previous login | `staff_login_logs` (`Logged in successfully`) |

Show **in CRM since**, **session length**, and **last seen** as a **top strip** — same visual prominence as a section header, not equal to Spoke to as “the” metric.

High hours + low contact/throughput is a **logging or workload** signal (see **Reading the day**). Do **not** derive a single “efficiency %” from hours + note counts. Do not rank staff by hours.

---

## 8. Follow-ups and office visits (diary, not contact)

Show as **scheduled work**, separate from Layer A.

| Source | Show as |
|--------|---------|
| Follow-up notes / consultant calendars for that staff, date = today | Booked / outcome |
| `checkin_logs` `user_id` = staff, `date` = today | Assigned walk-ins |
| Check-in related activity subjects | Reception/session handling |

A completed in-person visit **without** an In-Person note still does **not** increment `met_*`. That gap is intentional (trains note-taking). Optional later: warning “visit completed, no In-Person note”.

---

## 9. Who sees what

**My day is always the current user.** Query with `$staffId = Auth::guard('admin')->id()`. Ignore `Staff.role` for filtering metrics or choosing tiles.

Do not load another staff id. Do not build Team today on this page. Do not rank people on hours online.

---

## 10. What not to build

Also listed in **Agreed product brief**. Extra engineering constraints:

- Duration on Call / In-Person notes; no timer  
- Mouse/keystroke or per-request analytics  
- One “efficiency %” from hours online + note counts  
- Ranking staff by `activities_logs` row count (profile-edit farming) or by session length  
- Team today / manager view of another staff’s My day  
- Treating Action `task_group = Call` as a phone consult  
- Using follow-up / check-in as Layer A contact  
- Double-counting note + `added a note` activity  
- Subject `LIKE '%call%'` for spoke-to  
- Ghost credit for co-assignees / application owner without actor log  
- Copying migrationmanager2 three matter roles or visa idle stage names  
- Treating sheet page views as throughput  
- Counting CRM grants as caseload  
- Treating workflow **waiting** as the same as **inactive** (14+ days with no work by this staff)  

---

## 11. Implementation sketch

### Service

`App\Services\StaffWorkloadService` (or split contact / throughput / caseload). Reuse `DashboardService::getLoginStatistics` for the hours strip (same staff).

```
getDaySummary(int $staffId, Carbon $day): array
getWorkedOnStudents(...)
getWorkedOnApplications(...)
getWorkedOnColleges(...)
getContactEvents(...)  // notes only; student vs partner
getOpenCaseload(int $staffId): array  // includes quiet / inactive
```

Constructor-inject models/query builders. No DB in Blade. Eager-load student and partner names in dedicated queries (do not assume `client_id` always means `admins`).

Date range: `$day->copy()->timezone(config('app.timezone'))->startOfDay()` / `endOfDay()`.

### Indexes (add if EXPLAIN shows seq scans)

- `notes (user_id, is_action, created_at)` including `title` / `type` if the planner wants them  
- `notes (assigned_to, is_action, status, action_assign_date)` for caseload  
- `activities_logs (created_by, created_at)` — **required** for throughput and last-work timestamps  
- `application_activities_logs (user_id, created_at)` and/or `(user_id, type, created_at)`  
- `emails (user_id, created_at)` and `(type, user_id, created_at)` if useful  
- `sms_logs (sender_id, sent_at)`  
- `documents (created_by, created_at)`  
- `applications (user_id, status)` / stage as needed for caseload  

### UI (phase 1)

**My work today** (every `auth:admin` staff, **own id only**):

1. **Hours strip (top):** in CRM since · session length · last seen (existing login stats)  
2. Contact tiles: Spoke to students · Met students · New students · Spoke to colleges · Met colleges  
3. Throughput tiles: Unique students / applications / colleges worked on · stage moves · Actions completed · Docs · Emails/SMS · profile/other — **visible even when contact is zero**  
4. Separate small: Call not picked (not spoke-to)  
5. Caseload: Active apps · Waiting apps (when classified) · **Quiet (7–13d) · Inactive (14+d)**  
6. Lists: students contacted; colleges contacted; people/apps/colleges worked on; quiet/inactive files. Show co-assignees and application partner + stage where relevant.  
7. Optional: open actions / follow-ups due today  

Do not put this on the public activity feed. New routes under CRM, `auth:admin`. No `staff_id` switcher.

### Phase 2 (optional)

- Week range and **this week vs last week** (same staff only)  
- Days in current stage; flag long **active** + no activity by anyone on the file  
- Stage moves tagged waiting↔active  
- Due today / overdue actions and follow-ups strip  
- “Visit completed without In-Person note”  
- Inbound vs outbound email  
- Export CSV  
- Leave/part-time only if a source exists  

Nudge after stage move / call not picked, and Others/Attention hint, belong in **Reading the day** — ship with v1 UI if cheap; do not block hours + contact + throughput + quiet/inactive.

---

## 12. Pitfalls (from this codebase)

1. **`notes.title` vs `notes.task_group`.** Contact types are **titles** on file notes. Action types and Followup use **task_group**. Always filter `is_action`.  
2. **Student note audit.** `added a note` / `updated a note` in `activities_logs` doubles contact if counted with notes.  
3. **`ActivitiesLog::createdBy` → `Admin`.** `created_by` is staff id. Join `staff`, not `admins`, for attribution.  
4. **Partner `client_id`.** On partner notes, emails, documents, and many activity rows, `client_id` is **`partners.id`**, not a student. Resolve names accordingly.  
5. **Student-from-partner notes.** Note on student + activity on partner — college contact, not automatic student spoke-to.  
6. **Pure partner notes via `/create-note`.** May not write `activities_logs`; notes table is still canonical for Layer A.  
7. **Application stages are not in `activities_logs`.** Use `application_activities_logs` (`type = stage`, `user_id`).  
8. **`applications.stage` is a name string**, matched to `workflow_stages` by `w_id` + name — not a stage id FK.  
9. **CSV `assignee`.** Same pitfalls as `StaffClientVisibility` / `StaffAssigneeResolver` — comma-separated ids.  
10. **Super-admin dashboard feed** shows everyone’s activity — My day must not.  
11. **Follow-up consultant slugs** are not always `staff.id`.  
12. **Personal tasks** may have no `client_id`.  
13. **`ClientDetailActivities` “calls” filter** uses subject/description `LIKE '%call%'` — unsuitable for Layer A.  
14. **Companies / partners** are colleges; lists must label them as such.  
15. **Sheets** (ongoing, COE, discontinue, refund, checklist) are filtered application lists — opening them is not throughput.  
16. **Dashboard hours** already exist — reuse; do not invent a second session clock.  
17. **Call not picked** and profile/stage work must not look like “empty day” if contact tiles are zero.  

---

## 13. Tests to write

- Student Call note create increments `spoke_to_students` unique; second Call same student same day does not.  
- Student In-Person increments `met_students` only.  
- Partner Call note increments `spoke_to_colleges` only (not student spoke-to).  
- `is_action = 1`, `task_group = Call` does **not** increment spoke-to.  
- Matching `activities_logs` `added a note` does not double student contact.  
- `updated` Call note does not increment contact.  
- First-ever student In-Person counts as `new_to_firm`; second staff’s later Call is new-to-staff but not new-to-firm.  
- Stage row in `application_activities_logs` with staff `user_id` appears in applications progressed.  
- Timezone: event at 23:30 Melbourne counts on that calendar day.  
- Student with owner A and assignee B: Call by B increments only B’s spoke-to; both have student in caseload.  
- Application owned by C, Call on student by A: stage move by C credits C only; student contact credits A.  
- Student-from-partner Call-title note: college spoke-to increments; student spoke-to does not (unless separate student Call note).  
- Open action assigned to A on a student owned by B: only A’s open-action count increases.  
- Application in a `waiting` stage counts in waiting caseload for owner; a Call note still credits only the author.  
- Stage classified `unknown` is not lumped into waiting.  
- Students without an application are not “waiting”.  
- Grant-only access does not increase caseload.  
- Allocated student with last qualifying work by this staff 10 days ago is **quiet**, not inactive; 14 days ago is **inactive**.  
- Colleague’s note on the same student does **not** reset this staff’s quiet/inactive clock.  
- Call not picked alone does **not** count as qualifying work for quiet/inactive or as spoke-to.  
- Profile update / stage move with zero Call notes still increments throughput (worked on / stages), not spoke-to.  

---

## 14. Summary for implementers

The **Agreed product brief** and **Reading the day** sections are the source of truth. In one line:

**Hours** = existing login/session stats as a **top strip**, not a score. **Contact** = created Call / In-Person notes on **students** and **colleges** separately (**who logged it**). Empty contact on a busy profile/stage/call-not-picked day is expected — keep throughput visible. **Worked on** = unique students + applications + colleges from staff writes (`notes`, `activities_logs`, `application_activities_logs`, emails, SMS, docs, money). **Open work** = student allocation (`user_id` + `assignee`) + owned applications split **active / waiting / closed** by `workflow_stages.workload_class` (when set) + **quiet (7–13d) / inactive (14+d)** by **this staff’s** last qualifying work + actions assigned to that person. **My day** = always the logged-in user, same tiles regardless of CRM role. **No Team today.** **Phase 2** = days in stage, unstuck moves, due today, visit-without-note, self vs last week. **Not in v1** = call-duration timers, follow-up/check-in as contact, login-hours ranking, ghost credit, ranking colleagues, treating waiting as failure, guessing leave, migration matter roles.
