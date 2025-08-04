import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'

/**
 * Композабл для обработки ошибок аутентификации
 * Централизует логику обработки и отображения ошибок авторизации
 */
export function useAuthErrorHandler() {
  const toast = useToast()
  const lastError = ref(null)
  const isHandlingError = ref(false)

  /**
   * Типы ошибок аутентификации
   */
  const ERROR_TYPES = {
    INVALID_CREDENTIALS: 'invalid_credentials',
    NETWORK_ERROR: 'network_error',
    SERVER_ERROR: 'server_error',
    VALIDATION_ERROR: 'validation_error',
    TOKEN_EXPIRED: 'token_expired',
    UNAUTHORIZED: 'unauthorized',
    FORBIDDEN: 'forbidden',
    UNKNOWN: 'unknown',
  }

  /**
   * Сообщения об ошибках для пользователя
   */
  const ERROR_MESSAGES = {
    [ERROR_TYPES.INVALID_CREDENTIALS]: 'Неверный логин или пароль',
    [ERROR_TYPES.NETWORK_ERROR]:
      'Ошибка сети. Проверьте подключение к интернету',
    [ERROR_TYPES.SERVER_ERROR]: 'Ошибка сервера. Попробуйте позже',
    [ERROR_TYPES.VALIDATION_ERROR]: 'Проверьте правильность введенных данных',
    [ERROR_TYPES.TOKEN_EXPIRED]: 'Сессия истекла. Войдите в систему заново',
    [ERROR_TYPES.UNAUTHORIZED]: 'Необходима авторизация',
    [ERROR_TYPES.FORBIDDEN]: 'Недостаточно прав доступа',
    [ERROR_TYPES.UNKNOWN]: 'Произошла неизвестная ошибка',
  }

  /**
   * Определить тип ошибки на основе объекта ошибки
   * @param {Error|Object} error - объект ошибки
   * @returns {string} тип ошибки
   */
  const getErrorType = (error) => {
    if (!error) return ERROR_TYPES.UNKNOWN

    // Проверяем HTTP статус коды
    if (error.response?.status) {
      const status = error.response.status
      switch (status) {
        case 401:
          return ERROR_TYPES.UNAUTHORIZED
        case 403:
          return ERROR_TYPES.FORBIDDEN
        case 422:
          return ERROR_TYPES.VALIDATION_ERROR
        case 500:
        case 502:
        case 503:
        case 504:
          return ERROR_TYPES.SERVER_ERROR
        default:
          break
      }
    }

    // Проверяем сообщения об ошибках
    const message = error.message?.toLowerCase() || ''
    if (message.includes('network') || message.includes('fetch')) {
      return ERROR_TYPES.NETWORK_ERROR
    }
    if (message.includes('credentials') || message.includes('password')) {
      return ERROR_TYPES.INVALID_CREDENTIALS
    }
    if (message.includes('token') || message.includes('expired')) {
      return ERROR_TYPES.TOKEN_EXPIRED
    }

    // Проверяем кастомные типы ошибок
    if (error.type && ERROR_TYPES[error.type.toUpperCase()]) {
      return error.type.toLowerCase()
    }

    return ERROR_TYPES.UNKNOWN
  }

  /**
   * Получить пользовательское сообщение об ошибке
   * @param {string} errorType - тип ошибки
   * @param {Error|Object} originalError - оригинальная ошибка
   * @returns {string} сообщение для пользователя
   */
  const getErrorMessage = (errorType, originalError) => {
    // Если есть кастомное сообщение в ошибке
    if (originalError?.response?.data?.message) {
      return originalError.response.data.message
    }
    if (originalError?.userMessage) {
      return originalError.userMessage
    }

    return ERROR_MESSAGES[errorType] || ERROR_MESSAGES[ERROR_TYPES.UNKNOWN]
  }

  /**
   * Обработать ошибку аутентификации
   * @param {Error|Object} error - объект ошибки
   * @param {Object} options - дополнительные опции
   * @param {string} options.action - действие, при котором произошла ошибка
   * @param {boolean} options.showToast - показывать ли toast уведомление
   * @param {Function} options.onError - кастомный обработчик ошибки
   */
  const handleAuthError = (error, options = {}) => {
    const { action = 'authentication', showToast = true, onError } = options

    if (isHandlingError.value) return

    isHandlingError.value = true
    lastError.value = error

    try {
      const errorType = getErrorType(error)
      const errorMessage = getErrorMessage(errorType, error)

      console.error(`Auth error during ${action}:`, {
        type: errorType,
        message: errorMessage,
        originalError: error,
      })

      // Показываем toast уведомление
      if (showToast) {
        toast.add({
          severity: 'error',
          summary: 'Ошибка авторизации',
          detail: errorMessage,
          life: 5000,
        })
      }

      // Вызываем кастомный обработчик если есть
      if (onError && typeof onError === 'function') {
        onError(error, errorType, errorMessage)
      }

      // Специальная обработка для истекших токенов
      if (
        errorType === ERROR_TYPES.TOKEN_EXPIRED ||
        errorType === ERROR_TYPES.UNAUTHORIZED
      ) {
        // Здесь можно добавить логику для редиректа на страницу входа
        // или обновления токена
      }
    } catch (handlingError) {
      console.error('Error while handling auth error:', handlingError)
    } finally {
      isHandlingError.value = false
    }
  }

  /**
   * Очистить последнюю ошибку
   */
  const clearError = () => {
    lastError.value = null
  }

  /**
   * Проверить, можно ли повторить операцию после ошибки
   * @param {string} errorType - тип ошибки
   * @returns {boolean} можно ли повторить
   */
  const canRetry = (errorType) => {
    const retryableErrors = [
      ERROR_TYPES.NETWORK_ERROR,
      ERROR_TYPES.SERVER_ERROR,
    ]
    return retryableErrors.includes(errorType)
  }

  /**
   * Получить рекомендации по исправлению ошибки
   * @param {string} errorType - тип ошибки
   * @returns {string} рекомендации для пользователя
   */
  const getErrorSuggestion = (errorType) => {
    const suggestions = {
      [ERROR_TYPES.INVALID_CREDENTIALS]:
        'Проверьте правильность введенного логина и пароля',
      [ERROR_TYPES.NETWORK_ERROR]:
        'Проверьте подключение к интернету и попробуйте еще раз',
      [ERROR_TYPES.SERVER_ERROR]:
        'Попробуйте обновить страницу или повторить попытку позже',
      [ERROR_TYPES.VALIDATION_ERROR]:
        'Убедитесь, что все поля заполнены корректно',
      [ERROR_TYPES.TOKEN_EXPIRED]: 'Войдите в систему заново',
      [ERROR_TYPES.UNAUTHORIZED]:
        'Войдите в систему для доступа к этой функции',
      [ERROR_TYPES.FORBIDDEN]:
        'Обратитесь к администратору для получения доступа',
    }

    return (
      suggestions[errorType] ||
      'Попробуйте обновить страницу или обратитесь в поддержку'
    )
  }

  return {
    // State
    lastError,
    isHandlingError,

    // Constants
    ERROR_TYPES,
    ERROR_MESSAGES,

    // Methods
    handleAuthError,
    clearError,
    getErrorType,
    getErrorMessage,
    canRetry,
    getErrorSuggestion,
  }
}
