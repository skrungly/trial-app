<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { ApiError } from '@/api/client'
import {
  fetchPigeonholes,
  updateUnitStatus,
  type Operative,
  type PigeonholeWithFullness,
  type UnitWithPigeonhole,
} from '@/api/factory'
import OperativeSelect from '@/components/factory/OperativeSelect.vue'

// Who is consolidating. Only shown on the page for now; the units endpoint doesn't record it.
const operative = ref<Operative | null>(null)

const unitIdInput = ref('')
const input = ref<HTMLInputElement | null>(null)

const saving = ref(false)
const error = ref<string | null>(null)
// The unit just consolidated, whose pigeonhole is shown large.
const last = ref<UnitWithPigeonhole | null>(null)

// Every pigeonhole with how full it is, kept up to date while the page is open.
const pigeonholes = ref<PigeonholeWithFullness[]>([])
const pigeonholesLoading = ref(true)
const pigeonholesError = ref<string | null>(null)

// Often enough to pick up other operatives' consolidations without a noticeable lag.
const REFRESH_INTERVAL_MS = 15_000

let refreshTimer: ReturnType<typeof setInterval> | undefined

async function loadPigeonholes() {
  try {
    pigeonholes.value = await fetchPigeonholes()
    pigeonholesError.value = null

  } catch (e) {
    // Keep showing the last good list; just flag that it may be out of date.
    pigeonholesError.value = e instanceof Error ? e.message : 'Failed to load pigeonholes'

  } finally {
    pigeonholesLoading.value = false
  }
}

onMounted(() => {
  loadPigeonholes()
  refreshTimer = setInterval(loadPigeonholes, REFRESH_INTERVAL_MS)
})

onUnmounted(() => clearInterval(refreshTimer))

type Fullness = 'empty' | 'filling' | 'full'

function fullness(pigeonhole: PigeonholeWithFullness): Fullness {
  if (pigeonhole.order_id === null) {
    return 'empty'
  }

  return pigeonhole.consolidated_units_count >= pigeonhole.units_count ? 'full' : 'filling'
}

function percentFull(pigeonhole: PigeonholeWithFullness): number {
  return pigeonhole.units_count === 0 ? 0 : (pigeonhole.consolidated_units_count / pigeonhole.units_count) * 100
}

// Pigeonholes grouped by aisle, keeping the API's aisle-then-shelf order.
const aisles = computed(() => {
  const groups = new Map<string, PigeonholeWithFullness[]>()

  for (const pigeonhole of pigeonholes.value) {
    groups.set(pigeonhole.aisle, [...(groups.get(pigeonhole.aisle) ?? []), pigeonhole])
  }

  return [...groups].map(([aisle, list]) => ({ aisle, pigeonholes: list }))
})

function errorMessage(e: unknown, unitId: number): string {
  if (e instanceof ApiError && e.status === 404) {
    return `There's no unit ${unitId}.`
  }

  return e instanceof Error ? e.message : 'Failed to consolidate the unit'
}

async function focusInput() {
  await nextTick()
  input.value?.focus()
}

// The unit field only appears once an operative is chosen, so focus it then, ready to scan.
watch(operative, (chosen) => {
  if (chosen) {
    focusInput()
  }
})

async function consolidate() {
  const unitId = Number(unitIdInput.value.trim())

  if (!Number.isInteger(unitId) || unitId < 1) {
    error.value = 'Enter a unit number.'

    return
  }

  saving.value = true
  error.value = null

  try {
    const unit = await updateUnitStatus(unitId, 'consolidated')

    last.value = unit
    // Cleared ready for the next unit, e.g. from a barcode scanner.
    unitIdInput.value = ''
    // Show the pigeonhole filling up straight away rather than at the next refresh.
    loadPigeonholes()

  } catch (e) {
    last.value = null
    error.value = errorMessage(e, unitId)

  } finally {
    saving.value = false
    focusInput()
  }
}
</script>

<template>
  <section class="card bg-base-100 shadow-sm mx-auto max-w-xl">
    <div class="card-body gap-6">
      <div class="flex items-center justify-between gap-4">
        <h1 class="card-title text-2xl">Consolidation</h1>
        <span v-if="operative" class="text-sm text-base-content/60">{{ operative.name }}</span>
      </div>

      <!-- Choose who is consolidating first -->
      <div v-if="!operative" class="flex flex-col items-center gap-4 py-8 text-center">
        <OperativeSelect v-model="operative" placeholder="Choose who is consolidating" />
      </div>

      <template v-else>
        <form class="flex flex-col gap-2" @submit.prevent="consolidate">
          <label for="unit-id" class="text-sm font-medium">Unit number</label>
          <div class="join w-full">
            <input
              id="unit-id"
              ref="input"
              v-model="unitIdInput"
              type="text"
              inputmode="numeric"
              autocomplete="off"
              placeholder="Scan or type a unit number"
              class="input join-item w-full text-lg"
              :disabled="saving"
            />
            <button type="submit" class="btn btn-primary join-item" :disabled="saving || unitIdInput.trim() === ''">
              <span v-if="saving" class="loading loading-spinner"></span>
              Consolidate
            </button>
          </div>
        </form>

        <div v-if="error" role="alert" class="alert alert-error">
          <span>{{ error }}</span>
        </div>

        <!-- Where the unit just consolidated goes -->
        <div v-if="last" class="flex flex-col items-center gap-2 rounded-box bg-base-200 py-6 text-center" aria-live="polite">
          <span v-if="last.pigeonhole" class="text-6xl font-bold">{{ last.pigeonhole.code }}</span>
          <span v-else class="text-lg font-semibold">No pigeonhole assigned</span>
        </div>

        <!-- Every pigeonhole and how full it is -->
        <div>
          <div class="mb-2 flex items-center justify-between gap-2">
            <h2 class="text-sm font-medium">Pigeonholes</h2>
            <div class="flex items-center gap-3 text-xs text-base-content/60">
              <span class="flex items-center gap-1"><span class="status status-neutral"></span> Empty</span>
              <span class="flex items-center gap-1"><span class="status status-warning"></span> Filling</span>
              <span class="flex items-center gap-1"><span class="status status-success"></span> Ready to pack</span>
            </div>
          </div>

          <div v-if="pigeonholesError" role="alert" class="alert alert-warning mb-2 text-sm">
            <span>{{ pigeonholesError }} The list below may be out of date.</span>
          </div>

          <div v-if="pigeonholesLoading" class="flex justify-center py-4">
            <span class="loading loading-spinner"></span>
          </div>

          <p v-else-if="pigeonholes.length === 0" class="py-4 text-center text-base-content/60">There are no pigeonholes.</p>

          <div v-else class="flex flex-col gap-4">
            <section v-for="group in aisles" :key="group.aisle" :aria-labelledby="`aisle-${group.aisle}`">
              <h3 :id="`aisle-${group.aisle}`" class="mb-1 text-xs font-semibold uppercase tracking-wide text-base-content/60">
                Aisle {{ group.aisle }}
              </h3>

              <ul class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                <li
                  v-for="pigeonhole in group.pigeonholes"
                  :key="pigeonhole.id"
                  class="flex flex-col gap-1 rounded-box border p-2"
                  :class="{
                    'border-base-300 text-base-content/50': fullness(pigeonhole) === 'empty',
                    'border-warning': fullness(pigeonhole) === 'filling',
                    'border-success bg-success/10': fullness(pigeonhole) === 'full',
                    'ring-2 ring-primary': pigeonhole.id === last?.pigeonhole?.id,
                  }"
                >
                  <!-- Every tile has the same lines, so empty and filled tiles are the same height. -->
                  <span class="font-mono font-bold">{{ pigeonhole.code }}</span>

                  <span class="text-xs" :class="{ 'text-base-content/60': fullness(pigeonhole) !== 'empty' }">
                    {{ fullness(pigeonhole) === 'empty' ? 'Empty' : `Order #${pigeonhole.order_id}` }}
                  </span>

                  <!-- Hidden but kept when empty, like the count below, to hold the space. -->
                  <progress
                    class="progress h-1.5 w-full"
                    :class="{
                      'progress-success': fullness(pigeonhole) === 'full',
                      'progress-warning': fullness(pigeonhole) === 'filling',
                      invisible: fullness(pigeonhole) === 'empty',
                    }"
                    :value="percentFull(pigeonhole)"
                    max="100"
                    :aria-hidden="fullness(pigeonhole) === 'empty'"
                    :aria-label="`${pigeonhole.code}: ${pigeonhole.consolidated_units_count} of ${pigeonhole.units_count} units`"
                  ></progress>

                  <!-- Kept but hidden when empty, to hold the space. -->
                  <span class="text-xs tabular-nums" :class="{ invisible: fullness(pigeonhole) === 'empty' }">
                    {{ pigeonhole.consolidated_units_count }} / {{ pigeonhole.units_count }}
                  </span>
                </li>
              </ul>
            </section>
          </div>
        </div>

        <button type="button" class="btn btn-ghost btn-sm self-start" @click="operative = null">Change operative</button>
      </template>
    </div>
  </section>
</template>
