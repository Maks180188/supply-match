import {api} from '@/api/client'
import type {
  SourcingRequestListParams,
  SourcingRequestListResponse,
} from '@/types/sourcing-request'

export async function getSourcingRequests(
  params: SourcingRequestListParams = {},
): Promise<SourcingRequestListResponse> {
  const response = await api.get<SourcingRequestListResponse>(
    '/sourcing-requests',
    {params},
  )

  return response.data
}
