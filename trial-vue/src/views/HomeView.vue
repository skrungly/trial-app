<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { fetchManufacturers, type Manufacturer } from '@/api/manufacturers'
import { fetchMaterials, type Material } from '@/api/materials'
import { fetchProducts, type Product } from '@/api/products'
import AddToCartModal from '@/components/AddToCartModal.vue'
import ProductFormModal from '@/components/ProductFormModal.vue'
import { formatGBP, toPence } from '@/utils/money'

const products = ref<Product[]>([])
const manufacturers = ref<Manufacturer[]>([])
const materials = ref<Material[]>([])
const selectedManufacturerId = ref<number | null>(null)
const selectedMaterialId = ref<number | null>(null)
const searchInput = ref('')
const searchTerm = ref('')
const loading = ref(true)
const error = ref<string | null>(null)
const modalOpen = ref(false)
const editingProduct = ref<Product | null>(null)

const cartModalOpen = ref(false)
const cartProduct = ref<Product | null>(null)

// Clicking a row edits the product in edit mode, and adds it to the cart otherwise.
const editMode = ref(false)

function openCreateModal() {
  editingProduct.value = null
  modalOpen.value = true
}

function onRowSelect(product: Product) {
  if (editMode.value) {
    editingProduct.value = product
    modalOpen.value = true
  } else {
    cartProduct.value = product
    cartModalOpen.value = true
  }
}

function closeModal() {
  modalOpen.value = false
}

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
      name: searchTerm.value || null,
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

watch([selectedManufacturerId, selectedMaterialId, searchTerm], loadProducts)

// Wait until typing pauses before searching, so the list doesn't reload on every keystroke.
let searchTimer: ReturnType<typeof setTimeout> | undefined

watch(searchInput, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    searchTerm.value = value.trim()
  }, 300)
})

onUnmounted(() => clearTimeout(searchTimer))

function onProductSaved() {
  closeModal()
  loadProducts()
}
</script>

<template>
  <section class="card bg-base-100 shadow-sm">
    <div class="card-body">
      <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between gap-8">
          <h1 class="card-title text-2xl">Products</h1>

          <label class="label ml-auto cursor-pointer gap-2">
            <input v-model="editMode" type="checkbox" class="toggle toggle-primary" />
            Edit
          </label>

          <button
            type="button"
            class="btn btn-primary"
            :disabled="manufacturers.length === 0 || materials.length === 0"
            @click="openCreateModal"
          >
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M12 5v14M5 12h14" />
            </svg>
            New product
          </button>
        </div>

        <div class="flex w-full flex-col gap-2 md:flex-row">
          <label class="input w-full md:w-auto md:flex-1">
            <svg class="h-4 w-4 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.3-4.3" />
            </svg>
            <input
              v-model="searchInput"
              type="search"
              placeholder="Search by name"
              aria-label="Search products by name"
              maxlength="128"
            />
          </label>

          <select
            v-model.number="selectedManufacturerId"
            class="select w-full md:w-64 md:shrink-0"
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
            class="select w-full md:w-48 md:shrink-0"
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
              :aria-label="editMode ? `Edit ${product.name}` : `Add ${product.name} to cart`"
              @click="onRowSelect(product)"
              @keydown.enter="onRowSelect(product)"
            >
              <td class="font-medium">{{ product.name }}</td>
              <td>{{ product.manufacturer.name }}</td>
              <td>{{ product.material.name }}</td>
              <td class="capitalize">{{ product.colour }}</td>
              <td>{{ product.size }}</td>
              <td class="text-right tabular-nums">{{ formatGBP(toPence(product.price)) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ProductFormModal
      :open="modalOpen"
      :product="editingProduct"
      :manufacturers="manufacturers"
      :materials="materials"
      @close="closeModal"
      @saved="onProductSaved"
      @deleted="onProductSaved"
    />

    <AddToCartModal
      :open="cartModalOpen"
      :product="cartProduct"
      @close="cartModalOpen = false"
      @added="cartModalOpen = false"
    />
  </section>
</template>
