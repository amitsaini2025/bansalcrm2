# Slow pages (server HTML)

Measured 20 Aug 2026 on local Postgres (`bansalcrm_local3`), logged in as super admin. Time is **server HTML TTFB** (not browser CSS/JS).

**Standard used:** Google “good” TTFB is **≤ 800 ms**. Pages above that are listed. Browser load will be slower still.

**Scope:** 126 HTML pages. AJAX, export, download, and delete URLs were skipped. Client detail could not be timed (no `clients` row in this DB).

Recently modified clients was remeasured **21 Aug 2026** after dropping the four storage-split counts: **~0.7 s** (meets Google ≤ 800 ms; was **15.7 s**, then **1.4 s**).

Actions completed was remeasured **21 Aug 2026** after one grouped type-count and numbered pagination from that total: **~0.3 s** (meets Google ≤ 800 ms; was **5.1 s**).

---

## Critical (> 4 s)

| Page | URL | Time |
|---|---|---|
| Ongoing sheet | `/clients/sheets/ongoing` | **7.2 s** |
| COE enrolled sheet | `/clients/sheets/coe-enrolled` | **6.5 s** |
| Audit logs | `/audit-logs` | **6.3 s** (HTML ~19 MB) |
| Discontinue sheet | `/clients/sheets/discontinue` | **6.1 s** |
| Checklist sheet | `/clients/sheets/checklist` | **5.9 s** |
| Refund sheet | `/clients/sheets/refund` | **5.9 s** |
| Partners | `/partners` | **4.3 s** |

## Poor (2.5–4 s)

| Page | URL | Time |
|---|---|---|
| Partners inactive | `/partners-inactive` | 4.0 s |
| Actions assigned by me | `/action/assigned-by-me` | 3.3 s |
| Signatures | `/signatures` | 3.1 s |
| Sheets insights | `/clients/sheets/insights` | 2.5 s |

## Slow (0.8–2.5 s)

| Page | URL | Time |
|---|---|---|
| Actions | `/action` | 2.4 s |
| Dashboard | `/dashboard` | 2.2 s |
| Clients list | `/clients` | 2.2 s |
| Visa expiry report | `/reports/visaexpires` | 1.9 s (HTML ~4 MB) |
| Signatures create | `/signatures/create` | 1.7 s |
| Staff active | `/staff/active` | 1.6 s |
| Applications finalize | `/applications-finalize` | 1.3 s |
| Leads create | `/leads/create` | 1.2 s |
| Partners create | `/partners/create` | 1.0 s |
| Elite emails | `/emails/elite` | 1.0 s |
| Archived clients | `/archived` | 1.0 s |
| All notifications | `/all-notifications` | 0.9 s |
| Follow-ups | `/followups` | 0.9 s |
| Leads list | `/leads` | 0.9 s |
| Invoice edit | `/invoice/edit/{id}` | 0.8 s |

---

Most other pages were under 800 ms (login, admin console masters, office visits, many reports, SMS, workflows).
