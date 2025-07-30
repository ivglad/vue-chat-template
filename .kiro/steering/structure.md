# Project Structure & Organization

## Root Level Organization

```
├── backend/           # Laravel API and admin panel
├── frontend/          # Vue.js web application
├── docker/           # Docker configuration files
├── docker-compose.yml # Container orchestration
└── .env              # Environment variables
```

## Backend Structure (`backend/`)

### Core Application (`app/`)

```
app/
├── Console/Commands/     # Artisan commands (Telegram, testing, setup)
├── Filament/            # Admin panel resources and pages
│   ├── Resources/       # CRUD resources (Documents, Users, etc.)
│   ├── Pages/          # Custom admin pages
│   └── Widgets/        # Dashboard widgets
├── Http/Controllers/    # API and web controllers
│   └── Api/V1/         # Versioned API controllers
├── Jobs/               # Background job classes
├── Models/             # Eloquent models
├── Providers/          # Service providers
└── Services/           # Business logic services
```

### Key Directories

- **Console/Commands/**: Telegram bot commands, testing utilities, setup scripts
- **Filament/**: Complete admin interface for document and user management
- **Services/**: Core business logic (ChatService, DocumentService, YandexGptService)
- **Jobs/**: Async processing (document embeddings, transcription)
- **Models/**: Data models with relationships (User, Document, ChatMessage, etc.)

### Configuration & Routes

```
├── config/           # Laravel configuration files
├── routes/           # API and web routes
├── database/         # Migrations, seeders, factories
└── resources/        # Views, assets (mainly for Filament)
```

## Frontend Structure (`frontend/src/`)

### Core Application

```
src/
├── components/       # Reusable Vue components
│   └── Chat/        # Chat-specific components
├── views/           # Page-level components
├── layouts/         # Layout components
├── router/          # Vue Router configuration
└── store/           # Pinia state management
```

### Assets & Styling

```
├── assets/
│   ├── styles/      # SCSS stylesheets
│   │   ├── primevue/  # PrimeVue component overrides
│   │   └── *.scss     # Global styles, variables, mixins
│   └── svg/icons/   # Custom SVG icons
```

### Utilities & Helpers

```
├── composables/     # Vue composition functions
├── helpers/         # Utility functions and API helpers
├── utils/           # General utilities
└── plugins/         # Vue plugins and configurations
```

## Docker Structure (`docker/`)

```
docker/
├── backend/         # PHP-FPM + Laravel setup
├── frontend/        # Node.js build environment
└── nginx/           # Web server configuration
```

## Naming Conventions

### Backend (Laravel)

- **Models**: PascalCase singular (`Document`, `ChatMessage`)
- **Controllers**: PascalCase with Controller suffix (`ChatController`)
- **Services**: PascalCase with Service suffix (`YandexGptService`)
- **Commands**: PascalCase with Command suffix (`TelegramAskCommand`)
- **Jobs**: PascalCase describing action (`ProcessDocumentEmbeddings`)
- **Migrations**: Snake_case with timestamp (`create_documents_table`)

### Frontend (Vue.js)

- **Components**: PascalCase (`ChatHeader`, `ChatFooter`)
- **Views**: PascalCase (`Chat`, `Auth`)
- **Composables**: camelCase with `use` prefix (`useMarkdownParser`)
- **Stores**: camelCase with Store suffix (`userStore`)
- **Files**: kebab-case for non-components (`api.js`, `index.scss`)

## Key Architectural Patterns

### Backend Patterns

- **Service Layer**: Business logic separated into service classes
- **Job Queue**: Async processing for heavy operations (embeddings)
- **Resource Pattern**: Filament resources for admin CRUD operations
- **API Versioning**: Versioned API endpoints (`/api/v1/`)
- **Command Pattern**: Artisan commands for CLI operations

### Frontend Patterns

- **Composition API**: Vue 3 composition functions for logic reuse
- **Auto Imports**: Automatic imports for Vue, PrimeVue, and utilities
- **Component-Based**: Modular component architecture
- **State Management**: Centralized state with Pinia
- **Utility-First CSS**: TailwindCSS with component-specific SCSS

## File Organization Rules

### Backend

- Place business logic in Services, not Controllers
- Use Jobs for time-consuming operations
- Keep Controllers thin, delegate to Services
- Group related functionality in dedicated directories
- Use Filament Resources for admin interfaces

### Frontend

- Components in `components/` for reusability
- Views in `views/` for page-level components
- Composables for shared reactive logic
- Separate API logic in `helpers/api/`
- Custom styles in component-specific SCSS files

## Import/Export Conventions

### Frontend Auto-Imports

- Vue functions auto-imported globally
- PrimeVue components auto-imported
- Custom composables and utilities auto-imported
- Icons imported as `<IconName />` components
- API functions imported from `@/helpers/api/`
