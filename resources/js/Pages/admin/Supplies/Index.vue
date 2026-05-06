<script setup>
import { ref, watch, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Plus, X, Check, Package, Edit, Search, AlertTriangle, Power, Trash2, CheckCircle } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { useToast } from '@/Composables/useToast';
import { debounce } from 'lodash-es';

const props = defineProps({
    supplies: Object,
    filters: Object,
    stats: {
        type: Object,
        default: () => ({ total: 0, active: 0 })
    }
});

// --- State Management ---
const isModalOpen = ref(false);
const isEditMode = ref(false);
const editingId = ref(null);
const externalResults = ref([]);

// Delete Modal State
const isDeleteModalOpen = ref(false);
const supplyToDelete = ref(null);

// --- Toast State Management ---
const { showToast } = useToast();

// --- Search & Filter Logic ---
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const categoryFilter = ref(props.filters.category || '');

const hasFilters = computed(() => {
    return search.value !== '' || statusFilter.value !== '' || categoryFilter.value !== '';
});

const clearFilters = () => {
    search.value = '';
    statusFilter.value = '';
    categoryFilter.value = '';
};

watch([search, statusFilter, categoryFilter], debounce(([newSearch, newStatus, newCategory]) => {
    router.get(
        route('admin.supplies.index'),
        { search: newSearch, status: newStatus, category: newCategory },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300));

// --- Forms Initial States ---
const initialFormState = {
    id: null,
    item_code: '',
    item_description: '',
    category: '',
    unit: ''
};

const form = useForm({ ...initialFormState });

// --- External API Search Logic ---
const fetchExternalReferences = debounce(async (term) => {
    if (term.length < 2) { externalResults.value = []; return; }
    try {
        const response = await fetch(route('admin.supplies.search-external', { term }));
        externalResults.value = await response.json();
    } catch (error) {
        console.error("Failed to fetch external references", error);
    }
}, 300);

// Unit Mapping Dictionary
const unitMapping = {
    'PCE': 'pieces',
    'BOX': 'box',
    'BOT': 'bottle',
    'GAL': 'gallon',
    'BAR': 'bar',
    'PAC': 'pack',
    'PR': 'pair',
    'ROL': 'roll',
    'RM': 'ream',
    'PAD': 'pad',
    'TUB': 'tub'
};

const selectReference = (refItem) => {
    form.item_code = refItem.item_code;
    form.item_description = refItem.item_description;

    // Auto-convert unit of measure
    const originalUnit = refItem.unit_of_measure ? refItem.unit_of_measure.trim().toUpperCase() : '';
    form.unit = unitMapping[originalUnit] || originalUnit || ''; // Fallback if missing

    form.clearErrors();
    externalResults.value = [];
};

// --- Modal Actions ---
const openAddModal = () => {
    isEditMode.value = false;
    editingId.value = null;
    externalResults.value = [];

    form.defaults(initialFormState);
    form.reset();
    form.clearErrors();

    isModalOpen.value = true;
};

const openEditModal = (supply) => {
    isEditMode.value = true;
    editingId.value = supply.id;
    externalResults.value = [];

    form.defaults({
        id: supply.id,
        item_code: supply.item_code,
        item_description: supply.item_description || (supply.reference ? supply.reference.item_description : ''),
        category: supply.category,
        unit: supply.unit,
    });

    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    externalResults.value = [];
    setTimeout(() => {
        form.reset();
        form.clearErrors();
    }, 200);
};

// --- Form Submissions ---
const submitForm = () => {
    if (isEditMode.value) {
        form.put(route('admin.supplies.update', editingId.value), {
            onSuccess: () => {
                closeModal();
                showToast('Supply updated successfully!');
            },
            preserveScroll: true,
        });
    } else {
        form.post(route('admin.supplies.store'), {
            onSuccess: () => {
                closeModal();
                showToast('Supply added successfully!');
            },
            preserveScroll: true,
        });
    }
};

const toggleStatus = (supply) => {
    router.patch(route('admin.supplies.toggle-status', supply.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            const statusStr = !supply.is_active ? 'activated' : 'deactivated';
            showToast(`Supply successfully ${statusStr}!`);
        }
    });
};

// --- Delete Actions ---
const confirmDelete = (supply) => {
    supplyToDelete.value = supply;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    setTimeout(() => { supplyToDelete.value = null; }, 200);
};

const deleteSupply = () => {
    router.delete(route('admin.supplies.destroy', supplyToDelete.value.id), {
        onSuccess: () => {
            closeDeleteModal();
            showToast('Supply deleted successfully!');
        },
        onError: () => {
            closeDeleteModal();
            showToast('Failed to delete supply. It may be in use.', 'error');
        },
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Supplies Management" />

    <AppLayout>
        <div class="relative">
            <PageHeader title="Supplies Management"
                description="Manage requestable supplies, categories, and sync items" />

            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center mb-8 gap-6">
                <div class="flex flex-col sm:flex-row gap-4 w-full xl:w-auto">
                    <div
                        class="bg-white border-l-4 border-[#1369a8] shadow-sm rounded-r-xl p-5 flex items-center min-w-[200px]">
                        <div class="p-3 rounded-full bg-brand-blue-dark/10 	text-brand-blue-dark mr-4">
                            <Package class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Supplies</p>
                            <p class="text-2xl font-black text-gray-800">{{ stats.total || supplies.total || 0 }}</p>
                        </div>
                    </div>

                    <div
                        class="bg-white border-l-4 border-green-500 shadow-sm rounded-r-xl p-5 flex items-center min-w-[200px]">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <CheckCircle class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Active Supplies</p>
                            <p class="text-2xl font-black text-gray-800">{{ stats.active || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white rounded-lg shadow-sm p-4 flex flex-col md:flex-row justify-between items-center gap-4 border border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-4 w-full xl:w-auto">

                        <button v-if="hasFilters" @click="clearFilters"
                            class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-lg transition-colors focus:ring-2 focus:ring-gray-200 focus:outline-none whitespace-nowrap">
                            <X class="w-4 h-4 mr-2" /> Clear Filters
                        </button>

                        <div class="relative w-full sm:w-48">
                            <select v-model="categoryFilter"
                                class="block w-full py-2.5 px-3 border border-gray-300 rounded-lg leading-5 bg-white focus:outline-none focus:ring-1 focus:ring-[#1d62c7] focus:border-[#1d62c7] sm:text-sm transition duration-150 ease-in-out cursor-pointer">
                                <option value="">All Categories</option>
                                <option value="Office & Store Supplies">Office & Store Supplies</option>
                                <option value="Tech & Computer Supplies">Tech & Computer Supplies</option>
                                <option value="Cleaning & Janitorial Supplies">Cleaning & Janitorial Supplies</option>
                                <option value="General Supplies">General Supplies</option>
                            </select>
                        </div>

                        <div class="relative w-full sm:w-36">
                            <select v-model="statusFilter"
                                class="block w-full py-2.5 px-3 border border-gray-300 rounded-lg leading-5 bg-white focus:outline-none focus:ring-1 focus:ring-[#1d62c7] focus:border-[#1d62c7] sm:text-sm transition duration-150 ease-in-out cursor-pointer">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="relative w-full sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Search class="h-5 w-5 text-gray-400" />
                            </div>
                            <input v-model="search" type="text" placeholder="Search item code or description..."
                                class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-[#1d62c7] focus:border-[#1d62c7] sm:text-sm transition duration-150 ease-in-out" />
                        </div>

                        <button @click="openAddModal" class="btn-primary">
                            <Plus class="w-5 h-5 mr-2" /> Add Supply
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] overflow-hidden flex flex-col">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="table-header">
                            <tr>
                                <th class="px-6 py-4">Item Code</th>
                                <th class="px-6 py-4">Item Description</th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4">Unit</th>
                                <th class="px-6 py-4">Available Stocks</th>
                                <th class="px-6 py-4">Allocatable</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="supply in supplies.data" :key="supply.id"
                                class="hover:bg-blue-50/50 transition-colors">
                                <td class="px-6 py-4 font-bold 	text-brand-blue-dark">{{ supply.item_code }}</td>
                                <td class="px-6 py-4 font-bold text-gray-900">{{ supply.display_description || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ supply.category }}</td>
                                <td class="px-6 py-4 text-gray-600 font-medium">{{ supply.unit }}</td>
                                <td class="px-6 py-4 font-bold text-gray-800">{{ supply.available_stocks }}</td>

                                <td
                                    :class="['px-6 py-4 font-bold', supply.allocatable_stocks === 0 ? 'text-red-700' : 'text-green-700']">
                                    {{ supply.allocatable_stocks }}
                                </td>

                                <td class="px-6 py-4">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase',
                                        supply.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    ]">
                                        {{ supply.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button @click="openEditModal(supply)"
                                        class="text-brand-blue-dark hover:text-[#0b426e] transition-colors mr-3"
                                        title="Edit Supply">
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button @click="toggleStatus(supply)"
                                        :class="supply.is_active ? 'text-orange-500 hover:text-orange-700' : 'text-green-500 hover:text-green-700'"
                                        class="transition-colors mr-3"
                                        :title="supply.is_active ? 'Deactivate Supply' : 'Activate Supply'">
                                        <Power class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(supply)"
                                        class="text-red-500 hover:text-red-700 transition-colors" title="Delete Supply">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="supplies.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    No supply records available.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :links="supplies.links" :from="supplies.from" :to="supplies.to" :total="supplies.total"
                    :queryParams="{ search: search, status: statusFilter, category: categoryFilter }" />
            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal">
            <div class="bg-brand-blue-dark px-6 py-4 border-b border-[#0b426e] flex items-center justify-between">
                <h2 class="text-lg font-black text-white flex items-center tracking-wide">
                    <Package class="w-5 h-5 mr-2 opacity-80" />
                    {{ isEditMode ? 'Update Supply Information' : 'Add New Supply' }}
                </h2>
                <button @click="closeModal" class="text-white/70 hover:text-white transition-colors">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submitForm" class="flex flex-col bg-white">
                <div class="p-6 bg-gray-50/50 overflow-y-auto max-h-[70vh] flex flex-col gap-5">

                    <div class="relative">
                        <InputLabel value="Item Code" />
                        <TextInput v-model="form.item_code" @input="fetchExternalReferences(form.item_code)" type="text"
                            placeholder="Type to search external items..."
                            class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                        <InputError :message="form.errors.item_code" class="mt-2" />

                        <ul v-if="externalResults.length > 0"
                            class="absolute z-50 w-full bg-white border border-gray-200 mt-1 rounded-md shadow-lg max-h-48 overflow-y-auto">
                            <li v-for="ref in externalResults" :key="ref.item_code" @click="selectReference(ref)"
                                class="p-3 hover:bg-brand-blue-dark hover:text-white cursor-pointer transition-colors group">
                                <div class="font-bold text-gray-900 group-hover:text-white">{{ ref.item_code }}</div>
                                <div class="text-xs text-gray-500 group-hover:text-blue-100 mt-0.5">{{
                                    ref.item_description }}
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <InputLabel value="Item Description" />
                        <textarea v-model="form.item_description"
                            class="mt-1 block w-full border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] rounded-md shadow-sm"
                            rows="2" placeholder="Enter custom description or use auto-fill"></textarea>
                        <InputError :message="form.errors.item_description" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Category" />
                        <select v-model="form.category"
                            class="mt-1 block w-full border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] rounded-md shadow-sm cursor-pointer">
                            <option value="" disabled>Select a category...</option>
                            <option>Office & Store Supplies</option>
                            <option>Tech & Computer Supplies</option>
                            <option>Cleaning & Janitorial Supplies</option>
                            <option>General Supplies</option>
                        </select>
                        <InputError :message="form.errors.category" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Unit of Measure" />
                        <TextInput v-model="form.unit" type="text" list="unit-options"
                            placeholder="Select or type custom unit (e.g., pieces)"
                            class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />

                        <datalist id="unit-options">
                            <option value="pieces"></option>
                            <option value="box"></option>
                            <option value="bottle"></option>
                            <option value="gallon"></option>
                            <option value="bar"></option>
                            <option value="pack"></option>
                            <option value="pair"></option>
                            <option value="roll"></option>
                            <option value="ream"></option>
                            <option value="pad"></option>
                            <option value="tub"></option>
                        </datalist>

                        <InputError :message="form.errors.unit" class="mt-2" />
                    </div>

                </div>

                <div class="px-6 py-4 bg-gray-100 flex justify-end space-x-3 border-t border-gray-200">
                    <button type="button" @click="closeModal"
                        class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-bold rounded-md shadow-sm transition-colors focus:outline-none">
                        <X class="w-4 h-4 mr-2" /> Cancel
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-md shadow-sm transition-colors disabled:opacity-50">
                        <Check class="w-4 h-4 mr-2" /> {{ isEditMode ? 'Save Changes' : 'Save Record' }}
                    </button>
                </div>
            </form>
        </Modal>

        <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-red-100">
                    <AlertTriangle class="w-6 h-6 text-red-600" />
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-black text-gray-900">Delete Supply</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Are you sure you want to delete <span class="font-bold text-gray-800">{{
                            supplyToDelete?.display_description ||
                            supplyToDelete?.item_code }}</span>?<br>
                        This action cannot be undone.
                    </p>
                </div>
                <div class="flex justify-center space-x-4 mt-8">
                    <button @click="closeDeleteModal"
                        class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-md transition-colors focus:outline-none">
                        Cancel
                    </button>
                    <button @click="deleteSupply"
                        class="px-6 py-2.5 text-white text-sm font-bold rounded-md shadow-sm bg-red-600 hover:bg-red-700 focus:ring-red-500 transition-colors">
                        Yes, Delete
                    </button>
                </div>
            </div>
        </Modal>

    </AppLayout>
</template>