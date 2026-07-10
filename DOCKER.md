Docker (singkat) — pesupeluh

Build image:

```bash
docker build -t pesupeluh:latest -f Dockerfile .
```

Run container (PHP-FPM saja):

```bash
docker run --rm -p 9000:9000 --name pesupeluh_app \
  -v "$PWD":/var/www/html \
  -e APP_ENV=production \
  pesupeluh:latest
```

Run with Docker Compose:

```bash
docker compose up -d
```

Then open:

```bash
http://localhost:8081
```

Notes:
- `docker-compose.yml` starts `app` and `nginx` together.
- Nginx uses `docker/nginx.conf` and serves `public/` on port `8081`.
- If you need a custom `.env`, copy `.env.example` to `.env` before building.
