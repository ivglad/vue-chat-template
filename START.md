# Запуск системы

## Предварительные требования

1. Docker и Docker Compose
2. Backend/.env файл с настройками БД и API ключами

## Запуск всех сервисов

```bash
docker-compose up -d --build
```

## Проверка статуса сервисов

```bash
docker-compose ps
docker-compose logs -f backend
```

## Доступные эндпоинты

- Frontend: http://localhost:${HOST_FRONTEND_PORT}
- Backend API: http://localhost:${HOST_FRONTEND_PORT}/api
- Backend Admin: http://localhost:${HOST_FRONTEND_PORT}/admin

## Инициализация backend

```bash
docker-compose exec backend php artisan migrate:fresh --seed
docker-compose exec backend php artisan api:create-test-user
```

## Тестирование API

```bash
docker-compose exec backend php artisan test:api-chat
```
