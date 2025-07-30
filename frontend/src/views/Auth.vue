<script setup>
import { motion } from 'motion-v'
import { usePageTransition } from '@/composables/usePageTransition'

const userStore = useUserStore()
const router = useRouter()
const toast = useToast()

// Композабл для поочередной анимации элементов
const { getElementAnimationProps } = usePageTransition({
  staggerDelay: 0.1, // Увеличиваем задержку для более заметного эффекта
  enterDuration: 0.3,
  enterDelay: 0.2,
})

const initialValues = ref({
  email: '',
  password: '',
  remember: false,
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
  remember: z.boolean().refine((checked) => checked, {
    message: 'Необходимо принять условие',
  }),
})

const loginResolver = zodResolver(loginSchema)

const { mutate: loginUserMutation, isPending: loginUserIsPending } =
  useLoginUser()

const loginSubmit = async (e) => {
  if (!e.valid) return
  const { email, password } = e.states
  loginUserMutation(
    {
      email: email.value,
      password: password.value,
    },
    {
      onError: (error) => {
        e.states.email.valid = false
        e.states.email.invalid = true
        e.states.password.valid = false
        e.states.password.invalid = true
        // Для добавления сообщения об ошибке в tooltip поля
        // e.states.email.error = {
        //   message: error?.response?.data?.message,
        // }

        toast.add({
          severity: 'error',
          summary: 'Ошибка',
          detail: error?.response?.data?.message,
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

        // Перенаправляем на страницу чата
        router.push('/chat')
      },
    },
  )
}
</script>

<template>
  <div
    class="flex flex-col items-center justify-center self-center flex-1 h-screen gap-5 w-full">
    <!-- Заголовок с анимацией появления (индекс 0) -->
    <motion.h1 v-bind="getElementAnimationProps(0)" class="text-2xl mb-6">
      Авторизация
    </motion.h1>

    <!-- Форма с анимацией появления (индекс 1) -->
    <motion.div
      v-bind="getElementAnimationProps(1)"
      class="w-full flex justify-center">
      <Form
        class="auth-form flex flex-col items-center"
        :initialValues
        :resolver="loginResolver"
        @submit="loginSubmit">
        <!-- Поле Email с анимацией (индекс 2) -->
        <motion.div
          v-bind="getElementAnimationProps(2)"
          class="auth-form__formfield mb-8 w-full">
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
                class="rounded-xl" />
              <Message
                class="app-input-message text-base"
                :severity="$field?.invalid ? 'error' : 'contrast'"
                variant="simple"
                size="small"
                v-if="$field?.invalid && $field.error?.message">
                {{ $field.error?.message }}
              </Message>
              <label for="auth-form-email" class="text-sm">Email</label>
            </FloatLabel>
          </FormField>
        </motion.div>

        <!-- Поле Password с анимацией (индекс 3) -->
        <motion.div
          v-bind="getElementAnimationProps(3)"
          class="auth-form__formfield">
          <FormField v-slot="$field" validateOnValueUpdate name="password">
            <FloatLabel class="app-input text-base">
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
                class="rounded-xl" />
              <Message
                class="app-input-message text-base"
                :severity="$field?.invalid ? 'error' : 'contrast'"
                variant="simple"
                size="small"
                v-if="$field?.invalid && $field.error?.message">
                {{ $field.error?.message }}
              </Message>
              <label for="auth-form-password" class="text-sm">Пароль</label>
            </FloatLabel>
          </FormField>
        </motion.div>

        <!-- Кнопка с анимацией (индекс 4) -->
        <motion.div v-bind="getElementAnimationProps(4)">
          <Button
            class="auth-form__submit mt-6 w-fit h-[3.25rem] p-4 rounded-xl text-base"
            type="submit"
            label="Войти в систему"
            :loading="loginUserIsPending">
            <template #loadingicon>
              <!-- <i-custom-dot-loader /> -->
            </template>
          </Button>
        </motion.div>
      </Form>
    </motion.div>
  </div>
</template>
