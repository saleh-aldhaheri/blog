# Production Server Setup

This document describes how to provision a fresh production server for the
Blog backend and how the CI/CD deploy workflow operates against it.

## Architecture

| Piece | Detail |
| --- | --- |
| Host | AWS EC2 (Ubuntu) |
| Containers | `app` (PHP-FPM), `nginx`, `reverb` (WebSocket), `redis` |
| MySQL | Managed (Aiven) — external, reached over SSL using `ca.pem` |
| Image | `salehaldhaheri1010/blog-prod-app:production` (Docker Hub) |
| Domain | `prod-bknd.zenith-blog.blog` |
| Compose file | `docker-compose.prod.yml` (deployed as `docker-compose.yml`) |

---

## One-time provisioning

### 1. Launch the server

- EC2 **Ubuntu 24.04** (t3.small is sufficient).
- Security group: open **22** (SSH), **80** (HTTP), **443** (HTTPS),
  **8080** (Reverb WebSocket).
- Add an **A record**: `prod-bknd.zenith-blog.blog` → instance public IP.

### 2. Install Docker + Compose

```bash
sudo apt update && sudo apt install -y docker.io docker-compose-v2
sudo usermod -aG docker ubuntu          # log out and back in
```

The deploy user (`ubuntu`) needs passwordless sudo — the CI workflow runs
`sudo docker ...`. Verify with `sudo -n true` (must return success).

### 3. Lay down `/var/www/blog`

The following files are **not** in the Docker image and are gitignored, so
they must be copied manually from the project:

```bash
sudo mkdir -p /var/www/blog/cert /var/www/blog/certbot/{conf,www}
sudo chown -R ubuntu:ubuntu /var/www/blog

# from the local project directory:
scp docker-compose.prod.yml ubuntu@<IP>:/var/www/blog/docker-compose.yml  # renamed
scp .env.production          ubuntu@<IP>:/var/www/blog/.env
scp nginx.conf               ubuntu@<IP>:/var/www/blog/nginx.conf
scp ca.pem                   ubuntu@<IP>:/var/www/blog/cert/ca.pem
```

Checks:

- `.env`: set `APP_DEBUG=false`, `NGINX_HOST` matches the domain, and
  `MYSQL_ATTR_SSL_CA=/var/www/blog/cert/ca.pem`.
- `ca.pem`: the Aiven MySQL CA, present at `cert/ca.pem` and readable
  (mode 644).
- `docker-compose.yml` must be the **production** compose content — the
  deploy workflow calls `docker compose` with no `-f` flag.

### 4. Start app + Redis + Reverb (before nginx)

Nginx cannot start until a TLS cert exists, so bring up the other services
first and confirm DB connectivity:

```bash
cd /var/www/blog
sudo docker compose up -d app redis reverb
sudo docker compose logs -f app
```

Success looks like `Nothing to migrate.`, `Database state ensured.`, and
`fpm is running ... ready to handle connections`. If instead you see
`failed loading cafile stream: /var/www/blog/cert/ca.pem`, the CA file is
missing or unreadable.

### 5. Issue the TLS certificate

Port 80 is free until nginx runs, so use certbot standalone:

```bash
sudo docker run --rm -p 80:80 \
  -v /var/www/blog/certbot/conf:/etc/letsencrypt \
  -v /var/www/blog/certbot/www:/var/www/certbot \
  certbot/certbot certonly --standalone --preferred-challenges http \
  -d prod-bknd.zenith-blog.blog \
  --register-unsafely-without-email --agree-tos --non-interactive
```

### 6. Start nginx

```bash
sudo docker compose up -d nginx
sudo docker compose ps
```

All four services should report `Up`.

### 7. Certificate auto-renewal

The cert expires after ~90 days. The `certbot` service in the compose file
has no command, so add a cron job:

```cron
# sudo crontab -e
0 3 * * * docker run --rm -p 80:80 -p 443:443 -v /var/www/blog/certbot/conf:/etc/letsencrypt -v /var/www/blog/certbot/www:/var/www/certbot certbot/certbot renew --quiet && docker exec blog_nginx_production nginx -s reload
```

### 8. GitHub secrets

Configure the following repository secrets:

| Secret | Purpose |
| --- | --- |
| `PROD_DOCKER_USERNAME` | Docker Hub user (must match the image owner in the compose file) |
| `PROD_DOCKER_TOKEN` | Docker Hub access token |
| `PROD_EC2_HOST` | Server public IP / hostname |
| `PROD_EC2_USERNAME` | SSH user (e.g. `ubuntu`) |
| `PROD_EC2_SSH_KEY` | Private key for that user |

### 9. Verify

```bash
curl -I http://prod-bknd.zenith-blog.blog/    # expect 301
curl -I https://prod-bknd.zenith-blog.blog/   # expect 200
```

---

## How the deploy workflow works

`.github/workflows/deploy.yml` (triggered manually on `main`):

1. Builds the image from the repo and pushes two tags to Docker Hub:
   `production` and the short commit SHA.
2. SSHes into the server and runs, in `/var/www/blog`:

   ```bash
   sudo docker compose pull
   sudo docker compose down
   sudo docker compose up -d
   ```

Because the deploy only pulls the image and restarts containers, the files
provisioned in step 3 (`.env`, `nginx.conf`, `cert/ca.pem`, the compose
file) persist across deployments.

---

## Common pitfalls

- **App crash loop** — almost always the missing `ca.pem`, so Laravel cannot
  connect to Aiven MySQL over SSL.
- **Nginx serves the default page instead of the app** — the
  `nginx.conf` bind-mount turned into a directory (the file did not exist
  when the container first started). Fix with
  `sudo docker compose up -d --force-recreate nginx`.
- **`docker compose ps` → "no configuration file provided"** — you are not
  in `/var/www/blog`, or the compose file is not named `docker-compose.yml`
  / `compose.yml`.
- **Image mismatch** — the compose file pins
  `salehaldhaheri1010/blog-prod-app:production`; the workflow builds under
  `${{ secrets.PROD_DOCKER_USERNAME }}`. These must be the same account.
