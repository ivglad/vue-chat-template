import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/store/userStore'

const routes = [
  {
    path: '/:pathMatch(.*)*',
    name: 'Error',
    component: () => import('@/views/Error.vue'),
  },
  {
    path: '/',
    name: 'Чат',
    component: () => import('@/views/Chat.vue'),
  },
  {
    path: '/auth',
    name: 'Вход и регистрация',
    component: () => import('@/views/Auth.vue'),
  },
  {
    path: '/chat',
    name: 'Чат',
    component: () => import('@/views/ChatRefactored.vue'),
  },
  // {
  //   path: '/ui',
  //   name: 'Ui',
  //   component: () => import('@/views/Ui.vue'),
  // },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

const authMiddleware = (to, next, userStore) => {
  let userInLocalStorage = null
  try {
    userInLocalStorage = JSON.parse(localStorage.getItem('user'))
  } catch (e) {}

  if (!userInLocalStorage?.accessToken && userStore.user) {
    userStore.resetUser()
  }
  if (!userStore.user && to.path !== '/auth') {
    next('/auth')
    return false
  }
  if (userStore.user && (to.path === '/auth' || to.path === '/')) {
    next('/chat')
    return false
  }
  return true
}

const errorMiddleware = (to, next, user) => {
  if (to.name !== 'Error') return true
  if (!user) {
    next('/auth')
    return false
  }

  return false
}

const middlewarePipeline = (context) => {
  const { to, next, userStore } = context
  // Enable after authorization is implemented ---------------------->
  if (!authMiddleware(to, next, userStore)) return false
  if (!errorMiddleware(to, next, userStore.user)) return false
  // Enable after authorization is implemented ----------------------<
  return true
}

router.beforeEach(async (to, from, next) => {
  const userStore = useUserStore()
  const context = {
    to,
    from,
    next,
    userStore,
  }

  const pipeline = middlewarePipeline(context)
  if (pipeline) next()
  else return false
})

router.onError((e, to) => {
  if (
    e.message.includes('Failed to fetch dynamically imported module') ||
    e.message.includes('Importing a module script failed')
  ) {
    if (!to?.fullPath) {
      window.location.reload()
    } else {
      window.location = to.fullPath
    }
  }
})

export default router
