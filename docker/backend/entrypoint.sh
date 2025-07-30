#!/bin/bash
set -e

# =============================================================================
# Unified container initialization with built-in permissions management
# =============================================================================

# Colors for output
readonly RED='[0;31m'
readonly GREEN='[0;32m'
readonly YELLOW='[1;33m'
readonly BLUE='[0;34m'
readonly PURPLE='[0;35m'
readonly NC='[0m'

# Configuration
readonly LARAVEL_DIR="/var/www/backend"
readonly WEB_USER="www-data"
readonly WEB_GROUP="www-data"
readonly DB_TIMEOUT=60
readonly DEBUG=${DEBUG:-true}

# =============================================================================
# Logging Functions
# =============================================================================

log_debug() {
    [[ "$DEBUG" == "true" ]] && echo -e "${PURPLE}[DEBUG]${NC} $1" >&2
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

# =============================================================================
# Permission Management (Built-in)
# =============================================================================

check_permissions() {
    log_info "Checking Laravel permissions..."
    local errors=0
    
    # Check directories
    local dirs=(
        "storage:775"
        "bootstrap/cache:775"
        "storage/logs:775"
        "storage/framework:775"
        "storage/app:775"
    )
    
    for dir_perm in "${dirs[@]}"; do
        local dir="${dir_perm%:*}"
        local expected_perm="${dir_perm#*:}"
        local full_path="$LARAVEL_DIR/$dir"
        
        if [[ -d "$full_path" ]]; then
            local actual_perm=$(stat -c "%a" "$full_path" 2>/dev/null || echo "000")
            local actual_owner=$(stat -c "%U:%G" "$full_path" 2>/dev/null || echo "unknown")
            
            if [[ "$actual_perm" != "$expected_perm" ]] || [[ "$actual_owner" != "$WEB_USER:$WEB_GROUP" ]]; then
                log_debug "❌ $dir: $actual_owner ($actual_perm) - expected $WEB_USER:$WEB_GROUP ($expected_perm)"
                ((errors++))
            else
                log_debug "✅ $dir: correct permissions"
            fi
        else
            log_debug "⚠️ $dir: directory not found"
            ((errors++))
        fi
    done
    
    # Check files
    if [[ -f "$LARAVEL_DIR/.env" ]]; then
        local env_perm=$(stat -c "%a" "$LARAVEL_DIR/.env" 2>/dev/null || echo "000")
        local env_owner=$(stat -c "%U:%G" "$LARAVEL_DIR/.env" 2>/dev/null || echo "unknown")
        
        if [[ "$env_perm" != "664" ]] || [[ "$env_owner" != "$WEB_USER:$WEB_GROUP" ]]; then
            log_debug "❌ .env: $env_owner ($env_perm) - expected $WEB_USER:$WEB_GROUP (664)"
            ((errors++))
        else
            log_debug "✅ .env: correct permissions"
        fi
    fi
    
    if [[ $errors -eq 0 ]]; then
        log_success "All permissions are correct"
        return 0
    else
        log_warning "Found $errors permission issues"
        return 1
    fi
}

fix_permissions() {
    log_info "Fixing Laravel permissions..."
    
    # Temporarily disable exit on error for this function
    set +e
    
    log_debug "Changing to Laravel directory: $LARAVEL_DIR"
    if ! cd "$LARAVEL_DIR"; then
        log_error "Cannot change to Laravel directory: $LARAVEL_DIR"
        set -e
        return 1
    fi
    
    log_debug "Current directory: $(pwd)"
    log_debug "Directory contents: $(ls -la | head -5)"
    
    # Create required directories one by one with error checking
    log_debug "Creating required directories..."
    
    for dir in "storage/logs" "storage/framework/cache" "storage/framework/sessions" "storage/framework/views" "storage/app/private" "storage/app/public" "storage/app/livewire-tmp" "storage/app/public/livewire-tmp" "bootstrap/cache"; do
        if [ ! -d "$dir" ]; then
            log_debug "Creating directory: $dir"
            if mkdir -p "$dir"; then
                log_debug "✅ Created: $dir"
            else
                log_warning "❌ Failed to create: $dir"
            fi
        else
            log_debug "✅ Already exists: $dir"
        fi
    done
    
    # Set ownership with error checking
    log_debug "Setting ownership to $WEB_USER:$WEB_GROUP"
    if [ -d "storage" ]; then
        if chown -R "$WEB_USER:$WEB_GROUP" storage; then
            log_debug "✅ Set ownership for storage"
        else
            log_warning "❌ Failed to set ownership for storage"
        fi
    else
        log_warning "❌ storage directory not found"
    fi
    
    if [ -d "bootstrap/cache" ]; then
        if chown -R "$WEB_USER:$WEB_GROUP" bootstrap/cache; then
            log_debug "✅ Set ownership for bootstrap/cache"
        else
            log_warning "❌ Failed to set ownership for bootstrap/cache"
        fi
    else
        log_warning "❌ bootstrap/cache directory not found"
    fi
    
    # Set permissions with error checking
    log_debug "Setting directory permissions"
    if [ -d "storage" ]; then
        if chmod -R 775 storage; then
            log_debug "✅ Set permissions for storage"
        else
            log_warning "❌ Failed to set permissions for storage"
        fi
    fi
    
    if [ -d "bootstrap/cache" ]; then
        if chmod -R 775 bootstrap/cache; then
            log_debug "✅ Set permissions for bootstrap/cache"
        else
            log_warning "❌ Failed to set permissions for bootstrap/cache"
        fi
    fi
    
    # Special permissions for specific directories
    if [ -d "storage/app/private" ]; then
        chmod -R 755 storage/app/private 2>/dev/null && log_debug "✅ Set special permissions for storage/app/private"
    fi
    
    if [ -d "storage/app/livewire-tmp" ]; then
        chmod -R 755 storage/app/livewire-tmp 2>/dev/null && log_debug "✅ Set special permissions for storage/app/livewire-tmp"
    fi
    
    # Handle .env file
    if [ -f ".env" ]; then
        if chown "$WEB_USER:$WEB_GROUP" .env && chmod 664 .env; then
            log_debug "✅ Fixed .env permissions"
        else
            log_warning "❌ Failed to fix .env permissions"
        fi
    else
        log_debug "⚠️ .env file not found"
    fi
    
    # Make artisan executable
    if [ -f "artisan" ]; then
        if chmod +x artisan; then
            log_debug "✅ Made artisan executable"
        else
            log_warning "❌ Failed to make artisan executable"
        fi
    else
        log_debug "⚠️ artisan file not found"
    fi
    
    # Re-enable exit on error
    set -e
    
    log_success "Laravel permissions fixed successfully"
}

# =============================================================================
# Database Connection
# =============================================================================

wait_for_database() {
    if [[ "${WAIT_FOR_DB:-true}" != "true" ]]; then
        log_info "Database wait disabled, skipping..."
        return 0
    fi
    
    log_info "Waiting for database connection..."
    
    # Extract database info from .env
    local db_host db_port
    if [[ -f "$LARAVEL_DIR/.env" ]]; then
        db_host=$(grep -E "^DB_HOST=" "$LARAVEL_DIR/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        db_port=$(grep -E "^DB_PORT=" "$LARAVEL_DIR/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    fi
    
    # Default values
    db_host=${db_host:-database}
    db_port=${db_port:-5432}
    
    log_debug "Checking database at $db_host:$db_port"
    
    local timeout=$DB_TIMEOUT
    while ! nc -z "$db_host" "$db_port" 2>/dev/null; do
        timeout=$((timeout - 1))
        if [[ $timeout -le 0 ]]; then
            log_warning "Database connection timeout after ${DB_TIMEOUT}s, proceeding anyway..."
            return 1
        fi
        log_debug "Waiting for database... ($timeout seconds left)"
        sleep 1
    done
    
    log_success "Database connection established"
    return 0
}

# =============================================================================
# Admin User Creation
# =============================================================================

create_admin_user() {
    log_info "Creating default admin user..."
    
    # Admin user configuration from environment variables
    local admin_name="${ADMIN_NAME:-Admin}"
    local admin_email="${ADMIN_EMAIL:-admin@example.com}"
    local admin_password="${ADMIN_PASSWORD:-password}"
    
    log_debug "Admin user config: name=$admin_name, email=$admin_email"
    
    # Check if admin user already exists using artisan tinker
    log_debug "Checking if admin user exists..."
    if php artisan tinker --execute="echo App\Models\User::where('email', '$admin_email')->exists() ? 'true' : 'false';" 2>/dev/null | grep -q "true"; then
        log_success "Admin user already exists: $admin_email"
        return 0
    fi
    
    log_info "Admin user does not exist, creating..."
    
    # Create admin user using artisan tinker
    local create_command="
        \$user = App\Models\User::create([
            'name' => '$admin_name',
            'email' => '$admin_email', 
            'password' => Hash::make('$admin_password'),
            'email_verified_at' => now(),
        ]);
        echo 'User created with ID: ' . \$user->id;
    "
    
    if php artisan tinker --execute="$create_command" >/dev/null 2>&1; then
        log_success "✅ Admin user created successfully"
        log_info "📧 Email: $admin_email"
        log_info "🔑 Password: $admin_password"
        log_warning "⚠️  Please change the default password after first login!"
    else
        log_warning "❌ Failed to create admin user via tinker, trying alternative method..."
        
        # Alternative: try using make:filament-user with expect-like approach
        if command -v expect >/dev/null 2>&1; then
            log_debug "Using expect to automate filament user creation..."
            expect -c "
                spawn php artisan make:filament-user
                expect \"Name:\"
                send \"$admin_name\r\"
                expect \"Email address:\"
                send \"$admin_email\r\"
                expect \"Password:\"
                send \"$admin_password\r\"
                expect eof
            " >/dev/null 2>&1 && log_success "✅ Admin user created via filament command"
        else
            log_warning "❌ Could not create admin user automatically"
            log_info "💡 Please run manually: php artisan make:filament-user"
        fi
    fi
}

# =============================================================================
# Laravel Initialization
# =============================================================================

initialize_laravel() {
    log_info "Initializing Laravel application..."
    
    cd "$LARAVEL_DIR" || {
        log_error "Cannot change to Laravel directory: $LARAVEL_DIR"
        exit 1
    }
    
    # Check for .env file
    if [[ ! -f ".env" ]]; then
        log_error ".env file not found in $LARAVEL_DIR"
        exit 1
    fi
    
    # Install/update dependencies
    log_info "Installing Composer dependencies..."
    if ! composer install --optimize-autoloader --ignore-platform-reqs --no-interaction; then
        log_warning "Composer install completed with warnings"
    fi
    
    # Generate application key
    log_info "Generating application key..."
    php artisan key:generate --force || log_warning "Key generation skipped"
    
    # Cache configuration
    log_info "Caching configuration..."
    php artisan config:cache || log_warning "Config cache failed"
    php artisan route:cache || log_warning "Route cache failed"
    php artisan view:cache || log_warning "View cache failed"
    
    # Run migrations
    log_info "Running database migrations..."
    php artisan migrate --force || log_warning "Migration failed"
    
    # Create storage link
    log_info "Creating storage link..."
    php artisan storage:link || log_warning "Storage link failed"
    
    # Create default admin user
    create_admin_user || log_warning "Admin user creation failed"
    
    log_success "Laravel initialization completed"
}

# =============================================================================
# Health Check (Built-in)
# =============================================================================

health_check() {
    log_info "Performing health check..."
    
    # Check Laravel directory
    if [[ ! -d "$LARAVEL_DIR" ]]; then
        log_error "Laravel directory not found: $LARAVEL_DIR"
        return 1
    fi
    
    # Check permissions
    if ! check_permissions; then
        log_warning "Permission issues detected"
        return 1
    fi
    
    # Check if Laravel is responsive
    if [[ -f "$LARAVEL_DIR/artisan" ]]; then
        if ! php "$LARAVEL_DIR/artisan" --version >/dev/null 2>&1; then
            log_error "Laravel artisan not responsive"
            return 1
        fi
    fi
    
    log_success "Health check passed"
    return 0
}

# =============================================================================
# Signal Handling
# =============================================================================

cleanup() {
    log_info "Received shutdown signal, cleaning up..."
    # Add any cleanup logic here
    exit 0
}

trap cleanup SIGTERM SIGINT

# =============================================================================
# Main Function
# =============================================================================

main() {
    log_info "🚀 Starting Laravel Docker container initialization..."
    log_info "Container: $(hostname), User: $(whoami), PWD: $(pwd)"
    log_info "Debug mode: $DEBUG"
    
    # Handle special commands
    case "${1:-}" in
        "health-check"|"healthcheck")
            health_check
            exit $?
            ;;
        "check-permissions")
            check_permissions
            exit $?
            ;;
        "fix-permissions")
            fix_permissions
            exit $?
            ;;
        "create-admin")
            cd "$LARAVEL_DIR" || exit 1
            create_admin_user
            exit $?
            ;;
        "--help"|"-h")
            echo "Laravel Docker Entrypoint"
            echo "Usage: $0 [command]"
            echo ""
            echo "Commands:"
            echo "  health-check      - Perform health check"
            echo "  check-permissions - Check Laravel permissions"
            echo "  fix-permissions   - Fix Laravel permissions"
            echo "  create-admin      - Create default admin user"
            echo "  --help, -h        - Show this help"
            echo ""
            echo "Environment variables:"
            echo "  DEBUG=true        - Enable debug output"
            echo "  WAIT_FOR_DB=false - Skip database wait"
            echo "  ADMIN_NAME        - Admin user name (default: Admin)"
            echo "  ADMIN_EMAIL       - Admin user email (default: admin@example.com)"
            echo "  ADMIN_PASSWORD    - Admin user password (default: password)"
            exit 0
            ;;
    esac
    
    # Main initialization sequence
    fix_permissions
    wait_for_database
    initialize_laravel
    
    log_success "🎉 Container initialization completed successfully"
    
    # Execute the main command
    if [[ $# -gt 0 ]]; then
        log_info "Starting main process: $*"
        exec "$@"
    else
        log_info "No command specified, starting supervisor..."
        exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
    fi
}

# Run main function with all arguments
main "$@"