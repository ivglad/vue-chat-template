import { ref, readonly } from 'vue'

/**
 * Композабл для управления темой приложения
 * Использует встроенные методы PrimeVue для переключения темы
 */
export function useTheme() {
  const isDarkMode = ref(false)

  // Инициализация темы
  const initTheme = () => {
    // Проверяем сохраненную тему в localStorage
    const savedTheme = localStorage.getItem('theme')

    if (savedTheme === 'dark') {
      isDarkMode.value = true
      document.documentElement.classList.add('dark-mode')
    } else if (savedTheme === 'light') {
      isDarkMode.value = false
      document.documentElement.classList.remove('dark-mode')
    } else {
      // Если тема не сохранена, используем системные настройки
      const prefersDark = window.matchMedia(
        '(prefers-color-scheme: dark)',
      ).matches
      isDarkMode.value = prefersDark
      if (prefersDark) {
        document.documentElement.classList.add('dark-mode')
      }
    }
  }

  // Переключение темы (встроенный метод PrimeVue)
  const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value

    if (isDarkMode.value) {
      document.documentElement.classList.add('dark-mode')
      localStorage.setItem('theme', 'dark')
    } else {
      document.documentElement.classList.remove('dark-mode')
      localStorage.setItem('theme', 'light')
    }
  }

  // Установка конкретной темы
  const setTheme = (theme) => {
    if (theme === 'dark') {
      isDarkMode.value = true
      document.documentElement.classList.add('dark-mode')
      localStorage.setItem('theme', 'dark')
    } else {
      isDarkMode.value = false
      document.documentElement.classList.remove('dark-mode')
      localStorage.setItem('theme', 'light')
    }
  }

  // Получение текущей темы
  const getCurrentTheme = () => {
    return isDarkMode.value ? 'dark' : 'light'
  }

  return {
    isDarkMode: readonly(isDarkMode),
    initTheme,
    toggleTheme,
    setTheme,
    getCurrentTheme,
  }
}
