<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ApiError, type ValidationErrors } from '@/api/client'
import { placeOrder, type Order, type OrderInput } from '@/api/orders'
import { lineTotalPence, MAX_QUANTITY, useCart } from '@/stores/cart'
import { formatGBP, toPence } from '@/utils/money'

const { items, totalPence, remove, setQuantity, clear } = useCart()

const placing = ref(false)
const orderError = ref<string | null>(null)
const fieldErrors = ref<ValidationErrors>({})
const placedOrder = ref<Order | null>(null)
// The server charges current prices, which may differ from when items were added.
const pricesChanged = ref(false)

// Items come from the cart store when the order is placed, so the form only holds the customer details.
type CustomerForm = Omit<OrderInput, 'items'>

const form = reactive<CustomerForm>({
  customer_name: '',
  address: '',
})

// Turns "items.0.product_id" validation errors into a message naming the product.
function describeOrderError(e: unknown): string {
  if (e instanceof ApiError && e.status === 422) {
    const unavailable = Object.keys(e.errors)
      .map((key) => key.match(/^items\.(\d+)\.product_id$/))
      .filter((match) => match !== null)
      .map((match) => items.value[Number(match[1])]?.product.name)

    if (unavailable.length > 0) {
      return `No longer available: ${unavailable.join(', ')}. Edit your cart and try again.`
    }
  }

  return e instanceof Error ? e.message : 'Failed to place order'
}

async function submitOrder() {
  placing.value = true
  orderError.value = null
  fieldErrors.value = {}

  try {
    const order = await placeOrder({
      ...form,
      items: items.value.map((item) => ({ product_id: item.product.id, quantity: item.quantity })),
    })

    pricesChanged.value = toPence(order.total) !== totalPence.value
    placedOrder.value = order
    Object.assign(form, { customer_name: '', address: '' })
    clear()

  } catch (e) {
    if (e instanceof ApiError && e.status === 422) {
      fieldErrors.value = e.errors
    }

    // Name and address errors show beside their fields; anything else goes in the alert.
    const onlyFieldErrors = e instanceof ApiError && e.status === 422 &&
      Object.keys(e.errors).every((key) => key in form)

    orderError.value = onlyFieldErrors ? null : describeOrderError(e)

  } finally {
    placing.value = false
  }
}

function onQuantityInput(productId: number, event: Event) {
  const input = event.target as HTMLInputElement

  // Leave the cart alone while the field is being cleared or retyped.
  if (input.value === '') return

  setQuantity(productId, Number(input.value))
}
</script>

<template>
  <section class="card bg-base-100 shadow-sm">
    <div class="card-body">
      <h1 class="card-title text-2xl">Cart</h1>

      <div v-if="placedOrder" role="status" class="alert alert-success">
        <svg class="h-6 w-6 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M20 6 9 17l-5-5" />
        </svg>
        <div>
          <p class="font-bold">Order #{{ placedOrder.id }} placed</p>
          <p>
            Total charged: {{ formatGBP(toPence(placedOrder.total)) }}
            <template v-if="pricesChanged">(some prices changed since you added them to your cart)</template>
          </p>
        </div>
      </div>

      <div v-if="items.length === 0" class="flex flex-col items-center gap-4 py-8 text-center">
        <p v-if="!placedOrder" class="text-base-content/60">Your cart is empty.</p>
        <RouterLink to="/" class="btn btn-primary">Browse products</RouterLink>
      </div>

      <template v-else>
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th>Product</th>
                <th class="text-right">Unit price</th>
                <th>Quantity</th>
                <th class="text-right">Total</th>
                <th><span class="sr-only">Remove</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.product.id">
                <td>
                  <div class="font-medium">{{ item.product.name }}</div>
                  <div class="text-sm text-base-content/60">
                    {{ item.product.manufacturer.name }} · {{ item.product.material.name }} ·
                    <span class="capitalize">{{ item.product.colour }}</span> · {{ item.product.size }}
                  </div>
                </td>
                <td class="text-right tabular-nums">{{ formatGBP(toPence(item.product.price)) }}</td>
                <td>
                  <input
                    type="number"
                    min="1"
                    :max="MAX_QUANTITY"
                    step="1"
                    class="input input-sm w-20"
                    :value="item.quantity"
                    :aria-label="`Quantity of ${item.product.name}`"
                    @input="onQuantityInput(item.product.id, $event)"
                    @blur="($event.target as HTMLInputElement).value = String(item.quantity)"
                  />
                </td>
                <td class="text-right font-medium tabular-nums">{{ formatGBP(lineTotalPence(item)) }}</td>
                <td class="text-right">
                  <button
                    type="button"
                    class="btn btn-ghost btn-sm btn-square"
                    :aria-label="`Remove ${item.product.name}`"
                    @click="remove(item.product.id)"
                  >
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="orderError" role="alert" class="alert alert-error">
          <span>{{ orderError }}</span>
        </div>

        <form class="flex flex-col gap-2 border-t border-base-300 pt-4" @submit.prevent="submitOrder">
          <fieldset class="fieldset">
            <label class="fieldset-legend" for="order-customer-name">Name</label>
            <input id="order-customer-name" v-model="form.customer_name" class="input w-full" :class="{ 'input-error': fieldErrors.customer_name }" maxlength="128" autocomplete="name" required />
            <p v-if="fieldErrors.customer_name" class="text-error">{{ fieldErrors.customer_name[0] }}</p>
          </fieldset>

          <fieldset class="fieldset">
            <label class="fieldset-legend" for="order-address">Address</label>
            <textarea id="order-address" v-model="form.address" class="textarea w-full" :class="{ 'textarea-error': fieldErrors.address }" maxlength="1000" rows="3" autocomplete="street-address" required></textarea>
            <p v-if="fieldErrors.address" class="text-error">{{ fieldErrors.address[0] }}</p>
          </fieldset>

          <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-lg">
              Total: <span class="font-bold tabular-nums">{{ formatGBP(totalPence) }}</span>
            </p>

            <div class="flex items-center gap-2">
              <button type="button" class="btn btn-ghost btn-sm" :disabled="placing" @click="clear">Clear cart</button>

              <button type="submit" class="btn btn-primary" :disabled="placing">
                <span v-if="placing" class="loading loading-spinner loading-sm"></span>
                Place order
              </button>
            </div>
          </div>
        </form>
      </template>
    </div>
  </section>
</template>
