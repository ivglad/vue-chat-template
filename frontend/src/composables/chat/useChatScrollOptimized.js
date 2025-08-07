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
      console.log(
        '🚫 ResizeObserver (composable): Blocked by smart scroll flag',
      )
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

  // Упрощенная логика поиска разделителей без кэширования

  /**
   * Определить, является ли устройство мобильным
   */
  const isMobileDevice = () => {
    return windowWidth.value < 768 // Tailwind md breakpoint
  }

  /**
   * Найти разделитель по ID сообщения (упрощенная версия)
   */
  const findMessageSeparator = (messageId) => {
    if (!messageId || !scrollContainer.value) return null

    // Простой поиск по data-separator-id
    const element = scrollContainer.value.querySelector(
      `[data-separator-id="${messageId}"]`,
    )

    console.log(
      '🔍 Looking for separator with messageId:',
      messageId,
      'found:',
      !!element,
    )

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
   * Прокрутить разделитель к нижней границе экрана
   */
  const scrollSeparatorToBottom = (messageId) => {
    nextTick(() => {
      const separatorElement = findMessageSeparator(messageId)
      if (!separatorElement) return

      const targetY = calculateScrollToBottom(separatorElement)
      performScroll(targetY)
    })
  }

  /**
   * Прокрутить разделитель к верхней границе экрана
   */
  const scrollSeparatorToTop = (messageId) => {
    console.log('🔍 Searching for separator with messageId:', messageId)

    nextTick(() => {
      const separatorElement = findMessageSeparator(messageId)

      if (!separatorElement) {
        console.error('❌ Separator NOT FOUND for messageId:', messageId)
        return
      }

      console.log('✅ Separator FOUND:', separatorElement)

      const targetY = calculateScrollToTop(separatorElement)
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
        scrollSeparatorToBottom(newUserMessage.id)
      }
    }, 50)

    // Функция для мгновенной активации блокировки (без дебаунсинга)
    const activateSmartScrollBlocking = (newMessages, oldMessages) => {
      const repliedMessage = findNewlyRepliedMessage(newMessages, oldMessages)

      if (repliedMessage) {
        console.log(
          '🎯 Smart scroll: INSTANT blocking for message',
          repliedMessage.id,
        )

        // МГНОВЕННО активируем флаг блокировки
        isSmartScrollActive.value = true
        console.log('🚫 Smart scroll: Blocking automatic scroll (INSTANT)')
      }
    }

    // Дебаунсированная функция для самой прокрутки
    const debouncedSecondScroll = useDebounceFn((newMessages, oldMessages) => {
      const repliedMessage = findNewlyRepliedMessage(newMessages, oldMessages)

      if (repliedMessage) {
        console.log(
          '🎯 Smart scroll: Executing scroll for bot message',
          repliedMessage.id,
        )

        // Находим пользовательское сообщение, которое предшествует ответу бота
        // assistant-title находится МЕЖДУ пользовательским сообщением и ответом бота
        const userMessage = newMessages
          .filter((msg) => msg.type === 'user')
          .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0] // Последнее пользовательское сообщение

        if (userMessage) {
          // Ждем следующего тика, чтобы DOM успел обновиться
          nextTick(() => {
            console.log('🔍 Found corresponding user message:', userMessage.id)
            scrollSeparatorToTop(userMessage.id)
          })
        } else {
          console.error(
            '❌ No user message found for bot reply:',
            repliedMessage.id,
          )
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

  // Композабл больше не использует кэширование DOM элементов

  return {
    // Основные функции
    scrollToBottom,
    scrollToElement,
    enableAutoScroll,

    // Новые функции для умной прокрутки
    enableSmartScroll,
    scrollSeparatorToBottom,
    scrollSeparatorToTop,

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
