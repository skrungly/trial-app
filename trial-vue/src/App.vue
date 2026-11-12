<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <div class="min-h-screen bg-base-200">
    <header class="navbar bg-base-100 shadow-sm">
      <div class="container mx-auto px-4">
        <RouterLink to="/" class="btn btn-ghost text-xl" aria-current="false">Trial App</RouterLink>
      </div>
    </header>

    <main class="container mx-auto px-4 py-8">
      <RouterView />
    </main>
  </div>
</template>
