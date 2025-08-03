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
    class="bg-white rounded-2xl max-w-[250px] max-h-[200px] overflow-hidden overflow-y-auto [scrollbar-width]:w-[1.25rem] [&::-webkit-scrollbar]:w-[1.25rem] [&::-webkit-scrollbar-thumb]:border-[0.5rem] [&::-webkit-scrollbar-thumb]:rounded-full">

    <div class="flex flex-col h-full">
      <div
        class="flex-1 overflow-y-auto p-2 pr-0">
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
              class="flex items-center gap-3 p-3 rounded-xl cursor-pointer select-none hover:bg-[#EDEFF6] transition-colors duration-150"
              :class="{
                'bg-[#EDEFF6]': isSelected(
                  document.id,
                ),
              }"
              @click="toggleDocument(document)">
              <div class="flex-1 min-w-0">
                <div class="font-medium text-gray-900 truncate">
                  {{ document.title }}
                </div>
              </div>
            </div>
          </template>
        </AnimatedList>
      </div>
    </div>
  </div>
</template>
