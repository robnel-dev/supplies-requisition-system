<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Building2,
    Check,
    Edit,
    Plus,
    RotateCcw,
    Trash2,
    Users,
    X,
    Search
} from 'lucide-vue-next';
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
    departments: {
        type: Object,
        default: () => ({ data: [], links: [], from: null, to: null, total: null }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
    stats: {
        type: Object,
        default: () => ({ totalDepartments: 0, totalAssignedUsers: 0 }),
    },
    hoRefs: {
        type: Array,
        default: () => [],
    },
    storeAreaOptions: {
        type: Array,
        default: () => [],
    },
});

const { showToast } = useToast();

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('admin.departments.index'), { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

const totalDepartments = computed(() => props.stats.totalDepartments || props.departments.total || 0);
const totalAssignedUsers = computed(() => props.stats.totalAssignedUsers || 0);
const displayDepartments = computed(() => props.departments.data || []);

const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isEditMode = ref(false);
const editingId = ref(null);
const departmentToDelete = ref(null);
const selectedReference = ref(null);

const initialFormState = {
    external_department_reference_id: '',
    code: '',
    name: '',
    type: 'head_office',
    area: 'HO',
    cost_center: '',
    cost_center_source: 'manual',
};

const form = useForm({ ...initialFormState });

const hasExternalReference = computed(() => Boolean(form.external_department_reference_id));
const costCenterLocked = computed(() => hasExternalReference.value && form.cost_center_source === 'external');

const formatType = (type) => type === 'head_office' ? 'HEAD OFFICE' : 'STORE AREA';

const referenceLabel = (reference) => {
    const company = reference.company_code ? ` (${reference.company_code})` : '';

    return `${reference.department_code} - ${reference.name}${company}`;
};

const resetDepartmentFields = (type = form.type) => {
    form.external_department_reference_id = '';
    form.code = '';
    form.name = '';
    form.type = type;
    form.area = type === 'head_office' ? 'HO' : '';
    form.cost_center = '';
    form.cost_center_source = 'manual';
    selectedReference.value = null;
};

const handleTypeChange = () => {
    resetDepartmentFields(form.type);
};

const applyHeadOfficeReference = (reference) => {
    if (!reference) {
        return;
    }

    selectedReference.value = reference;
    form.external_department_reference_id = reference.id;
    form.code = reference.department_code;
    form.name = reference.name;
    form.area = reference.area || 'HO';
    form.cost_center = reference.cost_center;
    form.cost_center_source = 'external';
};

const selectHeadOfficeReference = () => {
    if (!form.external_department_reference_id) {
        selectedReference.value = null;
        form.cost_center_source = 'manual';
        return;
    }

    const reference = props.hoRefs.find((item) => Number(item.id) === Number(form.external_department_reference_id));

    applyHeadOfficeReference(reference);
};

const selectStoreArea = () => {
    const area = props.storeAreaOptions.find((item) => item.area === form.area);

    form.external_department_reference_id = '';
    selectedReference.value = null;
    form.code = area?.code || '';
    form.name = area?.name || '';
    form.cost_center = '';
    form.cost_center_source = 'manual';
};

const useManualEntry = () => {
    form.external_department_reference_id = '';
    form.cost_center_source = 'manual';
    selectedReference.value = null;
};

const overrideCostCenter = () => {
    form.cost_center_source = 'manual';
};

const openModal = () => {
    isEditMode.value = false;
    editingId.value = null;
    form.defaults(initialFormState);
    form.reset();
    form.clearErrors();
    selectedReference.value = null;
    isModalOpen.value = true;
};

const openEditModal = (dept) => {
    isEditMode.value = true;
    editingId.value = dept.id;

    form.defaults({
        external_department_reference_id: dept.external_department_reference_id || '',
        code: dept.code || '',
        name: dept.name || '',
        type: dept.type || 'head_office',
        area: dept.area || (dept.type === 'store' ? '' : 'HO'),
        cost_center: dept.cost_center || '',
        cost_center_source: dept.cost_center_source || 'manual',
    });
    form.reset();
    form.clearErrors();

    selectedReference.value = dept.external_reference || null;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;

    setTimeout(() => {
        form.reset();
        form.clearErrors();
        selectedReference.value = null;
    }, 200);
};

const submit = () => {
    const options = {
        onSuccess: () => {
            closeModal();
            showToast(isEditMode.value ? 'Department successfully updated!' : 'Department successfully created!');
        },
        preserveScroll: true,
    };

    if (isEditMode.value) {
        form.put(route('admin.departments.update', editingId.value), options);
        return;
    }

    form.post(route('admin.departments.store'), options);
};

const confirmDelete = (dept) => {
    departmentToDelete.value = dept;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;

    setTimeout(() => {
        departmentToDelete.value = null;
        form.clearErrors();
    }, 200);
};

const deleteDepartment = () => {
    form.delete(route('admin.departments.destroy', departmentToDelete.value.id), {
        onSuccess: () => {
            closeDeleteModal();
            showToast('Department deleted successfully!');
        },
        onError: (errors) => {
            closeDeleteModal();
            showToast(errors.delete || 'Unable to delete this department.', 'error');
        },
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Departments" />

    <AppLayout>
        <div class="relative">
            <PageHeader title="Department Management" description="Create and manage departments." />

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <div
                        class="bg-white border-l-4 border-[#1369a8] shadow-sm rounded-r-xl p-5 flex items-center min-w-[220px]">
                        <div class="p-3 rounded-full bg-brand-blue-dark/10 text-brand-blue-dark mr-4">
                            <Building2 class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Departments</p>
                            <p class="text-2xl font-black text-gray-800">{{ totalDepartments }}</p>
                        </div>
                    </div>
                    <div
                        class="bg-white border-l-4 border-[#1d62c7] shadow-sm rounded-r-xl p-5 flex items-center min-w-[220px]">
                        <div class="p-3 rounded-full bg-brand-blue-darker/10 text-brand-blue-darker mr-4">
                            <Users class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Users Assigned</p>
                            <p class="text-2xl font-black text-gray-800">{{ totalAssignedUsers }}</p>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white rounded-lg shadow-sm p-4 flex flex-col md:flex-row justify-between items-center gap-4 border border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                        <div class="relative w-full sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Search class="h-5 w-5 text-gray-400" />
                            </div>
                            <input v-model="search" type="text" placeholder="Search users..."
                                class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-[#1d62c7] focus:border-[#1d62c7] sm:text-sm transition duration-150 ease-in-out" />
                        </div>

                        <button @click="openModal" class="btn-primary">
                            <Plus class="w-5 h-5 mr-2" /> Create Department
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="table-header">
                            <tr>
                                <th class="px-6 py-4">Code</th>
                                <th class="px-6 py-4">Department / Area</th>
                                <th class="px-6 py-4">Type</th>
                                <th class="px-6 py-4">Cost Center Rule</th>
                                <th class="px-6 py-4 text-center">Users</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="dept in displayDepartments" :key="dept.id"
                                class="hover:bg-blue-50/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ dept.code }}</td>
                                <td class="px-6 py-4 font-medium text-gray-700">{{ dept.name }}</td>
                                <td class="px-6 py-4">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-full text-xs font-semibold',
                                        dept.type === 'head_office' ? 'bg-purple-50 text-purple-700' : 'bg-brand-yellow/20 text-yellow-800'
                                    ]">
                                        {{ formatType(dept.type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-800">
                                    {{ dept.type === 'store' ? 'Selected per user/store' : (dept.cost_center ||
                                        'Manual') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-blue-dark/10 text-brand-blue-dark font-bold text-xs">
                                        {{ dept.users_count || 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button @click="openEditModal(dept)"
                                        class="text-brand-blue-dark hover:text-[#0b426e] transition-colors mr-3"
                                        title="Edit">
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(dept)"
                                        class="text-red-500 hover:text-red-700 transition-colors" title="Delete">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="displayDepartments.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    No departments found. Click "Create Department" to get started.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="props.departments.links" :from="props.departments.from" :to="props.departments.to"
                    :total="props.departments.total" :queryParams="{ search: search }" />
            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal">
            <div class="bg-brand-blue-dark px-6 py-4 border-b border-[#0b426e] flex items-center justify-between">
                <h2 class="text-lg font-black text-white flex items-center tracking-wide">
                    <Building2 class="w-5 h-5 mr-2 opacity-80" />
                    {{ isEditMode ? 'Update Department' : 'Create New Department' }}
                </h2>
                <button @click="closeModal" class="text-white/70 hover:text-white transition-colors">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submit" class="flex flex-col bg-white">
                <div class="p-6 bg-gray-50/50 overflow-y-auto max-h-[70vh] flex flex-col gap-5">
                    <div>
                        <InputLabel for="type" value="Department Type" />
                        <select id="type" v-model="form.type" @change="handleTypeChange"
                            class="mt-1 block w-full border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] rounded-md shadow-sm">
                            <option value="head_office">Head Office Function</option>
                            <option value="store">Store Area</option>
                        </select>
                        <InputError :message="form.errors.type" class="mt-2" />
                    </div>

                    <div v-if="form.type === 'head_office'">
                        <InputLabel for="ho_ref" value="Head Office Reference" />
                        <select id="ho_ref" v-model="form.external_department_reference_id"
                            @change="selectHeadOfficeReference"
                            class="mt-1 block w-full border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] rounded-md shadow-sm">
                            <option value="">Manual Entry</option>
                            <option v-for="reference in hoRefs" :key="reference.id" :value="reference.id">
                                {{ referenceLabel(reference) }}
                            </option>
                        </select>
                        <InputError :message="form.errors.external_department_reference_id" class="mt-2" />
                    </div>

                    <div v-if="form.type === 'store'">
                        <InputLabel for="area" value="Store Area" />
                        <select id="area" v-model="form.area" @change="selectStoreArea"
                            class="mt-1 block w-full border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] rounded-md shadow-sm">
                            <option value="">Select Area</option>
                            <option v-for="area in storeAreaOptions" :key="area.area" :value="area.area">
                                {{ area.code }} - {{ area.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.area" class="mt-2" />
                    </div>

                    <div v-if="hasExternalReference"
                        class="flex items-center justify-between rounded-md border border-green-100 bg-green-50 px-3 py-2">
                        <span class="text-sm font-semibold text-green-800">
                            {{ selectedReference ? referenceLabel(selectedReference) : 'External reference selected' }}
                        </span>
                        <button type="button" @click="useManualEntry"
                            class="inline-flex items-center text-sm font-bold text-green-800 hover:text-green-900">
                            <RotateCcw class="w-4 h-4 mr-1.5" /> Manual Entry
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <InputLabel for="code" value="Department Code / Area Number" />
                            <TextInput id="code" v-model="form.code" type="text"
                                :readonly="hasExternalReference || form.type === 'store'"
                                :class="['mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm', (hasExternalReference || form.type === 'store') ? 'bg-gray-100' : '']"
                                placeholder="e.g. HHR or Area 1" />
                            <InputError :message="form.errors.code" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="name" value="Department / Area Name" />
                            <TextInput id="name" v-model="form.name" type="text"
                                :readonly="hasExternalReference || form.type === 'store'"
                                :class="['mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm', (hasExternalReference || form.type === 'store') ? 'bg-gray-100' : '']"
                                placeholder="e.g. HRD or South Luzon" />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>
                    </div>

                    <div v-if="form.type === 'head_office'">
                        <InputLabel for="cost_center" value="Cost Center" />
                        <div class="relative mt-1">
                            <TextInput id="cost_center" v-model="form.cost_center" type="text"
                                :readonly="costCenterLocked"
                                :class="['block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm pr-28', costCenterLocked ? 'bg-gray-100' : '']"
                                placeholder="e.g. 80040" />
                            <span v-if="form.cost_center_source === 'external'"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                                External
                            </span>
                        </div>
                        <InputError :message="form.errors.cost_center" class="mt-2" />
                        <button v-if="costCenterLocked" type="button" @click="overrideCostCenter"
                            class="mt-2 text-sm font-bold text-[#1369a8] hover:text-[#0b426e]">
                            Override Cost Center
                        </button>
                    </div>

                    <div v-else
                        class="rounded-md border border-yellow-100 bg-yellow-50 px-3 py-2 text-sm font-semibold text-yellow-900">
                        Store area departments do not hold one cost center. The store and cost center are selected when
                        creating or editing a user.
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-100 flex justify-end space-x-3 border-t border-gray-200">
                    <button type="button" @click="closeModal"
                        class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-bold rounded-md shadow-sm transition-colors focus:outline-none">
                        <X class="w-4 h-4 mr-2" /> Cancel
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-md shadow-sm transition-colors disabled:opacity-50">
                        <Check class="w-4 h-4 mr-2" /> {{ isEditMode ? 'Save Changes' : 'Create Record' }}
                    </button>
                </div>
            </form>
        </Modal>

        <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                    <AlertTriangle class="w-6 h-6 text-red-600" />
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-black text-gray-900">Confirm Deletion</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Are you sure you want to delete <span class="font-bold text-gray-800">{{
                            departmentToDelete?.name }}</span>?<br>
                        This action cannot be undone.
                    </p>
                </div>
                <div class="flex justify-center space-x-4 mt-8">
                    <button @click="closeDeleteModal"
                        class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-md transition-colors focus:outline-none">
                        No, Keep it
                    </button>
                    <button @click="deleteDepartment" :disabled="form.processing"
                        class="inline-flex items-center px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-md shadow-sm transition-colors focus:ring-2 focus:ring-red-500 focus:outline-none disabled:opacity-50">
                        Yes, Delete
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
