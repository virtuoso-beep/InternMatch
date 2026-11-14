# Production Deployment Guide - InternMatch

This guide outlines the procedure to deploy InternMatch to a production environment.

## 1. Server Requirements
- Linux server (Ubuntu 22.04 or 24.04 recommended)
- Minimum 4GB RAM, 2 vCPUs (AI service requires memory)
- At least 20GB of disk space
- Docker and Docker Compose installed
- Port 80 and 443 open to the public

## 2. DNS Configuration
Point your domain's A record (e.g., `internmatch.example.com`) to the server's public IP address.

## 3. Step-by-step Setup
1. **Clone the repository:**
   ```bash
   git clone <repository-url> /opt/internmatch
   cd /opt/internmatch
   ```
2. **Environment Configuration:**
   Copy the production environment template:
   ```bash
   cp .env.production.example .env
   ```
   Edit `.env` to configure `APP_URL`, database credentials, and `DOMAIN_NAME`.
   Generate the app key:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.production.yml run --rm backend php artisan key:generate
   ```

3. **SSL Setup (Let's Encrypt):**
   Before starting the full stack, set up SSL using the provided script. You might need to spin up a temporary Nginx container if you strictly require webroot verification, or just run the script which uses Certbot.
   ```bash
   sudo ./deploy/ssl-setup.sh internmatch.example.com admin@example.com
   ```
   Modify `deploy/nginx.production.conf` replacing `${DOMAIN_NAME}` with your actual domain, or use `envsubst` to generate it.

4. **First Deployment:**
   Run the deployment script:
   ```bash
   ./deploy/deploy.sh
   ```

## 4. Backup & Restore Procedures
- **Backups:** With Python 3.12+ installed, run `INTERNMATCH_PRODUCTION=1 bash deploy/backup.sh`. This captures the database plus private and public uploads with checksums. Use a maintenance window for coordinated database/document consistency. Production scheduling is deployment-specific; the current installation uses Windows locally instead.
- **Recovery drill:** Run `INTERNMATCH_PRODUCTION=1 bash deploy/restore.sh <backup-id>` to verify checksums and restore into a new isolated testing database and document directory. This no longer overwrites live data or runs migrations. See [local backup and recovery](LOCAL_BACKUP_RECOVERY.md) for the recorded Windows schedule, operational prerequisites and verification scope.

## 5. Monitoring & Maintenance
- Check container logs: `docker compose -f docker-compose.yml -f docker-compose.production.yml logs -f`
- Monitor system resources to ensure AI service isn't OOMing (Out of Memory).

## 6. Troubleshooting
- **502 Bad Gateway:** The backend container might be down or starting. Check `docker ps` and backend logs.
- **Database Connection Error:** Verify `.env` matches `docker-compose.production.yml` credentials.
