# Docwise Bot - Интеллектуальная система работы с документами

Система для загрузки документов, их индексации с помощью эмбеддингов YandexGPT и ответов на вопросы через Telegram-бота.

## Функциональность

-   📚 Загрузка документов через админ-панель Filament
-   🤖 Интеграция с YandexGPT для генерации эмбеддингов
-   💬 Telegram-бот для вопросов по документам
-   🌐 Веб-интерфейс чата с поддержкой markdown
-   🎨 Автоматическое форматирование ответов
-   👥 Система пользователей и ролей
-   🔍 Семантический поиск по документам

## Требования

-   PHP 8.2+
-   Laravel 12+
-   MySQL 8.0+
-   Composer
-   YandexGPT API ключ
-   Telegram Bot Token

## Установка

1. **Клонируйте репозиторий:**

```bash
git clone https://github.com/void-environment/docwise.git
cd docwise
```

2. **Установите зависимости:**

```bash
sudo apt update
sudo apt install php8.3-xml php8.3-dom
sudo apt install php8.3-pgsql
sudo apt install postgresql postgresql-contrib
sudo apt install -y postgresql-server-dev-16 build-essential
```

```bash
git clone https://github.com/pgvector/pgvector.git
cd pgvector
make
sudo make install
cd ../
rm -rf pgvector
```

```bash
sudo -u postgres psql
```

```sql
CREATE USER docwise WITH PASSWORD 'password';
CREATE DATABASE docwise OWNER docwise;
GRANT ALL PRIVILEGES ON DATABASE docwise TO docwise; -- Для тестирования
\q
```

```bash
psql -h 127.0.0.1 -U docwise -d docwise # -- Для тестирования
```

```bash
sudo -u postgres psql -d docwise -c "CREATE EXTENSION vector;"
```

```bash
sudo systemctl restart postgresql
```

```bash
composer install
npm install
```

```bash
sudo apt-get install libreoffice
```

3. **Настройте окружение:**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Настройте базу данных в .env:**

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=docwise
DB_USERNAME=docwise
DB_PASSWORD=password
```

5. **Настройте YandexGPT в .env:**

```env
YANDEX_GPT_API_KEY=ваш_api_ключ
YANDEX_GPT_FOLDER_ID=ваш_folder_id
```

6. **Настройте Telegram Bot в .env:**

```env
TELEGRAM_BOT_TOKEN=ваш_bot_token
```

7. **Выполните миграции:**

```bash
php artisan migrate
```

8. **Исправьте права доступа (для Docker):**

```bash
# Если используете Docker, выполните в контейнере:
docker exec backend-vue-chat-template /var/www/backend/fix-permissions.sh

# Или вручную:
docker exec backend-vue-chat-template chown -R www-data:www-data /var/www/backend/storage
docker exec backend-vue-chat-template chown -R www-data:www-data /var/www/backend/bootstrap/cache
docker exec backend-vue-chat-template chmod -R 775 /var/www/backend/storage
docker exec backend-vue-chat-template chmod -R 775 /var/www/backend/bootstrap/cache
```

9. **Создайте пользователя админа:**

```bash
php artisan make:filament-user
```

## Настройка YandexGPT

1. Перейдите в [Yandex Cloud Console](https://console.cloud.yandex.ru/)
2. Создайте новый проект или выберите существующий
3. Включите сервис "Yandex Foundation Models"
4. Создайте API ключ в разделе "Сервисные аккаунты"
5. Получите Folder ID из настроек проекта

## Настройка Telegram Bot

1. Создайте бота через [@BotFather](https://t.me/botfather)
2. Получите токен бота
3. Добавьте токен в .env файл
4. Запустите polling:

```bash
php artisan telegram:polling
```

## Использование

### Админ-панель

1. Откройте `/admin` в браузере
2. Войдите под созданным администратором
3. Перейдите в раздел "Документы"
4. Создайте новый документ:
    - Укажите название
    - Выберите пользователя
    - Загрузите файл или введите текст
    - Назначьте роли (опционально)
5. Нажмите "Генерировать эмбеддинги" после создания

### Telegram Bot

Доступные команды:

-   `/start` - Приветствие и справка
-   `/docs` - Просмотр доступных документов
-   `/ask [вопрос]` - Задать вопрос по документам

**Пример использования:**

```
/ask Что такое машинное обучение?
/ask Какие есть виды нейронных сетей?
```

## Структура проекта

```
app/
├── Console/Commands/          # Telegram команды
│   ├── TelegramStartCommand.php
│   ├── TelegramDocumentCommand.php
│   └── TelegramAskCommand.php
├── Filament/Resources/        # Админ-панель
│   └── DocumentResource.php
├── Jobs/                      # Фоновые задачи
│   └── ProcessDocumentEmbeddings.php
├── Models/                    # Модели данных
│   ├── Document.php
│   ├── DocumentEmbedding.php
│   └── User.php
└── Services/                  # Бизнес-логика
    ├── YandexGptService.php
    └── DocumentService.php
```

## API Endpoints

### YandexGPT Integration

-   **Эмбеддинги:** `POST /foundationModels/v1/textEmbedding`
-   **Генерация ответов:** `POST /foundationModels/v1/completion`

## Поддерживаемые форматы файлов

-   📄 `.txt` - Текстовые файлы
-   📄 `.pdf` - PDF документы (планируется)
-   📄 `.docx` - Word документы (планируется)

## Разработка

### Запуск в development режиме:

```bash
# Запуск веб-сервера
php artisan serve

# Запуск воркеров очереди
php artisan queue:work

# Запуск Telegram polling
php artisan telegram:polling

# Или всё сразу:
composer run dev
```

### Обработка эмбеддингов

Эмбеддинги генерируются:

-   Автоматически через админ-панель
-   В фоновом режиме через очереди
-   Вручную через кнопку в интерфейсе

### Настройка поиска

Система использует косинусное сходство для поиска релевантных фрагментов документов. Параметры можно настроить в `DocumentService`:

-   `$chunkSize` - размер фрагмента (по умолчанию 1000 символов)
-   `$overlap` - перекрытие фрагментов (по умолчанию 200 символов)
-   `$limit` - количество релевантных фрагментов (по умолчанию 5)

## Форматирование ответов

Система автоматически форматирует ответы ИИ для разных интерфейсов:

### Веб-интерфейс

-   Поддержка полного markdown синтаксиса
-   Автоматическая конвертация в HTML с Tailwind CSS стилями
-   Безопасное экранирование HTML тегов

### Telegram Bot

-   Конвертация markdown в читаемый текст с эмодзи
-   Автоматическая разбивка длинных сообщений
-   Безопасная обработка специальных символов

**Примеры форматирования:**

-   `**жирный**` → 🔸 жирный (Telegram) / **жирный** (Web)
-   `# Заголовок` → 📌 Заголовок (Telegram) / H1 заголовок (Web)
-   `` `код` `` → 💻 код (Telegram) / `код` (Web)

Подробнее: [FORMATTING_README.md](FORMATTING_README.md)

### Тестирование форматирования

```bash
# Тест форматирования
php artisan test:telegram-formatting

# Тест реальных ответов
php artisan test:chat-formatting --user-id=1
```

## Планы развития

-   🗄️ Переход на векторную базу данных (Qdrant/Weaviate)
-   📊 Аналитика использования
-   🔧 Расширенная обработка файлов
-   🌐 API для интеграции с другими системами

## Техподдержка

Для получения помощи:

1. Проверьте логи: `storage/logs/laravel.log`
2. Убедитесь, что все сервисы запущены
3. Проверьте настройки API ключей
4. Обратитесь к администратору системы

## Лицензия

MIT License
