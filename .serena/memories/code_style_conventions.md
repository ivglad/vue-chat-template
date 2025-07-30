# Code Style and Conventions

## Backend (Laravel/PHP)

### Naming Conventions
- **Models**: PascalCase singular (`Document`, `ChatMessage`, `DocumentEmbedding`)
- **Controllers**: PascalCase with Controller suffix (`ChatController`, `DocumentController`)
- **Services**: PascalCase with Service suffix (`YandexGptService`, `ChatService`, `DocumentService`)
- **Commands**: PascalCase with Command suffix (`TelegramAskCommand`, `SetupChatCommand`)
- **Jobs**: PascalCase describing action (`ProcessDocumentEmbeddings`, `GenerateChunkEmbedding`)
- **Migrations**: Snake_case with timestamp (`create_documents_table`, `add_vector_column_to_document_embeddings_table`)
- **Variables/Methods**: camelCase (`processMessage`, `generateEmbeddings`)

### Code Organization
- **Service Layer**: Business logic separated into service classes
- **Job Queue**: Async processing for heavy operations (embeddings, transcription)
- **Resource Pattern**: Filament resources for admin CRUD operations
- **API Versioning**: Versioned API endpoints (`/api/v1/`)
- **Command Pattern**: Artisan commands for CLI operations

### File Structure Patterns
- Place business logic in Services, not Controllers
- Use Jobs for time-consuming operations
- Keep Controllers thin, delegate to Services
- Group related functionality in dedicated directories
- Use Filament Resources for admin interfaces

## Frontend (Vue.js)

### Naming Conventions
- **Components**: PascalCase (`ChatHeader`, `ChatFooter`, `ChatContent`)
- **Views**: PascalCase (`Chat`, `Auth`, `Ui`)
- **Composables**: camelCase with `use` prefix (`useMarkdownParser`, `useTextAnimation`, `useLoadingPhrases`)
- **Stores**: camelCase with Store suffix (`userStore`)
- **Files**: kebab-case for non-components (`api.js`, `index.scss`, `common.scss`)

### Component Architecture
- **Composition API**: Vue 3 composition functions for logic reuse
- **Auto Imports**: Automatic imports for Vue, PrimeVue, and utilities
- **Component-Based**: Modular component architecture
- **State Management**: Centralized state with Pinia
- **Utility-First CSS**: TailwindCSS with component-specific SCSS

### File Organization
- Components in `components/` for reusability
- Views in `views/` for page-level components
- Composables for shared reactive logic
- Separate API logic in `helpers/api/`
- Custom styles in component-specific SCSS files

### Auto-Import Conventions
- Vue functions auto-imported globally
- PrimeVue components auto-imported
- Custom composables and utilities auto-imported
- Icons imported as `<IconName />` components
- API functions imported from `@/helpers/api/`

## Styling Conventions

### TailwindCSS + SCSS
- Use TailwindCSS for utility classes
- Component-specific styles in SCSS files
- PrimeVue component overrides in `assets/styles/primevue/`
- Global styles in `assets/styles/`
- Variables and mixins in dedicated files

### Animation Standards
- Use Motion-v for component animations
- Consistent animation timing and easing
- Reusable animation utilities in `utils/ui/animation/`