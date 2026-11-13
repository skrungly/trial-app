import { getJson, postJson } from './client'

export interface OrderItem {
  id: number
  order_id: number
  // Null once the product has been deleted; the name and price below are kept.
  product_id: number | null
  product_name: string
  unit_price: string
  quantity: number
  line_total: string
  created_at: string
  updated_at: string
}

export const ORDER_STATUSES = ['paid', 'picking', 'consolidated', 'packing', 'packed', 'dispatched'] as const

export type OrderStatus = (typeof ORDER_STATUSES)[number]

export interface Order {
  id: number
  total: string
  status: OrderStatus
  paid_at: string
  customer_name: string
  address: string
  items: OrderItem[]
  created_at: string
  updated_at: string
}

export interface OrderInput {
  customer_name: string
  address: string
  items: {
    product_id: number
    quantity: number
  }[]
}

export function placeOrder(data: OrderInput): Promise<Order> {
  return postJson<Order>('/api/orders', data)
}

export function fetchOrders(): Promise<Order[]> {
  return getJson<Order[]>('/api/orders')
}

export function fetchOrder(id: number): Promise<Order> {
  return getJson<Order>(`/api/orders/${id}`)
}
