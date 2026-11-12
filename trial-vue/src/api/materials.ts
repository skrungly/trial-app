import { isObject } from '@/utils/guards'
import { getJson } from './client'

export interface Material {
  id: number
  name: string
  created_at: string
  updated_at: string
}

export function isMaterial(value: unknown): value is Material {
  return (
    isObject(value) &&
    typeof value.id === 'number' &&
    typeof value.name === 'string' &&
    typeof value.created_at === 'string' &&
    typeof value.updated_at === 'string'
  )
}

export function fetchMaterials(): Promise<Material[]> {
  return getJson<Material[]>('/api/materials')
}
