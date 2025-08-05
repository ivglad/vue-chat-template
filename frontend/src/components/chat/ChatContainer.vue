<script setup>

const messagesListRef = ref(null)

const { messages, isLoading, hasMessages, sendMessage } = useChatMessages()

const {
  scrollToBottomInstant,
  scrollToLastAssistantDividerBottom,
  scrollToLastAssistantTitle,
  initScrollContainer,
  isScrolledToBottom,
} = useChatScroll()

/**
 * Обработать отправку сообщения
 * @param {Object} messageData - данные сообщения
 */
const handleSendMessage = async (messageData) => {
  try {
    await sendMessage(messageData)
  } catch (error) {
    console.error('Failed to send message:', error)
  }
}

// Умная логика скролла при изменении сообщений
watch(
  () => messages.value,
  (newMessages, oldMessages) => {
    if (!newMessages || newMessages.length === 0) return

    nextTick(() => {
      const oldLength = oldMessages?.length || 0
      const newLength = newMessages.length

      // Если добавились новые сообщения
      if (newLength > oldLength) {
        // Получаем последнее добавленное сообщение
        const lastMessage = newMessages[newLength - 1]
        
        if (lastMessage.type === 'user') {
          // При добавлении сообщения пользователя прокручиваем к разделителю
          // Добавляем достаточную задержку для обновления DOM
          setTimeout(() => {
            if (!isScrolledToBottom.value) {
              scrollToLastAssistantDividerBottom()
            } else {
              // Если скролл внизу, тоже прокручиваем к разделителю для консистентности
              scrollToLastAssistantDividerBottom()
            }
          }, 250)
        }
      }

      // Отдельно обрабатываем новые ответы ассистента (замена загрузочного сообщения)
      const newBotMessages = newMessages.filter(
        (msg) => msg.type === 'bot' && msg.isNew === true && !msg.isLoading,
      )

      if (newBotMessages.length > 0) {
        // Прокручиваем к заголовку ассистента
        setTimeout(() => {
          scrollToLastAssistantTitle()
        }, 250)
      }
    })
  },
  { deep: true },
)

// При загрузке чата мгновенно прокручиваем в самый низ без плавного скролла
watch(
  () => hasMessages.value,
  (newHasMessages) => {
    if (newHasMessages && !isLoading.value) {
      // Мгновенная прокрутка при первой загрузке сообщений
      nextTick(() => {
        scrollToBottomInstant()
      })
    }
  },
  { immediate: true },
)

// Инициализируем скролл контейнер при монтировании
onMounted(() => {
  nextTick(() => {
    if (messagesListRef.value?.messagesContainer) {
      initScrollContainer(messagesListRef.value.messagesContainer)
    }
  })
})
</script>

<template>
  <div class="h-full flex flex-col items-center relative bg-[#EDEFF6]">
    <ChatHeader />

    <ChatMessagesList
      ref="messagesListRef"
      :messages="messages"
      :is-loading="isLoading"
      :has-messages="hasMessages" />

    <ChatInput :disabled="isLoading" @send-message="handleSendMessage" />
  </div>
</template>
