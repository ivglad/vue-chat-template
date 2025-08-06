<script setup>
const messagesListRef = ref(null)

const { messages, isLoading, hasMessages, sendMessage } = useChatMessages()

// Отправка сообщения
const handleSendMessage = async (messageData) => {
  try {
    await sendMessage(messageData)
  } catch (error) {
    console.error('Failed to send message:', error)
  }
}
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
