import { isObject } from '@/utils/guards'
import { getJson } from './client'

export interface Manufacturer {
  id: number
  name: string
  created_at: string
  updated_at: string
}

export function isManufacturer(value: unknown): value is Manufacturer {
  return (
    isObject(value) &&
    typeof value.id === 'number' &&
    typeof value.name === 'string' &&
    typeof value.created_at === 'string' &&
    typeof value.updated_at === 'string'
  )
}

export function fetchManufacturers(): Promise<Manufacturer[]> {
  return getJson<Manufacturer[]>('/api/manufacturers')
}
