import { VueQueryPlugin } from '@tanstack/vue-query'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import DialogService from 'primevue/dialogservice'
import Tooltip from 'primevue/tooltip'
import KeyFilter from 'primevue/keyfilter'
import StyleClass from 'primevue/styleclass'
import AnimateOnScroll from 'primevue/animateonscroll'
import Preset from './primevue/preset.js'
import Locale from './primevue/locale.json'

export default function registerPlugins(app) {
  app.use(VueQueryPlugin)

  app.use(PrimeVue, {
    theme: {
      preset: Preset,
      options: {
        darkModeSelector: '.dark-mode',
        cssLayer: {
          name: 'default',
          order: 'reset, theme, base, default, preset, app',
        },
      },
    },
    locale: Locale,
    ripple: false,
  })
  app.use(ToastService)
  app.use(ConfirmationService)
  app.use(DialogService)
  app.directive('tooltip', Tooltip)
  app.directive('keyfilter', KeyFilter)
  app.directive('styleclass', StyleClass)
  app.directive('animateonscroll', AnimateOnScroll)
}
