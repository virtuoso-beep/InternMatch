# Local Filament setup

The Laravel management panel is at `/manage` on the backend origin (for example, `http://127.0.0.1:8001/manage` for the isolated QA server). It uses existing accounts and sessions. Only active administrators and coordinators can enter.

The program registry restricts coordinators to assigned programs. Administrators can update confirmed hours and terms using the same validated, audited service as the API. Unknown hours must remain empty. Other coordinator management workflows are still being integrated; installing the panel does not complete Phase 6.

PHP must load `intl`. Docker already builds it. On Windows, enable `extension=intl` in the PHP configuration, or use a local additional configuration directory. The ignored `.local/php-conf/internmatch.ini` used during this audit contains that setting. Set its directory in each development terminal before starting PHP or Composer so child processes inherit it:

```powershell
$env:PHP_INI_SCAN_DIR='C:\InternMatch\.local\php-conf'
```

Run `composer install` from `backend` after pulling the dependency changes. Its post-autoload scripts publish Filament assets. If necessary, run `php artisan filament:upgrade` explicitly. Generated assets are ignored and must be published during deployment.

Docker uses a separate named `backend_vendor` volume. Windows Composer installs do not update it. The development entrypoint now runs the locked Composer install before Laravel commands, and Compose invokes the current bind-mounted script. Start the main app with `docker compose up -d`; use port 8080 for the frontend and 8000 for the backend. The 8081/8001 pair below is only the isolated QA environment.

For the isolated browser QA environment, after preparing its existing dedicated database and fixtures:

```powershell
$env:PHP_INI_SCAN_DIR='C:\InternMatch\.local\php-conf'
$env:APP_ENV='browser-testing'
php artisan serve --host=127.0.0.1 --port=8001 --no-reload
```

The `--no-reload` option preserves the additional PHP configuration environment variable in Artisan's web-server subprocess. Restart the server manually after changing environment settings.

Automated tests reset the dedicated `_testing` database. Stop browser QA before running tests, then recreate synthetic fixtures before resuming it. Never point `.env.testing` or `.env.browser-testing` at the main development database.
