<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
  links: Array,
  from: {
    type: Number,
    default: null,
  },
  to: {
    type: Number,
    default: null,
  },
  total: {
    type: Number,
    default: null,
  },
  preserveScroll: {
    type: Boolean,
    default: true,
  },
  queryParams: {
    type: Object,
    default: () => ({}),
  },
})

const navigate = (url) => {
  router.get(url, props.queryParams, { preserveScroll: props.preserveScroll })
}
</script>

<template>
  <div v-if="links && links.length > 3"
    class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
    <p class="text-sm text-gray-600">
      <template v-if="from && to && total">
        Showing
        <span class="font-bold text-gray-900">{{ from }}</span>
        to
        <span class="font-bold text-gray-900">{{ to }}</span>
        of
        <span class="font-bold text-gray-900">{{ total }}</span>
        results
      </template>
      <template v-else>
        &nbsp;
      </template>
    </p>
    <div class="flex flex-wrap shadow-sm rounded-md">
      <template v-for="(link, index) in links" :key="index">
        <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm text-gray-400 border border-gray-200 rounded"
          v-html="link.label"></div>

        <button v-else @click.prevent="navigate(link.url)" :class="[
          'mr-1 mb-1 px-4 py-2 text-sm border rounded focus:border-brand-blue-darker focus:text-brand-blue-darker transition-colors',
          link.active
            ? 'bg-brand-blue-dark text-white border-brand-blue-dark hover:bg-brand-navy-light hover:text-white'
            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100 hover:text-gray-900'
        ]" v-html="link.label">
        </button>
      </template>
    </div>
  </div>
</template>