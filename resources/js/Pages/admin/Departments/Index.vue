<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, X, Check, Building2, Users, Edit, Trash2, ChevronDown, AlertTriangle } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useToast } from '@/Composables/useToast';


const { showToast } = useToast();

const props = defineProps({
    departments: Array,
});

const totalDepartments = computed(() => props.departments.length);
const totalAssignedUsers = computed(() => {
    return props.departments.reduce((total, dept) => total + (dept.users_count || 0), 0);
});

// --- State Management ---
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isEditMode = ref(false);
const editingId = ref(null);
const departmentToDelete = ref(null);

// --- Toast State Management ---
const toast = ref({
    show: false,
    message: '',
    type: 'success'
});

// Define initial form state for clean resets
const initialFormState = {
    name: '',
    code: '',
    type: 'head_office',
};

const form = useForm({ ...initialFormState });

// --- Predefined Departments Data ---
const predefinedDepartments = [
    { code: 'HHR', name: 'Human Resources' }, { code: 'HIT', name: 'Information Technology' },
    { code: 'HAC', name: 'Accounting' }, { code: 'HEO', name: 'Executive Office' },
    { code: 'HAU', name: 'Audit' }, { code: 'HTR', name: 'Treasury' },
    { code: 'HWA', name: 'Warehouse' }, { code: 'HAS', name: 'Sales Operations' },
    { code: 'HVM', name: 'Visual Merchandising' }, { code: 'HMS', name: 'Marketing' },
    { code: 'HPS', name: 'Purchasing' }, { code: 'HLP', name: 'Loss Prevention' },
    { code: 'HPB', name: 'Planning & Budgeting' }, { code: 'HLD', name: 'Learning & Development' },
    { code: 'Area 1', name: 'South Luzon' }, { code: 'Area 2', name: 'North Luzon' },
    { code: 'Area 3', name: 'NCR 1' }, { code: 'Area 4', name: 'NCR 2' },
    { code: 'Area 5', name: 'Visayas' }, { code: 'Area 6', name: 'Mindanao' }
];

// --- Dropdown Logic ---
const showCodeDropdown = ref(false);
const showNameDropdown = ref(false);
const codeDropdownRef = ref(null);
const nameDropdownRef = ref(null);
const highlightedCodeIndex = ref(-1);
const highlightedNameIndex = ref(-1);

const handleClickOutside = (event) => {
    if (codeDropdownRef.value && !codeDropdownRef.value.contains(event.target)) {
        showCodeDropdown.value = false;
        highlightedCodeIndex.value = -1;
    }
    if (nameDropdownRef.value && !nameDropdownRef.value.contains(event.target)) {
        showNameDropdown.value = false;
        highlightedNameIndex.value = -1;
    }
};

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside));

const filteredCodes = computed(() => {
    if (!form.code) return predefinedDepartments;
    return predefinedDepartments.filter(d => d.code.toLowerCase().includes(form.code.toLowerCase()));
});

const filteredNames = computed(() => {
    if (!form.name) return predefinedDepartments;
    return predefinedDepartments.filter(d => d.name.toLowerCase().includes(form.name.toLowerCase()));
});

const selectPredefinedCode = (dept) => {
    form.code = dept.code;
    form.name = dept.name;
    showCodeDropdown.value = false;
    highlightedCodeIndex.value = -1;
};

const selectPredefinedName = (dept) => {
    form.name = dept.name;
    form.code = dept.code;
    showNameDropdown.value = false;
    highlightedNameIndex.value = -1;
};

const handleCodeInput = () => {
    showCodeDropdown.value = true;
    highlightedCodeIndex.value = -1;
    const match = predefinedDepartments.find(d => d.code.toLowerCase() === form.code.toLowerCase());
    if (match) form.name = match.name;
};

const handleNameInput = () => {
    showNameDropdown.value = true;
    highlightedNameIndex.value = -1;
    const match = predefinedDepartments.find(d => d.name.toLowerCase() === form.name.toLowerCase());
    if (match) form.code = match.code;
};

const handleCodeKeydown = (e) => {
    if (!showCodeDropdown.value && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
        showCodeDropdown.value = true;
        return;
    }
    if (!showCodeDropdown.value) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedCodeIndex.value = (highlightedCodeIndex.value + 1) % filteredCodes.value.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedCodeIndex.value = highlightedCodeIndex.value <= 0 ? filteredCodes.value.length - 1 : highlightedCodeIndex.value - 1;
    } else if (e.key === 'Enter' && highlightedCodeIndex.value > -1) {
        e.preventDefault();
        selectPredefinedCode(filteredCodes.value[highlightedCodeIndex.value]);
    } else if (e.key === 'Escape') {
        showCodeDropdown.value = false;
    }
};

const handleNameKeydown = (e) => {
    if (!showNameDropdown.value && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
        showNameDropdown.value = true;
        return;
    }
    if (!showNameDropdown.value) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedNameIndex.value = (highlightedNameIndex.value + 1) % filteredNames.value.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedNameIndex.value = highlightedNameIndex.value <= 0 ? filteredNames.value.length - 1 : highlightedNameIndex.value - 1;
    } else if (e.key === 'Enter' && highlightedNameIndex.value > -1) {
        e.preventDefault();
        selectPredefinedName(filteredNames.value[highlightedNameIndex.value]);
    } else if (e.key === 'Escape') {
        showNameDropdown.value = false;
    }
};

// --- CRUD Actions ---

const openModal = () => {
    isEditMode.value = false;
    editingId.value = null;

    // Explicitly set defaults back to empty before resetting
    form.defaults(initialFormState);
    form.reset();
    form.clearErrors();

    isModalOpen.value = true;
};

const openEditModal = (dept) => {
    isEditMode.value = true;
    editingId.value = dept.id;

    // Explicitly set defaults to the selected record so cancel/reset works properly
    form.defaults({
        name: dept.name,
        code: dept.code,
        type: dept.type,
    });
    form.reset();
    form.clearErrors();

    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    showCodeDropdown.value = false;
    showNameDropdown.value = false;
    highlightedCodeIndex.value = -1;
    highlightedNameIndex.value = -1;

    // Wait for exit animation to finish before clearing
    setTimeout(() => {
        form.reset();
        form.clearErrors();
    }, 200);
};

const submit = () => {
    if (isEditMode.value) {
        form.put(route('admin.departments.update', editingId.value), {
            onSuccess: () => {
                closeModal();
                showToast('Department successfully updated!');
            },
            preserveScroll: true,
        });
    } else {
        form.post(route('admin.departments.store'), {
            onSuccess: () => {
                closeModal();
                showToast('Department successfully created!');
            },
            preserveScroll: true,
        });
    }
};

// --- Delete Actions ---
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
            // Trigger error toast if backend policy prevents deletion
            if (errors.delete) {
                closeDeleteModal();
                showToast(errors.delete, 'error');
            }
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
                        <div class="p-3 rounded-full	bg-brand-blue-dark/10 	text-brand-blue-dark mr-4">
                            <Building2 class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Departments</p>
                            <p class="text-2xl font-black text-gray-800">{{ totalDepartments }}</p>
                        </div>
                    </div>
                    <div
                        class="bg-white border-l-4 border-[#1d62c7] shadow-sm rounded-r-xl p-5 flex items-center min-w-[220px]">
                        <div class="p-3 rounded-full bg-brand-blue-darker/10 	text-brand-blue-darker mr-4">
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
                    <button @click="openModal" class="btn-primary">
                        <Plus class="w-5 h-5 mr-2" /> Create Department
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="table-header">
                            <tr>
                                <th class="px-6 py-4">Department Code</th>
                                <th class="px-6 py-4">Department Name</th>
                                <th class="px-6 py-4">Location Type</th>
                                <th class="px-6 py-4 text-center">Assigned Users</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="dept in departments" :key="dept.id"
                                class="hover:bg-blue-50/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ dept.code }}</td>
                                <td class="px-6 py-4 font-medium text-gray-700">{{ dept.name }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="['px-2.5 py-1 rounded-full text-xs font-semibold', dept.type === 'head_office' ? 'bg-purple-50 text-purple-700' : 'bg-brand-yellow/20 text-yellow-800']">
                                        {{ dept.type.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-blue-dark/10 text-brand-blue-dark font-bold text-xs">
                                        {{ dept.users_count || 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button @click="openEditModal(dept)"
                                        class="	text-brand-blue-dark hover:text-[#0b426e] transition-colors mr-3"
                                        title="Edit">
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(dept)"
                                        class="text-red-500 hover:text-red-700 transition-colors" title="Delete">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="departments.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                    No departments found. Click "Create Department" to get started.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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

                <div class="p-6 bg-gray-50/50 overflow-y-auto max-h-[60vh] flex flex-col gap-5">

                    <div class="relative" ref="codeDropdownRef">
                        <InputLabel for="code" value="Department Code / Area Number" />
                        <div class="relative">
                            <TextInput id="code" v-model="form.code" @input="handleCodeInput"
                                @focus="showCodeDropdown = true" @click="showCodeDropdown = true"
                                @keydown="handleCodeKeydown" type="text"
                                class="mt-1 block w-full pr-10 border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm"
                                placeholder="Search or type custom code" autocomplete="off" />
                            <div
                                class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                <ChevronDown class="w-4 h-4" />
                            </div>
                        </div>

                        <div v-if="showCodeDropdown && filteredCodes.length > 0"
                            class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto custom-scrollbar">
                            <ul class="py-1">
                                <li v-for="(dept, index) in filteredCodes" :key="'dropdown-code-' + dept.code"
                                    @click="selectPredefinedCode(dept)" @mouseenter="highlightedCodeIndex = index"
                                    :class="['px-4 py-2 cursor-pointer text-sm flex justify-between items-center group transition-colors', highlightedCodeIndex === index ? 'bg-blue-100' : 'hover:bg-blue-50']">
                                    <span class="font-bold text-brand-blue-dark">{{ dept.code }}</span>
                                    <span class="text-gray-500 group-hover:text-gray-700">{{ dept.name }}</span>
                                </li>
                            </ul>
                        </div>
                        <InputError :message="form.errors.code" class="mt-2" />
                    </div>

                    <div class="relative" ref="nameDropdownRef">
                        <InputLabel for="name" value="Department / Area Name" />
                        <div class="relative">
                            <TextInput id="name" v-model="form.name" @input="handleNameInput"
                                @focus="showNameDropdown = true" @click="showNameDropdown = true"
                                @keydown="handleNameKeydown" type="text"
                                class="mt-1 block w-full pr-10 border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm"
                                placeholder="Search or type custom name" autocomplete="off" />
                            <div
                                class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                <ChevronDown class="w-4 h-4" />
                            </div>
                        </div>

                        <div v-if="showNameDropdown && filteredNames.length > 0"
                            class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto custom-scrollbar">
                            <ul class="py-1">
                                <li v-for="(dept, index) in filteredNames" :key="'dropdown-name-' + dept.code"
                                    @click="selectPredefinedName(dept)" @mouseenter="highlightedNameIndex = index"
                                    :class="['px-4 py-2 cursor-pointer text-sm flex justify-between items-center group transition-colors', highlightedNameIndex === index ? 'bg-blue-100' : 'hover:bg-blue-50']">
                                    <span class="font-bold text-gray-800">{{ dept.name }}</span>
                                    <span class="text-brand-blue-dark/70 text-xs font-semibold">{{ dept.code }}</span>
                                </li>
                            </ul>
                        </div>
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="type" value="Location Type" />
                        <select id="type" v-model="form.type"
                            class="mt-1 block w-full border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] rounded-md shadow-sm">
                            <option value="head_office">Head Office</option>
                            <option value="store">Store Area</option>
                        </select>
                        <InputError :message="form.errors.type" class="mt-2" />
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

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>