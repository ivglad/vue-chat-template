/**
 * Минимальный композабл для fallback старых браузеров
 * Используется только если браузер не поддерживает dvh/svh/lvh
 */
export function useViewportFallback() {
  // Проверяем поддержку современных viewport единиц
  const supportsModernViewport = () => {
    return CSS.supports('height', '100dvh') || CSS.supports('height', '100svh')
  }

  const setViewportFallback = () => {
    // Только для браузеров без поддержки современных viewport единиц
    if (!supportsModernViewport()) {
      const vh = window.innerHeight * 0.01
      document.documentElement.style.setProperty('--vh', `${vh}px`)
    }
  }

  const initViewportFallback = () => {
    // Проверяем поддержку современных viewport единиц
    if (supportsModernViewport()) {
      // console.log(
      //   '✅ Браузер поддерживает современные viewport единицы (dvh/svh/lvh)',
      // )
      return // Не нужен JavaScript fallback
    }

    console.log(
      '⚠️ Браузер не поддерживает dvh/svh/lvh, используем JavaScript fallback',
    )

    // Устанавливаем начальное значение
    setViewportFallback()

    // Обновляем только при изменении размера окна
    window.addEventListener('resize', setViewportFallback)

    // Обновляем при изменении ориентации
    window.addEventListener('orientationchange', () => {
      setTimeout(setViewportFallback, 100)
    })
  }

  const cleanupViewportFallback = () => {
    if (!supportsModernViewport()) {
      window.removeEventListener('resize', setViewportFallback)
      window.removeEventListener('orientationchange', setViewportFallback)
    }
  }

  return {
    initViewportFallback,
    cleanupViewportFallback,
    supportsModernViewport,
  }
}
