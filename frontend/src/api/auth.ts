import type {AuthResponse, LoginPayload, User} from '@/types/auth'
import {api} from './client'

export async function login(payload: LoginPayload): Promise<AuthResponse> {
  const response = await api.post<AuthResponse>('/auth/login', payload)

  return response.data
}

export async function getCurrentUser(): Promise<User> {
  const response = await api.get<{ data: User }>('/auth/me')

  return response.data.data
}
