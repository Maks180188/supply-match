<script setup lang="ts">
import {ref} from 'vue'
import {useAuthStore} from '@/stores/auth'
import axios from 'axios'
import { useRouter } from 'vue-router'

const email = ref('')
const password = ref('')
const errorMessage = ref('')
const authStore = useAuthStore()
const router = useRouter()

async function submit(): Promise<void> {
  errorMessage.value = ''

  try {
    await authStore.login({
      email: email.value,
      password: password.value,
    })

    await router.push('/')
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 422) {
      errorMessage.value = 'Invalid email or password.'
      return
    }

    throw error
  }
}
</script>

<template>
  <section>
    <h1>Login</h1>

    <form @submit.prevent="submit">
      <div>
        <label for="email">Email</label>
        <input
          id="email"
          v-model="email"
          type="email"
        >
      </div>

      <div>
        <label for="password">Password</label>
        <input
          id="password"
          v-model="password"
          type="password"
        >
      </div>
      <p v-if="errorMessage">
        {{ errorMessage }}
      </p>
      <button type="submit">
        Login
      </button>
    </form>
  </section>
</template>
