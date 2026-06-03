import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { registerSW } from 'virtual:pwa-register'
import App from './App.vue'
import router from './router'
import './style.css'

if ('serviceWorker' in navigator) {
  registerSW({
    immediate: true,
    onOfflineReady() {
      console.info('[PWA] Aplikasi siap digunakan offline (shell).')
    },
  })
}

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
