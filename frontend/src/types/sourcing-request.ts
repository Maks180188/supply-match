import type {Company} from '@/types/auth'
import type {Category} from '@/types/category'

export type SourcingRequestStatus =
  | 'draft'
  | 'pending_moderation'
  | 'published'
  | 'rejected'
  | 'archived'

export interface SourcingRequest {
  id: number
  company_id: number
  category_id: number
  company: Company
  category: Category
  created_by: number
  title: string
  description: string
  status: SourcingRequestStatus
  submission_deadline: string | null
  published_at: string | null
  keywords: string[]
  created_at: string | null
  updated_at: string | null
  rejection_reason: string | null
}

export interface SourcingRequestListParams {
  category_id?: number
  q?: string
  page?: number
}

export interface SourcingRequestListResponse {
  data: SourcingRequest[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}
