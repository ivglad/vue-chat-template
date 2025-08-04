<script setup>
const messagesListRef = ref(null)

const chatStore = useChatStore()

const { messages, isLoading, hasMessages, sendMessage } = useChatMessages()

const { scrollToBottom, initScrollContainer } = useChatScroll()

// Убираем сложную обработку ошибок - теперь просто показываем toast

// Убираем старую логику isLoadingForMessage, теперь состояние хранится в самих сообщениях

/**
 * Обработать отправку сообщения
 * @param {Object} messageData - данные сообщения
 */
const handleSendMessage = async (messageData) => {
  try {
    // Сразу прокручиваем вниз после добавления локального сообщения
    nextTick(() => {
      scrollToBottom({ smooth: true })
    })

    await sendMessage(messageData)

    // Прокручиваем вниз после получения ответа
    nextTick(() => {
      setTimeout(() => {
        scrollToBottom({ smooth: true })
      }, 100)
    })
  } catch (error) {
    console.error('Failed to send message:', error)
  }
}

// Убрали функцию handleRetryMessage - функциональность повтора не нужна

// Убрали функцию handleRetry - теперь ошибки показываются только через toast

// Автоматически прокручиваем вниз при изменениях в сообщениях
watch(
  () => messages.value,
  (newMessages, oldMessages) => {
    // Прокручиваем только если добавились новые сообщения или изменилось содержимое
    if (newMessages.length !== oldMessages?.length) {
      // Небольшая задержка для завершения рендеринга
      nextTick(() => {
        setTimeout(() => {
          scrollToBottom({ smooth: true })
        }, 100)
      })
    }
  },
  { deep: true },
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
      :has-messages="hasMessages" />

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
