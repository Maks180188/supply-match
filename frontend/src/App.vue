<script setup lang="ts">
import { RouterView } from 'vue-router'
</script>

<template>
  <div class="app">
    <header class="app-header">
      <div class="container">
        <strong>SupplyMatch</strong>
      </div>
    </header>

    <main class="container">
      <RouterView />
    </main>
  </div>
</template>
