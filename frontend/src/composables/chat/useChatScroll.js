import { ref, nextTick, onMounted, onUnmounted } from 'vue'

/**
 * Композабл для управления скроллингом в чате
 * Обеспечивает плавную прокрутку и автоматическое позиционирование
 * Следует принципу Single Responsibility
 */
export function useChatScroll() {
  // ============================================================================
  // State
  // ============================================================================
  
  const scrollContainer = ref(null)
  const isAutoScrollEnabled = ref(true)
  const isScrolledToBottom = ref(true)
  const isScrolling = ref(false)
  
  // ============================================================================
  // Scroll Detection
  // ============================================================================
  
  /**
   * Проверить, находится ли скролл внизу
   * @param {HTMLElement} element - элемент контейнера
   * @returns {boolean}
   */
  const checkIfScrolledToBottom = (element) => {
    if (!element) return true
    
    const threshold = 50 // пикселей от низа
    const { scrollTop, scrollHeight, clientHeight } = element
    
    return scrollHeight - scrollTop - clientHeight <= threshold
  }
  
  /**
   * Обработчик события скролла
   * @param {Event} event - событие скролла
   */
  const handleScroll = (event) => {
    const element = event.target
    const wasScrolledToBottom = isScrolledToBottom.value
    
    isScrolledToBottom.value = checkIfScrolledToBottom(element)
    
    // Отключаем автоскролл если пользователь скроллит вверх
    if (!isScrolledToBottom.value && wasScrolledToBottom) {
      isAutoScrollEnabled.value = false
    }
    
    // Включаем автоскролл если пользователь вернулся вниз
    if (isScrolledToBottom.value && !wasScrolledToBottom) {
      isAutoScrollEnabled.value = true
    }
  }
  
  // ============================================================================
  // Scroll Actions
  // ============================================================================
  
  /**
   * Прокрутить к низу контейнера
   * @param {Object} options - опции прокрутки
   */
  const scrollToBottom = async (options = {}) => {
    const {
      smooth = true,
      force = false,
      delay = 0
    } = options
    
    if (!scrollContainer.value) return
    
    // Проверяем, нужно ли прокручивать
    if (!force && !isAutoScrollEnabled.value) return
    
    // Ждем указанную задержку
    if (delay > 0) {
      await new Promise(resolve => setTimeout(resolve, delay))
    }
    
    // Ждем следующий тик для обновления DOM
    await nextTick()
    
    const element = scrollContainer.value
    if (!element) return
    
    isScrolling.value = true
    
    try {
      element.scrollTo({
        top: element.scrollHeight,
        behavior: smooth ? 'smooth' : 'instant'
      })
      
      // Обновляем состояние после прокрутки
      setTimeout(() => {
        isScrolledToBottom.value = true
        isScrolling.value = false
      }, smooth ? 300 : 0)
      
    } catch (error) {
      console.warn('Scroll to bottom failed:', error)
      isScrolling.value = false
    }
  }
  
  /**
   * Прокрутить к определенному элементу
   * @param {string|HTMLElement} target - селектор или элемент
   * @param {Object} options - опции прокрутки
   */
  const scrollToElement = async (target, options = {}) => {
    const {
      smooth = true,
      block = 'nearest',
      inline = 'nearest'
    } = options
    
    if (!scrollContainer.value) return
    
    let element
    
    if (typeof target === 'string') {
      element = scrollContainer.value.querySelector(target)
    } else {
      element = target
    }
    
    if (!element) {
      console.warn('Scroll target not found:', target)
      return
    }
    
    isScrolling.value = true
    
    try {
      element.scrollIntoView({
        behavior: smooth ? 'smooth' : 'instant',
        block,
        inline
      })
      
      setTimeout(() => {
        isScrolling.value = false
        isScrolledToBottom.value = checkIfScrolledToBottom(scrollContainer.value)
      }, smooth ? 300 : 0)
      
    } catch (error) {
      console.warn('Scroll to element failed:', error)
      isScrolling.value = false
    }
  }
  
  /**
   * Прокрутить к последнему сообщению
   */
  const scrollToLastMessage = () => {
    scrollToElement('[data-message-id]:last-child', {
      block: 'end'
    })
  }
  
  /**
   * Включить автоматическую прокрутку
   */
  const enableAutoScroll = () => {
    isAutoScrollEnabled.value = true
    scrollToBottom({ smooth: true })
  }
  
  /**
   * Отключить автоматическую прокрутку
   */
  const disableAutoScroll = () => {
    isAutoScrollEnabled.value = false
  }
  
  // ============================================================================
  // Scroll Utilities
  // ============================================================================
  
  /**
   * Получить позицию скролла
   * @returns {Object} информация о позиции скролла
   */
  const getScrollInfo = () => {
    if (!scrollContainer.value) {
      return {
        scrollTop: 0,
        scrollHeight: 0,
        clientHeight: 0,
        scrollPercentage: 0
      }
    }
    
    const { scrollTop, scrollHeight, clientHeight } = scrollContainer.value
    const scrollPercentage = scrollHeight > clientHeight 
      ? (scrollTop / (scrollHeight - clientHeight)) * 100 
      : 100
    
    return {
      scrollTop,
      scrollHeight,
      clientHeight,
      scrollPercentage: Math.round(scrollPercentage)
    }
  }
  
  /**
   * Сохранить текущую позицию скролла
   * @returns {number} сохраненная позиция
   */
  const saveScrollPosition = () => {
    if (!scrollContainer.value) return 0
    return scrollContainer.value.scrollTop
  }
  
  /**
   * Восстановить позицию скролла
   * @param {number} position - позиция для восстановления
   */
  const restoreScrollPosition = (position) => {
    if (!scrollContainer.value) return
    
    scrollContainer.value.scrollTop = position
    isScrolledToBottom.value = checkIfScrolledToBottom(scrollContainer.value)
  }
  
  // ============================================================================
  // Lifecycle
  // ============================================================================
  
  /**
   * Инициализировать скролл контейнер
   * @param {HTMLElement} element - элемент контейнера
   */
  const initScrollContainer = (element) => {
    if (!element) return
    
    scrollContainer.value = element
    
    // Добавляем обработчик скролла
    element.addEventListener('scroll', handleScroll, { passive: true })
    
    // Проверяем начальное состояние
    isScrolledToBottom.value = checkIfScrolledToBottom(element)
  }
  
  /**
   * Очистить ресурсы
   */
  const cleanup = () => {
    if (scrollContainer.value) {
      scrollContainer.value.removeEventListener('scroll', handleScroll)
    }
  }
  
  // Автоматическая очистка при размонтировании
  onUnmounted(() => {
    cleanup()
  })
  
  // ============================================================================
  // Return
  // ============================================================================
  
  return {
    // Refs
    scrollContainer,
    isAutoScrollEnabled,
    isScrolledToBottom,
    isScrolling,
    
    // Actions
    scrollToBottom,
    scrollToElement,
    scrollToLastMessage,
    enableAutoScroll,
    disableAutoScroll,
    
    // Utilities
    getScrollInfo,
    saveScrollPosition,
    restoreScrollPosition,
    checkIfScrolledToBottom,
    
    // Lifecycle
    initScrollContainer,
    cleanup
  }
}