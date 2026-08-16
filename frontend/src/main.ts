import './assets/main.css'

import {createApp} from 'vue'
import {createPinia} from 'pinia'

import App from './App.vue'
import router from './router'
import {useAuthStore} from './stores/auth'

async function bootstrap(): Promise<void> {
  const app = createApp(App)
  const pinia = createPinia()

  app.use(pinia)

  const authStore = useAuthStore(pinia)

  try {
    await authStore.restore()
  } catch (error) {
    console.error('Failed to restore auth session', error)
  }

  app.use(router)
  app.mount('#app')
}

void bootstrap()
