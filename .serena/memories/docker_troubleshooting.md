# Docker Troubleshooting Commands

## Проверка контейнера
```bash
# Проверка доступности bash
docker exec backend-vue-chat-template which bash

# Проверка версии bash
docker exec backend-vue-chat-template bash --version

# Проверка entrypoint
docker exec backend-vue-chat-template ls -la /usr/local/bin/entrypoint.sh

# Проверка прав выполнения
docker exec backend-vue-chat-template stat /usr/local/bin/entrypoint.sh

# Запуск entrypoint вручную
docker exec backend-vue-chat-template /usr/local/bin/entrypoint.sh --help

# Проверка логов
docker-compose logs backend

# Интерактивный режим для отладки
docker run -it --rm backend-vue-chat-template /bin/sh
```

## Альтернативные команды
```bash
# Если bash недоступен, используем sh
docker exec backend-vue-chat-template sh /usr/local/bin/entrypoint.sh --help

# Проверка Alpine пакетов
docker exec backend-vue-chat-template apk list | grep bash
```