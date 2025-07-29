<script setup>
import { ref, computed, inject } from 'vue'
import { motion, AnimatePresence } from 'motion-v'
import { onClickOutside } from '@vueuse/core'
// import { useSendChatMessage, useDocuments } from '@/helpers/api/queries'

const { mutate: sendMessage, isPending } = useSendChatMessage()
const { data: documentsData } = useDocuments({ per_page: 100 })

// Получаем функции для работы с локальными сообщениями
const addLocalMessage = inject('addLocalMessage')
const clearLocalMessages = inject('clearLocalMessages')

const message = ref('')

const documentsMenu = ref()
const documentsMenuRef = ref()

const documents = computed(() => {
  return (
    documentsData.value?.documents?.map((doc) => ({
      label: doc.title,
      id: doc.id,
    })) || []
  )
})

const selectedDocuments = ref([])

// Закрытие меню при клике вне области
onClickOutside(documentsMenuRef, () => {
  documentsMenu.value = false
})

const removeDocument = (documentId) => {
  selectedDocuments.value = selectedDocuments.value.filter(
    (doc) => doc.id !== documentId,
  )
}

const handleSendMessage = () => {
  if (!message.value.trim() || isPending.value) return

  const messageData = {
    message: message.value.trim(),
  }

  // Если выбраны документы, добавляем их IDs
  if (selectedDocuments.value.length > 0) {
    messageData.document_ids = selectedDocuments.value.map((doc) => doc.id)
  }

  // Сразу добавляем сообщение локально для мгновенного отображения
  addLocalMessage(messageData)

  // Очищаем форму
  message.value = ''
  selectedDocuments.value = []

  // Отправляем на сервер
  sendMessage(messageData, {
    onSuccess: () => {
      // После успешной отправки очищаем локальные сообщения
      // Реальный ответ от сервера заменит локальное сообщение
      clearLocalMessages()
    },
    onError: (error) => {
      console.error('Ошибка отправки сообщения:', error)
      // При ошибке убираем локальное сообщение
      clearLocalMessages()
    },
  })
}

const handleKeydown = (event) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    handleSendMessage()
  }
}
</script>

<template>
  <div class="flex w-full items-center justify-center relative px-6 pt-0 pb-4">
    <AnimatePresence>
      <motion.div
        v-if="documentsMenu"
        ref="documentsMenuRef"
        :initial="{ opacity: 0, y: 10, scale: 0.95 }"
        :animate="{ opacity: 1, y: 0, scale: 1 }"
        :exit="{ opacity: 0, y: 10, scale: 0.95 }"
        :transition="{
          duration: 0.15,
          ease: 'easeOut',
        }"
        class="w-fit absolute bottom-full left-6 z-10">
        <Listbox
          v-model="selectedDocuments"
          :options="documents"
          multiple
          optionLabel="label"
          listStyle="max-height:250px"
          class="w-[280px] max-w-[280px] border-none shadow-lg rounded-2xl"
          :pt="{
            list: 'p-2.5 overflow-hidden',
            option: 'block rounded-xl text-nowrap text-ellipsis',
          }"
          @change="documentsMenu = false" />
      </motion.div>
    </AnimatePresence>

    <motion.div
      layout
      :transition="{ duration: 0.3, ease: 'easeOut' }"
      class="flex flex-col w-full items-center justify-center gap-2.5 bg-surface-0 min-h-[54px] rounded-2xl p-2">
      <!-- Показываем все выбранные документы -->
      <AnimatePresence>
        <motion.div
          v-for="document in selectedDocuments"
          :key="document.id"
          layout
          :initial="{ opacity: 0, scale: 0.98 }"
          :animate="{ opacity: 1, scale: 1 }"
          :exit="{ opacity: 0, scale: 0.98 }"
          :transition="{
            duration: 0.2,
            ease: 'easeOut',
          }"
          class="flex items-center gap-2.5 w-full p-3 rounded-xl bg-[#EDEFF6]">
          <i-custom-doc class="text-primary flex-shrink-0" />
          <div class="text-sm text-nowrap text-ellipsis overflow-hidden">
            {{ document.label }}
          </div>
          <Button
            class="p-0 ml-auto flex-shrink-0"
            variant="text"
            @click="removeDocument(document.id)">
            <template #icon>
              <i-custom-cross />
            </template>
          </Button>
        </motion.div>
      </AnimatePresence>

      <div class="flex w-full items-center justify-center">
        <Button
          class="w-[38px] min-w-[38px] h-[38px] min-h-[38px] rounded-xl"
          aria-label="plus"
          outlined
          aria-haspopup="true"
          aria-controls="documents-menu"
          @click="documentsMenu = !documentsMenu">
          <template #icon>
            <i-custom-plus />
          </template>
        </Button>
        <Textarea
          v-model="message"
          id="message-textarea"
          class="h-full px-2 py-2 border-none shadow-none"
          placeholder="Задайте вопрос..."
          rows="1"
          :disabled="isPending"
          @keydown="handleKeydown" />
        <Button
          :disabled="!message || isPending"
          class="w-[38px] min-w-[38px] h-[38px] min-h-[38px] rounded-xl"
          aria-label="send"
          @click="handleSendMessage">
          <template #icon>
            <div
              v-if="isPending"
              class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            <i-custom-send v-else />
          </template>
        </Button>
      </div>
    </motion.div>
  </div>
</template>
