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

// --- Toast ---
const { showToast } = useToast();

// --- Search & Filter ---
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const categoryFilter = ref(props.filters.category || '');

const hasFilters = computed(() =>
    search.value !== '' || statusFilter.value !== '' || categoryFilter.value !== ''
);

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

// --- Modal State ---
const isModalOpen = ref(false);
const isEditMode = ref(false);
const editingId = ref(null);
const externalResults = ref([]);
const isDeleteModalOpen = ref(false);
const supplyToDelete = ref(null);

// --- Form ---
const initialFormState = {
    item_code: '',
    item_description: '',
    category: '',
    unit: '',
};

const form = useForm({ ...initialFormState });

// --- External API Lookup ---
const fetchExternalReferences = debounce(async (term) => {
    if (term.length < 2) { externalResults.value = []; return; }
    try {
        const res = await fetch(route('admin.supplies.search-external', { term }));
        externalResults.value = await res.json();
    } catch {
        externalResults.value = [];
    }
}, 300);

const unitMapping = {
    PCE: 'pieces', BOX: 'box', BOT: 'bottle', GAL: 'gallon',
    BAR: 'bar', PAC: 'pack', PR: 'pair', ROL: 'roll',
    RM: 'ream', PAD: 'pad', TUB: 'tub',
};

const selectReference = (refItem) => {
    form.item_code = refItem.item_code;
    form.item_description = refItem.item_description;
    const rawUnit = refItem.unit_of_measure?.trim().toUpperCase() ?? '';
    form.unit = unitMapping[rawUnit] || rawUnit;
    form.clearErrors();
    externalResults.value = [];
};

// --- CRUD Actions ---
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

    const description = supply.item_description
        ?? supply.reference?.item_description
        ?? '';

    form.defaults({
        item_code: supply.item_code,
        item_description: description,
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
    setTimeout(() => { form.reset(); form.clearErrors(); }, 200);
};

const submitForm = () => {
    if (isEditMode.value) {
        form.put(route('admin.supplies.update', editingId.value), {
            onSuccess: () => { closeModal(); showToast('Supply updated successfully!'); },
            preserveScroll: true,
        });
    } else {
        form.post(route('admin.supplies.store'), {
            onSuccess: () => { closeModal(); showToast('Supply added successfully!'); },
            preserveScroll: true,
        });
    }
};

const toggleStatus = (supply) => {
    router.patch(route('admin.supplies.toggle-status', supply.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            const newStatus = supply.is_active ? 'deactivated' : 'activated';
            showToast(`Supply ${newStatus} successfully!`);
        },
    });
};

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
        onSuccess: () => { closeDeleteModal(); showToast('Supply deleted successfully!'); },
        onError: () => { closeDeleteModal(); showToast('Cannot delete — this supply is used in existing requests.', 'error'); },
        preserveScroll: true,
    });
};

const getDisplayDescription = (supply) => {
    return supply.item_description
        ?? supply.reference?.item_description
        ?? supply.item_code;
};
</script>

<template>

    <Head title="Supplies Management" />

    <AppLayout>
        <div class="relative">
            <PageHeader title="Supplies Management"
                description="Manage requestable supplies, categories, and monitor inventory status." />

            <!-- Stats + Filters Row -->
            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center mb-8 gap-6">

                <!-- Stat Cards -->
                <div class="flex flex-col sm:flex-row gap-4 w-full xl:w-auto">
                    <div class="card-stat border-l-4 border-brand-blue-dark">
                        <div class="p-3 rounded-full bg-brand-blue-dark/10 text-brand-blue-dark mr-4">
                            <Package class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Supplies</p>
                            <p class="text-2xl font-black text-gray-800">{{ stats.total }}</p>
                        </div>
                    </div>

                    <div class="card-stat border-l-4 border-green-500">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <CheckCircle class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Active Supplies</p>
                            <p class="text-2xl font-black text-gray-800">{{ stats.active }}</p>
                        </div>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div
                    class="bg-white rounded-lg shadow-sm p-4 flex flex-col md:flex-row items-center gap-4 border border-gray-200 w-full xl:w-auto">

                    <button v-if="hasFilters" @click="clearFilters"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-lg transition-colors whitespace-nowrap">
                        <X class="w-4 h-4 mr-2" /> Clear Filters
                    </button>

                    <select v-model="categoryFilter"
                        class="block w-full sm:w-52 py-2.5 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-brand-blue-darker focus:border-brand-blue-darker sm:text-sm cursor-pointer">
                        <option value="">All Categories</option>
                        <option>Office &amp; Store Supplies</option>
                        <option>Tech &amp; Computer Supplies</option>
                        <option>Cleaning &amp; Janitorial Supplies</option>
                        <option>General Supplies</option>
                    </select>

                    <select v-model="statusFilter"
                        class="block w-full sm:w-36 py-2.5 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-brand-blue-darker focus:border-brand-blue-darker sm:text-sm cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="h-5 w-5 text-gray-400" />
                        </div>
                        <input v-model="search" type="text" placeholder="Search item code or description..."
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-brand-blue-darker focus:border-brand-blue-darker sm:text-sm" />
                    </div>

                    <button @click="openAddModal" class="btn-primary whitespace-nowrap">
                        <Plus class="w-5 h-5 mr-2" /> Add Supply
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden flex flex-col border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="table-header">
                            <tr>
                                <th class="px-6 py-4">Item Code</th>
                                <th class="px-6 py-4">Description</th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4">Unit</th>
                                <th class="px-6 py-4 text-right">Available</th>
                                <th class="px-6 py-4 text-right">Allocatable</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="supply in supplies.data" :key="supply.id"
                                class="hover:bg-blue-50/50 transition-colors">

                                <td class="px-6 py-4 font-bold text-brand-blue-dark">{{ supply.item_code }}</td>

                                <td class="px-6 py-4 font-medium text-gray-900 max-w-xs">
                                    <span class="line-clamp-2 whitespace-normal">{{ getDisplayDescription(supply)
                                    }}</span>
                                </td>

                                <td class="px-6 py-4 text-gray-600">{{ supply.category }}</td>
                                <td class="px-6 py-4 text-gray-600 font-medium">{{ supply.unit }}</td>

                                <td class="px-6 py-4 font-bold text-gray-800 text-right">
                                    {{ supply.reference?.stock_quantity ?? '—' }}
                                </td>

                                <td class="px-6 py-4 font-bold text-right"
                                    :class="(supply.reference?.allocatable_quantity ?? 0) === 0 ? 'text-red-600' : 'text-green-700'">
                                    {{ supply.reference?.allocatable_quantity ?? '—' }}
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
                                    <div class="inline-flex items-center gap-2">
                                        <button @click="openEditModal(supply)"
                                            class="p-1.5 rounded text-brand-blue-dark hover:bg-blue-50 transition-colors"
                                            title="Edit Supply">
                                            <Edit class="w-4 h-4" />
                                        </button>
                                        <button @click="toggleStatus(supply)"
                                            :class="supply.is_active ? 'text-orange-500 hover:bg-orange-50' : 'text-green-600 hover:bg-green-50'"
                                            class="p-1.5 rounded transition-colors"
                                            :title="supply.is_active ? 'Deactivate' : 'Activate'">
                                            <Power class="w-4 h-4" />
                                        </button>
                                        <button @click="confirmDelete(supply)"
                                            class="p-1.5 rounded text-red-500 hover:bg-red-50 transition-colors"
                                            title="Delete Supply">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="supplies.data.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center text-gray-400">
                                    <Package class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                    <p class="font-medium">No supply records found.</p>
                                    <p class="text-sm mt-1">Try adjusting your filters or add a new supply.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :links="supplies.links" :from="supplies.from" :to="supplies.to" :total="supplies.total"
                    :queryParams="{ search, status: statusFilter, category: categoryFilter }" />
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="bg-brand-blue-dark px-6 py-4 border-b border-brand-navy flex items-center justify-between">
                <h2 class="text-lg font-black text-white flex items-center tracking-wide">
                    <Package class="w-5 h-5 mr-2 opacity-80" />
                    {{ isEditMode ? 'Update Supply' : 'Add New Supply' }}
                </h2>
                <button @click="closeModal" class="text-white/70 hover:text-white transition-colors">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submitForm" class="flex flex-col bg-white">
                <div class="p-6 bg-gray-50/50 overflow-y-auto max-h-[70vh] flex flex-col gap-5">

                    <!-- Item Code with External Autocomplete -->
                    <div class="relative">
                        <InputLabel value="Item Code" />
                        <TextInput v-model="form.item_code"
                            @input="!isEditMode && fetchExternalReferences(form.item_code)" :readonly="isEditMode"
                            type="text" placeholder="Input item code from M3 supplies..."
                            class="mt-1 block w-full focus:border-brand-blue-darker focus:ring-brand-blue-darker shadow-sm"
                            :class="isEditMode ? 'bg-gray-100 cursor-not-allowed text-gray-500' : ''" />
                        <p v-if="isEditMode" class="text-xs text-gray-400 mt-1">Item code cannot be changed after
                            creation.</p>
                        <InputError :message="form.errors.item_code" class="mt-2" />

                        <!-- Autocomplete Dropdown -->
                        <ul v-if="externalResults.length > 0"
                            class="absolute z-50 w-full bg-white border border-gray-200 mt-1 rounded-md shadow-lg max-h-48 overflow-y-auto">
                            <li v-for="ref in externalResults" :key="ref.item_code" @click="selectReference(ref)"
                                class="p-3 hover:bg-brand-blue-dark hover:text-white cursor-pointer transition-colors group">
                                <div class="font-bold text-gray-900 group-hover:text-white">{{ ref.item_code }}</div>
                                <div class="text-xs text-gray-500 group-hover:text-blue-100 mt-0.5">{{
                                    ref.item_description }}</div>
                            </li>
                        </ul>
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel value="Item Description" />
                        <textarea v-model="form.item_description"
                            class="mt-1 block w-full border-gray-300 focus:border-brand-blue-darker focus:ring-brand-blue-darker rounded-md shadow-sm"
                            rows="2"
                            placeholder="Auto-filled or enter custom description"></textarea>
                        <InputError :message="form.errors.item_description" class="mt-2" />
                    </div>

                    <!-- Category -->
                    <div>
                        <InputLabel value="Category" />
                        <select v-model="form.category"
                            class="mt-1 block w-full border-gray-300 focus:border-brand-blue-darker focus:ring-brand-blue-darker rounded-md shadow-sm cursor-pointer">
                            <option value="" disabled>Select a category...</option>
                            <option>Office &amp; Store Supplies</option>
                            <option>Tech &amp; Computer Supplies</option>
                            <option>Cleaning &amp; Janitorial Supplies</option>
                            <option>General Supplies</option>
                        </select>
                        <InputError :message="form.errors.category" class="mt-2" />
                    </div>

                    <!-- Unit -->
                    <div>
                        <InputLabel value="Unit of Measure" />
                        <TextInput v-model="form.unit" type="text" list="unit-options"
                            placeholder="Select or type unit (e.g., pieces, box)"
                            class="mt-1 block w-full focus:border-brand-blue-darker focus:ring-brand-blue-darker shadow-sm" />
                        <datalist id="unit-options">
                            <option value="pieces" />
                            <option value="box" />
                            <option value="bottle" />
                            <option value="gallon" />
                            <option value="bar" />
                            <option value="pack" />
                            <option value="pair" />
                            <option value="roll" />
                            <option value="ream" />
                            <option value="pad" />
                            <option value="tub" />
                        </datalist>
                        <InputError :message="form.errors.unit" class="mt-2" />
                    </div>

                </div>

                <div class="px-6 py-4 bg-gray-100 flex justify-end space-x-3 border-t border-gray-200">
                    <button type="button" @click="closeModal" class="btn-secondary">
                        <X class="w-4 h-4 mr-2" /> Cancel
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-md shadow-sm transition-colors disabled:opacity-50">
                        <Check class="w-4 h-4 mr-2" /> {{ isEditMode ? 'Save Changes' : 'Save Record' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Delete Confirm Modal -->
        <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-red-100">
                    <AlertTriangle class="w-6 h-6 text-red-600" />
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-black text-gray-900">Delete Supply</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Are you sure you want to delete
                        <span class="font-bold text-gray-800">{{ getDisplayDescription(supplyToDelete) ||
                            supplyToDelete?.item_code }}</span>?
                        <br>This action cannot be undone.
                    </p>
                </div>
                <div class="flex justify-center space-x-4 mt-8">
                    <button @click="closeDeleteModal" class="btn-secondary">Cancel</button>
                    <button @click="deleteSupply" class="btn-danger">Yes, Delete</button>
                </div>
            </div>
        </Modal>

    </AppLayout>
</template>
