<script setup>
const userStore = useUserStore()
const router = useRouter()

const toast = useToast()

const initialValues = ref({
  username: '',
  password: '',
  remember: false,
})
const loginSchema = z.object({
  username: z.string().trim().min(3, { message: 'Минимум 3 символа' }),
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
  const { username, password } = e.values
  loginUserMutation(
    {
      username,
      password,
    },
    {
      onError: (error) => {
        e.states.username.valid = false
        e.states.username.invalid = true
        e.states.password.valid = false
        e.states.password.invalid = true
        // Для добавления сообщения об ошибке в tooltip поля
        // e.states.username.error = {
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
        const userData = data.data
        userStore.initUser(userData)
      },
      // FIXME: remove in real app
      onSettled: (data, error, variables, context) => {
        // router.push('/ui')
      },
    },
  )
}
</script>

<template>
  <div class="flex flex-col items-center justify-center flex-1 gap-5 w-full">
    <h1 class="text-2xl mb-6">Авторизация</h1>
    <Form
      class="auth-form flex flex-col items-center"
      :initialValues
      :resolver="loginResolver"
      @submit="loginSubmit">
      <FormField
        class="auth-form__formfield mb-8 w-full"
        v-slot="$field"
        :validateOnValueUpdate="false"
        validateOnBlur
        name="username">
        <FloatLabel class="app-input text-base">
          <InputText
            id="auth-form-username"
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
          <label for="auth-form-username" class="text-sm">Логин</label>
        </FloatLabel>
      </FormField>
      <FormField
        class="auth-form__formfield"
        v-slot="$field"
        :validateOnValueUpdate="false"
        validateOnBlur
        name="password">
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
      <Button
        class="auth-form__submit mt-6 w-fit h-[3.25rem] p-4 rounded-xl text-base"
        type="submit"
        label="Войти в систему"
        :loading="loginUserIsPending">
        <template #loadingicon>
          <!-- <i-custom-dot-loader /> -->
        </template>
      </Button>
    </Form>
  </div>
</template>

<style lang="scss" scoped>
// .auth {
//   display: flex;
//   flex-direction: column;
//   align-items: center;
//   justify-content: center;
//   gap: 4rem;
//   width: 100%;

//   &-form {
//     display: flex;
//     flex-direction: column;
//     align-items: center;
//     gap: 2.5rem;
//     width: 100%;
//     max-width: 30rem;

//     &__formfield {
//       width: 100%;
//     }

//     &__checkbox {
//       align-self: flex-start;
//     }

//     &__submit {
//       width: 20rem;
//       height: 5rem;
//     }

//     &__restore-password {
//       text-decoration: underline;
//     }
//   }
// }
</style>
