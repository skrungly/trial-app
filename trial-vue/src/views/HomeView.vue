<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { fetchProducts, type Product } from '@/api/products'

const products = ref<Product[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    products.value = await fetchProducts()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load products'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="card bg-base-100 shadow-sm">
    <div class="card-body">
      <h1 class="card-title text-2xl">Products</h1>

      <div v-if="loading" class="flex justify-center py-8">
        <span class="loading loading-spinner loading-lg"></span>
      </div>

      <div v-else-if="error" role="alert" class="alert alert-error">
        <span>{{ error }}</span>
      </div>

      <p v-else-if="products.length === 0" class="py-8 text-center text-base-content/60">
        No products found.
      </p>

      <div v-else class="overflow-x-auto">
        <table class="table table-zebra">
          <thead>
            <tr>
              <th>Name</th>
              <th>Colour</th>
              <th>Size</th>
              <th class="text-right">Price</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in products" :key="product.id">
              <td class="font-medium">{{ product.name }}</td>
              <td class="capitalize">{{ product.colour }}</td>
              <td>{{ product.size }}</td>
              <td class="text-right tabular-nums">{{ product.price }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
