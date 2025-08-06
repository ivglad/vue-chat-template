/**
 * Композабл для управления сообщениями чата
 * Инкапсулирует логику работы с API и состоянием
 * Следует принципам DRY и Single Responsibility
 */
export function useChatMessages() {
  // ============================================================================
  // Dependencies
  // ============================================================================

  const chatStore = useChatStore()
  const { showError } = useChatErrorHandler()

  // ============================================================================
  // API Queries
  // ============================================================================

  const {
    data: historyData,
    isLoading: isLoadingHistory,
    error: historyError,
    refetch: refetchHistory,
  } = useChatHistory({ limit: 50 })

  const {
    mutate: sendMessageMutation,
    isPending: isSendingMessage,
    error: sendError,
  } = useSendChatMessage()

  const {
    mutate: clearHistoryMutation,
    isPending: isClearingHistory,
    error: clearError,
  } = useClearChatHistory()

  // ============================================================================
  // Computed Properties
  // ============================================================================

  const messages = computed(() => chatStore.sortedMessages)
  const isLoading = computed(
    () => isLoadingHistory.value || chatStore.isLoading,
  )
  const hasMessages = computed(() => chatStore.hasMessages)
  const lastMessage = computed(() => chatStore.lastMessage)

  // ============================================================================
  // Watchers
  // ============================================================================

  // Синхронизируем данные из API с store
  watch(
    historyData,
    (newData) => {
      if (newData?.messages) {
        // НЕ заменяем все сообщения, если есть локальные сообщения
        const currentMessages = chatStore.messages
        const hasLocalMessages = currentMessages.some(
          (msg) =>
            msg.isLocal ||
            msg.status === 'local' ||
            msg.status === 'sending' ||
            msg.status === 'loading' ||
            msg.status === 'error',
        )

        // Если есть локальные сообщения, не перезаписываем историю
        if (!hasLocalMessages) {
          chatStore.setMessages(newData.messages)
        }
      }
    },
    { immediate: true },
  )

  // Обрабатываем ошибки API
  watch([historyError, sendError, clearError], ([hError, sError, cError]) => {
    const error = hError || sError || cError
    if (error) {
      if (hError) {
        showError(error, 'Ошибка загрузки \nистории')
      } else if (sError) {
        showError(error, 'Ошибка отправки \nсообщения')
      } else if (cError) {
        showError(error, 'Ошибка очистки \nистории')
      }
      chatStore.setError(error)
    }
  })

  // ============================================================================
  // New Message Management with States
  // ============================================================================

  // ============================================================================
  // Message Actions
  // ============================================================================

  /**
   * Отправить сообщение с новой логикой состояний
   * @param {Object} messageData - данные сообщения
   * @returns {Promise}
   */
  const sendMessage = async (messageData) => {
    // 1. Создаем локальное сообщение пользователя (мгновенно отображается)
    const userMessage = chatStore.createLocalUserMessage(messageData)

    // 2. Создаем загрузочное сообщение бота
    const loadingMessage = chatStore.createLoadingBotMessage(userMessage.id)

    // 3. Небольшая задержка, чтобы пользователь увидел локальное сообщение
    await new Promise((resolve) => setTimeout(resolve, 100))

    // 4. Обновляем статус пользовательского сообщения на "отправляется"
    chatStore.updateMessageStatus(userMessage.id, 'sending')
    chatStore.clearError()

    try {
      const response = await new Promise((resolve, reject) => {
        sendMessageMutation(messageData, {
          onSuccess: resolve,
          onError: reject,
        })
      })

      if (
        response?.data?.data?.user_message &&
        response?.data?.data?.bot_response
      ) {
        // 5. Обновляем статус пользовательского сообщения на "отправлено"
        chatStore.updateMessageStatus(userMessage.id, 'sent')

        // 6. Заменяем загрузочное сообщение на реальный ответ бота
        chatStore.replaceLoadingMessage(
          loadingMessage.id,
          response.data.data.bot_response,
        )

        // 7. Обновляем статус пользовательского сообщения на "получен ответ"
        chatStore.updateMessageStatus(userMessage.id, 'replied')

        // 8. Обновляем документы в сообщении пользователя, если они не были прикреплены
        const serverUserMessage = response.data.data.user_message
        if (serverUserMessage && serverUserMessage.context_documents && 
            (!userMessage.context_documents || userMessage.context_documents.length === 0)) {
          chatStore.updateMessageDocuments(userMessage.id, serverUserMessage.context_documents)
        }
      }

      return response
    } catch (error) {
      // При ошибке НЕ удаляем локальное сообщение пользователя!
      // Заменяем загрузочное сообщение на сообщение об ошибке
      chatStore.replaceLoadingMessageWithError(loadingMessage.id)
      chatStore.updateMessageStatus(userMessage.id, 'replied')
      throw error
    }
  }

  /**
   * Очистить историю чата
   * @returns {Promise}
   */
  const clearHistory = async () => {
    chatStore.setLoading(true)
    chatStore.clearError()

    try {
      await new Promise((resolve, reject) => {
        clearHistoryMutation(undefined, {
          onSuccess: resolve,
          onError: reject,
        })
      })

      chatStore.clearMessages()
    } catch (error) {
      throw error
    } finally {
      chatStore.setLoading(false)
    }
  }

  /**
   * Обновить историю сообщений
   */
  const refreshMessages = () => {
    refetchHistory()
  }

  // ============================================================================
  // Return
  // ============================================================================

  return {
    // State
    messages,
    isLoading,
    hasMessages,
    lastMessage,
    isSendingMessage,
    isClearingHistory,

    // Actions
    sendMessage,
    clearHistory,
    refreshMessages,

    // Store actions (для прямого доступа если нужно)
    addMessage: chatStore.addMessage,
    addReply: chatStore.addReply,
    setLoading: chatStore.setLoading,
    setError: chatStore.setError,
    clearError: chatStore.clearError,
    updateMessageDocuments: chatStore.updateMessageDocuments,
  }
}
