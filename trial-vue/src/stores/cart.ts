import { computed, ref, toRaw, watch } from 'vue'
import { isProduct, type Product } from '@/api/products'
import { isObject } from '@/utils/guards'
import { toPence } from '@/utils/money'

export const MAX_QUANTITY = 999

const STORAGE_KEY = 'cart'

export interface CartItem {
  // A copy of the product's details at the time it was added to the cart.
  product: Product
  quantity: number
}

// Used when loading the saved cart, so stale or malformed data is dropped.
function isCartItem(value: unknown): value is CartItem {
  return (
    isObject(value) &&
    isProduct(value.product) &&
    Number.isInteger(value.quantity) &&
    (value.quantity as number) > 0
  )
}

function loadItems(): CartItem[] {
  try {
    const parsed: unknown = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]')

    return Array.isArray(parsed) ? parsed.filter(isCartItem) : []

  } catch {
    return []
  }
}

// Shared by every component that calls useCart().
const items = ref<CartItem[]>(loadItems())

watch(
  items,
  (value) => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(value))
    } catch {
      // Storage can be unavailable (e.g. private browsing); the cart still works for this visit.
    }
  },
  { deep: true },
)

function clampQuantity(quantity: number): number {
  return Math.min(Math.floor(quantity), MAX_QUANTITY)
}

export function lineTotalPence(item: CartItem): number {
  return toPence(item.product.price) * item.quantity
}

const itemCount = computed(() => items.value.reduce((sum, item) => sum + item.quantity, 0))
const totalPence = computed(() => items.value.reduce((sum, item) => sum + lineTotalPence(item), 0))

function add(product: Product, quantity: number) {
  const existing = items.value.find((item) => item.product.id === product.id)

  if (existing) {
    existing.quantity = clampQuantity(existing.quantity + quantity)
    return
  }

  items.value.push({
    // toRaw strips Vue's reactive wrapper, which structuredClone can't copy.
    product: structuredClone(toRaw(product)),
    quantity: clampQuantity(quantity),
  })
}

function remove(productId: number) {
  items.value = items.value.filter((item) => item.product.id !== productId)
}

function setQuantity(productId: number, quantity: number) {
  if (!Number.isFinite(quantity) || quantity < 1) {
    remove(productId)
    return
  }

  const item = items.value.find((item) => item.product.id === productId)

  if (item) {
    item.quantity = clampQuantity(quantity)
  }
}

function clear() {
  items.value = []
}

export function useCart() {
  return { items, itemCount, totalPence, add, remove, setQuantity, clear }
}
