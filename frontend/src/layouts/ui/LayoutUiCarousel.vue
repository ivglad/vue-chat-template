<script setup>
const products = ref([
  {
    title: 'Удобство',
    sumtitle: 'Всегда',
    description: 'Всегда удобно, всегда быстро, всегда доступно',
  },
  {
    title: 'Качество',
    sumtitle: 'Высочайшее',
    description: 'Высочайшее качество, которое доставляет радость',
  },
  {
    title: 'Современность',
    sumtitle: 'Всегда',
    description: 'Всегда современные и технологичные решения',
  },
  {
    title: 'Безопасность',
    sumtitle: 'Высочайшая',
    description: 'Высочайшая безопасность, которую мы обеспечиваем',
  },
  {
    title: 'Надежность',
    sumtitle: 'Полная',
    description: 'Полная надежность, которую мы обеспечиваем',
  },
])
const responsiveOptions = ref([
  {
    breakpoint: '1400px',
    numVisible: 3,
    numScroll: 1,
  },
  {
    breakpoint: '1000px',
    numVisible: 2,
    numScroll: 1,
  },
  {
    breakpoint: '767px',
    numVisible: 2,
    numScroll: 1,
  },
  {
    breakpoint: '575px',
    numVisible: 1,
    numScroll: 1,
  },
])
</script>

<template>
  <LayoutUiTemplate title="Carousel">
    <div class="content">
      <Carousel
        class="app-carousel"
        :value="products"
        :numVisible="3"
        :numScroll="1"
        :responsiveOptions="responsiveOptions"
        :autoplayInterval="3000"
        circular>
        <template #previcon>
          <i-custom-arrow-right style="transform: rotate(180deg)" />
        </template>
        <template #nexticon>
          <i-custom-arrow-right />
        </template>
        <template #item="slotProps">
          <Card class="app-card">
            <template #header>
              <i-custom-favicon />
              <i-custom-logo />
            </template>
            <template #title class="fw-semibold">
              {{ slotProps.data.title }}
            </template>
            <template #subtitle>{{ slotProps.data.subtitle }}</template>
            <template #content>
              <p class="m-0">{{ slotProps.data.description }}</p>
            </template>
            <template #footer>
              <Button
                label="Заказать"
                severity="secondary"
                size="small"
                outlined />
            </template>
          </Card>
        </template>
      </Carousel>
    </div>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped></style>
