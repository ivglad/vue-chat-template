<script setup>
const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  states: {
    type: Array,
    default: () => [],
  },
  variants: {
    type: Array,
    default: () => [],
  },
})

// CSS Grid template с равными колонками
const gridTemplateColumns = computed(() => {
  // Первая колонка для заголовка вариантов, остальные равные для состояний
  return `minmax(120px, min-content) repeat(${props.states.length}, 1fr)`
})

/**
 * Форматирует строку для использования в имени слота
 * Преобразует строку в нижний регистр и заменяет пробелы на дефисы
 * @param {string} str - строка для форматирования
 * @return {string} форматированная строка
 */
const formatSlotName = (str) => {
  return str.toLowerCase().replace(/\s+/g, '-')
}

/**
 * Генерирует имя слота на основе варианта и состояния
 * @param {string} variant - название варианта
 * @param {string} state - название состояния
 * @return {string} имя слота
 */
const getSlotName = (variant, state) => {
  return `${formatSlotName(variant)}-${formatSlotName(state)}`
}
</script>

<template>
  <section class="ui-layout-display">
    <div class="ui-layout-display__header">
      <h2>{{ title }}</h2>
    </div>
    <div class="ui-layout-display__content">
      <div class="raw-content-wrapper" v-if="!states?.length">
        <slot />
      </div>
      <div class="grid-table-wrapper" v-else>
        <!-- Заголовок таблицы -->
        <div class="grid-table-header">
          <div class="grid-cell header-cell first-cell">Variant</div>
          <div
            v-for="state in states"
            :key="state"
            class="grid-cell header-cell">
            {{ state }}
          </div>
        </div>

        <!-- Дополнительные варианты -->
        <div v-for="variant in variants" :key="variant" class="grid-table-row">
          <div class="grid-cell first-cell">{{ variant }}</div>
          <div v-for="state in states" :key="state" class="grid-cell">
            <div
              class="grid-cell-slot"
              v-if="$slots[getSlotName(variant, state)]">
              <slot :name="getSlotName(variant, state)"> </slot>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style lang="scss" scoped>
.ui-layout-display {
  display: flex;
  flex-direction: column;
  align-items: start;
  width: 100%;
  border: 1px solid var(--p-surface-200);
  border-radius: var(--p-border-radius-lg);
  overflow: hidden;
  &__header {
    display: flex;
    align-items: center;
    justify-content: start;
    width: 100%;
    padding: 1rem 2rem 1rem 2rem;
    border-radius: var(--p-border-radius-lg) var(--p-border-radius-lg) 0 0;
    background-color: var(--p-surface-100);
    .h2 {
      @include fluid-text(24, 18);
      font-weight: 600;
    }
  }
  &__content {
    width: fit-content;
    padding: 0 2rem 2rem 2rem;
    max-width: 100%;
    background-color: var(--p-surface-50);
    overflow-x: auto;
    @include mq(m) {
      width: 100%;
    }
    &:has(.raw-content-wrapper) {
      width: 100%;
      padding: 2rem 2rem 2rem 2rem;
    }
    .raw-content-wrapper {
      width: 100%;
    }
  }
}

.grid-table-wrapper {
  display: flex;
  flex-direction: column;
  width: 100%;
  overflow-x: auto;

  .grid-table-header,
  .grid-table-row {
    display: grid;
    grid-template-columns: v-bind(gridTemplateColumns);
    gap: 2rem;
    width: 100%;
    @include mq(m) {
      grid-template-columns: 1fr;
      grid-auto-flow: row;
      gap: 0;
    }
  }

  .grid-table-header {
    border-top-left-radius: var(--p-border-radius-lg);
    border-top-right-radius: var(--p-border-radius-lg);
    @include mq(m) {
      margin-bottom: 2rem;
    }

    .header-cell {
      font-weight: 600;

      &.first-cell {
        display: flex;
        justify-content: space-between;
        border-top-left-radius: var(--p-border-radius-lg);
        @include mq(m) {
          display: none;
        }
        & span:last-child {
          @include mq(m) {
            display: none;
          }
        }
      }

      &:not(.first-cell) {
        @include mq(m) {
          display: none;
        }
      }

      &:last-child {
        border-top-right-radius: var(--p-border-radius-lg);
      }
    }
  }

  .grid-cell {
    display: flex;
    align-items: center;
    width: 100%;
    @include mq(m) {
      &:not(.first-cell):not(.header-cell) {
        grid-column: 1 / -1;
        padding-top: 0;
      }
      &.first-cell {
        font-weight: 600;
      }
      &.first-cell:not(.header-cell) {
        padding: 0.5rem 0.25rem;
      }
    }

    &.header-cell {
      min-height: 50px;
    }
    &.first-cell {
      text-align: start;
      margin-bottom: 1rem;
    }

    // Поддержка разрывов строк в заголовках
    &.header-cell,
    &.first-cell {
      white-space: pre-line;
    }

    &-slot {
      width: 100%;
      margin-bottom: 2rem;
    }
  }
}
</style>
