import { isObject } from '@/utils/guards'
import { deleteJson, getJson, patchJson, postJson } from './client'
import { isManufacturer, type Manufacturer } from './manufacturers'
import { isMaterial, type Material } from './materials'

export interface Product {
  id: number
  name: string
  colour: string
  size: string
  price: string
  material_id: number
  manufacturer_id: number
  created_at: string
  updated_at: string
  
  manufacturer: Manufacturer
  material: Material
}

export function isProduct(value: unknown): value is Product {
  return (
    isObject(value) &&
    typeof value.id === 'number' &&
    typeof value.name === 'string' &&
    typeof value.colour === 'string' &&
    typeof value.size === 'string' &&
    typeof value.price === 'string' &&
    typeof value.material_id === 'number' &&
    typeof value.manufacturer_id === 'number' &&
    typeof value.created_at === 'string' &&
    typeof value.updated_at === 'string' &&
    isManufacturer(value.manufacturer) &&
    isMaterial(value.material)
  )
}

export interface ProductInput {
  name: string
  colour: string
  size: string
  price: string | number
  material_id: number
  manufacturer_id: number
}

export interface ProductFilters {
  material_id?: number | null
  manufacturer_id?: number | null
  name?: string | null
}

export function fetchProducts(filters: ProductFilters = {}): Promise<Product[]> {
  const params = new URLSearchParams()

  for (const [key, value] of Object.entries(filters)) {
    if (value != null) {
      params.set(key, String(value))
    }
  }

  const query = params.toString()

  return getJson<Product[]>(query ? `/api/products?${query}` : '/api/products')
}

export function createProduct(data: ProductInput) {
  return postJson<Product>('/api/products', data)
}

export function updateProduct(id: number, data: Partial<ProductInput>) {
  return patchJson<Product>(`/api/products/${id}`, data)
}

export function deleteProduct(id: number) {
  return deleteJson(`/api/products/${id}`)
}
