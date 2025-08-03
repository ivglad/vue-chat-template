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

  // ============================================================================
  // Getters
  // ============================================================================

  const sortedMessages = computed(() => {
    return messages.value
      .slice()
      .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
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
  }
})
