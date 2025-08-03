<script setup>
import { motion } from 'motion-v'

const userStore = useUserStore()
const router = useRouter()
const toast = useToast()

// Композабл для поочередной анимации элементов
const { getElementAnimationProps } = usePageTransition({
  staggerDelay: 0.1,
  enterDuration: 0.3,
  enterDelay: 0.2,
})

// Композабл для обработки ошибок (по аналогии с чатом)
const { handleError, clearError } = useAuthErrorHandler()

const initialValues = ref({
  email: '',
  password: '',
})

const loginSchema = z.object({
  email: z.string().trim().email({ message: 'Введите корректный Email' }),
  password: z
    .string()
    .trim()
    .min(3, { message: 'Минимум 3 символа' })
    .refine((value) => /[a-z]/.test(value), {
      message: 'Должен содержать строчные латинские буквы',
    }),
})

const loginResolver = zodResolver(loginSchema)

const { mutate: loginUserMutation, isPending: loginUserIsPending } =
  useLoginUser()

/**
 * Обработать отправку формы авторизации
 * @param {Object} e - событие формы
 */
const loginSubmit = async (e) => {
  if (!e.valid) return

  clearError()

  const { email, password } = e.states

  loginUserMutation(
    {
      email: email.value,
      password: password.value,
    },
    {
      onError: (error) => {
        // Обрабатываем ошибку через композабл
        handleError(error, {
          action: 'login',
          context: { email: email.value },
        })

        // Помечаем поля как невалидные
        e.states.email.valid = false
        e.states.email.invalid = true
        e.states.password.valid = false
        e.states.password.invalid = true

        // Показываем toast уведомление
        toast.add({
          severity: 'error',
          summary: 'Ошибка авторизации',
          detail: error?.response?.data?.message || 'Неверные учетные данные',
          life: 5000,
        })
      },
      onSuccess: (data) => {
        const { user, token } = data.data.data

        // Объединяем данные пользователя с токеном
        const userData = {
          ...user,
          accessToken: token,
        }

        // Инициализируем пользователя в store
        userStore.initUser(userData)

        // Показываем успешное уведомление
        toast.add({
          severity: 'success',
          summary: 'Успешно',
          detail: 'Добро пожаловать!',
          life: 3000,
        })

        // Перенаправляем на страницу чата
        router.push('/chat')
      },
    },
  )
}

defineOptions({
  name: 'AuthPage',
})
</script>

<template>
  <div
    class="flex flex-col items-center justify-center self-center flex-1 h-screen gap-5 w-full">
    <motion.h1
      v-bind="getElementAnimationProps(0)"
      class="text-2xl mb-6 font-semibold text-gray-900">
      Авторизация
    </motion.h1>

    <motion.div
      v-bind="getElementAnimationProps(1)"
      class="w-full flex justify-center">
      <Form
        class="flex flex-col items-center w-full max-w-md"
        :initialValues
        :resolver="loginResolver"
        @submit="loginSubmit">
        <motion.div v-bind="getElementAnimationProps(2)" class="mb-8 w-full">
          <FormField
            v-slot="$field"
            :validateOnValueUpdate="false"
            validateOnBlur
            name="email">
            <FloatLabel class="text-base">
              <InputText
                id="auth-form-email"
                v-tooltip.top="{
                  value: $field.error?.message,
                  showDelay: 500,
                }"
                :invalid="$field?.invalid"
                fluid
                class="rounded-xl border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-100" />
              <Message
                class="text-base mt-2"
                :severity="$field?.invalid ? 'error' : 'contrast'"
                variant="simple"
                size="small"
                v-if="$field?.invalid && $field.error?.message">
                {{ $field.error?.message }}
              </Message>
              <label for="auth-form-email" class="text-sm text-gray-600"
                >Email</label
              >
            </FloatLabel>
          </FormField>
        </motion.div>

        <motion.div v-bind="getElementAnimationProps(3)" class="mb-6 w-full">
          <FormField v-slot="$field" validateOnValueUpdate name="password">
            <FloatLabel class="text-base">
              <Password
                id="auth-form-password"
                type="text"
                v-tooltip.top="{
                  value: $field.error?.message,
                  showDelay: 500,
                }"
                :feedback="false"
                toggleMask
                fluid
                class="rounded-xl [&_.p-password-input]:border-gray-300 [&_.p-password-input]:focus:border-primary-500 [&_.p-password-input]:focus:ring-2 [&_.p-password-input]:focus:ring-primary-100" />
              <Message
                class="text-base mt-2"
                :severity="$field?.invalid ? 'error' : 'contrast'"
                variant="simple"
                size="small"
                v-if="$field?.invalid && $field.error?.message">
                {{ $field.error?.message }}
              </Message>
              <label for="auth-form-password" class="text-sm text-gray-600"
                >Пароль</label
              >
            </FloatLabel>
          </FormField>
        </motion.div>

        <motion.div v-bind="getElementAnimationProps(4)" class="w-full">
          <Button
            class="w-full h-[3.25rem] p-4 rounded-xl text-base font-medium bg-primary-500 hover:bg-primary-600 disabled:bg-gray-300 disabled:text-gray-500 transition-colors duration-200"
            type="submit"
            label="Войти в систему"
            :loading="loginUserIsPending"
            :disabled="loginUserIsPending">
            <template #loadingicon>
              <ProgressSpinner
                style="width: 20px; height: 20px"
                stroke-width="4" />
            </template>
          </Button>
        </motion.div>
      </Form>
    </motion.div>

    <Toast
      position="top-right"
      class="z-50 [&_.p-toast-message]:rounded-xl [&_.p-toast-message]:shadow-lg [&_.p-toast-message-content]:p-4" />
  </div>
</template>
