import { getJson } from './client'

export interface Manufacturer {
  id: number
  name: string
  created_at: string
  updated_at: string
}

export function fetchManufacturers(): Promise<Manufacturer[]> {
  return getJson<Manufacturer[]>('/api/manufacturers')
}
