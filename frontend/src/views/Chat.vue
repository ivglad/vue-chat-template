<script setup>
import { motion } from 'motion-v'
import { usePageTransition } from '@/composables/usePageTransition'
import { useChatHistory } from '@/helpers/api/queries'

const { data: chatData, isLoading } = useChatHistory({ limit: 50 })

// Композабл для поочередной анимации элементов
const { getElementAnimationProps } = usePageTransition({
  staggerDelay: 0.15, // Более медленная анимация для чата
  enterDuration: 0.4,
  enterDelay: 0.1,
})

// Локальные сообщения для мгновенного отображения
const localMessages = ref([])

// Функция для поиска соответствия между локальным и серверным сообщением
const findMatchingServerMessage = (localMessage, serverMessages) => {
  return serverMessages.find((serverMsg) => {
    // Сравниваем по тексту сообщения (основной критерий)
    const textMatch = serverMsg.message.trim() === localMessage.message.trim()

    // Дополнительная проверка по времени (локальные создаются позже серверных на несколько секунд)
    const localTime = new Date(localMessage.created_at).getTime()
    const serverTime = new Date(serverMsg.created_at).getTime()
    const timeDiff = Math.abs(localTime - serverTime)
    const timeMatch = timeDiff < 60000 // Разница меньше 1 минуты

    return textMatch && timeMatch
  })
}

// Умное объединение сообщений от сервера и локальных
const messagesHistory = computed(() => {
  const serverMessages = chatData.value?.messages || []

  if (localMessages.value.length === 0) {
    // Если нет локальных сообщений, возвращаем только серверные
    return serverMessages
  }

  const result = [...serverMessages]

  // Проверяем каждое локальное сообщение
  localMessages.value.forEach((localMsg) => {
    const matchingServer = findMatchingServerMessage(localMsg, serverMessages)

    if (!matchingServer) {
      // Если соответствие не найдено, добавляем локальное сообщение
      result.push(localMsg)
    }
    // Если найдено соответствие, серверное сообщение уже есть в result
    // и оно может содержать ответы от ИИ
  })

  // Сортируем по времени создания
  return result.sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
})

// Функция для добавления локального сообщения
const addLocalMessage = (messageData) => {
  const newMessage = {
    id: `local_${Date.now()}`,
    type: 'user',
    message: messageData.message,
    context_documents: messageData.documents
      ? messageData.documents.map((doc) => ({ id: doc.id, name: doc.label }))
      : messageData.document_ids
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

// Умная очистка локальных сообщений - удаляет только те, для которых есть серверные соответствия
const clearProcessedLocalMessages = () => {
  const serverMessages = chatData.value?.messages || []

  localMessages.value = localMessages.value.filter((localMsg) => {
    const matchingServer = findMatchingServerMessage(localMsg, serverMessages)
    return !matchingServer // Оставляем только те, для которых нет соответствий
  })
}

// Автоматическая очистка обработанных локальных сообщений при обновлении данных от сервера
watch(
  () => chatData.value?.messages,
  (newMessages, oldMessages) => {
    if (newMessages && oldMessages && newMessages.length > oldMessages.length) {
      // Если пришли новые сообщения от сервера, очищаем обработанные локальные
      setTimeout(() => {
        clearProcessedLocalMessages()
      }, 100) // Небольшая задержка для завершения всех вычислений
    }
  },
  { deep: true },
)

// Предоставляем функции дочерним компонентам
provide('addLocalMessage', addLocalMessage)
provide('clearLocalMessages', clearLocalMessages)

// Обработчик отправки сообщения
const { mutate: sendMessage } = useSendChatMessage()

const handleSendMessage = (messageData) => {
  sendMessage(messageData, {
    onSuccess: () => {},
    onError: (error) => {
      console.error('Ошибка отправки сообщения:', error)
    },
  })
}
</script>

<template>
  <div class="flex flex-col relative h-screen">
    <motion.div v-bind="getElementAnimationProps(0)">
      <ChatContainer
        :messages-history="messagesHistory"
        :is-loading="isLoading"
        @send-message="handleSendMessage" />
    </motion.div>
  </div>
</template>

<style lang="scss" scoped></style>
