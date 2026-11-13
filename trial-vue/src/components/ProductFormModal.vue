<script setup lang="ts">
import { reactive, ref, useTemplateRef, watch } from 'vue'
import { ApiError, type ValidationErrors } from '@/api/client'
import type { Manufacturer } from '@/api/manufacturers'
import type { Material } from '@/api/materials'
import { createProduct, deleteProduct, updateProduct, type Product, type ProductInput } from '@/api/products'

// When open with no product, the modal creates a new product instead of editing one.
const props = defineProps<{
  open: boolean
  product: Product | null
  manufacturers: Manufacturer[]
  materials: Material[]
}>()

// Manufacturer and material start unselected when creating a product.
type ProductForm = Omit<ProductInput, 'manufacturer_id' | 'material_id'> & {
  manufacturer_id: number | null
  material_id: number | null
}

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
  (e: 'deleted'): void
}>()

const dialog = useTemplateRef<HTMLDialogElement>('dialog')
const saving = ref(false)
const deleting = ref(false)
const confirmingDelete = ref(false)
const errors = ref<ValidationErrors>({})
const formError = ref<string | null>(null)

const form = reactive<ProductForm>({
  name: '',
  colour: '',
  size: '',
  price: '',
  weight: '',
  manufacturer_id: null,
  material_id: null,
})

watch(
  () => props.open,
  (open) => {
    if (!open) {
      dialog.value?.close()
      return
    }

    const product = props.product

    Object.assign(form, {
      name: product?.name ?? '',
      colour: product?.colour ?? '',
      size: product?.size ?? '',
      price: product?.price ?? '',
      weight: product?.weight ?? '',
      manufacturer_id: product?.manufacturer_id ?? null,
      material_id: product?.material_id ?? null,
    })

    errors.value = {}
    formError.value = null
    confirmingDelete.value = false
    dialog.value?.showModal()
  },
)

async function save() {
  saving.value = true
  errors.value = {}
  formError.value = null

  // The selects are required, so the browser won't submit while either is still null.
  const data = { ...form } as ProductInput

  try {
    if (props.product) {
      await updateProduct(props.product.id, data)
    } else {
      await createProduct(data)
    }

    emit('saved')

  } catch (e) {
    if (e instanceof ApiError && e.status === 422) {
      errors.value = e.errors
    } else {
      formError.value = e instanceof Error ? e.message : 'Failed to save product'
    }

  } finally {
    saving.value = false
  }
}

// The first click asks for confirmation; the second click deletes.
async function remove() {
  if (!props.product) return

  if (!confirmingDelete.value) {
    confirmingDelete.value = true
    return
  }

  deleting.value = true
  formError.value = null

  try {
    await deleteProduct(props.product.id)
    emit('deleted')

  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Failed to delete product'
    confirmingDelete.value = false

  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <dialog ref="dialog" class="modal" @close="emit('close')">
    <div class="modal-box">
      <h2 class="mb-4 text-lg font-bold">{{ product ? 'Edit product' : 'New product' }}</h2>

      <div v-if="formError" role="alert" class="alert alert-error mb-4">
        <span>{{ formError }}</span>
      </div>

      <form class="flex flex-col gap-2" @submit.prevent="save">
        <fieldset class="fieldset">
          <label class="fieldset-legend" for="product-name">Name</label>
          <input id="product-name" v-model="form.name" class="input w-full" :class="{ 'input-error': errors.name }" maxlength="128" required />
          <p v-if="errors.name" class="text-error">{{ errors.name[0] }}</p>
        </fieldset>

        <div class="grid gap-2 sm:grid-cols-2">
          <fieldset class="fieldset">
            <label class="fieldset-legend" for="product-manufacturer">Manufacturer</label>
            <select id="product-manufacturer" v-model.number="form.manufacturer_id" class="select w-full" :class="{ 'select-error': errors.manufacturer_id }" required>
              <option :value="null" disabled>Select a manufacturer</option>
              <option v-for="manufacturer in manufacturers" :key="manufacturer.id" :value="manufacturer.id">
                {{ manufacturer.name }}
              </option>
            </select>
            <p v-if="errors.manufacturer_id" class="text-error">{{ errors.manufacturer_id[0] }}</p>
          </fieldset>

          <fieldset class="fieldset">
            <label class="fieldset-legend" for="product-material">Material</label>
            <select id="product-material" v-model.number="form.material_id" class="select w-full" :class="{ 'select-error': errors.material_id }" required>
              <option :value="null" disabled>Select a material</option>
              <option v-for="material in materials" :key="material.id" :value="material.id">
                {{ material.name }}
              </option>
            </select>
            <p v-if="errors.material_id" class="text-error">{{ errors.material_id[0] }}</p>
          </fieldset>
        </div>

        <div class="grid gap-2 sm:grid-cols-4">
          <fieldset class="fieldset">
            <label class="fieldset-legend" for="product-colour">Colour</label>
            <input id="product-colour" v-model="form.colour" class="input w-full" :class="{ 'input-error': errors.colour }" maxlength="64" required />
            <p v-if="errors.colour" class="text-error">{{ errors.colour[0] }}</p>
          </fieldset>

          <fieldset class="fieldset">
            <label class="fieldset-legend" for="product-size">Size</label>
            <input id="product-size" v-model="form.size" class="input w-full" :class="{ 'input-error': errors.size }" maxlength="32" required />
            <p v-if="errors.size" class="text-error">{{ errors.size[0] }}</p>
          </fieldset>

          <fieldset class="fieldset">
            <label class="fieldset-legend" for="product-price">Price</label>
            <input id="product-price" v-model="form.price" type="number" min="0" step="0.01" class="input w-full" :class="{ 'input-error': errors.price }" required />
            <p v-if="errors.price" class="text-error">{{ errors.price[0] }}</p>
          </fieldset>

          <fieldset class="fieldset">
            <label class="fieldset-legend" for="product-weight">Weight (g)</label>
            <input id="product-weight" v-model="form.weight" type="number" min="0" max="1000000" step="1" class="input w-full" :class="{ 'input-error': errors.weight }" required />
            <p v-if="errors.weight" class="text-error">{{ errors.weight[0] }}</p>
          </fieldset>
        </div>

        <div class="modal-action">
          <button
            v-if="product"
            type="button"
            class="btn btn-error mr-auto"
            :class="{ 'btn-outline': !confirmingDelete }"
            :disabled="saving || deleting"
            @click="remove"
          >
            <span v-if="deleting" class="loading loading-spinner loading-sm"></span>
            {{ confirmingDelete ? 'Confirm delete' : 'Delete' }}
          </button>

          <button type="button" class="btn btn-ghost" :disabled="saving || deleting" @click="dialog?.close()">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="saving || deleting">
            <span v-if="saving" class="loading loading-spinner loading-sm"></span>
            {{ product ? 'Save' : 'Create' }}
          </button>
        </div>
      </form>
    </div>

    <form method="dialog" class="modal-backdrop">
      <button :disabled="saving || deleting">close</button>
    </form>
  </dialog>
</template>
