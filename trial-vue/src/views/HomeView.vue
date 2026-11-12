<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { fetchManufacturers, type Manufacturer } from '@/api/manufacturers'
import { fetchMaterials, type Material } from '@/api/materials'
import { fetchProducts, type Product } from '@/api/products'
import EditProductModal from '@/components/EditProductModal.vue'

const products = ref<Product[]>([])
const manufacturers = ref<Manufacturer[]>([])
const materials = ref<Material[]>([])
const selectedManufacturerId = ref<number | null>(null)
const selectedMaterialId = ref<number | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const editingProduct = ref<Product | null>(null)

function errorMessage(e: unknown): string {
  return e instanceof Error ? e.message : 'Failed to load products'
}

// Counts loadProducts calls so only the newest request updates the page.
// Responses from older requests (e.g. when filters change quickly) are ignored.
let latestRequest = 0

async function loadProducts() {
  const request = ++latestRequest
  loading.value = true
  error.value = null

  try {
    const result = await fetchProducts({
      manufacturer_id: selectedManufacturerId.value,
      material_id: selectedMaterialId.value,
    })

    if (request === latestRequest) products.value = result

  } catch (e) {
    if (request === latestRequest) error.value = errorMessage(e)

  } finally {
    if (request === latestRequest) loading.value = false
  }
}

onMounted(async () => {
  try {
    const [manufacturerList, materialList, productList] = await Promise.all([
      fetchManufacturers(),
      fetchMaterials(),
      fetchProducts(),
    ])

    manufacturers.value = manufacturerList
    materials.value = materialList
    products.value = productList

  } catch (e) {
    error.value = errorMessage(e)

  } finally {
    loading.value = false
  }
})

watch([selectedManufacturerId, selectedMaterialId], loadProducts)

function onProductSaved() {
  editingProduct.value = null
  loadProducts()
}
</script>

<template>
  <section class="card bg-base-100 shadow-sm">
    <div class="card-body">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="card-title text-2xl">Products</h1>

        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
          <select
            v-model.number="selectedManufacturerId"
            class="select w-full sm:w-64"
            aria-label="Filter by manufacturer"
            :disabled="manufacturers.length === 0"
          >
            <option :value="null">All manufacturers</option>
            <option v-for="manufacturer in manufacturers" :key="manufacturer.id" :value="manufacturer.id">
              {{ manufacturer.name }}
            </option>
          </select>

          <select
            v-model.number="selectedMaterialId"
            class="select w-full sm:w-48"
            aria-label="Filter by material"
            :disabled="materials.length === 0"
          >
            <option :value="null">All materials</option>
            <option v-for="material in materials" :key="material.id" :value="material.id">
              {{ material.name }}
            </option>
          </select>
        </div>
      </div>

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
              <th>Manufacturer</th>
              <th>Material</th>
              <th>Colour</th>
              <th>Size</th>
              <th class="text-right">Price</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="product in products"
              :key="product.id"
              class="cursor-pointer hover:bg-base-300"
              tabindex="0"
              :aria-label="`Edit ${product.name}`"
              @click="editingProduct = product"
              @keydown.enter="editingProduct = product"
            >
              <td class="font-medium">{{ product.name }}</td>
              <td>{{ product.manufacturer.name }}</td>
              <td>{{ product.material.name }}</td>
              <td class="capitalize">{{ product.colour }}</td>
              <td>{{ product.size }}</td>
              <td class="text-right tabular-nums">{{ product.price }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <EditProductModal
      :product="editingProduct"
      :manufacturers="manufacturers"
      :materials="materials"
      @close="editingProduct = null"
      @saved="onProductSaved"
    />
  </section>
</template>
