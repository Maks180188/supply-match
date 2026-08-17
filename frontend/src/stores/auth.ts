import {computed, ref} from 'vue'
import {defineStore} from 'pinia'
import {setAuthToken} from '@/api/client'
import type {LoginPayload, User} from '@/types/auth'
import axios from 'axios'
import {getCurrentUser, login as loginRequest} from '@/api/auth'
import {logout as logoutRequest} from '@/api/auth'

const AUTH_TOKEN_KEY = 'auth_token'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)

  const isAuthenticated = computed(() => token.value !== null)

  async function login(payload: LoginPayload): Promise<void> {
    const response = await loginRequest(payload)

    user.value = response.data
    token.value = response.token

    localStorage.setItem(AUTH_TOKEN_KEY, response.token)
    setAuthToken(response.token)
  }

  async function restore(): Promise<void> {
    const storedToken = localStorage.getItem(AUTH_TOKEN_KEY)

    if (storedToken === null) {
      return
    }

    token.value = storedToken
    setAuthToken(storedToken)

    try {
      user.value = await getCurrentUser()
    } catch (error) {
      if (!axios.isAxiosError(error) || error.response?.status !== 401) {
        throw error
      }

      user.value = null
      token.value = null

      localStorage.removeItem(AUTH_TOKEN_KEY)
      setAuthToken(null)
    }
  }

  async function logout(): Promise<void> {
    try {
      await logoutRequest()
    } finally {
      user.value = null
      token.value = null

      localStorage.removeItem(AUTH_TOKEN_KEY)
      setAuthToken(null)
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    restore,
    logout,
  }
})
