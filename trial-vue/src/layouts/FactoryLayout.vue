<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <div class="min-h-screen bg-base-200">
    <!--
      A darker bar than the shop's, so it's clear at a glance this is the internal factory side.
      daisyUI buttons colour their text, hover and active states from base-content and base-200
      rather than inheriting, so those are pointed at the neutral colours within the header.
    -->
    <header
      class="navbar bg-neutral text-neutral-content shadow-sm [--color-base-content:var(--color-neutral-content)] [--color-base-200:var(--color-neutral)]"
    >
      <div class="container mx-auto flex items-center justify-between px-4">
        <RouterLink :to="{ name: 'factory-picking' }" class="btn btn-ghost text-xl" aria-current="false">
          <span class="flex items-baseline gap-2">
            Trial App
            <span class="badge badge-sm badge-primary">Factory</span>
          </span>
        </RouterLink>

        <nav class="flex items-center gap-1" aria-label="Factory">
          <RouterLink
            :to="{ name: 'factory-picking' }"
            class="btn btn-ghost"
            exact-active-class="btn-active"
          >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M9 11l3 3L22 4" />
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
            </svg>
            Picking
          </RouterLink>

          <RouterLink
            :to="{ name: 'factory-consolidation' }"
            class="btn btn-ghost"
            exact-active-class="btn-active"
          >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="3" y="3" width="7" height="7" />
              <rect x="14" y="3" width="7" height="7" />
              <rect x="3" y="14" width="7" height="7" />
              <rect x="14" y="14" width="7" height="7" />
            </svg>
            Consolidation
          </RouterLink>
        </nav>
      </div>
    </header>

    <main class="container mx-auto px-4 py-8">
      <RouterView />
    </main>
  </div>
</template>
