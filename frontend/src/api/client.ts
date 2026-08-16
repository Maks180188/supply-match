import axios from 'axios'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    Accept: 'application/json',
  },
})

export function setAuthToken(token: string | null): void {
  if (token === null) {
    delete api.defaults.headers.common.Authorization

    return
  }

  api.defaults.headers.common.Authorization = `Bearer ${token}`
}
