<script setup>
const emit = defineEmits(['close', 'update:modelValue'])

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  documents: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
})
const searchQuery = ref('')

const selectedDocuments = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const filteredDocuments = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.documents
  }

  const query = searchQuery.value.toLowerCase().trim()
  return props.documents.filter(
    (doc) =>
      doc.title.toLowerCase().includes(query) ||
      doc.id.toString().includes(query),
  )
})

/**
 * Проверить, выбран ли документ
 * @param {number} documentId - ID документа
 * @returns {boolean}
 */
const isSelected = (documentId) => {
  return selectedDocuments.value.some((doc) => doc.id === documentId)
}

/**
 * Переключить выбор документа
 * @param {Object} document - объект документа
 */
const toggleDocument = (document) => {
  const currentSelection = [...selectedDocuments.value]
  const existingIndex = currentSelection.findIndex(
    (doc) => doc.id === document.id,
  )

  if (existingIndex !== -1) {
    // Убираем из выбранных
    currentSelection.splice(existingIndex, 1)
  } else {
    // Добавляем в выбранные
    currentSelection.push(document)
  }

  selectedDocuments.value = currentSelection
}

const clearSelection = () => {
  selectedDocuments.value = []
}
</script>

<template>
  <div
    class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden max-h-[400px]">
    <div
      class="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
      <h3 class="text-lg font-semibold text-gray-900">Выберите документы</h3>
      <Button
        icon="i-custom-cross"
        text
        rounded
        size="small"
        class="text-gray-500 hover:text-gray-700"
        @click="$emit('close')" />
    </div>

    <div class="flex flex-col h-full">
      <!-- <div class="p-4 border-b border-gray-100">
        <IconField iconPosition="left">
          <InputIcon class="i-custom-search" />
          <InputText
            v-model="searchQuery"
            placeholder="Поиск документов..."
            class="w-full" />
        </IconField>
      </div> -->

      <div
        class="flex-1 overflow-y-auto p-2 max-h-[250px] scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent">
        <div
          v-if="loading"
          class="flex flex-col items-center justify-center py-8 text-gray-500">
          <ProgressSpinner size="small" />
          <span class="mt-2 text-sm">Загрузка документов...</span>
        </div>

        <div
          v-else-if="filteredDocuments.length === 0"
          class="flex flex-col items-center justify-center py-8 text-gray-500">
          <i-custom-doc class="text-2xl text-gray-300" />
          <span class="mt-2 text-sm">
            {{
              searchQuery ? 'Документы не найдены' : 'Нет доступных документов'
            }}
          </span>
        </div>

        <AnimatedList
          v-else
          :items="filteredDocuments"
          item-preset="documentSlide"
          :stagger-delay="0.03"
          container-class="space-y-1">
          <template #item="{ item: document }">
            <div
              class="flex items-center gap-3 p-3 rounded-xl cursor-pointer hover:bg-gray-50 transition-colors duration-150"
              :class="{
                'bg-primary-50 border border-primary-200': isSelected(
                  document.id,
                ),
              }"
              @click="toggleDocument(document)">
              <i-custom-doc class="text-primary flex-shrink-0" />

              <div class="flex-1 min-w-0">
                <div class="font-medium text-gray-900 truncate">
                  {{ document.title }}
                </div>
                <!-- <div class="text-xs text-gray-500 mt-1">
                  ID: {{ document.id }}
                </div> -->
              </div>

              <!-- <div class="flex-shrink-0">
                <Checkbox
                  :model-value="isSelected(document.id)"
                  binary
                  @change="toggleDocument(document)" />
              </div> -->
            </div>
          </template>
        </AnimatedList>
      </div>

      <div
        v-if="selectedDocuments.length > 0"
        class="flex items-center justify-between p-4 border-t border-gray-100 bg-gray-50">
        <span class="text-sm font-medium text-gray-700">
          Выбрано: {{ selectedDocuments.length }}
        </span>
        <Button label="Очистить" text size="small" @click="clearSelection" />
      </div>
    </div>
  </div>
</template>
