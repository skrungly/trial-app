<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  fetchOperatives,
  generatePickList,
  updateUnitStatus,
  type Operative,
  type PickList,
} from '@/api/factory'

const operatives = ref<Operative[]>([])
const operativesLoading = ref(true)
const operativesError = ref<string | null>(null)
// Who is picking; kept between pick lists so the same operative can carry on.
const operativeId = ref<number | null>(null)

const pickList = ref<PickList | null>(null)
// Index of the unit being picked; equal to the number of units once the list is done.
const current = ref(0)

const loading = ref(false)
const saving = ref(false)
const error = ref<string | null>(null)
// Set when the server had nothing to put on a pick list.
const nothingToPick = ref(false)

const operative = computed(() => operatives.value.find((o) => o.id === operativeId.value) ?? null)
const units = computed(() => pickList.value?.units ?? [])
const unit = computed(() => units.value[current.value] ?? null)
const finished = computed(() => pickList.value !== null && current.value >= units.value.length)
const progress = computed(() => (units.value.length === 0 ? 0 : (current.value / units.value.length) * 100))

function errorMessage(e: unknown, fallback: string): string {
  return e instanceof Error ? e.message : fallback
}

onMounted(async () => {
  try {
    operatives.value = await fetchOperatives()

  } catch (e) {
    operativesError.value = errorMessage(e, 'Failed to load operatives')

  } finally {
    operativesLoading.value = false
  }
})

// Generating claims units for this operative, so it only happens when asked, never on page load.
async function startPickList() {
  if (operativeId.value === null) {
    return
  }

  loading.value = true
  error.value = null
  nothingToPick.value = false

  try {
    const list = await generatePickList(operativeId.value)

    pickList.value = list
    current.value = 0
    nothingToPick.value = list === null

  } catch (e) {
    error.value = errorMessage(e, 'Failed to get a pick list')

  } finally {
    loading.value = false
  }
}

// Back to the start screen, e.g. to hand over to another operative.
function changeOperative() {
  pickList.value = null
  current.value = 0
  nothingToPick.value = false
  error.value = null
}

async function markPicked() {
  if (!unit.value || saving.value) {
    return
  }

  saving.value = true
  error.value = null

  try {
    await updateUnitStatus(unit.value.id, 'picked')
    current.value++

  } catch (e) {
    // Stay on this unit so the picker can try again.
    error.value = errorMessage(e, 'Failed to mark the item as picked')

  } finally {
    saving.value = false
  }
}
</script>

<template>
  <section class="card bg-base-100 shadow-sm mx-auto max-w-xl">
    <div class="card-body gap-6">
      <div class="flex items-center justify-between gap-4">
        <h1 class="card-title text-2xl">Picking</h1>
        <span v-if="pickList" class="text-sm text-base-content/60">
          Pick list #{{ pickList.id }}<template v-if="operative"> · {{ operative.name }}</template>
        </span>
      </div>

      <div v-if="error" role="alert" class="alert alert-error">
        <span>{{ error }}</span>
      </div>

      <!-- Picking one unit at a time -->
      <template v-if="unit">
        <div>
          <div class="mb-1 flex justify-between text-sm text-base-content/60">
            <span>Item {{ current + 1 }} of {{ units.length }}</span>
          </div>
          <progress class="progress progress-primary w-full" :value="progress" max="100"></progress>
        </div>

        <div class="flex flex-col items-center gap-2 py-4 text-center">
          <span class="font-mono text-5xl font-bold" aria-label="Location">{{ unit.location.code }}</span>
        </div>

        <div class="flex flex-col items-center gap-2 text-center">
          <span class="text-2xl font-semibold">{{ unit.order_item.product_name }}</span>
          <span class="text-sm text-base-content/60">order #{{ unit.order_item.order_id }}</span>
        </div>

        <button type="button" class="btn btn-primary btn-lg" :disabled="saving" @click="markPicked">
          <span v-if="saving" class="loading loading-spinner"></span>
          {{ current + 1 === units.length ? 'Mark as picked and finish' : 'Mark as picked' }}
        </button>
      </template>

      <!-- Finished the batch -->
      <div v-else-if="finished" class="flex flex-col items-center gap-4 py-8 text-center">
        <p class="text-lg font-semibold">Pick list complete</p>
        <p class="text-base-content/60">
          All {{ units.length }} {{ units.length === 1 ? 'item has' : 'items have' }} been picked.
        </p>
        <div class="flex flex-wrap justify-center gap-2">
          <button type="button" class="btn btn-primary" :disabled="loading" @click="startPickList">
            <span v-if="loading" class="loading loading-spinner"></span>
            Start next pick list
          </button>
          <button type="button" class="btn btn-ghost" :disabled="loading" @click="changeOperative">
            Change operative
          </button>
        </div>
      </div>

      <!-- Not started yet: choose who is picking -->
      <div v-else class="flex flex-col items-center gap-4 py-8 text-center">
        <div v-if="operativesLoading" class="flex justify-center">
          <span class="loading loading-spinner loading-lg"></span>
        </div>

        <div v-else-if="operativesError" role="alert" class="alert alert-error">
          <span>{{ operativesError }}</span>
        </div>

        <p v-else-if="operatives.length === 0" class="text-base-content/60">
          There are no operatives yet, so there's no one to pick for.
        </p>

        <template v-else>
          <label class="w-full max-w-xs text-left">
            <span class="mb-1 block text-sm font-medium">Operative</span>
            <select v-model="operativeId" class="select w-full">
              <option :value="null" disabled>Choose who is picking</option>
              <option v-for="o in operatives" :key="o.id" :value="o.id">{{ o.name }}</option>
            </select>
          </label>

          <p v-if="nothingToPick" class="text-base-content/60">There's nothing waiting to be picked right now.</p>
          <p v-else class="text-base-content/60">Start a pick list to get the next batch of items to collect.</p>

          <button
            type="button"
            class="btn btn-primary"
            :disabled="loading || operativeId === null"
            @click="startPickList"
          >
            <span v-if="loading" class="loading loading-spinner"></span>
            {{ nothingToPick ? 'Check again' : 'Start pick list' }}
          </button>
        </template>
      </div>
    </div>
  </section>
</template>
