<script setup lang="ts">
import { reactive, ref, useTemplateRef, watch } from 'vue'
import { ApiError, type ValidationErrors } from '@/api/client'
import type { Manufacturer } from '@/api/manufacturers'
import type { Material } from '@/api/materials'
import { updateProduct, type Product, type ProductInput } from '@/api/products'

const props = defineProps<{
  product: Product | null
  manufacturers: Manufacturer[]
  materials: Material[]
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
}>()

const dialog = useTemplateRef<HTMLDialogElement>('dialog')
const saving = ref(false)
const errors = ref<ValidationErrors>({})
const formError = ref<string | null>(null)

const form = reactive<ProductInput>({
  name: '',
  colour: '',
  size: '',
  price: '',
  manufacturer_id: 0,
  material_id: 0,
})

watch(
  () => props.product,
  (product) => {
    if (!product) {
      dialog.value?.close()
      return
    }

    Object.assign(form, {
      name: product.name,
      colour: product.colour,
      size: product.size,
      price: product.price,
      manufacturer_id: product.manufacturer_id,
      material_id: product.material_id,
    })

    errors.value = {}
    formError.value = null
    dialog.value?.showModal()
  },
)

async function save() {
  if (!props.product) return

  saving.value = true
  errors.value = {}
  formError.value = null

  try {
    await updateProduct(props.product.id, { ...form })
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
</script>

<template>
  <dialog ref="dialog" class="modal" @close="emit('close')">
    <div class="modal-box">
      <h2 class="mb-4 text-lg font-bold">Edit product</h2>

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
            <select id="product-manufacturer" v-model.number="form.manufacturer_id" class="select w-full" :class="{ 'select-error': errors.manufacturer_id }">
              <option v-for="manufacturer in manufacturers" :key="manufacturer.id" :value="manufacturer.id">
                {{ manufacturer.name }}
              </option>
            </select>
            <p v-if="errors.manufacturer_id" class="text-error">{{ errors.manufacturer_id[0] }}</p>
          </fieldset>

          <fieldset class="fieldset">
            <label class="fieldset-legend" for="product-material">Material</label>
            <select id="product-material" v-model.number="form.material_id" class="select w-full" :class="{ 'select-error': errors.material_id }">
              <option v-for="material in materials" :key="material.id" :value="material.id">
                {{ material.name }}
              </option>
            </select>
            <p v-if="errors.material_id" class="text-error">{{ errors.material_id[0] }}</p>
          </fieldset>
        </div>

        <div class="grid gap-2 sm:grid-cols-3">
          <fieldset class="fieldset">
            <label class="fieldset-legend" for="product-colour">Colour</label>
            <input id="product-colour" v-model="form.colour" class="input w-full" :class="{ 'input-error': errors.colour }" maxlength="65" required />
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
        </div>

        <div class="modal-action">
          <button type="button" class="btn btn-ghost" :disabled="saving" @click="dialog?.close()">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            <span v-if="saving" class="loading loading-spinner loading-sm"></span>
            Save
          </button>
        </div>
      </form>
    </div>

    <form method="dialog" class="modal-backdrop">
      <button :disabled="saving">close</button>
    </form>
  </dialog>
</template>
