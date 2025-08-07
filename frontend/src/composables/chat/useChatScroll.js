import { watch, nextTick, onUnmounted, ref } from 'vue'
import {
  useScroll,
  useWindowSize,
  useDebounceFn,
  useTransition,
} from '@vueuse/core'
import { useChatStore } from '@/stores/chat/useChatStore'

/**
 * Композабл для управления плавной прокруткой чата
 * Обеспечивает автоматическую прокрутку в два этапа:
 * 1. После создания локального сообщения пользователя - к нижней границе экрана
 * 2. После получения ответа сервера - к верхней границе экрана
 * 3. Предоставляет простую функцию прокрутки к низу для внешнего управления
 *
 * @param {Ref<HTMLElement>} scrollContainer - ref контейнера для скролла
 * @returns {Object} API композабла с методами прокрутки
 */
export function useChatScroll(scrollContainer) {
  // Проверяем доступность контейнера
  if (!scrollContainer) {
    console.warn('useChatScroll: scrollContainer ref is required')
    return
  }

  const chatStore = useChatStore()

  // Отслеживаем размеры окна для адаптивности
  const { width: windowWidth } = useWindowSize()

  // Используем VueUse для управления скроллом
  const { isScrolling } = useScroll(scrollContainer, {
    behavior: 'smooth',
  })

  // Создаем плавную анимацию для прокрутки с easing
  const scrollPosition = ref(0)
  const animatedScrollPosition = useTransition(scrollPosition, {
    duration: 600, // Уменьшаем длительность для более отзывчивой анимации
    transition: [0.2, 0.0, 0.4, 1.0], // Cubic bezier для быстрого старта и плавного конца (ease-out)
  })

  // Кэш для элементов DOM для оптимизации производительности
  const elementCache = new Map()

  // Флаг для предотвращения множественных одновременных прокруток
  const isScrollInProgress = ref(false)



  /**
   * Проверить поддержку плавной прокрутки браузером
   * @returns {boolean} - поддерживается ли smooth scrolling
   */
  const isSmoothScrollSupported = () => {
    return 'scrollBehavior' in document.documentElement.style
  }

  /**
   * Определить, является ли устройство мобильным
   * @returns {boolean} - мобильное устройство или нет
   */
  const isMobileDevice = () => {
    return windowWidth.value < 768 // Tailwind md breakpoint
  }

  /**
   * Найти элемент assistant-divider-start по ID сообщения с кэшированием
   * @param {string} messageId - ID сообщения
   * @returns {HTMLElement|null} - найденный элемент или null
   */
  const findAssistantDivider = (messageId) => {
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

    const selector = `[data-message-id="${messageId}"].assistant-divider-start`
    const element = document.querySelector(selector)

    // Кэшируем найденный элемент
    if (element) {
      elementCache.set(messageId, element)
    }

    return element
  }

  /**
   * Рассчитать позицию для прокрутки элемента к нижней границе экрана
   * @param {HTMLElement} element - целевой элемент
   * @returns {number} - позиция для скролла
   */
  const calculateScrollToBottom = (element) => {
    if (!element || !scrollContainer.value) return 0

    const containerRect = scrollContainer.value.getBoundingClientRect()
    const elementRect = element.getBoundingClientRect()

    // Учитываем особенности мобильных устройств
    const offset = isMobileDevice() ? 20 : 0 // Дополнительный отступ для мобильных

    // Позиция для размещения элемента у нижней границы экрана
    return (
      elementRect.top +
      scrollContainer.value.scrollTop -
      (containerRect.bottom - elementRect.height - offset)
    )
  }

  /**
   * Рассчитать позицию для прокрутки элемента к верхней границе экрана
   * @param {HTMLElement} element - целевой элемент
   * @returns {number} - позиция для скролла
   */
  const calculateScrollToTop = (element) => {
    if (!element || !scrollContainer.value) return 0

    const containerRect = scrollContainer.value.getBoundingClientRect()
    const elementRect = element.getBoundingClientRect()

    // Учитываем особенности мобильных устройств
    const offset = isMobileDevice() ? 10 : 0 // Небольшой отступ сверху для мобильных

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
   * @param {number} targetY - целевая позиция
   */
  const performScroll = (targetY) => {
    // Не прокручиваем, если пользователь активно скроллит
    if (isScrolling.value) return

    // Не прокручиваем, если контейнер недоступен
    if (!scrollContainer.value) return

    // Предотвращаем множественные одновременные прокрутки
    if (isScrollInProgress.value) return

    isScrollInProgress.value = true

    // Проверяем поддержку плавной прокрутки
    if (!isSmoothScrollSupported()) {
      // Fallback для браузеров без поддержки smooth scrolling
      scrollContainer.value.scrollTop = targetY
      isScrollInProgress.value = false
      return
    }

    // Используем плавную анимацию с easing
    scrollPosition.value = targetY

    // Отслеживаем изменения анимированной позиции и применяем к скроллу
    const stopWatchingAnimation = watch(animatedScrollPosition, (newPos) => {
      if (scrollContainer.value) {
        scrollContainer.value.scrollTop = newPos
      }
    })

    // Сбрасываем флаг после завершения анимации
    const stopWatchingScroll = watch(isScrolling, (scrolling) => {
      if (!scrolling && isScrollInProgress.value) {
        isScrollInProgress.value = false
        stopWatchingScroll()
        stopWatchingAnimation()
      }
    })
  }

  /**
   * Прокрутить assistant-divider-start к нижней границе экрана
   * Используется при отправке нового сообщения пользователем
   * @public
   * @param {string} messageId - ID сообщения для поиска соответствующего divider
   */
  const scrollToAssistantDividerBottom = (messageId) => {
    nextTick(() => {
      const divider = findAssistantDivider(messageId)
      if (!divider) return

      const targetY = calculateScrollToBottom(divider)
      performScroll(targetY)
    })
  }

  /**
   * Прокрутить assistant-divider-start к верхней границе экрана
   * Используется при получении ответа от сервера
   * @public
   * @param {string} messageId - ID сообщения для поиска соответствующего divider
   */
  const scrollToAssistantDividerTop = (messageId) => {
    nextTick(() => {
      const divider = findAssistantDivider(messageId)
      if (!divider) return

      const targetY = calculateScrollToTop(divider)
      performScroll(targetY)
    })
  }

  /**
   * Простая мгновенная прокрутка к низу контейнера
   * Используется для первоначальной прокрутки при загрузке компонента
   * @public
   */
  const scrollToBottom = () => {
    if (!scrollContainer.value) return
    
    // Мгновенная прокрутка без анимации
    scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight
  }

  /**
   * Найти новое локальное сообщение пользователя
   * @param {Array} newMessages - новый массив сообщений
   * @param {Array} oldMessages - предыдущий массив сообщений
   * @returns {Object|null} - новое сообщение или null
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
   * @param {Array} newMessages - новый массив сообщений
   * @param {Array} oldMessages - предыдущий массив сообщений
   * @returns {Object|null} - сообщение с изменившимся статусом или null
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

  // Дебаунсинг для оптимизации производительности watchers
  const debouncedFirstScroll = useDebounceFn((newMessages, oldMessages) => {
    const newUserMessage = findNewLocalUserMessage(newMessages, oldMessages)
    if (newUserMessage) {
      scrollToAssistantDividerBottom(newUserMessage.id)
    }
  }, 50)

  const debouncedSecondScroll = useDebounceFn((newMessages, oldMessages) => {
    const repliedMessage = findNewlyRepliedMessage(newMessages, oldMessages)

    if (repliedMessage) {
      // Находим последнее локальное сообщение пользователя (самое новое)
      const lastUserMessage = newMessages
        .filter((msg) => msg.type === 'user')
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]

      if (lastUserMessage) {
        scrollToAssistantDividerTop(lastUserMessage.id)
      }
    }
  }, 50)

  // Отслеживание изменений в сообщениях для динамических прокруток
  watch(
    () => chatStore.sortedMessages,
    (newMessages, oldMessages) => {
      // Обычная логика для новых сообщений
      debouncedFirstScroll(newMessages, oldMessages)
    },
    {
      deep: true,
      flush: 'post', // Выполняем после обновления DOM
    },
  )



  // Отслеживание изменений в сообщениях для второй прокрутки (ответы сервера)
  watch(() => chatStore.sortedMessages, debouncedSecondScroll, {
    deep: true,
    flush: 'post', // Выполняем после обновления DOM
  })

  // Очистка кэша при размонтировании компонента
  onUnmounted(() => {
    elementCache.clear()
  })

  // Возвращаем публичный API композабла
  return {
    /**
     * Прокрутить к нижней границе экрана (для новых сообщений пользователя)
     * @param {string} messageId - ID сообщения
     */
    scrollToAssistantDividerBottom,
    
    /**
     * Прокрутить к верхней границе экрана (для ответов сервера)
     * @param {string} messageId - ID сообщения
     */
    scrollToAssistantDividerTop,
    
    /**
     * Простая прокрутка к низу контейнера (для первоначальной загрузки)
     */
    scrollToBottom,
  }
}
