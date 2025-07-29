#!/bin/bash

# Скрипт для исправления прав доступа Laravel в Docker контейнере
# Использование: docker exec backend-container ./fix-permissions.sh

echo "🔧 Исправление прав доступа Laravel..."

# Основные директории Laravel которые должны быть доступны для записи
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
chown www-data:www-data .env

# Установка правильных прав
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod 664 .env

# Специальные права для Livewire/Filament временных файлов
chmod -R 755 storage/app/private/
mkdir -p storage/app/livewire-tmp
chmod -R 755 storage/app/livewire-tmp
chown -R www-data:www-data storage/app/livewire-tmp

# Создание и настройка публичной директории для Livewire (основная для Filament)
mkdir -p storage/app/public/livewire-tmp
chown -R www-data:www-data storage/app/public/livewire-tmp
chmod -R 775 storage/app/public/livewire-tmp

# Убеждаемся что artisan исполняемый
chmod +x artisan

echo "✅ Права доступа исправлены!"
echo "📁 storage/ - www-data:www-data (775)"
echo "📁 bootstrap/cache/ - www-data:www-data (775)"
echo "📁 storage/app/private/ - www-data:www-data (755)"
echo "📁 storage/app/livewire-tmp/ - www-data:www-data (755)"
echo "📁 storage/app/public/livewire-tmp/ - www-data:www-data (775)"
echo "📄 .env - www-data:www-data (664)"

# Очистка кэша после изменения прав
echo "🧹 Очистка кэша Laravel..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "🎉 Готово! Laravel права доступа настроены корректно." 