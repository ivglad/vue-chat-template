import { ref } from 'vue'

/**
 * Композабл для централизованной обработки ошибок чата
 * Следует принципам Single Responsibility и DRY
 */
export function useChatErrorHandler() {
  // ============================================================================
  // State
  // ============================================================================
  
  const lastError = ref(null)
  const errorHistory = ref([])
  
  // ============================================================================
  // Error Mapping
  // ============================================================================
  
  const ERROR_MESSAGES = {
    // HTTP статус коды
    400: 'Некорректный запрос. Проверьте данные сообщения.',
    401: 'Необходима авторизация. Войдите в систему.',
    403: 'Нет доступа к указанным документам.',
    404: 'Ресурс не найден.',
    422: 'Ошибка валидации данных.',
    429: 'Слишком много запросов. Попробуйте позже.',
    500: 'Ошибка сервера. Попробуйте позже.',
    502: 'Сервер временно недоступен.',
    503: 'Сервис временно недоступен.',
    
    // Сетевые ошибки
    'NETWORK_ERROR': 'Проблемы с подключением к интернету.',
    'TIMEOUT_ERROR': 'Превышено время ожидания ответа.',
    'ABORT_ERROR': 'Запрос был отменен.',
    
    // Ошибки чата
    'EMPTY_MESSAGE': 'Сообщение не может быть пустым.',
    'MESSAGE_TOO_LONG': 'Сообщение слишком длинное.',
    'NO_DOCUMENTS_ACCESS': 'Нет доступа к выбранным документам.',
    'PROCESSING_ERROR': 'Ошибка обработки сообщения.',
    
    // Общие ошибки
    'UNKNOWN_ERROR': 'Произошла неизвестная ошибка.'
  }
  
  // ============================================================================
  // Error Classification
  // ============================================================================
  
  /**
   * Классифицировать ошибку по типу
   * @param {Error|Object} error - объект ошибки
   * @returns {Object} классификация ошибки
   */
  const classifyError = (error) => {
    // HTTP ошибки
    if (error?.response?.status) {
      return {
        type: 'http',
        code: error.response.status,
        severity: error.response.status >= 500 ? 'error' : 'warn',
        retryable: error.response.status >= 500 || error.response.status === 429
      }
    }
    
    // Сетевые ошибки
    if (error?.code === 'NETWORK_ERROR' || error?.message?.includes('Network Error')) {
      return {
        type: 'network',
        code: 'NETWORK_ERROR',
        severity: 'error',
        retryable: true
      }
    }
    
    // Timeout ошибки
    if (error?.code === 'ECONNABORTED' || error?.message?.includes('timeout')) {
      return {
        type: 'timeout',
        code: 'TIMEOUT_ERROR',
        severity: 'warn',
        retryable: true
      }
    }
    
    // Отмененные запросы
    if (error?.code === 'ERR_CANCELED') {
      return {
        type: 'abort',
        code: 'ABORT_ERROR',
        severity: 'info',
        retryable: false
      }
    }
    
    // Неизвестные ошибки
    return {
      type: 'unknown',
      code: 'UNKNOWN_ERROR',
      severity: 'error',
      retryable: false
    }
  }
  
  /**
   * Получить пользовательское сообщение об ошибке
   * @param {Object} errorClassification - классификация ошибки
   * @param {Error|Object} originalError - оригинальная ошибка
   * @returns {string} сообщение для пользователя
   */
  const getUserMessage = (errorClassification, originalError) => {
    // Проверяем кастомное сообщение с сервера
    const serverMessage = originalError?.response?.data?.message
    if (serverMessage && typeof serverMessage === 'string') {
      return serverMessage
    }
    
    // Используем предопределенное сообщение
    return ERROR_MESSAGES[errorClassification.code] || ERROR_MESSAGES.UNKNOWN_ERROR
  }
  
  // ============================================================================
  // Error Handling
  // ============================================================================
  
  /**
   * Обработать ошибку чата
   * @param {Error|Object} error - объект ошибки
   * @param {Object} context - контекст ошибки
   * @returns {Object} обработанная ошибка
   */
  const handleChatError = (error, context = {}) => {
    const classification = classifyError(error)
    const userMessage = getUserMessage(classification, error)
    
    const processedError = {
      id: Date.now(),
      timestamp: new Date().toISOString(),
      originalError: error,
      classification,
      userMessage,
      context,
      handled: true
    }
    
    // Сохраняем ошибку
    lastError.value = processedError
    errorHistory.value.unshift(processedError)
    
    // Ограничиваем историю ошибок
    if (errorHistory.value.length > 50) {
      errorHistory.value = errorHistory.value.slice(0, 50)
    }
    
    // Логируем для разработки
    if (process.env.NODE_ENV === 'development') {
      console.group('🚨 Chat Error')
      console.error('Original Error:', error)
      console.info('Classification:', classification)
      console.info('User Message:', userMessage)
      console.info('Context:', context)
      console.groupEnd()
    }
    
    // Показываем toast уведомление
    const toast = useToast()
    toast.add({
      severity: classification.severity,
      summary: 'Ошибка чата',
      detail: userMessage,
      life: classification.severity === 'error' ? 8000 : 5000
    })
    
    return processedError
  }
  
  /**
   * Обработать ошибку отправки сообщения
   * @param {Error|Object} error - объект ошибки
   * @param {Object} messageData - данные сообщения
   */
  const handleSendMessageError = (error, messageData) => {
    return handleChatError(error, {
      action: 'send_message',
      messageData: {
        messageLength: messageData?.message?.length || 0,
        hasDocuments: Boolean(messageData?.document_ids?.length),
        documentCount: messageData?.document_ids?.length || 0
      }
    })
  }
  
  /**
   * Обработать ошибку загрузки истории
   * @param {Error|Object} error - объект ошибки
   * @param {Object} params - параметры запроса
   */
  const handleHistoryError = (error, params) => {
    return handleChatError(error, {
      action: 'load_history',
      params
    })
  }
  
  /**
   * Обработать ошибку очистки истории
   * @param {Error|Object} error - объект ошибки
   */
  const handleClearHistoryError = (error) => {
    return handleChatError(error, {
      action: 'clear_history'
    })
  }
  
  /**
   * Проверить, можно ли повторить операцию
   * @param {Object} processedError - обработанная ошибка
   * @returns {boolean}
   */
  const canRetry = (processedError) => {
    return processedError?.classification?.retryable || false
  }
  
  /**
   * Очистить последнюю ошибку
   */
  const clearLastError = () => {
    lastError.value = null
  }
  
  /**
   * Очистить историю ошибок
   */
  const clearErrorHistory = () => {
    errorHistory.value = []
    lastError.value = null
  }
  
  // ============================================================================
  // Return
  // ============================================================================
  
  return {
    // State
    lastError,
    errorHistory,
    
    // General error handling
    handleChatError,
    classifyError,
    getUserMessage,
    
    // Specific error handlers
    handleSendMessageError,
    handleHistoryError,
    handleClearHistoryError,
    
    // Utilities
    canRetry,
    clearLastError,
    clearErrorHistory
  }
}