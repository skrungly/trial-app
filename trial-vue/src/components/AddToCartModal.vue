<script setup lang="ts">
import { computed, ref, useTemplateRef, watch } from 'vue'
import type { Product } from '@/api/products'
import { MAX_QUANTITY, useCart } from '@/stores/cart'
import { formatGBP, toPence } from '@/utils/money'

const props = defineProps<{
  open: boolean
  product: Product | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'added'): void
}>()

const cart = useCart()
const dialog = useTemplateRef<HTMLDialogElement>('dialog')

// v-model.number gives a number, or an empty string while the input is cleared.
const quantity = ref<number | ''>(1)

const quantityValid = computed(
  () =>
    typeof quantity.value === 'number' &&
    Number.isInteger(quantity.value) &&
    quantity.value >= 1 &&
    quantity.value <= MAX_QUANTITY,
)

const totalPence = computed(() =>
  props.product && quantityValid.value ? toPence(props.product.price) * (quantity.value as number) : null,
)

watch(
  () => props.open,
  (open) => {
    if (!open) {
      dialog.value?.close()
      return
    }

    quantity.value = 1
    dialog.value?.showModal()
  },
)

function addToCart() {
  if (!props.product || !quantityValid.value) return

  cart.add(props.product, quantity.value as number)
  emit('added')
}
</script>

<template>
  <dialog ref="dialog" class="modal" @close="emit('close')">
    <div v-if="product" class="modal-box">
      <h2 class="text-lg font-bold">{{ product.name }}</h2>
      <p class="mb-4 text-sm text-base-content/70">
        {{ product.manufacturer.name }} · {{ product.material.name }} ·
        <span class="capitalize">{{ product.colour }}</span> · {{ product.size }}
      </p>

      <form class="flex flex-col gap-4" @submit.prevent="addToCart">
        <div class="flex items-end gap-4">
          <fieldset class="fieldset">
            <label class="fieldset-legend" for="cart-quantity">Quantity</label>
            <input
              id="cart-quantity"
              v-model.number="quantity"
              type="number"
              min="1"
              :max="MAX_QUANTITY"
              step="1"
              class="input w-28"
              :class="{ 'input-error': !quantityValid }"
              required
            />
          </fieldset>

          <p class="pb-3 text-base-content/70">× {{ formatGBP(toPence(product.price)) }}</p>
        </div>

        <div class="flex items-center justify-between rounded-box bg-base-200 px-4 py-3">
          <span class="font-medium">Total</span>
          <span class="text-xl font-bold tabular-nums">
            {{ totalPence === null ? '—' : formatGBP(totalPence) }}
          </span>
        </div>

        <p v-if="!quantityValid" class="text-sm text-error">
          Enter a whole number from 1 to {{ MAX_QUANTITY }}.
        </p>

        <div class="modal-action">
          <button type="button" class="btn btn-ghost" @click="dialog?.close()">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="!quantityValid">Add to cart</button>
        </div>
      </form>
    </div>

    <form method="dialog" class="modal-backdrop">
      <button>close</button>
    </form>
  </dialog>
</template>
