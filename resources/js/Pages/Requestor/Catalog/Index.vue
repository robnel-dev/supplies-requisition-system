<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import debounce from 'lodash-es/debounce';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import AddToCartModal from '@/Components/Requestor/AddToCartModal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { X, Search, RefreshCcw } from 'lucide-vue-next';
import CartDrawer from '@/Components/Requestor/CartDrawer.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    supplies: Object,
    filters: Object,
    categories: Array,
    cart: Object,
    editingTransactionId: {
        type: String,
        default: null,
    },
});

// State for search and filtering
const search = ref(props.filters.search || '');
const category = ref(props.filters.category || 'All Categories');

// Keep filters in the URL so pagination and browser back preserve the current list.
watch([search, category], debounce(([newSearch, newCategory]) => {
    router.get(route('requestor.catalog.index'), {
        search: newSearch,
        category: newCategory
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));


const hasFilters = computed(() => {
    return search.value !== '' || category.value !== 'All Categories';
});

const clearFilters = () => {
    search.value = '';
    category.value = 'All Categories';
};

// Modal & Drawer State
const selectedSupply = ref(null);
const isModalOpen = ref(false);
const isDrawerOpen = ref(false);

const openAddModal = (supply) => {
    selectedSupply.value = supply;
    isModalOpen.value = true;
};

// --- ESC Key Handler for UX ---
const handleEscape = (e) => {
    if (e.key === 'Escape') {
        isModalOpen.value = false;
        isDrawerOpen.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', handleEscape));
onUnmounted(() => document.removeEventListener('keydown', handleEscape));
</script>

<template>

    <Head title="Supplies Catalog" />

    <AppLayout>

        <div class="relative">

            <PageHeader title="Supplies Catalog" description="Browse, search, and add items to your request list." />

            <!-- Editing Banner -->
            <div v-if="editingTransactionId"
                class="mb-4 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 flex items-center gap-3">
                <RefreshCcw class="w-5 h-5 text-amber-600 flex-shrink-0" />
                <div class="flex-1">
                    <p class="text-sm font-semibold text-amber-800">
                        You are editing request
                        <span class="font-mono font-extrabold text-amber-900">{{ editingTransactionId }}</span>
                    </p>
                    <p class="text-xs text-amber-600 mt-0.5">
                        Make your changes and re-submit when ready.
                    </p>
                </div>
                <Link :href="route('requestor.requests.show', cart?.id)"
                    class="text-xs font-bold text-amber-700 hover:text-amber-900 underline">
                View Details
                </Link>
            </div>

            <!-- Filters -->
            <div
                class="bg-white rounded-lg shadow-sm p-4 flex flex-col md:flex-row justify-between items-center gap-6 border border-gray-200 mb-3">

                <div class="flex flex-col sm:flex-row gap-4 w-full md:w-2/3">

                    <!-- Category -->
                    <div class="relative w-full sm:w-1/3">
                        <select v-model="category"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-[#1d62c7] focus:ring-[#1d62c7] text-sm text-gray-700 transition">
                            <option value="All Categories">All Categories</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">
                                {{ cat }}
                            </option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="relative w-full sm:w-2/3">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="h-5 w-5 text-gray-400" />
                        </div>
                        <input type="text" v-model="search" placeholder="Search by item description or code..."
                            class="w-full pl-10 border-gray-300 rounded-md shadow-sm focus:border-[#1d62c7] focus:ring-[#1d62c7] text-sm transition">
                    </div>

                    <button v-if="hasFilters" @click="clearFilters"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-lg transition-colors focus:ring-2 focus:ring-gray-200 focus:outline-none whitespace-nowrap">
                        <X class="w-4 h-4 mr-2" /> Clear Filters
                    </button>
                </div>

                <!-- Cart Button -->
                <div>
                    <button @click="isDrawerOpen = true"
                        class="relative flex items-center gap-2 bg-brand-blue-darker text-white px-6 py-2.5 rounded-full font-semibold shadow hover:bg-brand-blue-hover transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[#1d62c7]/50">

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                        </svg>

                        Item List

                        <span v-if="cart && cart.items && cart.items.length"
                            class="absolute -top-1 -right-1 flex items-center justify-center bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full ring-2 ring-white shadow-sm">
                            {{ cart.items.length }}
                        </span>
                    </button>
                </div>

            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="table-header">
                            <tr>
                                <th class="px-6 py-4 font-semibold uppercase text-xs">Item Code</th>
                                <th class="px-6 py-4 font-semibold uppercase text-xs">Item Description</th>
                                <th class="px-6 py-4 font-semibold uppercase text-xs">Category</th>
                                <th class="px-6 py-4 font-semibold uppercase text-xs">Unit</th>
                                <th class="px-6 py-4 font-semibold uppercase text-xs text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                            <tr v-for="supply in supplies.data" :key="supply.id"
                                class="hover:bg-blue-50/30 transition-colors">

                                <td class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">
                                    {{ supply.item_code }}
                                </td>

                                <td class="px-6 py-4">
                                    <div class="text-gray-600 text-sm break-words max-w-sm">
                                        {{ supply.item_description }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded border border-gray-200 text-xs font-bold uppercase">
                                        {{ supply.category }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 font-medium text-gray-800">
                                    {{ supply.unit || supply.unit_of_measure }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <button @click="openAddModal(supply)"
                                        class="inline-flex items-center justify-center w-9 h-9 bg-brand-blue-darker/10 	text-brand-blue-darker rounded-full hover:bg-brand-blue-darker hover:text-white transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[#1d62c7]/50">

                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                    </button>
                                </td>

                            </tr>

                            <tr v-if="supplies.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    No items found matching your search criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination  -->
                <Pagination :links="supplies.links" :from="supplies.from" :to="supplies.to" :total="supplies.total"
                    :queryParams="{ search: search, category: category }" />
            </div>

        </div>

        <AddToCartModal :show="isModalOpen" :supply="selectedSupply" @close="isModalOpen = false" />
        <CartDrawer :show="isDrawerOpen" :cart="cart" @close="isDrawerOpen = false" />

    </AppLayout>
</template>
