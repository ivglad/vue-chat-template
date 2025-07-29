# Требования к .env файлам

## backend/.env

```
DB_CONNECTION=pgsql
DB_HOST=database
DB_PORT=5432
DB_DATABASE=docwise
DB_USERNAME=docwise
DB_PASSWORD=password

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

YANDEX_GPT_API_KEY=your_api_key
YANDEX_GPT_FOLDER_ID=your_folder_id

TELEGRAM_BOT_TOKEN=your_bot_token

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

## .env (root)

```
DOCKER_PROJECT_NAME=vue-chat
HOST_FRONTEND_PORT=3000
DB_PASSWORD=password
DB_DATABASE=docwise
DB_USERNAME=docwise
```
