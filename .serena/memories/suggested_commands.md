# Development Commands

## Backend Development (Laravel)

### Main Development Command
```bash
composer run dev
```
This runs all services concurrently:
- `php artisan serve` - Web server
- `php artisan queue:listen --tries=1` - Queue worker
- `php artisan pail --timeout=0` - Real-time logs
- `npm run dev` - Frontend Vite server

### Individual Services
```bash
# Web server
php artisan serve

# Queue worker
php artisan queue:work

# Telegram bot polling
php artisan telegram:polling

# Real-time logs
php artisan pail --timeout=0
```

### Database Operations
```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Create admin user
php artisan make:filament-user
```

### Testing and Quality
```bash
# Run tests
php artisan test

# Format code
./vendor/bin/pint

# Clear config cache
php artisan config:clear
```

## Frontend Development (Vue.js)

```bash
# Development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Generate PWA assets
npm run generate-pwa-assets
```

## Docker Operations

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f [service_name]

# Execute commands in containers
docker exec backend-vue-chat-template php artisan migrate
docker exec backend-vue-chat-template /var/www/backend/fix-permissions.sh
docker exec backend-vue-chat-template /var/www/backend/check-permissions.sh

# Rebuild services
docker-compose build --no-cache
```

## Specialized Commands

### Chat System Setup
```bash
php artisan setup:chat
```

### Document Processing
```bash
# Test document processing
php artisan test:chat --user-id=1

# Test formatting
php artisan test:telegram-formatting
php artisan test:chat-formatting --user-id=1
```

### API Testing
```bash
php artisan test:api-chat
php artisan test:scribe-api
```

## System Commands (Linux)
- `ls -la` - List files with details
- `grep -r "pattern" .` - Search in files
- `find . -name "*.php"` - Find files by pattern
- `git status` - Git status
- `git log --oneline` - Git history