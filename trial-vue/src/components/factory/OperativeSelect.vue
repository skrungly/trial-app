<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { fetchOperatives, type Operative } from '@/api/factory'

/**
 * A dropdown for choosing an operative, which loads the operatives itself
 * and shows its own loading, error and empty states.
 *
 * v-model is the chosen Operative (or null), so pages get their name as well as their id.
 */
const model = defineModel<Operative | null>({ default: null })

withDefaults(
  defineProps<{
    label?: string
    placeholder?: string
  }>(),
  {
    label: 'Operative',
    placeholder: 'Choose an operative',
  },
)

const operatives = ref<Operative[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

// The select works on ids, so the choice survives the list being reloaded (e.g. when remounted)
// even though that gives new Operative objects.
const selectedId = computed<number | null>({
  get: () => model.value?.id ?? null,
  set: (id) => {
    model.value = operatives.value.find((o) => o.id === id) ?? null
  },
})

onMounted(async () => {
  try {
    operatives.value = await fetchOperatives()

  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load operatives'

  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="w-full max-w-xs text-left">
    <div v-if="loading" class="flex justify-center">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <div v-else-if="error" role="alert" class="alert alert-error">
      <span>{{ error }}</span>
    </div>

    <p v-else-if="operatives.length === 0" class="text-center text-base-content/60">There are no operatives yet.</p>

    <label v-else class="block">
      <span class="mb-1 block text-sm font-medium">{{ label }}</span>
      <select v-model="selectedId" class="select w-full">
        <option :value="null" disabled>{{ placeholder }}</option>
        <option v-for="o in operatives" :key="o.id" :value="o.id">{{ o.name }}</option>
      </select>
    </label>
  </div>
</template>
