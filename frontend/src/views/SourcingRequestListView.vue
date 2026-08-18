<script setup lang="ts">
import {onMounted, ref} from 'vue'

import {getSourcingRequests} from '@/api/sourcing-requests'
import type {SourcingRequest} from '@/types/sourcing-request'
import {getCategories} from '@/api/categories'
import type {Category} from '@/types/category'

const sourcingRequests = ref<SourcingRequest[]>([])
const categories = ref<Category[]>([])
const loading = ref(true)
const errorMessage = ref('')
const selectedCategoryId = ref<number | ''>('')
const searchQuery = ref('')
const currentPage = ref(1)
const lastPage = ref(1)

async function loadSourcingRequests(): Promise<void> {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await getSourcingRequests({
      category_id: selectedCategoryId.value === '' ? undefined : selectedCategoryId.value,
      q: searchQuery.value.trim() || undefined,
      page: currentPage.value,
    })

    sourcingRequests.value = response.data
    currentPage.value = response.meta.current_page
    lastPage.value = response.meta.last_page
  } catch {
    errorMessage.value = 'Failed to load sourcing requests.'
  } finally {
    loading.value = false
  }
}

async function applyFilters(): Promise<void> {
  currentPage.value = 1

  await loadSourcingRequests()
}

async function goToPage(page: number): Promise<void> {
  if (page < 1 || page > lastPage.value || page === currentPage.value) {
    return
  }

  currentPage.value = page

  await loadSourcingRequests()
}

onMounted(async () => {
  try {
    const [sourcingRequestsResponse, categoriesResponse] = await Promise.all([
      getSourcingRequests(),
      getCategories(),
    ])

    sourcingRequests.value = sourcingRequestsResponse.data
    categories.value = categoriesResponse
    currentPage.value = sourcingRequestsResponse.meta.current_page
    lastPage.value = sourcingRequestsResponse.meta.last_page
  } catch {
    errorMessage.value = 'Failed to load sourcing requests.'
  } finally {
    loading.value = false
  }
})
</script>
<template>
  <main>
    <h1>Sourcing requests</h1>
    <div>
      <label for="category">Category</label>
      <select
        id="category"
        v-model="selectedCategoryId"
        :disabled="loading"
        @change="applyFilters"
      >
        <option value="">All categories</option>
        <option
          v-for="category in categories"
          :key="category.id"
          :value="category.id"
        >
          {{ category.name }}
        </option>
      </select>
    </div>
    <form @submit.prevent="applyFilters">
      <label for="search">Search</label>
      <input
        id="search"
        v-model="searchQuery"
        type="search"
        placeholder="Search by title or description"
        :disabled="loading"
      >
      <button type="submit" :disabled="loading">
        Search
      </button>
    </form>
    <p v-if="errorMessage">{{ errorMessage }}</p>
    <p v-else-if="loading">Loading sourcing requests...</p>
    <p v-else-if="sourcingRequests.length === 0">
      No sourcing requests found.
    </p>
    <ul v-else>>
      <li v-for="sourcingRequest in sourcingRequests" :key="sourcingRequest.id">
        <h2>{{ sourcingRequest.title }}</h2>
        <p>{{ sourcingRequest.description }}</p>
        <p>Buyer: {{ sourcingRequest.company.name }}</p>
        <p>Category: {{ sourcingRequest.category.name }}</p>
        <p v-if="sourcingRequest.submission_deadline">
          Submission deadline: {{ sourcingRequest.submission_deadline }}
        </p>
      </li>
    </ul>
    <nav
      v-if="!loading && !errorMessage && lastPage > 1"
      aria-label="Pagination"
    >
      <button
        type="button"
        :disabled="currentPage === 1"
        @click="goToPage(currentPage - 1)"
      >
        Previous
      </button>

      <span>Page {{ currentPage }} of {{ lastPage }}</span>

      <button
        type="button"
        :disabled="currentPage === lastPage"
        @click="goToPage(currentPage + 1)"
      >
        Next
      </button>
    </nav>
  </main>
</template>

<style scoped>

</style>
