# Technology Stack & Build System

## Backend Stack

- **Framework**: Laravel 12+ with PHP 8.2+
- **Admin Panel**: Filament 3.0+ for administrative interface
- **Database**: PostgreSQL with pgvector extension for vector embeddings
- **Cache**: Redis for session and cache management
- **Authentication**: Laravel Sanctum for API authentication
- **Queue System**: Laravel queues for background processing
- **AI Integration**: YandexGPT API for embeddings and chat responses
- **Bot Framework**: Telegram Bot SDK for bot functionality
- **Document Processing**:
  - PDF parsing with smalot/pdfparser
  - Word documents with phpoffice/phpword
  - Audio transcription with whisper.php
- **File Management**: Spatie Media Library for file handling
- **Permissions**: Spatie Laravel Permission for role-based access

## Frontend Stack

- **Framework**: Vue.js 3 with Composition API
- **Build Tool**: Vite 7+ for development and building
- **UI Framework**: PrimeVue 4.3+ with custom theming
- **Styling**: TailwindCSS 4+ with SCSS preprocessing
- **State Management**: Pinia for application state
- **HTTP Client**: Axios with TanStack Vue Query for API calls
- **Animations**: Motion-v for smooth transitions
- **Icons**: Unplugin Icons with custom SVG loader
- **Auto Imports**: Unplugin Auto Import for Vue, PrimeVue, and utilities
- **PWA**: Vite PWA plugin (configurable)

## Development Tools

- **Code Quality**: Laravel Pint for PHP formatting
- **API Documentation**: Scribe for API documentation generation
- **Testing**: PHPUnit for backend testing
- **Package Management**: Composer (backend), npm (frontend)
- **Containerization**: Docker with docker-compose

## Common Commands

### Backend Development

```bash
# Start development server with all services
composer run dev

# Individual services
php artisan serve                    # Web server
php artisan queue:work              # Queue worker
php artisan telegram:polling        # Telegram bot
php artisan pail --timeout=0       # Real-time logs

# Database operations
php artisan migrate                 # Run migrations
php artisan db:seed                # Seed database

# Testing and quality
php artisan test                   # Run tests
./vendor/bin/pint                  # Format code

# Document processing
php artisan setup:chat             # Setup chat system
php artisan make:filament-user     # Create admin user
```

### Frontend Development

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

### Docker Operations

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f [service_name]

# Execute commands in containers
docker exec backend-[project] php artisan migrate
docker exec backend-[project] /var/www/backend/fix-permissions.sh

# Rebuild services
docker-compose build --no-cache
```

## Environment Configuration

### Required Environment Variables

- `YANDEX_GPT_API_KEY` - YandexGPT API access
- `YANDEX_GPT_FOLDER_ID` - Yandex Cloud folder ID
- `TELEGRAM_BOT_TOKEN` - Telegram bot token
- `DB_*` - PostgreSQL database configuration
- `REDIS_*` - Redis configuration

### Development vs Production

- Development: Uses `composer run dev` for concurrent services
- Production: Containerized with nginx, separate service containers
- PWA: Enabled via `VITE_ENABLE_PWA=true` environment variable
