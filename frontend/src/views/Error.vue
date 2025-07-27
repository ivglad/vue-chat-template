<script setup>
const router = useRouter()
</script>

<template>
  <Transition name="fade" mode="out-in" appear>
    <div class="error">
      <h1 class="title glitch glitch-layers" data-text="404 Упс! Ошибка :(">
        404 Упс! Ошибка :(
      </h1>
      <div class="text">
        Страница, на которую вы пытались перейти,<br />не найдена или была
        удалена
      </div>
      <Button label="Домой" aria-label="Домой" @click="router.push('/')" />
    </div>
  </Transition>
</template>

<style lang="scss" scoped>
// .error {
//   display: flex;
//   flex-direction: column;
//   align-items: center;
//   justify-content: center;
//   gap: 2rem;
//   width: 100%;
//   height: 100%;
//   padding: 6rem;

//   .title {
//     @include font(6rem, 900, 1);
//     color: var(--p-primary-color);
//   }

//   .text {
//     @include font(2rem, 400, 1.2);
//     text-align: center;
//     color: var(--p-primary-400);
//   }
// }
</style>
