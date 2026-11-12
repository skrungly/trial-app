import { getJson, patchJson } from './client'
import type { Manufacturer } from './manufacturers'
import type { Material } from './materials'

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

export function updateProduct(id: number, data: Partial<ProductInput>) {
  return patchJson<Product>(`/api/products/${id}`, data)
}
