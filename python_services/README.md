# Unified Python Services

FastAPI microservice used by BansalCRM2 for:

- **PDF** — convert, merge, validate, normalize, signatures, page images
- **Email** — parse `.msg`, analyze, render HTML, parse→render PDF
- **Documents** — DOCX/DOC → PDF (LibreOffice or `docx2pdf`)

Default listen address: **`127.0.0.1:5001`**

## Layout

```
python_services/
├── main.py                      # FastAPI app
├── config.py                    # Env-based settings
├── requirements.txt
├── start_services.py            # Cross-platform starter
├── start_services.sh            # Linux starter
├── install_service_linux.sh     # systemd installer
├── migration-python-services.service.template
├── Dockerfile / docker-compose.yml
├── services/
│   ├── pdf_service.py
│   ├── email_parser_service.py
│   ├── email_analyzer_service.py
│   ├── email_renderer_service.py
│   └── docx_converter_service.py
└── utils/
    ├── logger.py
    ├── validators.py
    └── datetime_format.py
```

## Install

### Windows

```bash
cd C:\xampp\htdocs\bansalcrm2\python_services
pip install -r requirements.txt
python start_services.py
# or:
python main.py --host 127.0.0.1 --port 5001
```

### Linux

```bash
cd /var/www/bansalcrm2/python_services   # adjust path as needed
python3 -m pip install -r requirements.txt
chmod +x start_services.sh
./start_services.sh
```

Production (systemd):

```bash
sudo ./install_service_linux.sh
sudo systemctl status migration-python-services
```

### Docker

```bash
docker compose up -d
# or: docker build -t bansalcrm-python-services . && docker run -d -p 5001:5001 ...
```

## Laravel `.env`

```env
PYTHON_SERVICE_URL=http://127.0.0.1:5001
```

Laravel callers default to that URL (e.g. email V2 upload, Python email parser, public documents).

## Configuration

Optional env vars (see `config.py`):

| Variable | Default | Notes |
| --- | --- | --- |
| `SERVICE_HOST` | `127.0.0.1` | Bind host |
| `SERVICE_PORT` | `5001` | Bind port |
| `DEBUG` | `False` | Enables reload when true |
| `MAX_FILE_SIZE_MB` | `30` | General upload limit |
| `ALLOWED_PDF_SIZE_MB` | `50` | PDF size limit |
| `LIBREOFFICE_PATH` | (auto) | Path to LibreOffice binary |
| `DOCX_CONVERTER_METHOD` | `auto` | `auto`, `libreoffice`, `docx2pdf`, `disabled` |
| `LOG_LEVEL` | `INFO` | Logging |

## API

Interactive docs when running: `http://127.0.0.1:5001/docs`

### Health

```http
GET /
GET /health
```

### PDF

```http
POST /pdf/convert-to-images
POST /pdf/merge
POST /convert_page
POST /pdf_info
POST /validate_pdf
POST /normalize_pdf
POST /add_signatures
POST /batch_convert
POST /convert
POST /convert-json
```

### Email

```http
POST /email/parse
POST /email/analyze
POST /email/render
POST /email/parse-render-pdf
POST /email/parse-analyze-render
```

### Example (Laravel)

```php
use Illuminate\Support\Facades\Http;

$base = rtrim(env('PYTHON_SERVICE_URL', 'http://127.0.0.1:5001'), '/');

$response = Http::timeout(120)
    ->attach('file', file_get_contents($msgPath), 'email.msg')
    ->post($base.'/email/parse');

$emailData = $response->json();
```

## Smoke test

```bash
curl http://127.0.0.1:5001/health
python test_service.py   # if present
```

Logs: `python_services/logs/` (created at runtime).

## Troubleshooting

1. Confirm the process is listening: `curl http://127.0.0.1:5001/health`
2. Install deps: `pip install -r requirements.txt`
3. For DOCX→PDF, install LibreOffice or ensure `docx2pdf` works on Windows
4. For PDF→images, install poppler and ensure it is on `PATH`
5. Check Laravel `PYTHON_SERVICE_URL` matches the bind port (**5001**, not 5000)

## License

Internal use — BansalCRM2 / Migration Manager stack.
