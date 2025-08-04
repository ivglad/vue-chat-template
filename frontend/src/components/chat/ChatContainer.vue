<script setup>
const messagesListRef = ref(null)

const chatStore = useChatStore()

const { messages, isLoading, hasMessages, sendMessage } = useChatMessages()

const { scrollToBottom, initScrollContainer } = useChatScroll()

// Убираем сложную обработку ошибок - теперь просто показываем toast

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

// Убрали функцию handleRetry - теперь ошибки показываются только через toast

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
  <div class="h-full flex flex-col items-center relative bg-[#EDEFF6]">
    <ChatHeader :is-loading="isLoading" />

    <ChatMessagesList
      ref="messagesListRef"
      :messages="messages"
      :is-loading="isLoading"
      :has-messages="hasMessages"
      :is-loading-for-message="isLoadingForMessage" />

    <ChatInput :disabled="isLoading" @send-message="handleSendMessage" />
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
