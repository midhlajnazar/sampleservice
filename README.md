# SampleService

A demo government-style service portal. Applicants apply for services (trade license renewal, driving license renewal, good conduct certificate), upload documents, pay through a dummy card gateway, and track their application. Admins review, request more information, approve or reject from a back office. Everything is also available over a token-authenticated REST API.

**Stack:** Laravel 13 · Inertia v3 · Vue 3 + TypeScript · Tailwind v4 (shadcn-vue) · Fortify auth · Sanctum API tokens · SQLite

## Getting started

```bash
composer install
npm install
cp .env.example .env   # if needed
php artisan key:generate
php artisan migrate:fresh --seed
npm run build          # or: npm run dev
php artisan serve
```

Open http://127.0.0.1:8000.

### Demo accounts (password: `password`)

| Email               | Role                                                      |
| ------------------- | --------------------------------------------------------- |
| `admin@example.com` | Admin (back office at `/admin`)                           |
| `test@example.com`  | Applicant, with 5 sample applications in different states |

Email verification is disabled. The UI is light theme only.

## What's included

### Applicant

- **Service catalogue** with eligibility, required documents, fees and processing time.
- **Multi-step application wizard** generated from each service's JSON schema:
  text, email, phone, number, date, textarea, **dropdowns**, radio cards, checkbox groups and a consent checkbox.
    - **Conditional fields** (`show_if`), for example "Ejari number" only for physical offices, or delivery address only for courier delivery.
    - **Document uploads** (Emirates ID front/back, passport, visa, Ejari, MoA, photo…) with drag and drop, type and size checks, and conditional requirements (the visa copy is only asked for residents).
    - **Live fee calculator**: options can carry fees (renewal years, express processing, courier), plus VAT.
    - Review step, then submit.
- **Dummy payment gateway ("SamplePay")** with a hosted-style checkout, failed attempts and retries.
- Application page with progress tracker, history timeline, payments, documents (view/download).
- **Respond to "information requested"** with a comment and replacement documents (old versions are kept as _superseded_).
- Cancel unpaid applications. Printable **receipt** and **certificate** pages.
- **API tokens** page (Settings → API tokens): create scoped tokens with expiry, copy once, revoke.

### Admin back office (`/admin`)

- Dashboard: queue counts, revenue/refunds, breakdown by status and service, oldest open requests.
- Request list with search (reference/name/email) and status/service filters.
- Request detail: answers, applicant, documents with **accept/reject per document**, payments, internal notes, full audit trail.
- Workflow actions: **Start review → Request info / Approve / Reject → Mark completed**. Rejecting refunds the payment; approving issues a certificate number and validity date (based on the chosen renewal period).
- Manage services: edit name, fees, VAT, processing days, form/document JSON schemas; take a service offline.
- Payments ledger.

### Workflow

```
pending_payment ──pay──▶ submitted ──▶ under_review ──▶ approved ──▶ completed
      │                     │              │  ▲
   cancel                   │              ▼  │ applicant responds
      ▼                     │        info_requested
  cancelled                 └──────────┴──────────▶ rejected (payment refunded)
```

All state changes go through `App\Services\ServiceRequestManager`, shared by the web and API controllers. Transition rules live in `App\Enums\RequestStatus::adminTransitions()`.

## Dummy payment gateway

`App\Services\Payments\DummyPaymentGateway`. Use any future expiry and any CVV.

| Card number           | Result                |
| --------------------- | --------------------- |
| `4242 4242 4242 4242` | Approved (Visa)       |
| `5555 5555 5555 4444` | Approved (Mastercard) |
| `4000 0000 0000 0002` | Declined              |
| `4000 0000 0000 9995` | Insufficient funds    |
| `4000 0000 0000 0069` | Expired card          |

Any other Luhn-valid number is approved. Only the brand and last 4 digits are stored.

## Adding a new service

No code needed: add a row to `services` (see `database/seeders/ServiceSeeder.php`) or edit one under **Admin → Manage services**. Validation (`App\Services\ServiceFormValidator`), fees (`App\Services\FeeCalculator`) and the wizard UI are all driven by the schema.

```jsonc
// form_schema: sections of fields
[{ "title": "Details", "fields": [
  { "name": "renewal_years", "label": "Renewal period", "type": "select", "required": true,
    "options": [{ "value": "1", "label": "1 year" }, { "value": "2", "label": "2 years", "fee": 1350 }] },
  { "name": "ejari_number", "label": "Ejari number", "type": "text", "required": true,
    "show_if": { "field": "office_type", "value": "physical" } }
]}]

// document_schema
[{ "key": "visa_copy", "label": "Residence visa copy", "required": true,
   "mimes": ["pdf", "jpg", "png"], "max_kb": 4096,
   "show_if": { "field": "residency_status", "value": "resident" } }]
```

## REST API (v1)

Base URL: `/api/v1`. Send `Accept: application/json` and `Authorization: Bearer <token>`.

### Getting a token

- In the UI: **Settings → API tokens**, or
- `POST /api/v1/auth/token` with `email`, `password`, `device_name`, optional `abilities[]`.

Abilities: `services:read`, `requests:read`, `requests:write`, `payments:write`, `admin` (admins only).

### Endpoints

| Method | Path                                     | Ability        | Description                                                                             |
| ------ | ---------------------------------------- | -------------- | --------------------------------------------------------------------------------------- |
| POST   | `/auth/token`                            | public         | Issue a token                                                                           |
| DELETE | `/auth/token`                            | any            | Revoke the current token                                                                |
| GET    | `/user`                                  | any            | Current user                                                                            |
| GET    | `/services`                              | public         | List active services                                                                    |
| GET    | `/services/{slug}`                       | public         | Service with form & document schema                                                     |
| POST   | `/services/{slug}/quote`                 | public         | Fee quote for `fields[...]`                                                             |
| GET    | `/requests`                              | requests:read  | Your applications (`?status=`, `?per_page=`)                                            |
| GET    | `/requests/{reference}`                  | requests:read  | Application detail                                                                      |
| GET    | `/documents/{id}`                        | requests:read  | Download an uploaded document                                                           |
| POST   | `/requests`                              | requests:write | Create an application (multipart)                                                       |
| POST   | `/requests/{reference}/respond`          | requests:write | Reply to an info request (`comment`, `documents[...]`)                                  |
| POST   | `/requests/{reference}/cancel`           | requests:write | Cancel an unpaid application                                                            |
| POST   | `/requests/{reference}/pay`              | payments:write | Pay (`card_holder`, `card_number`, `expiry` MM/YY, `cvv`). 201 if paid, 402 if declined |
| GET    | `/admin/requests`                        | admin          | All applications (`?status=`, `?service=`)                                              |
| GET    | `/admin/requests/{reference}`            | admin          | Detail + `allowed_transitions`                                                          |
| POST   | `/admin/requests/{reference}/transition` | admin          | `status`, `comment` (required for info_requested/rejected)                              |

### Example: apply and pay

```bash
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/v1/auth/token \
  -H "Accept: application/json" \
  -d email=test@example.com -d password=password -d device_name=cli | jq -r .token)

curl -X POST http://127.0.0.1:8000/api/v1/requests \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -F service=good-conduct-certificate \
  -F "fields[purpose]=employment" -F "fields[language]=en" -F "fields[copies]=1" \
  -F "fields[residency_status]=resident" -F "fields[declaration]=1" \
  -F "documents[emirates_id_front]=@eid-front.pdf" \
  -F "documents[emirates_id_back]=@eid-back.pdf" \
  -F "documents[passport_copy]=@passport.pdf" \
  -F "documents[visa_copy]=@visa.pdf" \
  -F "documents[personal_photo]=@photo.jpg"

curl -X POST http://127.0.0.1:8000/api/v1/requests/SR-XXXXXXXX-XXXXX/pay \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -d card_holder="TEST USER" -d card_number=4242424242424242 -d expiry=12/30 -d cvv=123
```

Booleans in multipart bodies are sent as `1` / `0`.

## Notifications

Applicants get an email on submission and on every status change (`App\Notifications\ServiceRequestUpdated`). With `MAIL_MAILER=log` these land in `storage/logs/laravel.log`.

## Tests & quality

```bash
php artisan test            # feature tests incl. the full workflow and API
npm run types:check         # vue-tsc
npm run check               # lint + format
vendor/bin/phpstan analyse
```

Uploaded files are stored on the private `local` disk (`storage/app/private/service-requests/{reference}`) and are served only to the owner or admins.
