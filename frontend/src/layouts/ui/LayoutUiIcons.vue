<script setup>
const dynamicIcons = {
  contracts: {
    title: 'Договоры',
    icon: 'documentation',
    component: defineAsyncComponent(() => import('~icons/custom/settings')),
  },
  calculation: {
    title: 'Калькуляция',
    icon: 'calculator',
    component: defineAsyncComponent(() => import('~icons/custom/options')),
  },
}
</script>

<template>
  <LayoutUiTemplate title="Icons">
    <div class="content">
      <div class="icons">
        <div class="row">
          <i-custom-favicon />
          <i-custom-logo />
        </div>
        <div class="row">
          <i-custom-close />
          <i-custom-message-send />
          <i-custom-settings />
          <i-custom-plus />
          <i-custom-minus />
          <i-custom-search />
          <i-custom-options />
          <i-custom-notifications />
          <i-custom-triangle-down />
          <i-custom-arrow-right />
          <i-custom-arrow-down />
          <i-custom-dot-loader />
        </div>
        <div class="row">
          <span>Динамический импорт:</span>
          <div v-for="icon in dynamicIcons" :key="icon.title">
            <component :is="icon.component" />
          </div>
        </div>
        <div class="row">
          <!-- Импорт предзагруженных иконок из библиотеки https://icon-sets.iconify.design/fluent -->
          <span>Внешняя библиотека:</span>
          <i-fluent-home-16-regular />
          <i-fluent-target-arrow-20-regular />
        </div>
      </div>
    </div>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped>
.icons {
  .icon {
    color: var(--p-surface-500);
  }
}
</style>
