import { api } from '@/api/client'
import type { Category } from '@/types/category'

export async function getCategories(): Promise<Category[]> {
  const response = await api.get<{ data: Category[] }>('/categories')

  return response.data.data
}
