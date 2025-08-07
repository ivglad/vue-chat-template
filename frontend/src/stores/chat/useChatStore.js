import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

/**
 * Основной store для управления состоянием чата
 * Следует принципам Single Responsibility и Separation of Concerns
 */
export const useChatStore = defineStore('chat', () => {
  // ============================================================================
  // State
  // ============================================================================

  const messages = ref([])
  const isLoading = ref(false)
  const error = ref(null)
  const currentUser = ref(null)

  // UI состояние
  const isTyping = ref(false)
  const loadingMessageId = ref(null)

  // Счетчик для локальных ID
  let localIdCounter = 0

  // ============================================================================
  // Getters
  // ============================================================================

  const sortedMessages = computed(() => {
    // Используем умную сортировку, которая группирует сообщения по парам
    const result = []
    
    // Сначала сортируем все сообщения по времени создания
    const timesorted = messages.value
      .slice()
      .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
    
    // Группируем сообщения по парам пользователь-бот
    const processed = new Set()
    
    timesorted.forEach(message => {
      if (processed.has(message.id)) return
      
      if (message.type === 'user') {
        // Добавляем сообщение пользователя
        result.push(message)
        processed.add(message.id)
        
        // Ищем соответствующий ответ бота
        // 1. Сначала проверяем replies (для серверных данных)
        if (message.replies && message.replies.length > 0) {
          message.replies.forEach(reply => {
            result.push(reply)
            processed.add(reply.id)
          })
        } else {
          // 2. Ищем бота с parentId равным ID пользователя (для локальных данных)
          const botReply = timesorted.find(msg => 
            msg.type === 'bot' && 
            msg.parentId === message.id && 
            !processed.has(msg.id)
          )
          if (botReply) {
            result.push(botReply)
            processed.add(botReply.id)
          }
        }
      }
    })
    return result
  })

  const hasMessages = computed(() => messages.value.length > 0)

  const lastMessage = computed(() => {
    return sortedMessages.value[sortedMessages.value.length - 1] || null
  })

  const messagesWithReplies = computed(() => {
    return sortedMessages.value.filter((msg) => msg.type === 'user')
  })

  // ============================================================================
  // Actions
  // ============================================================================

  /**
   * Установить сообщения из API
   * @param {Array} newMessages - массив сообщений
   */
  const setMessages = (newMessages) => {
    messages.value = newMessages || []
    error.value = null
  }

  /**
   * Добавить новое сообщение
   * @param {Object} message - объект сообщения
   */
  const addMessage = (message) => {
    if (!message || !message.id) {
      console.warn('Invalid message object:', message)
      return
    }

    // Проверяем, не существует ли уже такое сообщение
    const existingIndex = messages.value.findIndex((m) => m.id === message.id)
    if (existingIndex !== -1) {
      // Обновляем существующее сообщение
      const updatedMessage = {
        ...messages.value[existingIndex],
        ...message,
      }
      messages.value = [
        ...messages.value.slice(0, existingIndex),
        updatedMessage,
        ...messages.value.slice(existingIndex + 1),
      ]
    } else {
      // Добавляем новое сообщение
      messages.value = [...messages.value, message]
    }
  }

  /**
   * Добавить ответ к существующему сообщению
   * @param {number} parentId - ID родительского сообщения
   * @param {Object} reply - объект ответа
   */
  const addReply = (parentId, reply) => {
    const parentMessageIndex = messages.value.findIndex(
      (m) => m.id === parentId,
    )
    if (parentMessageIndex !== -1) {
      const parentMessage = messages.value[parentMessageIndex]
      const currentReplies = parentMessage.replies || []

      // Проверяем, не существует ли уже такой ответ
      const existingReplyIndex = currentReplies.findIndex(
        (r) => r.id === reply.id,
      )

      let updatedReplies
      if (existingReplyIndex !== -1) {
        // Обновляем существующий ответ
        updatedReplies = [...currentReplies]
        updatedReplies[existingReplyIndex] = reply
      } else {
        // Добавляем новый ответ
        updatedReplies = [...currentReplies, reply]
      }

      // Создаем обновленное сообщение
      const updatedMessage = {
        ...parentMessage,
        replies: updatedReplies,
      }

      // Заменяем сообщение в массиве
      messages.value = [
        ...messages.value.slice(0, parentMessageIndex),
        updatedMessage,
        ...messages.value.slice(parentMessageIndex + 1),
      ]
    }
  }

  /**
   * Удалить сообщение по ID
   * @param {number} messageId - ID сообщения
   */
  const removeMessage = (messageId) => {
    const index = messages.value.findIndex((m) => m.id === messageId)
    if (index !== -1) {
      messages.value = [
        ...messages.value.slice(0, index),
        ...messages.value.slice(index + 1),
      ]
    }
  }

  /**
   * Очистить все сообщения
   */
  const clearMessages = () => {
    messages.value = []
    error.value = null
    isLoading.value = false
    loadingMessageId.value = null
  }

  /**
   * Установить состояние загрузки
   * @param {boolean} loading - состояние загрузки
   * @param {number|null} messageId - ID сообщения для которого показывать загрузку
   */
  const setLoading = (loading, messageId = null) => {
    isLoading.value = loading
    loadingMessageId.value = messageId
  }

  /**
   * Установить ошибку
   * @param {Error|string|null} newError - объект ошибки или сообщение
   */
  const setError = (newError) => {
    error.value = newError
    isLoading.value = false
  }

  /**
   * Очистить ошибку
   */
  const clearError = () => {
    error.value = null
  }

  /**
   * Установить состояние печати
   * @param {boolean} typing - состояние печати
   */
  const setTyping = (typing) => {
    isTyping.value = typing
  }

  /**
   * Создать локальное сообщение пользователя
   * @param {Object} messageData - данные сообщения
   * @returns {Object} созданное сообщение
   */
  const createLocalUserMessage = (messageData) => {
    const localId = `local_${Date.now()}_${++localIdCounter}`

    const localMessage = {
      id: localId,
      message: messageData.message,
      type: 'user',
      status: 'local',
      isLoading: false,
      loadingText: '',
      context_documents: messageData.documents?.map((doc) => doc.label) || null,
      created_at: new Date().toISOString(),
      parentId: null,
      replies: [],
      isLocal: true,
    }

    addMessage(localMessage)
    return localMessage
  }

  /**
   * Создать загрузочное сообщение бота
   * @param {string} parentId - ID родительского сообщения
   * @returns {Object} созданное загрузочное сообщение
   */
  const createLoadingBotMessage = (parentId) => {
    const loadingId = `loading_${Date.now()}_${++localIdCounter}`

    const loadingMessage = {
      id: loadingId,
      message: '',
      type: 'bot',
      status: 'loading',
      isLoading: true,
      loadingText: 'Осуществляется поиск...',
      context_documents: null,
      created_at: new Date().toISOString(),
      parentId: parentId,
      replies: [],
      isLocal: true,
    }

    addMessage(loadingMessage)
    return loadingMessage
  }

  /**
   * Обновить статус сообщения
   * @param {string} messageId - ID сообщения
   * @param {string} status - новый статус
   */
  const updateMessageStatus = (messageId, status) => {
    const messageIndex = messages.value.findIndex((m) => m.id === messageId)
    if (messageIndex !== -1) {
      const updatedMessage = {
        ...messages.value[messageIndex],
        status: status,
      }

      messages.value = [
        ...messages.value.slice(0, messageIndex),
        updatedMessage,
        ...messages.value.slice(messageIndex + 1),
      ]
    }
  }

  /**
   * Заменить загрузочное сообщение на реальный ответ бота
   * @param {string} loadingMessageId - ID загрузочного сообщения
   * @param {Object} botResponse - ответ бота с сервера
   */
  const replaceLoadingMessage = (loadingMessageId, botResponse) => {
    const messageIndex = messages.value.findIndex(
      (m) => m.id === loadingMessageId,
    )
    if (messageIndex !== -1) {
      // Сначала обновляем существующее сообщение, сохраняя его структуру
      const currentMessage = messages.value[messageIndex]
      const updatedMessage = {
        ...currentMessage,
        id: botResponse.id,
        message: botResponse.message,
        status: 'replied',
        isLoading: false,
        loadingText: 'Вот что я нашёл по этому вопросу',
        context_documents: botResponse.context_documents,
        // ВАЖНО: Сохраняем оригинальное время создания загрузочного сообщения
        // чтобы не нарушить порядок сортировки
        created_at: currentMessage.created_at,
        isLocal: false,
        isNew: true, // Помечаем как новое сообщение для анимации
      }

      messages.value = [
        ...messages.value.slice(0, messageIndex),
        updatedMessage,
        ...messages.value.slice(messageIndex + 1),
      ]
    }
  }

  /**
   * Заменить загрузочное сообщение на сообщение об ошибке
   * @param {string} loadingMessageId - ID загрузочного сообщения
   */
  const replaceLoadingMessageWithError = (loadingMessageId) => {
    const messageIndex = messages.value.findIndex(
      (m) => m.id === loadingMessageId,
    )
    if (messageIndex !== -1) {
      const currentMessage = messages.value[messageIndex]
      const errorMessage = {
        ...currentMessage,
        id: `error_${Date.now()}_${++localIdCounter}`,
        message: '',
        status: 'error',
        isLoading: false,
        loadingText: 'Произошла ошибка',
        context_documents: null,
        created_at: currentMessage.created_at,
        isLocal: false,
      }

      messages.value = [
        ...messages.value.slice(0, messageIndex),
        errorMessage,
        ...messages.value.slice(messageIndex + 1),
      ]
    }
  }

  /**
   * Сбросить флаг isNew для сообщения (после завершения анимации)
   * @param {string|number} messageId - ID сообщения
   */
  const markMessageAsAnimated = (messageId) => {
    const messageIndex = messages.value.findIndex((m) => m.id === messageId)
    if (messageIndex !== -1) {
      const currentMessage = messages.value[messageIndex]
      const updatedMessage = {
        ...currentMessage,
        isNew: false,
      }

      messages.value = [
        ...messages.value.slice(0, messageIndex),
        updatedMessage,
        ...messages.value.slice(messageIndex + 1),
      ]
    }
  }

  /**
   * Обновить документы в сообщении пользователя
   * @param {string|number} messageId - ID сообщения
   * @param {Array} contextDocuments - массив документов
   */
  const updateMessageDocuments = (messageId, contextDocuments) => {
    const messageIndex = messages.value.findIndex((m) => m.id === messageId)
    if (messageIndex !== -1) {
      const currentMessage = messages.value[messageIndex]
      const updatedMessage = {
        ...currentMessage,
        context_documents: contextDocuments,
      }

      messages.value = [
        ...messages.value.slice(0, messageIndex),
        updatedMessage,
        ...messages.value.slice(messageIndex + 1),
      ]
    }
  }

  // ============================================================================
  // Return
  // ============================================================================

  return {
    // State
    messages,
    isLoading,
    error,
    currentUser,
    isTyping,
    loadingMessageId,

    // Getters
    sortedMessages,
    hasMessages,
    lastMessage,
    messagesWithReplies,

    // Actions
    setMessages,
    addMessage,
    addReply,
    removeMessage,
    clearMessages,
    setLoading,
    setError,
    clearError,
    setTyping,
    createLocalUserMessage,
    createLoadingBotMessage,
    updateMessageStatus,
    replaceLoadingMessage,
    replaceLoadingMessageWithError,
    markMessageAsAnimated,
    updateMessageDocuments,
  }
})
