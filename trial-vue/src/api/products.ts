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
}

export async function fetchProducts(): Promise<Product[]> {
  const response = await fetch('/api/products', {
    headers: { Accept: 'application/json' },
  })

  if (!response.ok) {
    throw new Error(`Failed to load products (${response.status})`)
  }

  return response.json()
}
