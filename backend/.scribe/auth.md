# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {ВАШ_ТОКЕН}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Вы можете получить токен авторизации через эндпоинт <code>POST /api/v1/auth/login</code>. Токен должен быть передан в заголовке Authorization с префиксом Bearer.
