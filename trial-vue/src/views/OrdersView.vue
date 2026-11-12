<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { fetchOrders, type Order } from '@/api/orders'
import { formatGBP, toPence } from '@/utils/money'

const orders = ref<Order[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

const dateFormat = new Intl.DateTimeFormat('en-GB', { dateStyle: 'medium', timeStyle: 'short' })

function itemCount(order: Order): number {
  return order.items.reduce((sum, item) => sum + item.quantity, 0)
}

onMounted(async () => {
  try {
    orders.value = await fetchOrders()

  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load orders'

  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="card bg-base-100 shadow-sm">
    <div class="card-body">
      <h1 class="card-title text-2xl">Orders</h1>

      <div v-if="loading" class="flex justify-center py-8">
        <span class="loading loading-spinner loading-lg"></span>
      </div>

      <div v-else-if="error" role="alert" class="alert alert-error">
        <span>{{ error }}</span>
      </div>

      <div v-else-if="orders.length === 0" class="flex flex-col items-center gap-4 py-8 text-center">
        <p class="text-base-content/60">No orders yet.</p>
        <RouterLink to="/" class="btn btn-primary">Browse products</RouterLink>
      </div>

      <div v-else class="flex flex-col gap-4 mt-4">
        <details v-for="order in orders" :key="order.id" class="collapse collapse-arrow border border-base-300">
          <summary class="collapse-title flex flex-wrap items-center gap-x-6 gap-y-1 pr-12">
            <span class="font-bold">Order #{{ order.id }}</span>
            <span class="text-sm text-base-content/60">{{ dateFormat.format(new Date(order.created_at)) }}</span>
            <span class="text-sm text-base-content/60">
              {{ itemCount(order) }} {{ itemCount(order) === 1 ? 'item' : 'items' }}
            </span>
            <span class="ml-auto font-bold tabular-nums">{{ formatGBP(toPence(order.total)) }}</span>
          </summary>

          <div class="collapse-content overflow-x-auto">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Product</th>
                  <th class="text-right">Unit price</th>
                  <th class="text-right">Quantity</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in order.items" :key="item.id">
                  <td>
                    {{ item.product_name }}
                    <span v-if="item.product_id === null" class="badge badge-ghost badge-sm ml-1">No longer available</span>
                  </td>
                  <td class="text-right tabular-nums">{{ formatGBP(toPence(item.unit_price)) }}</td>
                  <td class="text-right tabular-nums">{{ item.quantity }}</td>
                  <td class="text-right tabular-nums">{{ formatGBP(toPence(item.line_total)) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </details>
      </div>
    </div>
  </section>
</template>
