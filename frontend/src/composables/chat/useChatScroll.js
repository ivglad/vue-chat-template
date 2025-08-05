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
    const { smooth = true, force = false, delay = 0 } = options

    if (!scrollContainer.value) return

    // Проверяем, нужно ли прокручивать
    if (!force && !isAutoScrollEnabled.value) return

    // Ждем указанную задержку
    if (delay > 0) {
      await new Promise((resolve) => setTimeout(resolve, delay))
    }

    // Ждем следующий тик для обновления DOM
    await nextTick()

    const element = scrollContainer.value
    if (!element) return

    isScrolling.value = true

    try {
      element.scrollTo({
        top: element.scrollHeight,
        behavior: smooth ? 'smooth' : 'instant',
      })

      // Обновляем состояние после прокрутки
      setTimeout(
        () => {
          isScrolledToBottom.value = true
          isScrolling.value = false
        },
        smooth ? 300 : 0,
      )
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
    const { smooth = true, block = 'nearest', inline = 'nearest' } = options

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
        inline,
      })

      setTimeout(
        () => {
          isScrolling.value = false
          isScrolledToBottom.value = checkIfScrolledToBottom(
            scrollContainer.value,
          )
        },
        smooth ? 300 : 0,
      )
    } catch (error) {
      console.warn('Scroll to element failed:', error)
      isScrolling.value = false
    }
  }

  /**
   * Прокрутить к последнему assistant-divider-start (нижняя граница к нижней границе экрана)
   */
  const scrollToLastAssistantDividerBottom = async () => {
    if (!scrollContainer.value) return

    await nextTick()

    // Ищем все элементы с классом assistant-divider-start
    const dividerElements = scrollContainer.value.querySelectorAll(
      '.assistant-divider-start',
    )

    if (dividerElements.length === 0) {
      // Если нет разделителей, просто прокручиваем вниз
      scrollToBottom({ smooth: true })
      return
    }

    // Берем последний разделитель
    const lastDivider = dividerElements[dividerElements.length - 1]

    isScrolling.value = true

    try {
      // Прокручиваем так, чтобы нижняя граница разделителя была у нижней границы экрана
      lastDivider.scrollIntoView({
        behavior: 'smooth',
        block: 'end',
        inline: 'nearest',
      })

      // Обновляем состояние после прокрутки
      setTimeout(() => {
        isScrolledToBottom.value = checkIfScrolledToBottom(
          scrollContainer.value,
        )
        isScrolling.value = false
      }, 300)
    } catch (error) {
      console.warn('Scroll to assistant divider bottom failed:', error)
      isScrolling.value = false
    }
  }

  /**
   * Прокрутить к последнему assistant-title (к верхней границе экрана)
   */
  const scrollToLastAssistantTitle = async () => {
    if (!scrollContainer.value) return

    await nextTick()

    // Ищем все элементы с классом assistant-title
    const titleElements =
      scrollContainer.value.querySelectorAll('.assistant-title')

    if (titleElements.length === 0) {
      console.warn('No assistant titles found')
      return
    }

    // Берем последний заголовок
    const lastTitle = titleElements[titleElements.length - 1]

    // Отключаем автоскролл временно
    const wasAutoScrollEnabled = isAutoScrollEnabled.value
    isAutoScrollEnabled.value = false
    isScrolling.value = true

    try {
      // Прокручиваем так, чтобы заголовок был в верхней части экрана
      lastTitle.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
        inline: 'nearest',
      })

      // Восстанавливаем автоскролл через некоторое время
      setTimeout(() => {
        isAutoScrollEnabled.value = wasAutoScrollEnabled
        isScrolledToBottom.value = checkIfScrolledToBottom(
          scrollContainer.value,
        )
        isScrolling.value = false
      }, 500)
    } catch (error) {
      console.warn('Scroll to assistant title failed:', error)
      isAutoScrollEnabled.value = wasAutoScrollEnabled
      isScrolling.value = false
    }
  }

  /**
   * Мгновенная прокрутка вниз без анимации (для загрузки чата)
   */
  const scrollToBottomInstant = async () => {
    if (!scrollContainer.value) return

    await nextTick()

    const element = scrollContainer.value
    if (!element) return

    try {
      // Мгновенная прокрутка без анимации
      element.scrollTo({
        top: element.scrollHeight,
        behavior: 'instant',
      })

      // Обновляем состояние
      isScrolledToBottom.value = true
      isAutoScrollEnabled.value = true
    } catch (error) {
      console.warn('Instant scroll to bottom failed:', error)
    }
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
    scrollToBottomInstant,
    scrollToElement,
    scrollToLastAssistantDividerBottom,
    scrollToLastAssistantTitle,

    // Utilities
    checkIfScrolledToBottom,

    // Lifecycle
    initScrollContainer,
    cleanup,
  }
}
