# Production Deployment Guide - InternMatch

How to put InternMatch on a production server and keep it updated. Everything runs in Docker; the server only
needs Docker, Git, Python 3 and certbot.

## 1. Server requirements

- Linux server (Ubuntu 22.04 or 24.04), at least **4 vCPUs, 8 GB RAM** (the AI service uses up to 4 GB, the
  frontend build up to 2 GB) and 30 GB of disk
- Docker Engine with **Docker Compose 2.24 or newer** (`docker compose version`)
- Git, Python 3.12 or newer (backups), certbot (installed by `deploy/ssl-setup.sh` if missing)
- A domain name whose A record points to the server; ports **80 and 443** open to the internet
- Outgoing internet access during deployment (Docker images, Composer and npm packages, the E5 model download
  on the first start, about 1.1 GB)
- An SMTP account for account e-mails (activation, password reset, notifications)

## 2. First deployment

1. **Get the code**

   ```bash
   sudo git clone <repository-url> /opt/internmatch
   sudo chown -R "$USER" /opt/internmatch
   cd /opt/internmatch
   ```

2. **Create `/opt/internmatch/.env`** (next to `docker-compose.yml`; never commit it). Every setting below is
   required unless a default is shown; `deploy.sh` stops and names any that is missing.

   | Setting | Example / note |
   |---|---|
   | `DOMAIN_NAME` | `internmatch.example.edu` |
   | `APP_URL` | `https://internmatch.example.edu` |
   | `FRONTEND_URL` | same as `APP_URL` |
   | `SANCTUM_STATEFUL_DOMAINS` | `internmatch.example.edu` |
   | `APP_KEY` | generate once (below) and keep it: changing it makes encrypted data unreadable |
   | `DB_DATABASE` | `internmatch` |
   | `DB_USERNAME` | `internmatch` (not `root`) |
   | `DB_PASSWORD` | long random value |
   | `DB_ROOT_PASSWORD` | a different long random value |
   | `MAIL_MAILER` | `smtp` |
   | `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` | from the e-mail provider (`MAIL_PORT` default 587) |
   | `MAIL_SCHEME` | optional: `smtps` for port 465 |
   | `MAIL_FROM_ADDRESS` | `noreply@example.edu` |
   | `MAIL_FROM_NAME` | default `InternMatch` |
   | `LOG_LEVEL` | default `info` |
   | `AI_MODEL_REVISION` | optional; default is the pinned E5 revision |

   Generate `APP_KEY` (prints a value; copy it into `.env` as `APP_KEY=base64:...`):

   ```bash
   docker run --rm php:8.4-cli php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
   ```

   Restrict the file: `chmod 600 .env`. If the server already has a `.env.production.example` copy from an
   earlier guide, check it against this table (`DB_ROOT_PASSWORD`, `APP_KEY` and the `MAIL_` settings are new).

3. **Certificate (Let's Encrypt)** — port 80 must be free:

   ```bash
   sudo bash deploy/ssl-setup.sh internmatch.example.edu it-office@example.edu
   ```

   Add `--with-www` as a third argument to include `www.` (its DNS record must exist). Renewal is automatic.

4. **Deploy**

   ```bash
   bash deploy/deploy.sh
   ```

   The first run takes 10 to 20 minutes (image builds, the frontend build and the AI model download). It ends
   when the database, Laravel, the frontend and Nginx are all healthy.

5. **Create the first administrator** (prompts for the name and the password; the password is not shown):

   ```bash
   docker compose -f docker-compose.yml -f docker-compose.production.yml exec backend \
     php artisan internmatch:create-first-admin it-office@example.edu
   ```

   Sign in at `https://<domain>/manage`. Create every other account (coordinators, deans, supervisors,
   students, imports) from the administration screens. The command refuses to run once an administrator exists.

6. **Schedule backups** (nightly at 02:00 by default) and test one restore:

   ```bash
   bash deploy/install-backup-cron.sh 02:00
   INTERNMATCH_PRODUCTION=1 bash deploy/backup.sh
   INTERNMATCH_PRODUCTION=1 bash deploy/restore.sh <backup-id printed above>
   ```

   `restore.sh` checks the checksums and restores into a new, separate `internmatch_recovery_..._testing`
   database and folder; it never touches live data. Copy `backend/storage/app/backups/` to another machine
   regularly. The newest 14 backups are kept (`INTERNMATCH_BACKUP_KEEP` changes this).

## 3. Updating

```bash
cd /opt/internmatch
git pull
bash deploy/deploy.sh
```

`deploy.sh` backs up first, rebuilds, restarts the services that run the code (Laravel migrates and caches on
start; the frontend rebuilds) and waits until everything is healthy. The site is unavailable while the frontend
rebuilds (a few minutes), so update outside working hours. If a migration fails, the backend reports it in its
log and the backup taken at the start of the run is the way back (restore it with the database administrator;
`restore.sh` only rehearses restores).

## 4. What runs

| Service | Role |
|---|---|
| `nginx` | the only public entry point: HTTPS on 443, HTTP 80 redirects (and certificate renewal) |
| `backend` | Laravel on Apache with PHP's production settings (`backend/Dockerfile`, target `production`) |
| `worker`, `scheduler` | queue jobs, hourly monitoring refresh and other scheduled tasks |
| `frontend` | role portals (TanStack Start, Node) and the admin bundle build |
| `ai` | E5 embeddings and recommendation ranking (internal only) |
| `mysql` | MySQL 8.4 (internal only; not published on the host) |

Laravel trusts the Nginx proxy (`TRUSTED_PROXIES`), so it sees HTTPS and each user's own IP address (sign-in
rate limits are per address), and generates `https://` links (`FORCE_HTTPS`).

## 5. Recommendation learning in production

`ML_ALLOW_SYNTHETIC_MODEL` is on in production, so the administrator's "Update recommendations" action can use
the flagged synthetic labels ("Use practice data") once they are imported with
`php artisan internmatch:import-synthetic`. While such a model ranks, screens say "Test mode: rankings use
practice data, not real coordinator judgments." Real coordinator judgments always take over once there are
30 suitable and 30 not suitable. See `ai-service/ml/README.md`.

## 6. Monitoring and maintenance

- Status: `docker compose -f docker-compose.yml -f docker-compose.production.yml ps`
- Logs: `docker compose -f docker-compose.yml -f docker-compose.production.yml logs -f backend` (or `nginx`,
  `frontend`, `worker`, `ai`)
- Backup log: `backend/storage/logs/backup-cron.log`
- Disk: Docker images and backups grow; `docker system df`, `docker image prune` after updates
- Base images: `deploy.sh` never pulls MySQL or Nginx, so an update never restarts the database by surprise.
  To take their security patches, in a maintenance window: back up, then
  `docker compose -f docker-compose.yml -f docker-compose.production.yml pull mysql nginx` and
  `docker compose -f docker-compose.yml -f docker-compose.production.yml up -d mysql nginx`

## 7. Troubleshooting

- **502 Bad Gateway right after an update:** the frontend or backend is still starting; `ps` shows `starting`.
- **`deploy.sh` stops with "Set ... in .env":** add that setting (section 2, step 2).
- **Certificate errors:** check `/etc/letsencrypt/live/<DOMAIN_NAME>/` exists and `DOMAIN_NAME` matches it.
- **E-mails not arriving:** check the `MAIL_` settings and `docker compose ... logs worker`.
