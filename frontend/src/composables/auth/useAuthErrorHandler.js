import { ref, computed } from 'vue'

/**
 * Композабл для обработки ошибок авторизации
 * Следует принципам централизованной обработки ошибок
 * Аналогичен useChatErrorHandler но для авторизации
 */
export const useAuthErrorHandler = () => {
  // ============================================================================
  // State
  // ============================================================================
  
  const lastError = ref(null)
  const errorHistory = ref([])
  
  // ============================================================================
  // Computed
  // ============================================================================
  
  const hasError = computed(() => lastError.value !== null)
  
  const canRetry = computed(() => {
    if (!lastError.value) return false
    
    const retryableErrors = [
      'network_error',
      'timeout_error',
      'server_error'
    ]
    
    return retryableErrors.includes(lastError.value.type)
  })
  
  const errorMessage = computed(() => {
    if (!lastError.value) return ''
    
    return lastError.value.message || 'Произошла неизвестная ошибка'
  })
  
  // ============================================================================
  // Methods
  // ============================================================================
  
  /**
   * Обработать ошибку
   * @param {Error|Object} error - объект ошибки
   * @param {Object} context - контекст ошибки
   */
  const handleError = (error, context = {}) => {
    const processedError = processError(error, context)
    
    lastError.value = processedError
    errorHistory.value.push({
      ...processedError,
      timestamp: new Date().toISOString()
    })
    
    // Ограничиваем историю ошибок
    if (errorHistory.value.length > 10) {
      errorHistory.value = errorHistory.value.slice(-10)
    }
    
    console.error('Auth Error:', processedError)
  }
  
  /**
   * Обработать объект ошибки
   * @param {Error|Object} error - ошибка
   * @param {Object} context - контекст
   * @returns {Object} обработанная ошибка
   */
  const processError = (error, context) => {
    const baseError = {
      id: Date.now(),
      context,
      timestamp: new Date().toISOString()
    }
    
    // Обработка HTTP ошибок
    if (error?.response) {
      const status = error.response.status
      const data = error.response.data
      
      return {
        ...baseError,
        type: getErrorTypeByStatus(status),
        message: data?.message || getDefaultMessageByStatus(status),
        status,
        details: data
      }
    }
    
    // Обработка сетевых ошибок
    if (error?.code === 'NETWORK_ERROR' || error?.message?.includes('Network Error')) {
      return {
        ...baseError,
        type: 'network_error',
        message: 'Ошибка сети. Проверьте подключение к интернету.'
      }
    }
    
    // Обработка ошибок таймаута
    if (error?.code === 'ECONNABORTED' || error?.message?.includes('timeout')) {
      return {
        ...baseError,
        type: 'timeout_error',
        message: 'Превышено время ожидания. Попробуйте еще раз.'
      }
    }
    
    // Обработка общих ошибок
    return {
      ...baseError,
      type: 'unknown_error',
      message: error?.message || 'Произошла неизвестная ошибка'
    }
  }
  
  /**
   * Получить тип ошибки по HTTP статусу
   * @param {number} status - HTTP статус
   * @returns {string} тип ошибки
   */
  const getErrorTypeByStatus = (status) => {
    if (status >= 400 && status < 500) {
      return 'client_error'
    }
    if (status >= 500) {
      return 'server_error'
    }
    return 'unknown_error'
  }
  
  /**
   * Получить сообщение по умолчанию для HTTP статуса
   * @param {number} status - HTTP статус
   * @returns {string} сообщение
   */
  const getDefaultMessageByStatus = (status) => {
    const messages = {
      400: 'Неверные данные запроса',
      401: 'Неверные учетные данные',
      403: 'Доступ запрещен',
      404: 'Ресурс не найден',
      422: 'Ошибка валидации данных',
      429: 'Слишком много запросов. Попробуйте позже.',
      500: 'Внутренняя ошибка сервера',
      502: 'Сервер недоступен',
      503: 'Сервис временно недоступен'
    }
    
    return messages[status] || `Ошибка ${status}`
  }
  
  /**
   * Очистить последнюю ошибку
   */
  const clearError = () => {
    lastError.value = null
  }
  
  /**
   * Очистить всю историю ошибок
   */
  const clearErrorHistory = () => {
    errorHistory.value = []
    lastError.value = null
  }
  
  /**
   * Получить статистику ошибок
   * @returns {Object} статистика
   */
  const getErrorStats = () => {
    const stats = {
      total: errorHistory.value.length,
      byType: {},
      recent: errorHistory.value.slice(-5)
    }
    
    errorHistory.value.forEach(error => {
      stats.byType[error.type] = (stats.byType[error.type] || 0) + 1
    })
    
    return stats
  }
  
  // ============================================================================
  // Return
  // ============================================================================
  
  return {
    // State
    lastError,
    errorHistory,
    
    // Computed
    hasError,
    canRetry,
    errorMessage,
    
    // Methods
    handleError,
    clearError,
    clearErrorHistory,
    getErrorStats
  }
}