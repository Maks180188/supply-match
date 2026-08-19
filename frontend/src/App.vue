<script setup lang="ts">
import { RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

async function logout(): Promise<void> {
  await authStore.logout()
  await router.push('/login')
}
</script>

<template>
  <div class="app">
    <header class="app-header">
      <div class="container">
        <strong>SupplyMatch</strong>
        <button
          v-if="authStore.isAuthenticated"
          type="button"
          @click="logout"
        >
          Logout
        </button>
      </div>
    </header>

    <main class="container">
      <RouterView />
    </main>
  </div>
</template>
