<template>
  <div class="document-selector">
    <div class="selector-header">
      <h3 class="selector-title">Выберите документы</h3>
      <Button
        icon="pi pi-times"
        text
        rounded
        size="small"
        class="close-button"
        @click="$emit('close')"
      />
    </div>
    
    <div class="selector-content">
      <!-- Поиск документов -->
      <div class="search-container">
        <IconField iconPosition="left">
          <InputIcon class="pi pi-search" />
          <InputText
            v-model="searchQuery"
            placeholder="Поиск документов..."
            class="search-input"
          />
        </IconField>
      </div>
      
      <!-- Список документов -->
      <div class="documents-list">
        <div 
          v-if="loading"
          class="loading-state"
        >
          <ProgressSpinner size="small" />
          <span class="loading-text">Загрузка документов...</span>
        </div>
        
        <div 
          v-else-if="filteredDocuments.length === 0"
          class="empty-state"
        >
          <i class="pi pi-file-o empty-icon"></i>
          <span class="empty-text">
            {{ searchQuery ? 'Документы не найдены' : 'Нет доступных документов' }}
          </span>
        </div>
        
        <AnimatedList
          v-else
          :items="filteredDocuments"
          item-preset="documentSlide"
          :stagger-delay="0.03"
          container-class="documents-grid"
        >
          <template #item="{ item: document }">
            <div
              class="document-item"
              :class="{ 'selected': isSelected(document.id) }"
              @click="toggleDocument(document)"
            >
              <div class="document-icon">
                <i class="pi pi-file-o"></i>
              </div>
              
              <div class="document-info">
                <div class="document-title">
                  {{ document.title }}
                </div>
                <div class="document-meta">
                  ID: {{ document.id }}
                </div>
              </div>
              
              <div class="document-checkbox">
                <Checkbox
                  :model-value="isSelected(document.id)"
                  binary
                  @change="toggleDocument(document)"
                />
              </div>
            </div>
          </template>
        </AnimatedList>
      </div>
      
      <!-- Футер с информацией -->
      <div 
        v-if="selectedDocuments.length > 0"
        class="selector-footer"
      >
        <span class="selected-count">
          Выбрано: {{ selectedDocuments.length }}
        </span>
        <Button
          label="Очистить"
          text
          size="small"
          @click="clearSelection"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import AnimatedList from '@/components/ui/animations/AnimatedList.vue'

/**
 * Компонент для выбора документов
 * Поддерживает поиск, множественный выбор и фильтрацию
 * Следует принципу Single Responsibility
 */

// ============================================================================
// Emits
// ============================================================================

const emit = defineEmits(['close', 'update:modelValue'])

// ============================================================================
// Props
// ============================================================================

const props = defineProps({
  /**
   * Выбранные документы (v-model)
   */
  modelValue: {
    type: Array,
    default: () => []
  },
  
  /**
   * Список доступных документов
   */
  documents: {
    type: Array,
    default: () => []
  },
  
  /**
   * Состояние загрузки
   */
  loading: {
    type: Boolean,
    default: false
  }
})

// ============================================================================
// State
// ============================================================================

const searchQuery = ref('')

// ============================================================================
// Computed
// ============================================================================

const selectedDocuments = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const filteredDocuments = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.documents
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  return props.documents.filter(doc => 
    doc.title.toLowerCase().includes(query) ||
    doc.id.toString().includes(query)
  )
})

// ============================================================================
// Methods
// ============================================================================

/**
 * Проверить, выбран ли документ
 * @param {number} documentId - ID документа
 * @returns {boolean}
 */
const isSelected = (documentId) => {
  return selectedDocuments.value.some(doc => doc.id === documentId)
}

/**
 * Переключить выбор документа
 * @param {Object} document - объект документа
 */
const toggleDocument = (document) => {
  const currentSelection = [...selectedDocuments.value]
  const existingIndex = currentSelection.findIndex(doc => doc.id === document.id)
  
  if (existingIndex !== -1) {
    // Убираем из выбранных
    currentSelection.splice(existingIndex, 1)
  } else {
    // Добавляем в выбранные
    currentSelection.push(document)
  }
  
  selectedDocuments.value = currentSelection
}

/**
 * Очистить выбор
 */
const clearSelection = () => {
  selectedDocuments.value = []
}
</script>

<style scoped>
.document-selector {
  @apply bg-white rounded-2xl shadow-lg border border-surface-200 overflow-hidden;
  max-height: 400px;
}

.selector-header {
  @apply flex items-center justify-between p-4 border-b border-surface-200 bg-surface-50;
}

.selector-title {
  @apply text-lg font-semibold text-surface-900;
}

.close-button {
  @apply text-surface-500 hover:text-surface-700;
}

.selector-content {
  @apply flex flex-col h-full;
}

.search-container {
  @apply p-4 border-b border-surface-100;
}

.search-input {
  @apply w-full;
}

.documents-list {
  @apply flex-1 overflow-y-auto p-2;
  max-height: 250px;
}

.loading-state,
.empty-state {
  @apply flex flex-col items-center justify-center py-8 text-surface-500;
}

.loading-text,
.empty-text {
  @apply mt-2 text-sm;
}

.empty-icon {
  @apply text-2xl text-surface-300;
}

.documents-grid {
  @apply space-y-1;
}

.document-item {
  @apply flex items-center gap-3 p-3 rounded-xl cursor-pointer;
  @apply hover:bg-surface-50 transition-colors duration-150;
}

.document-item.selected {
  @apply bg-primary-50 border border-primary-200;
}

.document-icon {
  @apply flex-shrink-0 w-8 h-8 flex items-center justify-center;
  @apply bg-surface-100 rounded-lg text-surface-600;
}

.document-item.selected .document-icon {
  @apply bg-primary-100 text-primary-600;
}

.document-info {
  @apply flex-1 min-w-0;
}

.document-title {
  @apply font-medium text-surface-900 truncate;
}

.document-meta {
  @apply text-xs text-surface-500 mt-1;
}

.document-checkbox {
  @apply flex-shrink-0;
}

.selector-footer {
  @apply flex items-center justify-between p-4 border-t border-surface-100 bg-surface-50;
}

.selected-count {
  @apply text-sm font-medium text-surface-700;
}

/* Scrollbar */
.documents-list::-webkit-scrollbar {
  width: 4px;
}

.documents-list::-webkit-scrollbar-track {
  background: transparent;
}

.documents-list::-webkit-scrollbar-thumb {
  background: theme('colors.surface.300');
  border-radius: 2px;
}

.documents-list::-webkit-scrollbar-thumb:hover {
  background: theme('colors.surface.400');
}
</style>