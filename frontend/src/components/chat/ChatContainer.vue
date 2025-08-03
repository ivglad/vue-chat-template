<script setup>
import ChatHeader from './ChatHeader.vue'
import ChatMessage from './ChatMessage.vue'
import ChatInput from './ChatInput.vue'
import ChatEmptyState from './ChatEmptyState.vue'
import ChatErrorDisplay from './ChatErrorDisplay.vue'
import ChatMessagesList from './ChatMessagesList.vue'

const messagesListRef = ref(null)

const chatStore = useChatStore()

const { messages, isLoading, hasMessages, sendMessage } = useChatMessages()

const { scrollToBottom, initScrollContainer } = useChatScroll()

const { lastError, clearLastError, canRetry } = useChatErrorHandler()

const isLoadingForMessage = computed(() => {
  return (messageId) => {
    return chatStore.loadingMessageId === messageId
  }
})

/**
 * Обработать отправку сообщения
 * @param {Object} messageData - данные сообщения
 */
const handleSendMessage = async (messageData) => {
  try {
    await sendMessage(messageData)

    // Прокручиваем вниз после отправки
    setTimeout(() => {
      scrollToBottom({ smooth: true, delay: 100 })
    }, 200)
  } catch (error) {
    console.error('Failed to send message:', error)
  }
}

/**
 * Повторить последнюю неудачную операцию
 */
const handleRetry = () => {
  if (!lastError.value || !canRetry(lastError.value)) {
    return
  }

  const context = lastError.value.context

  switch (context?.action) {
    case 'send_message':
      // Повторяем отправку сообщения
      if (context.messageData) {
        handleSendMessage(context.messageData)
      }
      break

    case 'load_history':
      // Обновляем историю
      window.location.reload()
      break
  }

  clearLastError()
}

// Автоматически прокручиваем вниз при новых сообщениях
watch(
  () => messages.value.length,
  (newLength, oldLength) => {
    if (newLength > oldLength) {
      // Небольшая задержка для завершения анимаций
      setTimeout(() => {
        scrollToBottom({ smooth: true })
      }, 300)
    }
  },
)

onMounted(() => {
  // Инициализируем скролл контейнер
  if (messagesListRef.value?.messagesContainer) {
    initScrollContainer(messagesListRef.value.messagesContainer)
  }

  // Прокручиваем вниз при загрузке
  setTimeout(() => {
    scrollToBottom({ smooth: false })
  }, 100)
})

onUnmounted(() => {
  // Очистка происходит автоматически в композаблах
})
</script>

<template>
  <div class="h-full flex flex-col relative bg-[#EDEFF6]">
    <ChatHeader :is-loading="isLoading" />

    <ChatMessagesList
      ref="messagesListRef"
      :messages="messages"
      :is-loading="isLoading"
      :has-messages="hasMessages"
      :is-loading-for-message="isLoadingForMessage" />

    <ChatInput :disabled="isLoading" @send-message="handleSendMessage" />

    <ChatErrorDisplay
      v-if="lastError"
      :error="lastError"
      :can-retry="canRetry"
      @retry="handleRetry"
      @clear="clearLastError" />
  </div>
</template>

<style scoped>
@reference '@/assets/styles/main.css';

@media (max-width: 768px) {
  .chat-messages {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }
}
</style>
