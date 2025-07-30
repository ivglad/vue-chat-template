<template>
  <div class="chat-input-container">
    <!-- Селектор документов -->
    <AnimatePresence>
      <AnimatedContainer
        v-if="showDocumentsMenu"
        preset="slideUp"
        container-class="documents-menu-container"
      >
        <ChatDocumentSelector
          v-model="selectedDocuments"
          :documents="documents"
          :loading="documentsLoading"
          @close="closeDocumentsMenu"
        />
      </AnimatedContainer>
    </AnimatePresence>
    
    <!-- Основная область ввода -->
    <AnimatedContainer
      preset="layoutShift"
      container-class="input-main-container"
    >
      <!-- Выбранные документы -->
      <AnimatePresence>
        <AnimatedList
          v-if="selectedDocuments.length > 0"
          :items="selectedDocuments"
          item-preset="documentSlide"
          :stagger-delay="0.05"
          container-class="selected-documents-list"
        >
          <template #item="{ item: document }">
            <ChatSelectedDocument
              :document="document"
              @remove="removeDocument(document.id)"
            />
          </template>
        </AnimatedList>
      </AnimatePresence>
      
      <!-- Поле ввода -->
      <div class="input-row">
        <!-- Кнопка документов -->
        <Button
          class="documents-button"
          :class="{ 'active': showDocumentsMenu }"
          icon="pi pi-paperclip"
          outlined
          rounded
          size="small"
          :disabled="disabled"
          @click="toggleDocumentsMenu"
        />
        
        <!-- Текстовое поле -->
        <Textarea
          ref="textareaRef"
          v-model="messageText"
          class="message-textarea"
          placeholder="Задайте вопрос..."
          :rows="1"
          :disabled="disabled"
          :maxlength="5000"
          auto-resize
          @keydown="handleKeydown"
          @focus="handleFocus"
          @blur="handleBlur"
        />
        
        <!-- Кнопка отправки -->
        <Button
          class="send-button"
          :disabled="!canSend"
          :loading="disabled"
          icon="pi pi-send"
          rounded
          size="small"
          @click="handleSendMessage"
        />
      </div>
      
      <!-- Счетчик символов -->
      <div 
        v-if="showCharacterCount"
        class="character-count"
        :class="{ 'warning': isNearLimit }"
      >
        {{ messageText.length }}/5000
      </div>
    </AnimatedContainer>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue'
import { motion, AnimatePresence } from 'motion-v'
import { onClickOutside } from '@vueuse/core'
import { useDocuments } from '@/helpers/api/queries'

import AnimatedContainer from '@/components/ui/animations/AnimatedContainer.vue'
import AnimatedList from '@/components/ui/animations/AnimatedList.vue'
import ChatDocumentSelector from './ChatDocumentSelector.vue'
import ChatSelectedDocument from './ChatSelectedDocument.vue'

/**
 * Компонент ввода сообщений в чате
 * Поддерживает выбор документов, валидацию и отправку
 * Следует принципам Single Responsibility и Composition
 */

// ============================================================================
// Emits
// ============================================================================

const emit = defineEmits(['send-message'])

// ============================================================================
// Props
// ============================================================================

const props = defineProps({
  /**
   * Отключить ввод
   */
  disabled: {
    type: Boolean,
    default: false
  }
})

// ============================================================================
// Refs
// ============================================================================

const textareaRef = ref(null)
const inputContainer = ref(null)

// ============================================================================
// State
// ============================================================================

const messageText = ref('')
const selectedDocuments = ref([])
const showDocumentsMenu = ref(false)
const isFocused = ref(false)

// ============================================================================
// API
// ============================================================================

const { 
  data: documentsData, 
  isLoading: documentsLoading 
} = useDocuments({ per_page: 100 })

// ============================================================================
// Computed
// ============================================================================

const documents = computed(() => {
  return documentsData.value?.documents?.map(doc => ({
    id: doc.id,
    title: doc.title,
    label: doc.title // для совместимости
  })) || []
})

const canSend = computed(() => {
  return messageText.value.trim().length > 0 && !props.disabled
})

const showCharacterCount = computed(() => {
  return isFocused.value && messageText.value.length > 0
})

const isNearLimit = computed(() => {
  return messageText.value.length > 4500
})

// ============================================================================
// Methods
// ============================================================================

/**
 * Обработать отправку сообщения
 */
const handleSendMessage = () => {
  if (!canSend.value) return
  
  const messageData = {
    message: messageText.value.trim()
  }
  
  // Добавляем документы если выбраны
  if (selectedDocuments.value.length > 0) {
    messageData.document_ids = selectedDocuments.value.map(doc => doc.id)
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

/**
 * Обработать фокус поля ввода
 */
const handleFocus = () => {
  isFocused.value = true
}

/**
 * Обработать потерю фокуса поля ввода
 */
const handleBlur = () => {
  isFocused.value = false
}

/**
 * Переключить меню документов
 */
const toggleDocumentsMenu = () => {
  showDocumentsMenu.value = !showDocumentsMenu.value
}

/**
 * Закрыть меню документов
 */
const closeDocumentsMenu = () => {
  showDocumentsMenu.value = false
}

/**
 * Удалить документ из выбранных
 * @param {number} documentId - ID документа
 */
const removeDocument = (documentId) => {
  selectedDocuments.value = selectedDocuments.value.filter(
    doc => doc.id !== documentId
  )
}

// ============================================================================
// Click Outside
// ============================================================================

onClickOutside(inputContainer, () => {
  if (showDocumentsMenu.value) {
    closeDocumentsMenu()
  }
})

// ============================================================================
// Watchers
// ============================================================================

// Автоматически закрываем меню при выборе документов
watch(selectedDocuments, (newDocs, oldDocs) => {
  if (newDocs.length > (oldDocs?.length || 0)) {
    closeDocumentsMenu()
  }
}, { deep: true })
</script>

<style scoped>
.chat-input-container {
  @apply relative p-4 bg-surface-0 border-t border-surface-200;
}

.documents-menu-container {
  @apply absolute bottom-full left-4 right-4 mb-2 z-20;
}

.input-main-container {
  @apply bg-surface-50 rounded-2xl p-3 border border-surface-200;
  @apply focus-within:border-primary-300 focus-within:ring-2 focus-within:ring-primary-100;
  transition: all 0.2s ease;
}

.selected-documents-list {
  @apply flex flex-wrap gap-2 mb-3;
}

.input-row {
  @apply flex items-end gap-3;
}

.documents-button {
  @apply flex-shrink-0 w-10 h-10;
  @apply text-surface-600 hover:text-primary-600;
}

.documents-button.active {
  @apply text-primary-600 bg-primary-50 border-primary-300;
}

.message-textarea {
  @apply flex-1 border-none shadow-none bg-transparent resize-none;
  @apply text-surface-900 placeholder-surface-500;
  @apply focus:ring-0 focus:border-transparent;
  min-height: 40px;
  max-height: 120px;
}

.send-button {
  @apply flex-shrink-0 w-10 h-10;
  @apply bg-primary-500 text-white hover:bg-primary-600;
  @apply disabled:bg-surface-300 disabled:text-surface-500;
}

.character-count {
  @apply text-xs text-surface-500 text-right mt-2;
}

.character-count.warning {
  @apply text-orange-600;
}

/* Анимации */
.input-main-container {
  transition: all 0.3s ease;
}

/* Responsive */
@media (max-width: 768px) {
  .chat-input-container {
    @apply p-3;
  }
  
  .documents-menu-container {
    @apply left-3 right-3;
  }
  
  .input-main-container {
    @apply p-2;
  }
  
  .input-row {
    @apply gap-2;
  }
}
</style>