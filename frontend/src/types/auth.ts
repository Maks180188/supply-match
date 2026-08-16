export type UserRole = 'buyer' | 'supplier' | 'admin'

export type CompanyType = 'buyer' | 'supplier'

export interface Company {
  id: number
  name: string
  type: CompanyType
}

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  company: Company | null
}

export interface LoginPayload {
  email: string
  password: string
}

export interface AuthResponse {
  data: User
  token: string
}
