<script setup>
import { AnimatePresence } from 'motion-v'

const userStore = useUserStore()
const router = useRouter()
const { mutate: logoutUserMutation } = useLogoutUser()

const headerContainer = ref(null)
const showUserMenu = ref(false)

// Используем композабл для управления темой
// const { isDarkMode, initTheme, toggleTheme } = useTheme()

// Инициализация темы при загрузке компонента
onMounted(() => {
  // TODO: реализовать пресет темной темы
  // initTheme()
})

// Опции меню пользователя
const userMenuOptions = [
  // TODO: реализовать пресет темной темы
  // {
  //   id: 'switchTheme',
  //   label: 'Тема чата',
  //   action: 'switchTheme',
  // },
  {
    id: 'logout',
    label: 'Выйти',
    action: 'logout',
  },
]

// Переключить меню пользователя
const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
}

// Закрыть меню пользователя
const closeUserMenu = () => {
  showUserMenu.value = false
}

// Обработать выбор опции меню
const handleMenuOption = (option) => {
  if (option.value.action === 'logout') {
    handleLogout()
  } else if (option.value.action === 'switchTheme') {
    toggleTheme()
  }
  closeUserMenu()
}

// Обработать выход из системы
const handleLogout = () => {
  logoutUserMutation(undefined, {
    onSuccess: () => {
      userStore.resetUser()
      router.push('/auth')
    },
    onError: (error) => {
      console.error('Ошибка при выходе:', error)
      // В случае ошибки все равно очищаем локальные данные
      userStore.resetUser()
      router.push('/auth')
    },
  })
}

// Закрыть меню при клике вне его
onClickOutside(headerContainer, () => {
  if (showUserMenu.value) {
    closeUserMenu()
  }
})
</script>

<template>
  <div
    ref="headerContainer"
    class="flex items-center justify-center w-full h-18 px-6">
    <div class="relative flex items-center justify-center flex-1 max-w-[70rem]">
      <div>
        <Button variant="text" class="text-color p-0 opacity-0 cursor-none">
          <i-custom-menu />
        </Button>
      </div>
      <div class="flex items-center justify-center flex-1">
        <h1 class="font-semibold">Docwise+</h1>
      </div>
      <div>
        <Button
          variant="text"
          class="text-color p-0 select-none"
          @click="toggleUserMenu">
          <i-custom-filter />
        </Button>
      </div>
      <AnimatePresence>
        <AnimatedContainer
          v-if="showUserMenu"
          preset="slideUp"
          container-class="absolute top-full right-0 mt-4 z-100">
          <Listbox
            :options="userMenuOptions"
            optionLabel="label"
            class="w-[10rem] border-none shadow-none rounded-2xl"
            :pt="{
              root: 'bg-white rounded-2xl shadow-lg',
              list: 'p-2 overflow-hidden',
              option:
                'block rounded-xl text-nowrap text-ellipsis p-3 hover:bg-[#EDEFF6] transition-colors duration-150 cursor-pointer',
              optionLabel: 'truncate',
            }"
            @change="handleMenuOption">
            <template #option="{ option }">
              <div class="flex items-center justify-between gap-2.5">
                <span>{{ option.label }}</span>
                <i-custom-sun
                  v-if="option.action === 'switchTheme' && isDarkMode"
                  class="text-color" />
                <i-custom-moon
                  v-else-if="option.action === 'switchTheme' && !isDarkMode"
                  class="text-color" />
              </div>
            </template>
          </Listbox>
        </AnimatedContainer>
      </AnimatePresence>
    </div>

  </div>
</template>
