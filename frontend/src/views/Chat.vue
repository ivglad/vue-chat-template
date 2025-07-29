<script setup>
import { ref, computed, provide } from 'vue'
import { useChatHistory } from '@/helpers/api/queries'

const { data: chatData, isLoading } = useChatHistory({ limit: 50 })

// Локальные сообщения для мгновенного отображения
const localMessages = ref([])

// Объединяем сообщения от сервера и локальные
const messagesHistory = computed(() => {
  const serverMessages = chatData.value?.messages || []
  return [...serverMessages, ...localMessages.value]
})

// Функция для добавления локального сообщения
const addLocalMessage = (messageData) => {
  const newMessage = {
    id: `local_${Date.now()}`,
    type: 'user',
    message: messageData.message,
    context_documents: messageData.document_ids
      ? messageData.document_ids.map((id) => ({ id, name: `Документ ${id}` }))
      : null,
    replies: [],
    created_at: new Date().toISOString(),
    isLocal: true, // флаг что это локальное сообщение
  }

  localMessages.value.push(newMessage)
  return newMessage
}

// Функция для очистки локальных сообщений после синхронизации
const clearLocalMessages = () => {
  localMessages.value = []
}

// Предоставляем функции дочерним компонентам
provide('addLocalMessage', addLocalMessage)
provide('clearLocalMessages', clearLocalMessages)
</script>

<template>
  <div class="flex flex-col h-screen overflow-hidden">
    <ChatHeader />
    <ChatContent :messages-history="messagesHistory" :is-loading="isLoading" />
    <ChatFooter />
  </div>
</template>

<style lang="scss" scoped></style>
