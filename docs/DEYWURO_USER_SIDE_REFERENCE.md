# Deywuro SMS Platform — User-Side Reference

**Purpose of this document.** You are building a separate *SMS Request & Campaign Management Platform* where clients submit campaign requests, get invoiced, pay, and then **you** log into Deywuro and send the campaign manually on their behalf. Deywuro is not integrated into your platform.

That makes this document a **specification of the target system**. Every field your request form collects, every validation rule you enforce, and every status you show the client should be derived from what Deywuro will actually accept when you re-key the campaign. If your portal accepts a request that Deywuro will reject, you find out *after* the client has already paid.

Scope: the client-facing experience only (login → sender ID → compose → cost → send → reports). Admin, reseller, email, voice, and social features are out of scope.

---

## 1. The core idea: what a campaign actually consists of

Stripped of UI, one Deywuro campaign is five pieces of data:

| Piece | Deywuro field | Constraint |
|---|---|---|
| Who it appears to be from | `sender_id` | Max 11 characters, **must be pre-approved** |
| Who receives it | recipient list | File upload or comma-separated numbers |
| What it says | `message_content` | Max 1528 chars in the UI textarea |
| When to send | `schedule_date` | Blank = send now; past date = now + 1 min |
| Campaign label | `name` | Required for bulk; free text |

Plus three toggles: **language type** (`text` / `unicode`), **message type** (`text` / `flash`), and **remove duplicates**.

**This is your minimum request payload.** If your portal captures exactly these, an operator can fulfil any request without going back to the client for clarification. Everything else in this document is about the rules that govern these five fields.

---

## 2. Account, access, and balance

**Login.** `GET|POST /login` — username + password (`LoginController`). Google/Facebook social login exists. Clients are role `user`; resellers are role `reseller` (role_id 3) and get a separate login and their own client sub-accounts.

**Registration.** Self-service company registration at `/register` with OTP verification by phone (`Client\CompanyController`). New accounts are `must_change_password = 1` on first login.

**Balance.** Every account has one row in `credits`:

- `new_bal` — current spendable balance (in the account's currency)
- `price` — the account's default per-SMS price
- `foreign_price` — price for international routes
- `credit_used` — running total consumed

Balance is shown on the dashboard and re-checked at every send. **All sending is prepaid** — when `new_bal` runs out, sending stops with *"Sorry, you don't have enough credit to proceed!"* There is no postpaid/credit-limit mode anywhere in the platform, despite `users.payment_status` and `companies.client_type` existing in the schema (both are unused/reporting-only).

**Top-up.** Clients buy credit with Mobile Money in-app: `/new-purchase` → `BuyCredit` → OTP → `credit/purchase/status`, handled by `CreditPurchaseService` with statuses `PENDING` / `SUCCESS` / `FAILED` / `CANCELLED`.

> **Implication for your portal:** your invoice/payment cycle replaces this top-up flow for your clients. But *your* Deywuro account still needs enough credit to actually send. Your platform should track your own Deywuro float, or you will collect payment for a campaign you cannot dispatch. Worth surfacing on your admin dashboard alongside each approved-but-unsent campaign.

---

## 3. Sender IDs — the single biggest source of delay

The sender ID is the alphanumeric name the recipient sees (e.g. `SynlabGhana`, `GCB Bank`). It is **not** free text at send time: the dropdown on every send form is populated only from sender IDs belonging to the user **with `status = 1`** (approved).

```php
'sender_ids' => UserSenderId::query()->where([['user_id', Auth::user()->id], ['status', 1]])->get()
```

### Lifecycle

1. Client requests a sender ID from their profile page (`DashboardController@sender_id_submit`).
2. Validation: `required|max:11`, optional supporting document (`pdf,doc,docx,jpg,jpeg,png`, max 5 MB). The client may instead tick *"uses company letterhead"* if their company profile already has an approved document on file.
3. Row created in `user_sender_ids` with `status = 0`. Ops are notified by SMS and email.
4. An administrator approves or rejects, setting `status` and an optional `comment`.
5. Only then does it appear in send dropdowns.

Editing an approved sender ID **resets it to unapproved** (`status = false`, review fields cleared) and it must be re-approved. Sender IDs are unique across the `user_sender_ids` table.

### Rules that matter

- **11 characters maximum.** This is an SMPP/GSM alphanumeric originator limit, not a Deywuro preference. It cannot be worked around.
- Approval is **manual and human**, typically taking hours to days, and MNOs may require documentation proving the client owns the brand.
- Some sender IDs are rewritten at the gateway. For example the live gateway maps `GCBMobile`, `GCB BANK`, `GCBATMALERT` and several others all to `GCB Bank`, and `STANBICGH`/`STANBIC` to `Stanbic`.

> **Implication for your portal:** make sender ID a **first-class registered object per client, not a free-text field on the campaign form.** Client onboarding should include "register your sender ID" with document upload, and you shepherd it through Deywuro approval once. Then campaign requests pick from an approved list. If you instead let clients type a sender ID per campaign, you will regularly take payment for campaigns that cannot go out for days. Validate `≤ 11 chars` at the point of registration and show approval state (`Pending` / `Approved` / `Rejected + reason`) on the client dashboard.

---

## 4. The five ways a user sends SMS

All send flows are multi-step wizards holding state in the session: **Step 1** compose → **Step 2** confirm (personalised bulk has a third step for placeholder mapping). Nothing is dispatched until the confirm step is submitted.

| Mode | Route | Recipients from | Notes |
|---|---|---|---|
| **Single / quick SMS** | `/send-single-sms` | Comma-separated numbers typed in | Sends immediately (or scheduled); best for a handful of numbers |
| **Bulk SMS** | *(via ESME panel)* `/send-bulk-sms` | Uploaded file | The workhorse for campaigns |
| **Personalised bulk** | `/send-personalised-bulk-sms` | Uploaded file with headers | Merge fields per recipient |
| **Group SMS** | `/send-group-sms` | Saved contact groups | Recipients staged into `tmp_group_sms` |
| **Personalised group** | `/send-personalised-group-sms` | Saved contact groups | Merge fields + groups |
| *Targeted SMS* | `/send-targeted-sms` | Typed numbers | Campaign-named variant of single |

Each mode is permission-gated (`send-sms-single`, `send-sms-bulk`, `send-sms-group`, `send-sms-bulk-perso`, `send-sms-group-perso`), so a given account may not have all of them.

### 4a. Single SMS

Validation (`SmsController@post_send_single_sms`):

```php
'phone_numbers'   => 'required',          // comma-separated
'sender_id'       => 'required|max:11',
'flash'           => 'required|max:11',   // 'text' or 'flash'
'schedule_date'   => 'present',           // may be empty
'message_content' => 'required',
'image_upload'    => 'file|mimes:jpeg,jpg,png,gif',
```

Cost is computed **per recipient by network** (see §6), so a mixed MTN/Telecel list prices correctly.

### 4b. Bulk SMS

Validation (`SmsController@post_send_bulk_sms`):

```php
'file_upload'     => 'required|mimes:csv,txt,xlsx,xls',
'sender_id'       => 'required|max:11',
'schedule_date'   => 'present',
'message_content' => 'required',
'name'            => 'required',          // campaign name
'image_upload'    => 'file|mimes:jpeg,jpg,png,gif',
```

The file is moved to `storage/bulk_sms/`, recipients are counted without loading the whole file into memory, and the campaign is saved as a `UserJob` row (`type = 'bulk'`, `status = 'pending'`) picked up asynchronously by the `SpeedyBulkSms` console command. The client sees *"Your contacts were successfully uploaded!"* — **submission is not delivery.**

Two side effects to be aware of: an internal **blast alert SMS** goes to ops contacts (`BLAST_ALERT_CONTACT`) for every bulk job, and a support **ticket is auto-created**. Volumes at or above `BLAST_ALERT` (default 100) trigger an extra alert.

A couple of accounts (`SCBULK`, `GCBCARDS`) are hard-coded to land in `Awaiting approval` instead of `pending`, requiring an internal approver before anything sends.

### 4c. Personalised bulk

Three steps: upload file → **map placeholders** → confirm. The uploaded file's header row becomes clickable buttons; clicking one inserts a placeholder into the message.

**Placeholder syntax is square brackets around the exact column header**, replaced case-insensitively per row:

```php
$retValue = str_ireplace('['.$header.']', $value, $retValue);
```

So with headers `PHONE, FIRSTNAME, BALANCE`, a message reads:

```
Dear [FIRSTNAME], your balance is GHS [BALANCE]. Thank you.
```

Missing or empty cells are replaced with an empty string — the message still sends, just with a gap. Step 2 renders a live preview using the **first data row**.

> **Implication for your portal:** if you offer personalised campaigns, collect the file *and* the template with `[HEADER]` placeholders, and validate that every placeholder in the template matches a real column header in the uploaded file. This is the single most common way a personalised campaign goes out looking broken, and it is trivially preventable at request time. Also warn that personalisation makes per-message length variable — a long name can push some recipients into an extra billable segment.

### 4d. Group SMS

Sends to saved contact groups from the client's address book. Selected groups' contacts are copied into a `tmp_group_sms` staging table where individual numbers can be reviewed and deleted before confirming. Extra ad-hoc numbers can be appended.

Since your portal owns the client relationship, you will likely keep contact lists on your side and always upload a file into Deywuro's **bulk** flow rather than maintaining groups inside Deywuro.

---

## 5. Recipient list rules

### Accepted formats

`.csv`, `.txt`, `.xlsx`, `.xls`. A sample template is offered for download in the UI (`public/sample/BULK_SMS_EXCEL_TEMPLATE.xlsx`), and the form carries this guidance:

- Prefer CSV upload (recommended)
- If using Excel, format the phone column as Text before saving
- Download the sample file and follow that format

The Excel-as-Text warning is not cosmetic: spreadsheets silently convert `0244304528` to the number `244304528`, or to scientific notation on long international numbers, corrupting the list before it ever reaches Deywuro.

### Number normalisation

`SmsTools::format_gh_number()` normalises Ghanaian numbers to international format without `+`:

| Input | Becomes |
|---|---|
| `0244304528` (10 digits, leading 0) | `233244304528` |
| `244304528` (9 digits, no leading 0) | `233244304528` |
| `233244304528` | unchanged |

Non-digits are stripped (`preg_replace('/\D/', '', ...)`). International numbers must already be in full international form.

### Length validation and rejection

The gateway validates length against the country code — for `233` it expects 9 digits after the code; other codes have their own expected lengths (`checkCode()`). A number failing this check is written to `rejected_sms`, logged with status `FAILED` and route `no_route`, and returns `1706|Invalid Destination`. **The client is not charged for rejected numbers**, but they also never receive the message.

Certain destinations are hard-blocked at the gateway (test numbers like `123`, `12345`, and a handful of specific MSISDNs), and some client + link-content combinations are blocked outright as fraud prevention (e.g. `GCBCARDS` messages containing `http` unless the URL is whitelisted; `G-MONEY` messages containing `https`).

### Duplicates

A **Remove Duplicate** checkbox (ticked by default) deduplicates the uploaded list.

> **Implication for your portal:** normalise and validate the uploaded list **at request time**, before invoicing. Show the client "18,432 valid, 61 invalid, 209 duplicates removed → 18,432 billable" and let them fix the file *before* they pay. Store the cleaned list as the canonical one you upload to Deywuro. This turns your platform's biggest genuine value-add — clients almost never get their lists right — into a visible feature, and it protects you from the awkward conversation where the invoice says 18,702 and the delivery report says 18,432.

---

## 6. Message length, segments, and cost

### Segment ("page") counting

Cost scales with the number of SMS segments. `SmsTools::no_pages()`:

| Characters | Pages billed |
|---|---|
| 1 – 160 | 1 |
| 161 – 306 | 2 |
| 307 – 459 | 3 |
| 460 – 621 | 4 |
| 622 – 766 | 5 |
| 767 – 919 | 6 |
| 920 – 1072 | 7 |
| 1073+ | 8 |

The UI textarea caps input at **1528 characters** and shows a live counter.

> **Gotcha — inconsistent ceilings.** `SmsTools::no_pages()` and `SpeedyPersoBulkSms` go up to 8 pages, but `SendSms.php` and `SpeedyBulkSms` cap at **5** (anything over 621 chars). A very long bulk message can therefore be quoted at one segment count and billed/sent at another. Keep your own quotes to the 8-tier table above and, more practically, **discourage messages over 621 characters** — they are expensive, they truncate awkwardly on some handsets, and they sit in the part of the code where the two implementations disagree.

### Cost formula

**Single SMS** — priced per recipient by that recipient's network:

```
cost = pages × Σ (price of each recipient's network)
```

**Bulk SMS** — priced at the account's flat rate:

```
cost = recipients × pages × credits.price
```

Price resolution for a number (`SmsTools::get_msisdn_network()`): match the MSISDN prefix against `networks`, then look for a client-specific override in `user_networks` (per user + network + country); fall back to the network's default `sms_price`; fall back finally to `credits.price`. International routes (`intergo`, `rsms`) use `credits.foreign_price`.

> **Gotcha:** bulk quoting uses the flat `credits.price` while single-SMS quoting uses per-network prices. If your client's account has per-network overrides, a bulk quote can differ from the true per-network cost. Also note the second balance check on bulk submit compares `recipients × price` **without multiplying by pages**, so a multi-page campaign can pass the final check with less credit than it truly needs.

> **Implication for your portal:** implement the cost estimator as `recipients × pages × your_client_rate`, computing `pages` with the 8-tier table above, and recompute it live as the client types the message. Show the segment count and the cost side by side — a client who can see that adding one more sentence costs them 40% more will edit the sentence. Your client-facing rate is your own commercial decision (your Deywuro cost is your floor), so keep a rate-per-client field rather than hard-coding one.

### Language type: text vs unicode

`text` (English/GSM) vs `unicode` (other languages/special characters). Unicode messages are submitted to the gateway with `charset=UTF-8&coding=2` (UCS-2). In real GSM terms UCS-2 fits only **70 characters per segment**, not 160 — but Deywuro's page counter uses the same character thresholds regardless of language type. **Unicode campaigns can therefore be materially under-quoted.** Flag this to any client sending non-English or emoji content, and treat a "unicode" request as needing manual cost review.

### Flash messages

`flash` displays on screen without being saved to the handset inbox. Selectable per campaign, generally reserved for OTPs and urgent alerts.

### Attaching an image

An image (`jpeg/jpg/png/gif`) can be attached. Deywuro **does not send MMS** — it stores the image, generates a short URL, and appends that URL to the message text. This lengthens the message and may add a billable segment. Some clients (notably `GCBCARDS`) are blocked from sending links at all.

### Banned-word filter

Message content is checked word-by-word against the `filters` table. A match blocks submission with *"The words X, Y are not permitted."* This list is admin-managed and not visible to clients, so a request can be rejected at send time for a word the client had no way to know about.

> **Implication for your portal:** you cannot mirror this list (it lives in Deywuro's admin), but you can handle it gracefully: give your admin a "reject with reason" action so a blocked campaign returns to the client as *"content not permitted — please revise"* with the offending word, and make sure a rejected campaign either refunds or credits the client's payment rather than sitting in limbo.

### Saved templates

Clients can save reusable message bodies (`SavedMessage`, the "Saved Messages" dropdown) via `POST /save-sms` and `POST /get-saved-sms`.

---

## 7. Scheduling

`schedule_date` is optional on every send form (a date-time picker).

- Empty → sends now (in practice `now() + 1 minute`).
- A past date → coerced to `now() + 1 minute`.
- A future date → the job's `sched_datetime`; the cron worker picks it up when due.

Scheduled campaigns exist as `UserJob` rows with `status = 'pending'` until dispatched.

> **Implication for your portal:** capture the client's requested send window as a first-class field, and make your internal SLA explicit — because *you* are the scheduler for anything time-sensitive. If a client requests "Friday 9am" and your operator only sees it Friday afternoon, the campaign is worthless. Consider a "requested send time" plus a "hard deadline / do not send after" field, and alert your admin when an approved-and-paid campaign is approaching its window.

---

## 8. What happens after the user clicks send

```
User confirms
   └─> UserJob row created (status: pending | Awaiting approval)
        └─> Cron worker (SpeedyBulkSms / SpeedyPersoBulkSms / SendSms job)
             └─> HTTP GET to Kannel:  /cgi-bin/sendsms?username=...&to=...&from=...&text=...&dlr-mask=...&dlr-url=...
                  └─> Row inserted into `logs` with status PENDING
                       └─> Kannel hands off to the MNO's SMSC
                            └─> DLR callback hits /api/dlr-receiver-route
                                 └─> `logs` row updated: status, delivery_date, msgid
```

### The `logs` table — your reconciliation source

One row per message per recipient. Columns written at submit time:

| Column | Meaning |
|---|---|
| `id` | Unique message ID (also passed to the gateway as `account`) |
| `job_id` | Groups all messages of one campaign |
| `user_id` / `username` | Sending account |
| `msisdn` | Recipient |
| `sender` | Sender ID shown to recipient |
| `sms_count` | Segments billed for this message |
| `network` | Detected network (MTN, TIGO, AIRTEL, GLO, …) |
| `message` | Final message text sent |
| `submit_date` | When handed to the gateway |
| `status` | `PENDING` initially |
| `response` | Raw gateway reply |
| `bpid` | Route/bind used (e.g. `mtnnpontu3`, `intergo`) |
| `delivery_date` | Filled by the DLR callback |
| `msgid` | Network message ID, filled by DLR |

### Statuses the client sees

| Status | Meaning |
|---|---|
| `PENDING` | Accepted by the gateway, no delivery receipt yet |
| `DELIVERED` | Confirmed delivered to the handset |
| `EXPIRED` | Network gave up (handset off / unreachable for the validity period) |
| `REJECTED` | Network refused it (often sender ID or content policy) |
| `FAILED` | Never left the platform (invalid destination, no route) |

### Gateway response codes in `logs.response`

| Code | Meaning |
|---|---|
| `1701` | Accepted for delivery — the success case |
| `1703` | Invalid credentials |
| `1704` | User authentication failed |
| `1705` | Destination number must be numeric |
| `1706` | Invalid destination |
| `0: Accepted for delivery` | Kannel accepted and is sending |
| `3: Queued for later delivery` | Kannel accepted but **has no live route right now** — queued. Clears when the SMSC link recovers; if it stays `PENDING` with `msgid` still NULL, the message never left the gateway. Usually an SMSC/route availability problem, not a client error. |

> **Implication for your portal:** design your client-facing status model as a **superset** of Deywuro's, because your workflow has stages Deywuro knows nothing about:
>
> `Draft → Submitted → Under review → Invoiced → Paid → Queued for dispatch → Sent to gateway → Delivered (with report) → Closed`, plus `Rejected` and `Cancelled`.
>
> Only the last three map onto Deywuro data. Never show a client a raw Deywuro status without translation — *"3: Queued for later delivery"* means nothing to them and sounds like a failure. Map it to something like *"With network operator — delivery in progress."*

---

## 9. Reports available to the client

These are what you will pull to give your clients delivery reports:

| Report | Route |
|---|---|
| SMS log, last 24 h | `/report/sms-log-24h` |
| Delivered, last 24 h | `/report/report_get_sms_delivered_log_24h` |
| Undelivered, last 24 h | `/report/report_get_sms_undelivered_log_24h` |
| Expired, last 24 h | `/report/report_get_sms_expired_log_24h` |
| **Custom date range** | `/report/specify-period` |
| Export to Excel / CSV | `POST /report/specify-period/export-excel` / `export-csv` |
| Async report builder (queued, downloadable) | `/report/new-report` |
| Period comparison | `/report/comparison` |
| Credit history | `/credit-history` |
| Two-way / inbound | `/report/2way` |

The dashboard aggregates totals for submitted, delivered, expired, and rejected.

> **Implication for your portal:** the **date-range report with CSV/Excel export** is your delivery-report pipeline. Workflow: after sending, export the period covering the campaign, filter by `job_id` or by sender ID + timestamp, and attach or import the result against the client's campaign record. Because you send on the client's behalf from your own account, **your Deywuro report contains every client's traffic mixed together** — so give each campaign a unique, traceable campaign `name` in Deywuro (e.g. `PORTAL-<request_id>-<client>`) and record the returned `job_id` on the request. Without that convention, splitting one export across many clients becomes guesswork. Summary counts (sent / delivered / expired / failed) are usually enough for the client dashboard; keep the raw CSV for disputes.

---

## 10. Operational gotchas worth designing around

1. **Sender ID approval is the critical path.** Register and approve sender IDs at client onboarding, never per campaign.
2. **Excel destroys phone numbers.** Push CSV, and validate every uploaded list yourself.
3. **Segment counting disagrees between code paths** above 621 characters. Quote from the 8-tier table and steer clients under 621.
4. **Unicode is under-counted** — real capacity is 70 chars/segment, so non-English campaigns need manual cost review.
5. **Images are not MMS** — a URL is appended, which lengthens the message and may add a segment.
6. **The banned-word filter is invisible to clients** — build a "rejected: content" path with a refund/credit action.
7. **"Uploaded successfully" ≠ delivered.** Never let your portal imply delivery at submission.
8. **Everything is prepaid.** Watch your own Deywuro float against your queue of paid-but-unsent campaigns.
9. **Bulk sends trigger internal ops alerts and tickets** — expect noise on large volumes; coordinate with Npontu ops before a very large blast.
10. **Rejected numbers are not billed but silently dropped** — reconcile invoiced volume against delivered volume, or clients will query the gap.

---

## 11. Optional: the HTTP API (for later automation)

You have said this build is manual, and that is the right call to start. But the option to automate later exists, so avoid design choices that would block it.

**Endpoint:** `POST https://deywuro.com/api/sms`

**Parameters:** `username`, `password`, `source` (sender ID), `destination` (comma-separated), `message`

**Response:** JSON, `{"code":0,"message":"1 sms sent!"}` on success. Errors mirror the gateway codes in §8 (401 for `1703` invalid credentials, etc.).

Two caveats if you ever switch it on:

- API authentication uses the bcrypt `users.password`, but the underlying gateway authenticates against a **plaintext `users.pass`** column. These fall out of sync, and passwords containing unusual characters can pass the API and still fail at the gateway. Use a simple alphanumeric password for any API account.
- Delivery reports still arrive via the DLR callback, so you would need a receiving endpoint to get per-message status.

> **Design for it now, cheaply:** keep the dispatch step behind a single internal service or interface (`dispatchCampaign(request)`) whose only implementation today is *"mark as sent manually, record the Deywuro `job_id`"*. Swapping in an API call later then touches one class rather than your whole workflow.

---

## 12. Suggested mapping: your portal → Deywuro

| Your platform | Deywuro equivalent | Notes |
|---|---|---|
| Client account | *(none — you use one account)* | Namespace campaign names per client |
| Registered sender ID | `user_sender_ids` (approved) | Pre-approve at onboarding; ≤ 11 chars |
| Campaign request | `UserJob` | Your `request_id` → Deywuro campaign `name` |
| Uploaded contact list | `file_upload` | Normalise/validate before invoicing |
| Message body | `message_content` | ≤ 1528 chars; count segments for pricing |
| Merge fields | `[HEADER]` placeholders | Validate against file headers |
| Requested send time | `schedule_date` | You are the scheduler — track your SLA |
| Cost estimate | `recipients × pages × rate` | Your rate; Deywuro cost is your floor |
| Invoice + MoMo payment | *(your own — replaces `/new-purchase`)* | Deywuro float is separate |
| Delivery report | `/report/specify-period` export | Filter by `job_id` / campaign name |
| Campaign status | `logs.status` + your own stages | Translate gateway text for clients |

---

## Appendix: key source files

| Area | File |
|---|---|
| All SMS send flows | `deywuro/app/Http/Controllers/SmsController.php` |
| Sender ID request / profile | `deywuro/app/Http/Controllers/DashboardController.php` |
| Pricing, segments, number formatting | `deywuro/app/Tools/SmsTools.php` |
| Bulk dispatch worker | `deywuro/app/Console/Commands/SpeedyBulkSms.php` |
| Personalised dispatch + `update_msg()` | `deywuro/app/Console/Commands/SpeedyPersoBulkSms.php` |
| Send forms (UI fields, help text) | `deywuro/resources/views/sms/*.blade.php` |
| User-facing routes | `deywuro/routes/web.php` |
| Live gateway: routing, auth, billing | `index.php` (deployed at `/var/www/html/bulksms/`) |
| Public API | `deywuro-api/app/Http/Controllers/SmsApiController.php` |
