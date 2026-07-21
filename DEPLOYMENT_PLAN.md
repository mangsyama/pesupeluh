# Deployment Plan – pesupeluh Docker Stack

## Overview
This document outlines the exact steps to build and launch the five‑container stack (**PHP 8.3‑FPM**, **Nginx**, **Redis**, **Laravel Reverb**, **Queue Worker**) using the `docker-compose.yml` already present in the project root. All containers are given explicit, easy‑to‑read names:
- `pesupeluh-php`
- `pesupeluh-nginx`
- `pesupeluh-redis`
- `pesupeluh-reverb`
- `pesupeluh-queue-worker`

The plan assumes a Windows host with Docker Desktop (Docker Engine ≥ 20.10) installed.

---

## 1️⃣ Prerequisites
1. **Docker Engine & Compose**
   ```powershell
   docker --version
   docker compose version
   ```
2. **Free host ports**: `80` (HTTP) and `6379` (Redis).
3. **Project root**
   ```powershell
   cd C:\project\pesupeluh
   ```
4. (Optional) Create a `.env` file with any secret key/value pairs that the PHP container should consume.

---

## 2️⃣ Validate the Compose File
```powershell
docker compose config   # prints the fully‑resolved config
```
If the command exits without error, the YAML is syntactically correct.

---

## 3️⃣ Build the Custom PHP Image
```powershell
docker compose build php
```
- Docker reads `docker/php8.3-sqlsrv/Dockerfile` and builds the image.
- On success you will see `Successfully tagged pesupeluh-php:latest`.

---

## 4️⃣ Launch the Stack
```powershell
docker compose up -d
```
Docker will:
1. Create a default network (e.g., `pesupeluh_default`).
2. Start the **php** container first.
3. Start **nginx** (depends_on php) with the custom config `nginx/pesupeluh.conf`.
4. Start **redis**.
5. Start **reverb** (WebSocket server) and **queue-worker** containers automatically.

---

## 5️⃣ Verify the Deployment
| Check | Command | Expected result |
|-------|---------|-----------------|
| Containers up | `docker compose ps` | All five show `State: Up` |
| PHP version | `docker exec pesupeluh-php php -v` | `PHP 8.3.x` |
| Nginx serving | Open `http://localhost:8081` in a browser | Your Laravel app (or default Nginx page) |
| Redis health | `docker exec pesupeluh-redis redis-cli ping` | `PONG` |
| Reverb logs | `docker compose logs reverb` | Shows "Starting server on 0.0.0.0:8080" |
| Worker logs | `docker compose logs queue-worker` | Shows worker status / Listening for jobs |
| Live logs | `docker compose logs -f` | Streams logs; look for "ready for connections" (Redis) and "PHP-FPM started" |

---

## 6️⃣ Optional Enhancements
- **Environment variables** – add under the `php:` service:
  ```yaml
  env_file:
    - ./.env
  ```
- **Database service** – include a MySQL/PostgreSQL container and add it to the `depends_on` list.
- **Healthchecks** – define `healthcheck:` blocks for PHP and Redis to enable automatic restarts.

---

## 7️⃣ Clean‑up / Re‑deploy
- Stop & remove containers: `docker compose down`
- Remove built images (force fresh rebuild): `docker compose down --rmi all`
- Prune dangling Docker objects: `docker system prune -f`

---

## 8️⃣ One‑liner for quick deployment
```powershell
cd C:\project\pesupeluh && docker compose build php && docker compose up -d
```
Running this line builds the PHP image (if needed) and brings the entire stack up in detached mode.

---

*End of plan.*
