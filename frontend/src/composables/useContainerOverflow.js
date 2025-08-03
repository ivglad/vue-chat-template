/**
 * Композабл для отслеживания переполнения контейнера и динамического применения классов
 *
 * @param {Ref} containerRef - ref родительского контейнера
 * @param {Ref} childRef - ref дочернего элемента
 * @param {Object} options - настройки композабла
 * @returns {Object} объект с реактивными свойствами и методами
 */
export function useContainerOverflow(containerRef, childRef, options = {}) {
  const {
    overflowClass = 'pr-1',
    baseClasses = '',
    compareBy = 'height',
    threshold = 0,
    watchProps = [],
    debounceMs = 16, // ~60fps
  } = options

  // Реактивное состояние переполнения
  const hasOverflow = ref(false)

  // ResizeObserver для отслеживания изменений размеров
  let resizeObserver = null
  let debounceTimer = null

  /**
   * Проверить размеры контейнеров и определить переполнение
   */
  const checkOverflow = () => {
    if (!containerRef.value || !childRef.value) {
      hasOverflow.value = false
      return
    }

    const container = containerRef.value
    const child = childRef.value

    let isOverflowing = false

    switch (compareBy) {
      case 'height':
        const containerHeight = container.clientHeight
        const childHeight = child.scrollHeight || child.offsetHeight
        isOverflowing = childHeight > containerHeight + threshold
        break

      case 'width':
        const containerWidth = container.clientWidth
        const childWidth = child.scrollWidth || child.offsetWidth
        isOverflowing = childWidth > containerWidth + threshold
        break

      case 'both':
        const cHeight = container.clientHeight
        const cWidth = container.clientWidth
        const chHeight = child.scrollHeight || child.offsetHeight
        const chWidth = child.scrollWidth || child.offsetWidth
        isOverflowing =
          chHeight > cHeight + threshold || chWidth > cWidth + threshold
        break

      default:
        console.warn(
          `useContainerOverflow: неизвестный режим сравнения "${compareBy}"`,
        )
    }

    hasOverflow.value = isOverflowing
  }

  /**
   * Debounced версия проверки для оптимизации производительности
   */
  const debouncedCheck = () => {
    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }
    debounceTimer = setTimeout(checkOverflow, debounceMs)
  }

  /**
   * Инициализация ResizeObserver
   */
  const initObserver = () => {
    if (typeof ResizeObserver === 'undefined') {
      console.warn('useContainerOverflow: ResizeObserver не поддерживается')
      return
    }

    resizeObserver = new ResizeObserver(debouncedCheck)

    if (containerRef.value) {
      resizeObserver.observe(containerRef.value)
    }

    if (childRef.value) {
      resizeObserver.observe(childRef.value)
    }
  }

  /**
   * Очистка observer
   */
  const cleanupObserver = () => {
    if (resizeObserver) {
      resizeObserver.disconnect()
      resizeObserver = null
    }
    if (debounceTimer) {
      clearTimeout(debounceTimer)
      debounceTimer = null
    }
  }

  /**
   * Computed классы для контейнера
   */
  const containerClasses = computed(() => {
    const classes = baseClasses ? [baseClasses] : []

    if (hasOverflow.value && overflowClass) {
      if (Array.isArray(overflowClass)) {
        classes.push(...overflowClass)
      } else {
        classes.push(overflowClass)
      }
    }

    return classes
  })

  // Lifecycle hooks
  onMounted(() => {
    initObserver()

    // Первоначальная проверка с задержкой для корректного рендера
    nextTick(() => {
      setTimeout(checkOverflow, 100)
    })
  })

  onUnmounted(() => {
    cleanupObserver()
  })

  // Watchers для отслеживания изменений в переданных props
  if (watchProps.length > 0) {
    watchProps.forEach((prop) => {
      if (isRef(prop) || isReactive(prop)) {
        watch(
          prop,
          () => {
            nextTick(debouncedCheck)
          },
          { flush: 'post' },
        )
      }
    })
  }

  // Методы для ручного управления
  const methods = {
    /**
     * Ручная проверка переполнения
     */
    check: checkOverflow,

    /**
     * Принудительное обновление observer
     */
    refresh: () => {
      cleanupObserver()
      nextTick(() => {
        initObserver()
        checkOverflow()
      })
    },

    /**
     * Временное отключение observer
     */
    pause: cleanupObserver,

    /**
     * Возобновление работы observer
     */
    resume: initObserver,
  }

  return {
    // Реактивные свойства
    hasOverflow: readonly(hasOverflow),
    containerClasses,

    // Методы
    ...methods,
  }
}
