<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, X, Check, Building2, Users, Edit, Trash2, ChevronDown } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    departments: Array,
});

const totalDepartments = computed(() => props.departments.length);
const totalAssignedUsers = computed(() => {
    return props.departments.reduce((total, dept) => total + (dept.users_count || 0), 0);
});

const isModalOpen = ref(false);

//  Toast State Management
const toast = ref({
    show: false,
    message: ''
});

const showSuccessToast = (message) => {
    toast.value = { show: true, message };
    // Auto-hide the toast after 3 seconds
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
};

const form = useForm({
    name: '',
    code: '',
    type: 'head_office',
});

const predefinedDepartments = [
    { code: 'HHR', name: 'Human Resources' },
    { code: 'HIT', name: 'Information Technology' },
    { code: 'HAC', name: 'Accounting' },
    { code: 'HEO', name: 'Executive Office' },
    { code: 'HAU', name: 'Audit' },
    { code: 'HTR', name: 'Treasury' },
    { code: 'HWA', name: 'Warehouse' },
    { code: 'HAS', name: 'Sales Operations' },
    { code: 'HVM', name: 'Visual Merchandising' },
    { code: 'HMS', name: 'Marketing' },
    { code: 'HPS', name: 'Purchasing' },
    { code: 'HLP', name: 'Loss Prevention' },
    { code: 'HPB', name: 'Planning & Budgeting' },
    { code: 'HLD', name: 'Learning & Development' },
    { code: 'Area 1', name: 'South Luzon' },
    { code: 'Area 2', name: 'North Luzon' },
    { code: 'Area 3', name: 'NCR 1' },
    { code: 'Area 4', name: 'NCR 2' },
    { code: 'Area 5', name: 'Visayas' },
    { code: 'Area 6', name: 'Mindanao' }
];

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

const openModal = () => isModalOpen.value = true;

const closeModal = () => {
    isModalOpen.value = false;
    showCodeDropdown.value = false;
    showNameDropdown.value = false;
    highlightedCodeIndex.value = -1;
    highlightedNameIndex.value = -1;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.post(route('admin.departments.store'), {
        // Trigger the toast ONLY when Inertia says the request was a full success
        onSuccess: () => {
            closeModal();
            showSuccessToast('Department successfully created!');
        },
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Departments" />

    <AppLayout>
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-12 py-8 relative">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <div
                        class="bg-white border-l-4 border-[#1369a8] shadow-sm rounded-r-xl p-5 flex items-center min-w-[220px]">
                        <div class="p-3 rounded-full bg-[#1369a8]/10 text-[#1369a8] mr-4">
                            <Building2 class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Departments</p>
                            <p class="text-2xl font-black text-gray-800">{{ totalDepartments }}</p>
                        </div>
                    </div>
                    <div
                        class="bg-white border-l-4 border-[#1d62c7] shadow-sm rounded-r-xl p-5 flex items-center min-w-[220px]">
                        <div class="p-3 rounded-full bg-[#1d62c7]/10 text-[#1d62c7] mr-4">
                            <Users class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Users Assigned</p>
                            <p class="text-2xl font-black text-gray-800">{{ totalAssignedUsers }}</p>
                        </div>
                    </div>
                </div>

                <button @click="openModal"
                    class="inline-flex items-center justify-center px-5 py-3 bg-[#1d62c7] hover:bg-[#1369a8] text-white text-sm font-bold rounded-lg shadow-md transition-colors focus:ring-2 focus:ring-[#1d62c7]/50 focus:outline-none whitespace-nowrap">
                    <Plus class="w-5 h-5 mr-2" /> Create Department
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-[#1369a8] uppercase tracking-wider text-[11px] font-bold text-white">
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
                                        :class="['px-2.5 py-1 rounded-full text-xs font-semibold', dept.type === 'head_office' ? 'bg-purple-50 text-purple-700' : 'bg-brand-yellow/10 text-yellow-800']">
                                        {{ dept.type.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-[#1369a8]/10 text-[#1369a8] font-bold text-xs">{{
                                            dept.users_count || 0 }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button class="text-[#1369a8] hover:text-[#0b426e] transition-colors mr-3"
                                        title="Edit">
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button class="text-red-500 hover:text-red-700 transition-colors" title="Delete">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="departments.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400">No departments found. Click
                                    "Create Department" to get started.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal">
            <div class="bg-[#1369a8] px-6 py-4 border-b border-[#0b426e] flex items-center justify-between">
                <h2 class="text-lg font-black text-white flex items-center tracking-wide">
                    <Building2 class="w-5 h-5 mr-2 opacity-80" /> Create New Department
                </h2>
                <button @click="closeModal" class="text-white/70 hover:text-white transition-colors">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="p-6 bg-gray-50/50">
                <form @submit.prevent="submit">

                    <div class="mb-5 relative" ref="codeDropdownRef">
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
                                    :class="['px-4 py-2 cursor-pointer text-sm flex justify-between items-center group transition-colors',
                                        highlightedCodeIndex === index ? 'bg-blue-100' : 'hover:bg-blue-50']">
                                    <span class="font-bold text-[#1369a8]">{{ dept.code }}</span>
                                    <span class="text-gray-500 group-hover:text-gray-700">{{ dept.name }}</span>
                                </li>
                            </ul>
                        </div>
                        <InputError :message="form.errors.code" class="mt-2" />
                    </div>

                    <div class="mb-5 relative" ref="nameDropdownRef">
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
                                    :class="['px-4 py-2 cursor-pointer text-sm flex justify-between items-center group transition-colors',
                                        highlightedNameIndex === index ? 'bg-blue-100' : 'hover:bg-blue-50']">
                                    <span class="font-bold text-gray-800">{{ dept.name }}</span>
                                    <span class="text-[#1369a8]/70 text-xs font-semibold">{{ dept.code }}</span>
                                </li>
                            </ul>
                        </div>
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div class="mb-8">
                        <InputLabel for="type" value="Location Type" />
                        <select id="type" v-model="form.type"
                            class="mt-1 block w-full border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] rounded-md shadow-sm">
                            <option value="head_office">Head Office</option>
                            <option value="store">Store Area</option>
                        </select>
                        <InputError :message="form.errors.type" class="mt-2" />
                    </div>

                    <div class="flex justify-end space-x-3 pt-5 border-t border-gray-200">
                        <button type="button" @click="closeModal"
                            class="inline-flex items-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-md shadow-sm transition-colors focus:ring-2 focus:ring-red-500 focus:outline-none">
                            <X class="w-4 h-4 mr-2" /> Cancel
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="inline-flex items-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-md shadow-sm transition-colors focus:ring-2 focus:ring-green-500 focus:outline-none disabled:opacity-50">
                            <Check class="w-4 h-4 mr-2" /> Create Record
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <Transition enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="toast.show"
                class="fixed top-6 right-6 z-[100] flex items-center w-full max-w-sm p-4 space-x-3 text-gray-800 bg-white border-l-4 border-green-500 rounded-lg shadow-[0_4px_20px_rgba(0,0,0,0.15)]">
                <div
                    class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-600 bg-green-100 rounded-lg">
                    <Check class="w-5 h-5" />
                </div>
                <div class="text-sm font-bold">{{ toast.message }}</div>
                <button @click="toast.show = false"
                    class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8 items-center justify-center transition-colors">
                    <X class="w-4 h-4" />
                </button>
            </div>
        </Transition>
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