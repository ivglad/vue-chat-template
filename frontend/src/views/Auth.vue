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

// Композабл для обработки ошибок
const { handleAuthError, clearError } = useAuthErrorHandler()

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
        handleAuthError(error, {
          action: 'login',
          context: { email: email.value },
        })

        // Помечаем поля как невалидные
        e.states.email.valid = false
        e.states.email.invalid = true
        e.states.password.valid = false
        e.states.password.invalid = true

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
        userStore.initUser(userData)
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
      class="text-2xl mb-6 font-semibold">
      Авторизация
    </motion.h1>

    <motion.div
      v-bind="getElementAnimationProps(1)"
      class="w-full flex justify-center max-w-[350px]">
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
            <FloatLabel class="app-input text-base">
              <InputText
                id="auth-form-email"
                v-tooltip.top="{
                  value: $field.error?.message,
                  showDelay: 500,
                }"
                :invalid="$field?.invalid"
                fluid
                class="rounded-xl text-base" />
              <Message
                class="app-input-message"
                :severity="$field?.invalid ? 'error' : 'contrast'"
                variant="simple"
                v-if="$field?.invalid && $field.error?.message">
                {{ $field.error?.message }}
              </Message>
              <label for="auth-form-email">Email</label>
            </FloatLabel>
          </FormField>
        </motion.div>

        <motion.div v-bind="getElementAnimationProps(3)" class="mb-6 w-full">
          <FormField v-slot="$field" validateOnValueUpdate name="password">
            <FloatLabel class="app-input">
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
                class="rounded-xl text-base" />
              <Message
                class="app-input-message"
                :severity="$field?.invalid ? 'error' : 'contrast'"
                variant="simple"
                v-if="$field?.invalid && $field.error?.message">
                {{ $field.error?.message }}
              </Message>
              <label for="auth-form-password">Пароль</label>
            </FloatLabel>
          </FormField>
        </motion.div>

        <motion.div v-bind="getElementAnimationProps(4)">
          <Button
            class="w-fit h-[3.25rem] p-4 rounded-xl text-base"
            type="submit"
            label="Войти в систему"
            :disabled="loginUserIsPending">
          </Button>
        </motion.div>
      </Form>
    </motion.div>
  </div>
</template>
