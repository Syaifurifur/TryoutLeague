# Production deployment (Dokploy)

This branch adds a container image for the Laravel + Inertia React application.
It does not change the application schema or run migrations automatically.

## Services

Create isolated Dokploy services for this application only:

- **Web application**: build from this repository and branch `deployment/production`,
  Dockerfile path `/Dockerfile`, exposed container port `8080`.
- **MySQL**: MySQL 8.4, persistent volume mounted at `/var/lib/mysql`.
- **Redis**: Redis 7.4, persistent volume mounted at `/data`, append-only persistence enabled.
- **Queue worker**: same application image, command:
  `php artisan queue:work redis --sleep=3 --tries=3 --timeout=90`.
- **Scheduler**: same application image, command:
  `php artisan schedule:work`.

Put all services on the same private Dokploy network. Do not publish MySQL 3306
or Redis 6379 to the public host. Use unique database credentials and a dedicated
database/Redis instance for this application. Do not reuse Competition credentials,
volumes, or containers.

## Web application environment

Set these in Dokploy (use secret fields for passwords and APP_KEY):

```dotenv
APP_NAME="Tryout League Indonesia"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tryout.digitalspacehub.cloud
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=<dokploy-mysql-service-hostname>
DB_PORT=3306
DB_DATABASE=tryout_apps
DB_USERNAME=tryout_user
DB_PASSWORD=<strong-unique-secret>

REDIS_CLIENT=predis
REDIS_HOST=<dokploy-redis-service-hostname>
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
FILESYSTEM_DISK=public
```

Generate a new application key once with `php artisan key:generate --show` in a
trusted local environment, then save the resulting value as the Dokploy
`APP_KEY` secret. Never commit `.env`, credentials, or generated keys.

If Redis authentication is enabled, set `REDIS_PASSWORD` to the matching secret.
If Redis authentication is not enabled, leave it as `null`.

## Persistent storage and startup

Attach a persistent volume to `/var/www/html/storage` for uploads, generated
certificates, sessions/logs if applicable. Ensure the container can write to it.
The image prepares Laravel storage/cache directories and creates the public
storage symlink when the container starts.

The image intentionally does **not** run `php artisan migrate` at startup.
After MySQL is healthy, run this once as a controlled release/deployment command:

```bash
php artisan migrate --force
```

Take a database backup before subsequent production migrations. Do not run
`migrate:fresh` against production.

## Health check and domain

- Container port: `8080`
- Health check URL: `/up`
- Dokploy domain: `tryout.digitalspacehub.cloud`
- Enable HTTPS/Let's Encrypt in Dokploy after DNS A record points to the VPS.

## Important operational notes

- The scheduler is required: `routes/console.php` schedules
  `attempts:expire` every minute. Keep exactly one scheduler process running.
- Keep at least one queue worker running for Redis jobs.
- Queue worker and scheduler must use the same image, environment, private
  network, and persistent storage as the web application.
- Confirm upload and certificate paths in application code before production;
  use the public disk for user-accessible files.
- This image has not been built or integration-tested by this documentation
  alone. Run a build and smoke test in Dokploy before directing real users to it.
