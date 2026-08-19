import axios from 'axios'
import {computed, ref} from 'vue'
import {defineStore} from 'pinia'
import {getCurrentUser, login as loginRequest, logout as logoutRequest} from '@/api/auth'
import type {LoginPayload, User} from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)

  const isAuthenticated = computed(() => user.value !== null)

  async function login(payload: LoginPayload): Promise<void> {
    const response = await loginRequest(payload)

    user.value = response.data
  }

  async function restore(): Promise<void> {
    try {
      user.value = await getCurrentUser()
    } catch (error) {
      if (!axios.isAxiosError(error) || error.response?.status !== 401) {
        throw error
      }

      user.value = null
    }
  }

  async function logout(): Promise<void> {
    await logoutRequest()

    user.value = null
  }

  return {
    user,
    isAuthenticated,
    login,
    restore,
    logout,
  }
})
