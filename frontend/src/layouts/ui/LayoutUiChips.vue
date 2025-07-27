<script setup>
// Определение вариантов и состояний для chips
const chipStates = ['Default', 'Disabled']
const chipVariants = ['Default', 'Removable', 'Icon']
</script>

<template>
  <LayoutUiTemplate title="Chips" :states="chipStates" :variants="chipVariants">
    <!-- Default -->
    <template #default-default>
      <Chip label="Chip">
        <template #removeicon>
          <i-custom-close />
        </template>
      </Chip>
    </template>
    <template #default-disabled>
      <Chip label="Chip" disabled>
        <template #removeicon>
          <i-custom-close />
        </template>
      </Chip>
    </template>

    <!-- Removable -->
    <template #removable-default>
      <Chip label="Chip" removable>
        <template #removeicon>
          <i-custom-close />
        </template>
      </Chip>
    </template>
    <template #removable-disabled>
      <Chip label="Chip" removable disabled>
        <template #removeicon>
          <i-custom-close />
        </template>
      </Chip>
    </template>

    <!-- Icon -->
    <template #icon-default>
      <Chip class="app-chip" label="Линус Бенедикт Торвальдс" removable>
        <template #icon>
          <div class="app-chip-icon">
            {{ getInitialsHelper('Линус Бенедикт Торвальдс', [2, 1]) }}
          </div>
        </template>
        <template #removeicon>
          <i-custom-close />
        </template>
      </Chip>
    </template>
    <template #icon-disabled>
      <Chip
        class="app-chip"
        label="Линус Бенедикт Торвальдс"
        removable
        disabled>
        <template #icon>
          <div class="icon app-chip-icon">
            {{ getInitialsHelper('Линус Бенедикт Торвальдс', [2, 1]) }}
          </div>
        </template>
        <template #removeicon>
          <i-custom-close />
        </template>
      </Chip>
    </template>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped></style>
