<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

const page = usePage();

// Define breadcrumb mapping
const breadcrumbMap = {
    'dashboard': [{ name: 'Dashboard', href: route('dashboard') }],
    // Add more as modules are built
};

const breadcrumbs = computed(() => {
    const currentUrl = page.url;
    // Simple implementation - can be enhanced
    for (const [routeName, crumbs] of Object.entries(breadcrumbMap)) {
        if (currentUrl.includes(routeName)) {
            return crumbs;
        }
    }
    return [{ name: 'Home', href: '/' }];
});
</script>

<template>
    <nav v-if="breadcrumbs.length > 1" class="flex mb-4" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li v-for="(crumb, index) in breadcrumbs" :key="index" class="inline-flex items-center">
                <Link v-if="index < breadcrumbs.length - 1" 
                      :href="crumb.href"
                      class="text-sm text-gray-500 hover:text-brand-blue">
                    {{ crumb.name }}
                </Link>
                <span v-else class="text-sm font-medium text-gray-700">
                    {{ crumb.name }}
                </span>
                <svg v-if="index < breadcrumbs.length - 1" 
                     class="w-3 h-3 text-gray-400 mx-1" 
                     fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </li>
        </ol>
    </nav>
</template>