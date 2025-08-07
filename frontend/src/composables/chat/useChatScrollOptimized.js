import {
  computed,
  nextTick,
  watchEffect,
  onUnmounted,
  ref,
  readonly,
} from 'vue'
import {
  useElementVisibility,
  useScroll,
  useResizeObserver,
  watchArray,
  watchDeep,
  useDebounceFn,
  useWindowSize,
} from '@vueuse/core'

/**
 * Оптимизированный композабл для управления прокруткой чата
 * Использует VueUse для реактивного отслеживания состояния без таймаутов
 *
 * @param {Ref<HTMLElement>} scrollContainer - ref контейнера для скролла
 * @param {Object} options - опции конфигурации
 * @returns {Object} API композабла с методами прокрутки
 */
export function useChatScrollOptimized(scrollContainer, options = {}) {
  const { behavior = 'smooth', threshold = 0.1, rootMargin = '0px' } = options

  if (!scrollContainer) {
    console.warn('useChatScrollOptimized: scrollContainer ref is required')
    return {
      scrollToBottom: () => {},
      isScrolledToBottom: computed(() => false),
      canScrollToBottom: computed(() => false),
    }
  }

  // Используем VueUse для отслеживания прокрутки
  const {
    y: scrollY,
    arrivedState,
    isScrolling,
    directions,
  } = useScroll(scrollContainer, { behavior })

  // Отслеживаем видимость контейнера
  const containerVisible = useElementVisibility(scrollContainer, {
    threshold,
    rootMargin,
  })

  // Флаг для блокировки автоматической прокрутки во время умной прокрутки
  const isSmartScrollActive = ref(false)

  // Отслеживаем изменения размера контейнера
  useResizeObserver(scrollContainer, () => {
    // Блокируем автоматическую прокрутку во время умной прокрутки
    if (isSmartScrollActive.value) {
      console.log('🚫 ResizeObserver (composable): Blocked by smart scroll flag')
      return
    }
    
    // Автоматически прокручиваем вниз при изменении размера, если уже были внизу
    if (arrivedState.bottom) {
      console.log('📏 ResizeObserver (composable): Scrolling to bottom')
      nextTick(() => scrollToBottom())
    }
  })

  // Вычисляем, находимся ли мы внизу
  const isScrolledToBottom = computed(() => arrivedState.bottom)

  // Вычисляем, можем ли мы прокрутить вниз
  const canScrollToBottom = computed(() => {
    if (!scrollContainer.value) return false
    const { scrollHeight, clientHeight } = scrollContainer.value
    return scrollHeight > clientHeight
  })

  // Функция для плавной прокрутки вниз
  const scrollToBottom = () => {
    if (!scrollContainer.value || !containerVisible.value) return

    const element = scrollContainer.value
    const targetScrollTop = element.scrollHeight - element.clientHeight

    if (behavior === 'smooth') {
      element.scrollTo({
        top: targetScrollTop,
        behavior: 'smooth',
      })
    } else {
      element.scrollTop = targetScrollTop
    }
  }

  // Функция для прокрутки к определенному элементу
  const scrollToElement = (elementOrSelector, options = {}) => {
    if (!scrollContainer.value || !containerVisible.value) return

    let targetElement
    if (typeof elementOrSelector === 'string') {
      targetElement = scrollContainer.value.querySelector(elementOrSelector)
    } else {
      targetElement = elementOrSelector
    }

    if (!targetElement) return

    const {
      block = 'start',
      inline = 'nearest',
      behavior: scrollBehavior = behavior,
    } = options

    targetElement.scrollIntoView({
      behavior: scrollBehavior,
      block,
      inline,
    })
  }

  // Отслеживаем размеры окна для адаптивности
  const { width: windowWidth } = useWindowSize()

  // Кэш для элементов DOM для оптимизации производительности
  const elementCache = new Map()

  /**
   * Определить, является ли устройство мобильным
   */
  const isMobileDevice = () => {
    return windowWidth.value < 768 // Tailwind md breakpoint
  }

  /**
   * Найти элемент assistant-title по ID сообщения с кэшированием
   * assistant-title - это разделитель между локальным сообщением пользователя и ответом сервера
   */
  const findAssistantTitle = (messageId) => {
    if (!messageId) return null

    // Проверяем кэш
    if (elementCache.has(messageId)) {
      const cachedElement = elementCache.get(messageId)
      // Проверяем, что элемент все еще в DOM
      if (document.contains(cachedElement)) {
        return cachedElement
      } else {
        // Удаляем из кэша, если элемент больше не в DOM
        elementCache.delete(messageId)
      }
    }

    // Стратегия 1: Ищем внутри элемента с data-message-id (оригинальный подход)
    let element = scrollContainer.value?.querySelector(`[data-message-id="${messageId}"] .assistant-title`)
    
    if (!element) {
      // Стратегия 2: Ищем ближайший assistant-title к элементу с data-message-id
      const messageElement = scrollContainer.value?.querySelector(`[data-message-id="${messageId}"]`)
      if (messageElement) {
        // Ищем assistant-title до или после элемента сообщения
        element = messageElement.previousElementSibling?.classList.contains('assistant-title') 
          ? messageElement.previousElementSibling
          : messageElement.nextElementSibling?.classList.contains('assistant-title')
          ? messageElement.nextElementSibling
          : null
      }
    }
    
    if (!element) {
      // Стратегия 3: Ищем по индексу (последний assistant-title для последнего сообщения)
      const allAssistantTitles = Array.from(scrollContainer.value?.querySelectorAll('.assistant-title') || [])
      const allMessageElements = Array.from(scrollContainer.value?.querySelectorAll('[data-message-id]') || [])
      
      // Находим индекс текущего сообщения
      const messageIndex = allMessageElements.findIndex(el => el.getAttribute('data-message-id') === messageId)
      
      if (messageIndex >= 0 && allAssistantTitles[messageIndex]) {
        element = allAssistantTitles[messageIndex]
      }
    }

    console.log('element', element)

    // Кэшируем найденный элемент
    if (element) {
      elementCache.set(messageId, element)
    }

    return element
  }

  /**
   * Рассчитать позицию для прокрутки элемента к нижней границе экрана
   */
  const calculateScrollToBottom = (element) => {
    if (!element || !scrollContainer.value) return 0

    const containerRect = scrollContainer.value.getBoundingClientRect()
    const elementRect = element.getBoundingClientRect()

    // Учитываем особенности мобильных устройств
    const offset = isMobileDevice() ? 20 : 0

    // Позиция для размещения элемента у нижней границы экрана
    return (
      elementRect.top +
      scrollContainer.value.scrollTop -
      (containerRect.bottom - elementRect.height - offset)
    )
  }

  /**
   * Рассчитать позицию для прокрутки элемента к верхней границе экрана
   */
  const calculateScrollToTop = (element) => {
    if (!element || !scrollContainer.value) return 0

    const containerRect = scrollContainer.value.getBoundingClientRect()
    const elementRect = element.getBoundingClientRect()

    // Учитываем особенности мобильных устройств
    const offset = isMobileDevice() ? 10 : 0

    // Позиция для размещения элемента у верхней границы экрана
    return (
      elementRect.top +
      scrollContainer.value.scrollTop -
      containerRect.top -
      offset
    )
  }

  /**
   * Выполнить плавную прокрутку к указанной позиции
   */
  const performScroll = (targetY) => {
    // Не прокручиваем, если пользователь активно скроллит
    if (isScrolling.value) return

    // Не прокручиваем, если контейнер недоступен
    if (!scrollContainer.value || !containerVisible.value) return

    const element = scrollContainer.value

    if (behavior === 'smooth') {
      element.scrollTo({
        top: targetY,
        behavior: 'smooth',
      })
    } else {
      element.scrollTop = targetY
    }
  }

  /**
   * Прокрутить assistant-title к нижней границе экрана
   */
  const scrollAssistantTitleToBottom = (messageId) => {
    nextTick(() => {
      const titleElement = findAssistantTitle(messageId)
      if (!titleElement) return

      const targetY = calculateScrollToBottom(titleElement)
      performScroll(targetY)
    })
  }

  /**
   * Прокрутить assistant-title к верхней границе экрана
   */
  const scrollAssistantTitleToTop = (messageId) => {
    console.log('🔍 Searching for assistant-title with messageId:', messageId)
    
    nextTick(() => {
      const titleElement = findAssistantTitle(messageId)
      
      if (!titleElement) {
        console.error('❌ Assistant-title NOT FOUND for messageId:', messageId)
        console.log('📦 scrollContainer.value:', scrollContainer.value)

        // Подробная диагностика
        const allElementsInContainer = Array.from(
          scrollContainer.value?.querySelectorAll('[data-message-id]') || [],
        )
        console.log(
          '🔍 Available elements with data-message-id (in container):',
          allElementsInContainer.length,
        )

        const allElementsInDocument = Array.from(
          document.querySelectorAll('[data-message-id]'),
        )
        console.log(
          '🔍 Available elements with data-message-id (in document):',
          allElementsInDocument.length,
        )

        const allAssistantTitles = Array.from(
          scrollContainer.value?.querySelectorAll('.assistant-title') || [],
        )
        console.log(
          '🎯 All assistant-title elements found:',
          allAssistantTitles.length,
        )

        return
      }

      console.log('✅ Assistant-title FOUND:', titleElement)
      
      const targetY = calculateScrollToTop(titleElement)
      console.log('📐 Calculated scroll position:', targetY)
      
      performScroll(targetY)
      console.log('🎯 Scroll executed to position:', targetY)
    })
  }

  /**
   * Найти новое локальное сообщение пользователя
   */
  const findNewLocalUserMessage = (newMessages, oldMessages) => {
    if (!newMessages || !oldMessages) return null

    // Ищем новые сообщения пользователя с isLocal: true
    const newUserMessages = newMessages.filter(
      (msg) =>
        msg.type === 'user' &&
        msg.isLocal === true &&
        !oldMessages.find((oldMsg) => oldMsg.id === msg.id),
    )

    // Возвращаем последнее добавленное сообщение
    return newUserMessages.length > 0
      ? newUserMessages[newUserMessages.length - 1]
      : null
  }

  /**
   * Найти сообщение бота, которое изменило статус на 'replied'
   */
  const findNewlyRepliedMessage = (newMessages, oldMessages) => {
    if (!newMessages || !oldMessages) return null

    // Ищем сообщения бота, которые изменили статус с 'loading' на 'replied'
    for (const newMsg of newMessages) {
      if (newMsg.type === 'bot' && newMsg.status === 'replied') {
        const oldMsg = oldMessages.find((old) => old.id === newMsg.id)

        // Проверяем, что статус действительно изменился
        if (oldMsg && oldMsg.status === 'loading') {
          return newMsg
        }

        // Также проверяем новые сообщения с isNew флагом
        if (newMsg.isNew === true) {
          return newMsg
        }
      }
    }

    return null
  }

  /**
   * Умная прокрутка при изменениях в сообщениях
   */
  const enableSmartScroll = (messagesRef) => {
    if (!messagesRef) return

    // Дебаунсинг для оптимизации производительности
    const debouncedFirstScroll = useDebounceFn((newMessages, oldMessages) => {
      const newUserMessage = findNewLocalUserMessage(newMessages, oldMessages)
      if (newUserMessage) {
        scrollAssistantTitleToBottom(newUserMessage.id)
      }
    }, 50)

    // Функция для мгновенной активации блокировки (без дебаунсинга)
    const activateSmartScrollBlocking = (newMessages, oldMessages) => {
      const repliedMessage = findNewlyRepliedMessage(newMessages, oldMessages)

      if (repliedMessage) {
        console.log('🎯 Smart scroll: INSTANT blocking for message', repliedMessage.id)
        
        // МГНОВЕННО активируем флаг блокировки
        isSmartScrollActive.value = true
        console.log('🚫 Smart scroll: Blocking automatic scroll (INSTANT)')
      }
    }

    // Дебаунсированная функция для самой прокрутки
    const debouncedSecondScroll = useDebounceFn((newMessages, oldMessages) => {
      const repliedMessage = findNewlyRepliedMessage(newMessages, oldMessages)

      if (repliedMessage) {
        console.log('🎯 Smart scroll: Executing scroll for bot message', repliedMessage.id)
        
        // Находим пользовательское сообщение, которое предшествует ответу бота
        // assistant-title находится МЕЖДУ пользовательским сообщением и ответом бота
        const userMessage = newMessages
          .filter(msg => msg.type === 'user')
          .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0] // Последнее пользовательское сообщение
        
        if (userMessage) {
          // Ждем следующего тика, чтобы DOM успел обновиться
          nextTick(() => {
            console.log('🔍 Found corresponding user message:', userMessage.id)
            scrollAssistantTitleToTop(userMessage.id)
          })
        } else {
          console.error('❌ No user message found for bot reply:', repliedMessage.id)
        }
        
        // Сбрасываем флаг через время анимации + буфер
        setTimeout(() => {
          isSmartScrollActive.value = false
          console.log('✅ Smart scroll: Unblocking automatic scroll')
        }, 1000) // 600ms анимация + 400ms буфер
      }
    }, 50)

    // Отслеживание изменений в сообщениях для динамических прокруток
    watchArray(
      messagesRef,
      (newMessages, oldMessages, added, removed) => {
        // Первоначальная загрузка - прокручиваем к низу
        if (oldMessages.length === 0 && newMessages.length > 0) {
          nextTick(() => scrollToBottom())
          return
        }

        // Обычная логика для новых сообщений
        debouncedFirstScroll(newMessages, oldMessages)
      },
      { flush: 'post' },
    )

    // Отслеживание изменений в сообщениях для второй прокрутки (ответы сервера)
    watchDeep(
      messagesRef,
      (newMessages, oldMessages) => {
        // МГНОВЕННО активируем блокировку (без дебаунсинга)
        activateSmartScrollBlocking(newMessages, oldMessages)
        // Затем выполняем дебаунсированную прокрутку
        debouncedSecondScroll(newMessages, oldMessages)
      },
      { flush: 'post' },
    )
  }

  // Автоматическая прокрутка при появлении нового контента
  const enableAutoScroll = (enable = true) => {
    if (!enable) return

    watchEffect(() => {
      // Блокируем автоматическую прокрутку во время умной прокрутки
      if (isSmartScrollActive.value) return
      
      if (containerVisible.value && arrivedState.bottom && !isScrolling.value) {
        // Небольшая задержка для обеспечения обновления DOM
        nextTick(() => {
          if (canScrollToBottom.value) {
            scrollToBottom()
          }
        })
      }
    })
  }

  // Очистка кэша при размонтировании компонента
  onUnmounted(() => {
    elementCache.clear()
  })

  return {
    // Основные функции
    scrollToBottom,
    scrollToElement,
    enableAutoScroll,

    // Новые функции для умной прокрутки
    enableSmartScroll,
    scrollAssistantTitleToBottom,
    scrollAssistantTitleToTop,

    // Состояние прокрутки
    scrollY: readonly(scrollY),
    isScrolling: readonly(isScrolling),
    isScrolledToBottom,
    canScrollToBottom,
    arrivedState: readonly(arrivedState),
    directions: readonly(directions),

    // Состояние умной прокрутки
    isSmartScrollActive: readonly(isSmartScrollActive),

    // Видимость контейнера
    containerVisible: readonly(containerVisible),
  }
}
