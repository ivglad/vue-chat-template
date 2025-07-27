<script setup>
const home = ref({
  label: 'Вселенная',
})
const items = ref([
  { label: 'Планета' },
  { label: 'Континент' },
  { label: 'Страна' },
  { label: 'Город' },
  { label: 'Улица' },
  { label: 'Дом' },
])
</script>

<template>
  <LayoutUiTemplate title="Breadcrumb">
    <div class="content">
      <div>
        <div class="row">
          <Breadcrumb :home="home" :model="items">
            <template #item="{ item }">
              <a class="cursor-pointer" href="#">
                <span>{{ item.label }}</span>
              </a>
            </template>
            <template #separator>
              <i-custom-arrow-right />
            </template>
          </Breadcrumb>
        </div>
      </div>
    </div>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped></style>
