<script setup>
import { onClickOutside } from '@vueuse/core'
import { AnimatePresence } from 'motion-v'
import { useDocuments } from '@/helpers/api/queries'
import ChatDocumentSelector from './ChatDocumentSelector.vue'

const emit = defineEmits(['send-message'])

const props = defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
})

const textareaRef = ref(null)
const inputContainer = ref(null)

const messageText = ref('')
const selectedDocuments = ref([])
const showDocumentsMenu = ref(false)

const { data: documentsData, isLoading: documentsLoading } = useDocuments({
  per_page: 100,
})

const documents = computed(() => {
  return (
    documentsData.value?.documents?.map((doc) => ({
      id: doc.id,
      title: doc.title,
      label: doc.title, // для совместимости
    })) || []
  )
})

const canSend = computed(() => {
  return messageText.value.trim().length > 0 && !props.disabled
})

// Обработать отправку сообщения
const handleSendMessage = () => {
  if (!canSend.value) return

  const messageData = {
    message: messageText.value.trim(),
  }

  // Добавляем документы если выбраны
  if (selectedDocuments.value.length > 0) {
    messageData.document_ids = selectedDocuments.value.map((doc) => doc.id)
    messageData.documents = selectedDocuments.value
  }

  // Отправляем сообщение
  emit('send-message', messageData)

  // Очищаем поле ввода
  messageText.value = ''

  // Фокусируемся обратно на поле ввода
  nextTick(() => {
    textareaRef.value?.focus()
  })
}

/**
 * Обработать нажатие клавиш
 * @param {KeyboardEvent} event - событие клавиатуры
 */
const handleKeydown = (event) => {
  // Enter без Shift - отправить сообщение
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    handleSendMessage()
  }

  // Escape - закрыть меню документов
  if (event.key === 'Escape' && showDocumentsMenu.value) {
    closeDocumentsMenu()
  }
}

// Переключить меню документов
const toggleDocumentsMenu = () => {
  showDocumentsMenu.value = !showDocumentsMenu.value
}

//  Закрыть меню документов
const closeDocumentsMenu = () => {
  showDocumentsMenu.value = false
}

/**
 * Удалить документ из выбранных
 * @param {number} documentId - ID документа
 */
const removeDocument = (documentId) => {
  selectedDocuments.value = selectedDocuments.value.filter(
    (doc) => doc.id !== documentId,
  )
}

onClickOutside(inputContainer, () => {
  if (showDocumentsMenu.value) {
    closeDocumentsMenu()
  }
})

// Автоматически закрываем меню при выборе документов
watch(
  selectedDocuments,
  (newDocs, oldDocs) => {
    if (newDocs.length > (oldDocs?.length || 0)) {
      closeDocumentsMenu()
    }
  },
  { deep: true },
)
</script>

<template>
  <div ref="inputContainer" class="relative px-4 pb-4">
    <AnimatePresence>
      <AnimatedContainer
        v-if="showDocumentsMenu"
        preset="slideUp"
        container-class="absolute bottom-full left-4 right-4 w-fit mb-4">
        <ChatDocumentSelector
          v-model="selectedDocuments"
          :documents="documents"
          :loading="documentsLoading"
          @close="closeDocumentsMenu" />
      </AnimatedContainer>
    </AnimatePresence>

    <AnimatedContainer
      preset="layoutShift"
      container-class="bg-white rounded-2xl p-2 transition-all duration-200">
      <AnimatePresence>
        <AnimatedList
          v-if="selectedDocuments.length > 0"
          :items="selectedDocuments"
          item-preset="documentSlide"
          :stagger-delay="0.05"
          container-class="flex flex-wrap gap-2 mb-3">
          <template #item="{ item: document }">
            <div
              class="flex items-center gap-2 bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
              <span>{{ document.title }}</span>
              <button
                @click="removeDocument(document.id)"
                class="hover:bg-blue-200 rounded-full p-1">
                <i-custom-cross />
              </button>
            </div>
          </template>
        </AnimatedList>
      </AnimatePresence>

      <div class="flex items-center">
        <Button
          :disabled="disabled"
          class="w-[38px] min-w-[38px] h-[38px] min-h-[38px] rounded-xl"
          aria-label="plus"
          outlined
          aria-haspopup="true"
          aria-controls="documents-menu"
          @click="toggleDocumentsMenu">
          <template #icon>
            <i-custom-plus />
          </template>
        </Button>

        <Textarea
          v-model="messageText"
          ref="textareaRef"
          class="h-full px-2 py-2 border-none shadow-none transition-all"
          placeholder="Задайте вопрос..."
          rows="1"
          :disabled="disabled"
          :maxlength="5000"
          @keydown="handleKeydown" />

        <Button
          :disabled="!canSend"
          :loading="disabled"
          class="w-[38px] min-w-[38px] h-[38px] min-h-[38px] rounded-xl"
          aria-label="send"
          @click="handleSendMessage">
          <template #icon>
            <div
              v-if="disabled"
              class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            <i-custom-send v-else />
          </template>
        </Button>
      </div>
    </AnimatedContainer>
  </div>
</template>
