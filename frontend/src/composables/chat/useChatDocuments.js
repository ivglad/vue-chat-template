import { ref, computed } from 'vue'
import { useDocuments } from '@/helpers/api/queries'

/**
 * Композабл для управления документами в чате
 * Инкапсулирует логику выбора и работы с документами
 * Следует принципу Single Responsibility
 */
export function useChatDocuments() {
  // ============================================================================
  // State
  // ============================================================================
  
  const selectedDocuments = ref([])
  const searchQuery = ref('')
  
  // ============================================================================
  // API
  // ============================================================================
  
  const { 
    data: documentsData, 
    isLoading: documentsLoading,
    error: documentsError,
    refetch: refetchDocuments
  } = useDocuments({ per_page: 100 })
  
  // ============================================================================
  // Computed
  // ============================================================================
  
  const documents = computed(() => {
    return documentsData.value?.documents?.map(doc => ({
      id: doc.id,
      title: doc.title,
      label: doc.title // для совместимости с существующим кодом
    })) || []
  })
  
  const filteredDocuments = computed(() => {
    if (!searchQuery.value.trim()) {
      return documents.value
    }
    
    const query = searchQuery.value.toLowerCase().trim()
    return documents.value.filter(doc => 
      doc.title.toLowerCase().includes(query) ||
      doc.id.toString().includes(query)
    )
  })
  
  const hasSelectedDocuments = computed(() => selectedDocuments.value.length > 0)
  
  const selectedDocumentIds = computed(() => selectedDocuments.value.map(doc => doc.id))
  
  // ============================================================================
  // Methods
  // ============================================================================
  
  /**
   * Проверить, выбран ли документ
   * @param {number} documentId - ID документа
   * @returns {boolean}
   */
  const isDocumentSelected = (documentId) => {
    return selectedDocuments.value.some(doc => doc.id === documentId)
  }
  
  /**
   * Добавить документ в выбранные
   * @param {Object} document - объект документа
   */
  const selectDocument = (document) => {
    if (!isDocumentSelected(document.id)) {
      selectedDocuments.value.push(document)
    }
  }
  
  /**
   * Убрать документ из выбранных
   * @param {number} documentId - ID документа
   */
  const unselectDocument = (documentId) => {
    selectedDocuments.value = selectedDocuments.value.filter(
      doc => doc.id !== documentId
    )
  }
  
  /**
   * Переключить выбор документа
   * @param {Object} document - объект документа
   */
  const toggleDocument = (document) => {
    if (isDocumentSelected(document.id)) {
      unselectDocument(document.id)
    } else {
      selectDocument(document)
    }
  }
  
  /**
   * Очистить выбор документов
   */
  const clearSelection = () => {
    selectedDocuments.value = []
  }
  
  /**
   * Выбрать все документы
   */
  const selectAllDocuments = () => {
    selectedDocuments.value = [...documents.value]
  }
  
  /**
   * Установить выбранные документы
   * @param {Array} docs - массив документов
   */
  const setSelectedDocuments = (docs) => {
    selectedDocuments.value = docs || []
  }
  
  /**
   * Найти документ по ID
   * @param {number} documentId - ID документа
   * @returns {Object|null} документ или null
   */
  const findDocumentById = (documentId) => {
    return documents.value.find(doc => doc.id === documentId) || null
  }
  
  /**
   * Получить данные для отправки сообщения
   * @returns {Object} данные документов для API
   */
  const getMessageDocumentData = () => {
    if (!hasSelectedDocuments.value) {
      return {}
    }
    
    return {
      document_ids: selectedDocumentIds.value,
      documents: selectedDocuments.value
    }
  }
  
  /**
   * Обновить поисковый запрос
   * @param {string} query - поисковый запрос
   */
  const setSearchQuery = (query) => {
    searchQuery.value = query || ''
  }
  
  /**
   * Очистить поисковый запрос
   */
  const clearSearchQuery = () => {
    searchQuery.value = ''
  }
  
  // ============================================================================
  // Return
  // ============================================================================
  
  return {
    // State
    selectedDocuments,
    searchQuery,
    
    // Computed
    documents,
    filteredDocuments,
    hasSelectedDocuments,
    selectedDocumentIds,
    documentsLoading,
    documentsError,
    
    // Methods
    isDocumentSelected,
    selectDocument,
    unselectDocument,
    toggleDocument,
    clearSelection,
    selectAllDocuments,
    setSelectedDocuments,
    findDocumentById,
    getMessageDocumentData,
    setSearchQuery,
    clearSearchQuery,
    refetchDocuments
  }
}