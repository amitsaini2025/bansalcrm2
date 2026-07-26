# BansalCRM2

Internal CRM for Bansal Immigration / Education — leads, clients, partners, documents, emails, invoices, tasks, and Education Elite inbox.

## Stack

| Layer | Versions |
| --- | --- |
| PHP / Laravel | PHP **8.3+**, Laravel **13** |
| Frontend | Vite **8**, Bootstrap **5**, Node **≥22**, npm **≥11** |
| Python services | FastAPI on **127.0.0.1:5001** (PDF, `.msg` email parse/render, DOCX→PDF) |
| Storage / mail | AWS S3, AWS SES, Twilio / Cellcast SMS |

## Requirements

- PHP 8.3+ with common Laravel extensions (mbstring, openssl, pdo_mysql, tokenizer, xml, curl, gd/imagick, zip)
- Composer 2
- MySQL / MariaDB
- Node.js ≥22 and npm ≥11
- Python 3.7+ (for `python_services/`)
- Optional: LibreOffice (DOCX→PDF), poppler (`pdf2image`)

Typical local layout (XAMPP): `C:\xampp\htdocs\bansalcrm2`

## Quick start

```bash
# PHP deps
composer install

# Env (copy from a teammate or existing .env — there is no public .env.example in this repo)
# Then set APP_KEY if needed:
php artisan key:generate

# Database
php artisan migrate

# Frontend
npm ci
npm run sync-fontawesome
npm run build
# or for local HMR:
npm run dev

# Storage link (if not already linked)
php artisan storage:link
```

Serve via Apache/XAMPP pointing at `public/`, or:

```bash
php artisan serve
```

### Python services (email V2 / PDF)

Required for `.msg` upload, email rendering, and some document flows.

```bash
cd python_services
pip install -r requirements.txt
python start_services.py
# or: python main.py --host 127.0.0.1 --port 5001
```

In Laravel `.env`:

```env
PYTHON_SERVICE_URL=http://127.0.0.1:5001
```

Health check: `http://127.0.0.1:5001/health`  
Full docs: [python_services/README.md](python_services/README.md)

## Useful commands

```bash
npm run build              # production assets
npm run build:prod         # ci + fontawesome sync + build
npm run audit:legacy-js    # legacy public JS audit
php artisan test           # PHPUnit
```

Git helpers (when chat git fails): see [scripts/README.md](scripts/README.md) and [.cursor/GIT-FROM-CHAT-SETUP.md](.cursor/GIT-FROM-CHAT-SETUP.md).

## Project layout

```
app/                 Laravel application (CRM, Elite, AdminConsole, Auth)
config/              App + CRM feature config (see config/crm.php)
database/migrations  Schema migrations
docs/                Internal plans / migration notes
public/              Web root
python_services/     Unified FastAPI microservice
resources/           Blade views, Sass, Vite JS entrypoints
routes/              HTTP routes
scripts/             Dev / migration / git helper scripts
```

## Related docs

- [docs/](docs/) — migration and feature notes
- [SES_EMAIL_MIGRATION.md](SES_EMAIL_MIGRATION.md) / [SES_USAGE_FRONTEND_BACKEND.md](SES_USAGE_FRONTEND_BACKEND.md) — SES mail
- [CRM_EMAIL_S3_IMPLEMENTATION.md](CRM_EMAIL_S3_IMPLEMENTATION.md) — inbound email + S3

## License

Internal use — Bansal Education / Immigration.
