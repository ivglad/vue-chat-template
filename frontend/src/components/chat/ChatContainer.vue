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
  <div class="flex flex-col items-center relative w-full h-full bg-[#EDEFF6] overflow-hidden">
    <ChatHeader />

    <ChatMessagesList
      ref="messagesListRef"
      :messages="messages"
      :is-loading="isLoading"
      :has-messages="hasMessages" />

    <ChatInput :disabled="isLoading" @send-message="handleSendMessage" />
  </div>
</template>
