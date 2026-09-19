# Financial Information System

This is a comprehensive financial information system built with Laravel and React (Inertia.js).

## Prerequisites

Before cloning and running the project, ensure you have the following installed:
- PHP (>= 8.2)
- Composer
- Node.js (>= 20) & npm
- Database (MySQL, PostgreSQL, or SQLite)

## Local Development Setup

Follow these steps to clone the project and run it locally:

1. **Clone the repository**
   ```bash
   git clone <your-repository-url>
   cd financial-is
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Set up the environment variables**
   ```bash
   cp .env.example .env
   ```
   *Note: Open the `.env` file and configure your database settings (`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).*

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Run database migrations (and seeders if available)**
   ```bash
   php artisan migrate --seed
   ```

7. **Start the local development servers**
   You will need three terminal windows for this:

   Terminal 1 (Build Frontend):
   ```bash
   npm run build
   ```

   Terminal 2 (Backend):
   ```bash
   composer run dev
   ```

   Terminal 3 (Reverb):
   ```bash
   php artisan reverb:start
   ```

8. **Access the application**
   Open your browser and visit `http://localhost:8000`.

---

## Deployment to Server and Hosting

Deploying a Laravel + React/Inertia application requires a server with PHP, Composer, Node.js, a web server (Nginx/Apache), and a database. 

Here are the typical steps for deploying to a standard VPS (like DigitalOcean, AWS EC2, etc.):

1. **Clone the repository on the server**
   ```bash
   git clone <your-repository-url>
   cd financial-is
   ```

2. **Install Composer dependencies (Production)**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   ```
   *Edit the `.env` file:*
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`
   - Configure database credentials
   - Set `APP_URL` to your production domain

4. **Generate App Key (If first time deployment)**
   ```bash
   php artisan key:generate
   ```

5. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

6. **Build Frontend Assets (Production)**
   ```bash
   npm install
   npm run build
   ```

7. **Cache Configurations and Routes**
   To optimize performance in production, run the following caching commands:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache
   ```

8. **Set Directory Permissions**
   Ensure your web server (e.g., `www-data` or `nginx`) has write permissions to `storage` and `bootstrap/cache`:
   ```bash
   chown -R www-data:www-data storage bootstrap/cache
   chmod -R 775 storage bootstrap/cache
   ```

9. **Configure Reverb Server (Supervisor)**
   To keep the Laravel Reverb WebSocket server running continuously in production, use a process monitor like Supervisor. Create a configuration file at `/etc/supervisor/conf.d/reverb.conf`:
   ```ini
   [program:reverb]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/financial-is/artisan reverb:start
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=1
   stdout_logfile=/path/to/financial-is/storage/logs/reverb.log
   ```
   *Note: Make sure to replace `/path/to/financial-is` with the actual path to your project.*

   Then, start the process by running:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start reverb:*
   ```

*Tip: For a much smoother and automated deployment experience, consider using [Laravel Forge](https://forge.laravel.com) or [Laravel Cloud](https://cloud.laravel.com/).*