<script setup>
const toast = useToast()
const inputValue = ref('')

const autoCompleateItems = ref([])
const autoCompleateSearch = useDebounceFn((event) => {
  autoCompleateItems.value = [...Array(10).keys()].map(
    (item) => event.query + '-' + item,
  )
  toast.add({
    severity: 'info',
    summary: 'Успешно',
    detail: 'Поиск осуществлен с задержкой ввода',
    life: 4000,
  })
}, 2000)

// Определение вариантов и состояний для автокомплита
const autocompleteStates = ['Default', 'Disabled']
const autocompleteVariants = ['Default input with icon', 'Autocomplete']
</script>

<template>
  <LayoutUiTemplate
    title="AutoComplete"
    :states="autocompleteStates"
    :variants="autocompleteVariants">
    <!-- Default -->
    <template #default-input-with-icon-default>
      <IconField>
        <InputIcon class="pe-none">
          <i-custom-search />
        </InputIcon>
        <InputText v-model="inputValue" placeholder="Search" />
      </IconField>
    </template>
    <template #default-input-with-icon-disabled>
      <IconField>
        <InputIcon class="p-disabled">
          <i-custom-search />
        </InputIcon>
        <InputText v-model="inputValue" placeholder="Search" disabled />
      </IconField>
    </template>

    <!-- Autocomplete -->
    <template #autocomplete-default
      ><IconField>
        <InputIcon class="pe-none">
          <i-custom-search />
        </InputIcon>
        <AutoComplete
          v-model="inputValue"
          :suggestions="autoCompleateItems"
          placeholder="Search"
          @complete="autoCompleateSearch">
          <template #loader>
            <ProgressSpinner
              class="app-progressspinner-inside"
              fill="transparent" />
          </template>
        </AutoComplete> </IconField
    ></template>
    <template #autocomplete-disabled>
      <IconField>
        <InputIcon class="p-disabled">
          <i-custom-search />
        </InputIcon>
        <AutoComplete
          v-model="inputValue"
          :suggestions="autoCompleateItems"
          placeholder="Search"
          disabled
          @complete="autoCompleateSearch">
          <template #loader>
            <ProgressSpinner
              class="app-progressspinner-inside"
              fill="transparent" />
          </template>
        </AutoComplete> </IconField
    ></template>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped></style>
