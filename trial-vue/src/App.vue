<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { useCart } from '@/stores/cart'

const { itemCount } = useCart()

const cartLabel = computed(() => `Cart, ${itemCount.value} ${itemCount.value === 1 ? 'item' : 'items'}`)
</script>

<template>
  <div class="min-h-screen bg-base-200">
    <header class="navbar bg-base-100 shadow-sm">
      <div class="container mx-auto flex items-center justify-between px-4">
        <RouterLink to="/" class="btn btn-ghost text-xl" aria-current="false">Trial App</RouterLink>

        <RouterLink to="/cart" class="btn btn-ghost" :aria-label="cartLabel">
          <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="8" cy="21" r="1" />
            <circle cx="19" cy="21" r="1" />
            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
          </svg>
          Cart
          <span v-if="itemCount > 0" class="badge badge-sm badge-primary">{{ itemCount }}</span>
        </RouterLink>
      </div>
    </header>

    <main class="container mx-auto px-4 py-8">
      <RouterView />
    </main>
  </div>
</template>
