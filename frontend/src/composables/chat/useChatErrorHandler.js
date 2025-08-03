/**
 * Простой композабл для обработки ошибок чата
 * Показывает toast уведомления при ошибках
 */
export function useChatErrorHandler() {
  const toast = useToast()

  /**
   * Получить сообщение об ошибке для пользователя
   * @param {Error|Object} error - объект ошибки
   * @returns {string} сообщение для пользователя
   */
  const getErrorMessage = (error) => {
    // Проверяем сообщение с сервера
    const serverMessage = error?.response?.data?.message
    if (serverMessage && typeof serverMessage === 'string') {
      return serverMessage
    }

    // Проверяем HTTP статус коды
    const status = error?.response?.status
    if (status) {
      const statusMessages = {
        400: 'Некорректный запрос. Проверьте данные сообщения.',
        401: 'Необходима авторизация. Войдите в систему.',
        403: 'Нет доступа к указанным документам.',
        404: 'Ресурс не найден.',
        422: 'Ошибка валидации данных.',
        429: 'Слишком много запросов. Попробуйте позже.',
        500: 'Ошибка сервера. Попробуйте позже.',
        502: 'Сервер временно недоступен.',
        503: 'Сервис временно недоступен.',
      }
      return statusMessages[status] || 'Произошла ошибка на сервере.'
    }

    // Сетевые ошибки
    if (
      error?.code === 'NETWORK_ERROR' ||
      error?.message?.includes('Network Error')
    ) {
      return 'Проблемы с подключением к интернету.'
    }

    if (error?.code === 'ECONNABORTED' || error?.message?.includes('timeout')) {
      return 'Превышено время ожидания ответа.'
    }

    // Общая ошибка
    return 'Произошла неизвестная ошибка.'
  }

  /**
   * Показать ошибку в toast
   * @param {Error|Object} error - объект ошибки
   * @param {string} title - заголовок ошибки
   */
  const showError = (error, title = 'Ошибка') => {
    const message = getErrorMessage(error)

    // Логируем для разработки
    if (process.env.NODE_ENV === 'development') {
      console.error('Chat Error:', error)
    }

    // Показываем toast
    toast.add({
      severity: 'error',
      summary: title,
      detail: message,
      life: 5000,
    })
  }

  return {
    showError,
  }
}
