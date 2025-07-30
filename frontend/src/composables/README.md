# Система анимации страниц

## usePageTransition

Композабл для создания поочередной анимации элементов при переходах между страницами.

### Основные возможности

- ✨ **Поочередная анимация элементов** - элементы появляются и исчезают с задержкой
- 🎭 **Гибкие настройки** - полная кастомизация времени, задержек и эффектов
- 🎨 **Множественные эффекты** - opacity, scale, blur, translate
- 🏎️ **Производительность** - оптимизировано для плавной работы
- 📱 **Респонсивность** - поддержка `prefers-reduced-motion`

### Быстрый старт

```vue
<script setup>
import { motion } from 'motion-v'
import { usePageTransition } from '@/composables/usePageTransition'

const { getElementAnimationProps } = usePageTransition({
  staggerDelay: 0.1, // Задержка между элементами
  enterDuration: 0.3, // Длительность появления
  enterDelay: 0.2, // Начальная задержка
})
</script>

<template>
  <!-- Каждому элементу присваиваем индекс для поочередной анимации -->
  <motion.h1 v-bind="getElementAnimationProps(0)">
    Заголовок (появится первым)
  </motion.h1>

  <motion.div v-bind="getElementAnimationProps(1)">
    Содержимое (появится вторым)
  </motion.div>

  <motion.button v-bind="getElementAnimationProps(2)">
    Кнопка (появится третьей)
  </motion.button>
</template>
```

### Настройки

```javascript
const options = {
  staggerDelay: 0.05, // Задержка между элементами (сек)
  exitDuration: 0.15, // Длительность исчезновения
  enterDuration: 0.2, // Длительность появления
  exitDelay: 0, // Задержка перед исчезновением
  enterDelay: 0.1, // Задержка перед появлением
  maxStaggerItems: 20, // Макс. элементов для анимации
}

const { getElementAnimationProps } = usePageTransition(options)
```

### Кастомные эффекты

```vue
<motion.div
  v-bind="
    getElementAnimationProps(0, 'enter', {
      initial: { opacity: 0, scale: 0.8, rotate: -10 },
      enter: { opacity: 1, scale: 1, rotate: 0 },
      exit: { opacity: 0, scale: 0.9, rotate: 5 },
    })
  ">
  Элемент с кастомной анимацией
</motion.div>
```

### Альтернатива с Tailwind CSS

Для простых случаев можно использовать Tailwind классы:

```vue
<script setup>
const { getTailwindClasses } = usePageTransition()
</script>

<template>
  <div :class="getTailwindClasses(0)">Элемент с Tailwind анимацией</div>
</template>
```

### Состояния анимации

```vue
<script setup>
const {
  isTransitioning, // Идет ли переход
  isExiting, // Фаза исчезновения
  isEntering, // Фаза появления
  isIdle, // Состояние покоя
} = usePageTransition()
</script>

<template>
  <div v-if="isTransitioning">Идет переход...</div>
</template>
```

### Интеграция с Vue Router

Переходы между страницами автоматически настроены в `App.vue`:

- **Auth → Chat**: плавное появление с blur и scale эффектами
- **Chat → Auth**: плавное появление с blur и scale эффектами
- **Другие переходы**: стандартный fade эффект

### Примеры использования

**Форма авторизации:**

```vue
<!-- Auth.vue -->
<motion.h1 v-bind="getElementAnimationProps(0)">Авторизация</motion.h1>
<motion.div v-bind="getElementAnimationProps(1)"><!-- форма --></motion.div>
<motion.div
  v-bind="getElementAnimationProps(2)"><!-- поле email --></motion.div>
<motion.div
  v-bind="getElementAnimationProps(3)"><!-- поле пароль --></motion.div>
<motion.div v-bind="getElementAnimationProps(4)"><!-- кнопка --></motion.div>
```

**Чат интерфейс:**

```vue
<!-- Chat.vue -->
<motion.div v-bind="getElementAnimationProps(0)"><!-- header --></motion.div>
<motion.div v-bind="getElementAnimationProps(1)"><!-- контент --></motion.div>
<motion.div v-bind="getElementAnimationProps(2)"><!-- footer --></motion.div>
```

### Доступность

Композабл автоматически учитывает настройки пользователя:

- При `prefers-reduced-motion: reduce` отключаются сложные эффекты
- Анимации заменяются на простой fade
- Сохраняется функциональность без ущерба для UX

### Производительность

- Использует GPU-ускорение через motion-v
- Ограничивает количество анимируемых элементов
- Оптимизированные timing-функции
- Минимальный overhead для простых случаев
