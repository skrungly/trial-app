import { getJson } from './client'

export interface Material {
  id: number
  name: string
  created_at: string
  updated_at: string
}

export function fetchMaterials(): Promise<Material[]> {
  return getJson<Material[]>('/api/materials')
}
