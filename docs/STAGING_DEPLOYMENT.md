# Staging Server Setup & Deployment

This document describes how to provision a fresh **staging** server for the
Blog backend and how the staging CI/CD workflow operates against it.

## Architecture

| Piece | Detail |
| --- | --- |
| Host | AWS EC2 (Ubuntu 24.04) |
| Containers | `app` (PHP-FPM), `nginx`, `reverb` (WebSocket), `redis`, `mysql` |
| MySQL | **Local container** on the same server (no external DB, no SSL CA) |
| Image | `salehaldhaheri1010/blog-app:staging` (Docker Hub) |
| Domain | `staging-bknd.zenith-blog.blog` |
| Compose file | `docker-compose.local.yml` (deployed as `docker-compose.yml`) |
| Data | **Ephemeral** — wiped on every deploy (`docker compose down -v`) |

### Staging vs production

| | Staging | Production |
| --- | --- | --- |
| MySQL | container on same server | external Aiven (SSL) |
| `cert/ca.pem` (MySQL SSL CA) | **not needed** | required |
| DB data | wiped each deploy (`down -v`) | persists |
| Compose source | `docker-compose.local.yml` | `docker-compose.prod.yml` |
| Image | `blog-app:staging` | `blog-prod-app:production` |

---

## One-time provisioning

### 1. Launch the server

- EC2 **Ubuntu 24.04**, **minimum t3.small (2 GB RAM)**. A t3.micro (1 GB)
  runs out of memory and the whole box thrashes (MySQL gets OOM-killed).
- Security group: open **22** (SSH), **80** (HTTP), **443** (HTTPS),
  **8080** (Reverb).
- Add an **A record**: `staging-bknd.zenith-blog.blog` → instance public IP.
  - **Attach an Elastic IP** — otherwise the public IP changes on every
    stop/start or instance-type change, and you must update DNS again.
  - Note: the old record had a 4-hour TTL, so after a change, clients keep
    the stale IP for up to 4 hours unless you flush their DNS cache.

### 2. Install Docker + Compose

```bash
sudo apt update && sudo apt install -y docker.io docker-compose-v2
sudo usermod -aG docker ubuntu          # log out and back in
```

The deploy user (`ubuntu`) needs passwordless sudo — the workflow runs
`docker compose ...`. Verify with `sudo -n true`.

### 3. Lay down `/var/www/blog`

The compose file, `.env`, and `nginx.conf` are **not** in the image and are
gitignored, so copy them from the project:

```bash
sudo mkdir -p /var/www/blog/certbot/conf /var/www/blog/certbot/www
sudo chown -R ubuntu:ubuntu /var/www/blog

# from the local project directory:
scp docker-compose.local.yml ubuntu@<IP>:/var/www/blog/docker-compose.yml  # renamed
scp nginx.conf               ubuntu@<IP>:/var/www/blog/nginx.conf
# create .env on the server (see step 4)
```

> No `cert/` directory and no `ca.pem` on staging — those are production-only
> (Aiven MySQL SSL). Staging's MySQL runs in a local container.

### 4. Create the staging `.env`

```ini
APP_NAME=Blog
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:<generate-with: php artisan key:generate --show>
APP_URL=https://staging-bknd.zenith-blog.blog

DB_CONNECTION=mysql
DB_HOST=mysql                 # container name, not 127.0.0.1
DB_PORT=3306
DB_DATABASE=blog_system
DB_USERNAME=bloguser          # must match MYSQL_USER in the compose file
DB_PASSWORD=<random>
DB_PASSWORD_ROOT=<random>     # must match MYSQL_ROOT_PASSWORD in the compose file

CACHE_STORE=redis
QUEUE_CONNECTION=sync
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=<random>

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=<id>
REVERB_APP_KEY=<key>
REVERB_APP_SECRET=<secret>
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=http

SESSION_DRIVER=database

NGINX_HOST=staging-bknd.zenith-blog.blog
```

The `DB_*` values are staging-specific and self-contained: the `mysql`
container creates the `bloguser` account from `DB_USERNAME`/`DB_PASSWORD`,
and the app connects with those same values.

### 5. Start app + MySQL + Redis + Reverb (before nginx)

Nginx cannot start until a TLS cert exists, so bring up everything else first
and confirm the app boots:

```bash
cd /var/www/blog
sudo docker compose up -d app mysql redis reverb
sudo docker compose logs -f app
```

Success = `Nothing to migrate.`, `Database state ensured.`, and
`fpm is running ... ready to handle connections`.

### 6. Issue the TLS certificate

Port 80 is free until nginx runs, so use certbot standalone:

```bash
sudo docker run --rm -p 80:80 \
  -v /var/www/blog/certbot/conf:/etc/letsencrypt \
  -v /var/www/blog/certbot/www:/var/www/certbot \
  certbot/certbot certonly --standalone --preferred-challenges http \
  -d staging-bknd.zenith-blog.blog \
  --register-unsafely-without-email --agree-tos --non-interactive
```

### 7. Start nginx

```bash
sudo docker compose up -d nginx
sudo docker compose ps
```

All six services should report `Up` (including `blog_db_staging` healthy).

### 8. Certificate auto-renewal

The cert expires after ~90 days. Add a cron job:

```cron
# sudo crontab -e
0 3 * * * docker run --rm -p 80:80 -p 443:443 -v /var/www/blog/certbot/conf:/etc/letsencrypt -v /var/www/blog/certbot/www:/var/www/certbot certbot/certbot renew --quiet && docker exec blog_nginx_staging nginx -s reload
```

### 9. GitHub secrets

| Secret | Purpose |
| --- | --- |
| `STAGING_DOCKER_USERNAME` | Docker Hub user (must match image owner in the compose file) |
| `STAGING_DOCKER_TOKEN` | Docker Hub access token |
| `STAGING_EC2_HOST` | Server public IP / hostname |
| `STAGING_EC2_USERNAME` | SSH user (e.g. `ubuntu`) |
| `STAGING_EC2_SSH_KEY` | Private key for that user |
| `STAGING_BLOG_DOMAIN` | Full URL posted in the PR comment, e.g. `https://staging-bknd.zenith-blog.blog/` |

### 10. Verify

```bash
curl -I http://staging-bknd.zenith-blog.blog/    # expect 301
curl -I https://staging-bknd.zenith-blog.blog/   # expect 200
```

---

## How the staging workflow works

`.github/workflows/staging.yml` triggers when someone comments **`@staging`**
on a pull request:

1. Resolves the PR's head branch/SHA.
2. Builds the image and pushes `blog-app:staging` + a commit-SHA tag.
3. SSHes into the server and, in `/var/www/blog`:
   ```bash
   docker compose pull
   docker compose down -v     # ← wipes DB + storage volumes (ephemeral)
   docker compose up -d
   ```
4. Posts a comment on the PR: `Deployed to (${STAGING_BLOG_DOMAIN})`.

Because `down -v` removes named volumes, staging's MySQL data is reset on
every deploy — by design.

---

## Common pitfalls

- **`docker compose ps` → "no configuration file provided"** — the compose
  file must be named `docker-compose.yml` (or `compose.yml`), not
  `docker-compose.local.yml`. The workflow calls `docker compose` with no
  `-f` flag.
- **nginx crash-loop: `cannot load certificate .../fullchain.pem`** — nginx
  started before a cert was ever issued. Run step 6 (certbot) first, then
  `docker compose up -d --force-recreate nginx`.
- **nginx.conf cert path** — the repo's `nginx.conf` hardcodes
  `prod-bknd.zenith-blog.blog` in `ssl_certificate`/`ssl_certificate_key`.
  Staging needs `.../live/${NGINX_HOST}/fullchain.pem` (envsubst resolves
  `NGINX_HOST` at container start). See the fix below.
- **Whole server becomes unresponsive (SSH times out)** — instance is too
  small. The `reverb` container recompiles `pcntl` from source on every boot
  (`docker-php-ext-install pcntl`), which is CPU/memory heavy. Use ≥ 2 GB.
- **"I can't reach the site" while curl works** — almost always stale DNS
  cache (old A record with a long TTL). Verify with
  `nslookup <domain> 8.8.8.8`; flush local cache or add a hosts entry.

---

## Known follow-ups (repo changes)

1. **Make `nginx.conf` portable** — change the two hardcoded cert lines to
   `${NGINX_HOST}` so the same file works for prod and staging:
   ```nginx
   ssl_certificate     /etc/letsencrypt/live/${NGINX_HOST}/fullchain.pem;
   ssl_certificate_key /etc/letsencrypt/live/${NGINX_HOST}/privkey.pem;
   ```
2. **Bake `pcntl` into the Dockerfile** instead of compiling it at runtime in
   the `reverb` container command.
3. **Force JSON responses for `/api/*`** — protected routes return 500
   instead of 401 when the request lacks `Accept: application/json` (auth
   middleware redirects to a nonexistent `login` route). A small middleware
   that sets the header fixes it.
