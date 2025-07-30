import { computed, watch } from 'vue'
import { useChatStore } from '@/stores/chat/useChatStore'
import { useChatHistory, useSendChatMessage, useClearChatHistory } from '@/helpers/api/queries'
import { useChatErrorHandler } from './useChatErrorHandler'

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
  const { handleChatError } = useChatErrorHandler()
  
  // ============================================================================
  // API Queries
  // ============================================================================
  
  const { 
    data: historyData, 
    isLoading: isLoadingHistory,
    error: historyError,
    refetch: refetchHistory
  } = useChatHistory({ limit: 50 })
  
  const { 
    mutate: sendMessageMutation, 
    isPending: isSendingMessage,
    error: sendError
  } = useSendChatMessage()
  
  const { 
    mutate: clearHistoryMutation,
    isPending: isClearingHistory,
    error: clearError
  } = useClearChatHistory()
  
  // ============================================================================
  // Computed Properties
  // ============================================================================
  
  const messages = computed(() => chatStore.sortedMessages)
  const isLoading = computed(() => isLoadingHistory.value || chatStore.isLoading)
  const hasMessages = computed(() => chatStore.hasMessages)
  const lastMessage = computed(() => chatStore.lastMessage)
  
  // ============================================================================
  // Watchers
  // ============================================================================
  
  // Синхронизируем данные из API с store
  watch(historyData, (newData) => {
    if (newData?.messages) {
      chatStore.setMessages(newData.messages)
    }
  }, { immediate: true })
  
  // Обрабатываем ошибки API
  watch([historyError, sendError, clearError], ([hError, sError, cError]) => {
    const error = hError || sError || cError
    if (error) {
      handleChatError(error)
      chatStore.setError(error)
    }
  })
  
  // ============================================================================
  // Local Message Management
  // ============================================================================
  
  const localMessages = new Map()
  
  /**
   * Добавить локальное сообщение для мгновенного отображения
   * @param {Object} messageData - данные сообщения
   * @returns {string} localId - локальный ID сообщения
   */
  const addLocalMessage = (messageData) => {
    const localId = `local_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    
    const localMessage = {
      id: localId,
      message: messageData.message,
      type: 'user',
      created_at: new Date().toISOString(),
      isLocal: true,
      context_documents: messageData.documents?.map(doc => doc.label) || null,
      replies: []
    }
    
    localMessages.set(localId, localMessage)
    chatStore.addMessage(localMessage)
    
    return localId
  }
  
  /**
   * Удалить локальное сообщение
   * @param {string} localId - локальный ID сообщения
   */
  const removeLocalMessage = (localId) => {
    if (localMessages.has(localId)) {
      localMessages.delete(localId)
      chatStore.removeMessage(localId)
    }
  }
  
  /**
   * Заменить локальное сообщение на серверное
   * @param {string} localId - локальный ID
   * @param {Object} serverMessage - сообщение с сервера
   */
  const replaceLocalMessage = (localId, serverMessage) => {
    removeLocalMessage(localId)
    chatStore.addMessage(serverMessage)
    
    // Добавляем ответы если есть
    if (serverMessage.replies && serverMessage.replies.length > 0) {
      serverMessage.replies.forEach(reply => {
        chatStore.addReply(serverMessage.id, reply)
      })
    }
  }
  
  // ============================================================================
  // Message Actions
  // ============================================================================
  
  /**
   * Отправить сообщение
   * @param {Object} messageData - данные сообщения
   * @returns {Promise}
   */
  const sendMessage = async (messageData) => {
    // Добавляем локальное сообщение для мгновенного отображения
    const localId = addLocalMessage(messageData)
    
    // Устанавливаем состояние загрузки
    chatStore.setLoading(true, localId)
    chatStore.clearError()
    
    try {
      const response = await new Promise((resolve, reject) => {
        sendMessageMutation(messageData, {
          onSuccess: resolve,
          onError: reject
        })
      })
      
      // Заменяем локальное сообщение на серверное
      if (response?.data?.data?.user_message) {
        replaceLocalMessage(localId, response.data.data.user_message)
        
        // Добавляем ответ бота если есть
        if (response.data.data.bot_response) {
          chatStore.addReply(
            response.data.data.user_message.id, 
            response.data.data.bot_response
          )
        }
      }
      
      return response
      
    } catch (error) {
      // Удаляем локальное сообщение при ошибке
      removeLocalMessage(localId)
      throw error
      
    } finally {
      chatStore.setLoading(false)
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
          onError: reject
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
    addLocalMessage,
    removeLocalMessage,
    
    // Store actions (для прямого доступа если нужно)
    addMessage: chatStore.addMessage,
    addReply: chatStore.addReply,
    setLoading: chatStore.setLoading,
    setError: chatStore.setError,
    clearError: chatStore.clearError
  }
}