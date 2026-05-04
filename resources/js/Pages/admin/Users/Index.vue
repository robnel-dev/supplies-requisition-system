<script setup>
import { ref, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Plus, X, Check, Users as UsersIcon, UserCheck, Edit, Trash2, Key, Search, AlertTriangle } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    users: Object,
    departments: Array,
    filters: Object,
    stats: Object,
});

// --- State Management ---
const isModalOpen = ref(false);
const isPasswordModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isEditMode = ref(false);
const editingId = ref(null);
const userToManage = ref(null);

// --- Search Logic (Debounced) ---
const search = ref(props.filters.search || '');
let searchTimeout = null;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('admin.users.index'),
            { search: value },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300);
});

// --- Toast State Management ---
const toast = ref({ show: false, message: '', type: 'success' });
const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

// --- Forms Initial States ---
const initialFormState = {
    name: '',
    email: '',
    role: 'requestor',
    department_id: '',
    cost_center: '',
    password: '',
    password_confirmation: '',
};

const initialPasswordState = {
    password: '',
    password_confirmation: '',
};

// Initialize Forms
const form = useForm({ ...initialFormState });
const passwordForm = useForm({ ...initialPasswordState });

// --- Main Modal Actions ---
const openCreateModal = () => {
    isEditMode.value = false;
    editingId.value = null;
    
    // Force defaults back to empty before resetting
    form.defaults(initialFormState);
    form.reset();
    form.clearErrors();
    
    isModalOpen.value = true;
};

const openEditModal = (user) => {
    isEditMode.value = true;
    editingId.value = user.id;
    
    // Set defaults to the selected user's data
    form.defaults({
        name: user.name,
        email: user.email,
        role: user.role,
        department_id: user.department_id || '',
        cost_center: user.cost_center || '',
        password: '',
        password_confirmation: '',
    });
    form.reset();
    form.clearErrors();
    
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => {
        form.reset();
        form.clearErrors();
    }, 200);
};

const submit = () => {
    if (isEditMode.value) {
        form.transform((data) => {
            const { password, password_confirmation, ...editData } = data;
            return editData;
        }).put(route('admin.users.update', editingId.value), {
            onSuccess: () => {
                closeModal();
                showToast('User updated successfully!');
            },
            preserveScroll: true,
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => {
                closeModal();
                showToast('User created successfully!');
            },
            preserveScroll: true,
        });
    }
};

// --- Password Actions ---
const openPasswordModal = (user) => {
    userToManage.value = user;
    passwordForm.defaults(initialPasswordState);
    passwordForm.reset();
    passwordForm.clearErrors();
    isPasswordModalOpen.value = true;
};

const closePasswordModal = () => {
    isPasswordModalOpen.value = false;
    setTimeout(() => { userToManage.value = null; }, 200);
};

const submitPassword = () => {
    passwordForm.put(route('admin.users.password', userToManage.value.id), {
        onSuccess: () => {
            closePasswordModal();
            showToast('Password updated successfully!');
        },
        preserveScroll: true,
    });
};

// --- Delete Actions ---
const confirmDelete = (user) => {
    userToManage.value = user;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    setTimeout(() => { userToManage.value = null; }, 200);
};

const deleteUser = () => {
    router.delete(route('admin.users.destroy', userToManage.value.id), {
        onSuccess: () => {
            closeDeleteModal();
            showToast('User deleted successfully!');
        },
        onError: (errors) => {
            closeDeleteModal();
            if (errors.delete) {
                showToast(errors.delete, 'error');
            } else {
                showToast('An error occurred.', 'error');
            }
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Users" />

    <AppLayout>
        <div class="relative">
            <PageHeader title="User Management" description="Create and manage employee and manager accounts" />

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <div class="bg-white border-l-4 border-[#1369a8] shadow-sm rounded-r-xl p-5 flex items-center min-w-[220px]">
                        <div class="p-3 rounded-full bg-[#1369a8]/10 text-[#1369a8] mr-4">
                            <UsersIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Users</p>
                            <p class="text-2xl font-black text-gray-800">{{ stats.total }}</p> 
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="h-5 w-5 text-gray-400" />
                        </div>
                        <input v-model="search" type="text" placeholder="Search users..." 
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-[#1d62c7] focus:border-[#1d62c7] sm:text-sm transition duration-150 ease-in-out" />
                    </div>

                    <button @click="openCreateModal" class="inline-flex items-center justify-center px-5 py-2.5 bg-[#1d62c7] hover:bg-[#1369a8] text-white text-sm font-bold rounded-lg shadow-md transition-colors focus:ring-2 focus:ring-[#1d62c7]/50 focus:outline-none whitespace-nowrap">
                        <Plus class="w-5 h-5 mr-2" /> Create User
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] overflow-hidden flex flex-col">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-[#1369a8] uppercase tracking-wider text-[11px] font-bold text-white">
                            <tr>
                                <th class="px-6 py-4">Account Name</th>
                                <th class="px-6 py-4">Email Address</th>
                                <th class="px-6 py-4">User Role</th>
                                <th class="px-6 py-4">Department / Area</th>
                                <th class="px-6 py-4">Cost Center</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-blue-50/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ user.name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ user.email }}</td>
                                <td class="px-6 py-4">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase',
                                        user.role === 'hr_admin' ? 'bg-[#1369a8] text-white' :
                                            user.role === 'approver' ? 'bg-[#fcb503]/20 text-yellow-800' :
                                                'bg-blue-50 text-blue-700'
                                    ]">
                                        {{ user.role.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    {{ user.department ? `${user.department.name} (${user.department.code})` : '—' }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ user.cost_center || '—' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <button @click="openEditModal(user)" class="text-[#1369a8] hover:text-[#0b426e] transition-colors mr-3" title="Edit User">
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button @click="openPasswordModal(user)" class="text-orange-500 hover:text-orange-600 transition-colors mr-3" title="Change Password">
                                        <Key class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(user)" class="text-red-500 hover:text-red-700 transition-colors" title="Delete User">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    No users found matching your search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="users.links.length > 3" class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        Showing <span class="font-bold text-gray-900">{{ users.from || 0 }}</span> to <span class="font-bold text-gray-900">{{ users.to || 0 }}</span> of <span class="font-bold text-gray-900">{{ users.total }}</span> results
                    </p>
                    <div class="flex flex-wrap shadow-sm rounded-md">
                        <template v-for="(link, index) in users.links" :key="index">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm text-gray-400 border border-gray-200 rounded" v-html="link.label"></div>
                            <button v-else @click.prevent="router.get(link.url, { search: search }, { preserveScroll: true })" 
                                :class="['mr-1 mb-1 px-4 py-2 text-sm border rounded hover:bg-gray-100 focus:border-[#1d62c7] focus:text-[#1d62c7] transition-colors', link.active ? 'bg-[#1369a8] text-white border-[#1369a8] hover:bg-[#0b426e]' : 'bg-white text-gray-700 border-gray-300']" 
                                v-html="link.label">
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal">
            <div class="bg-[#1369a8] px-6 py-4 border-b border-[#0b426e] flex items-center justify-between">
                <h2 class="text-lg font-black text-white flex items-center tracking-wide">
                    <UsersIcon class="w-5 h-5 mr-2 opacity-80" /> 
                    {{ isEditMode ? 'Update User Information' : 'Create New User' }}
                </h2>
                <button @click="closeModal" class="text-white/70 hover:text-white transition-colors">
                    <X class="w-5 h-5" />
                </button>
            </div>
            
            <form @submit.prevent="submit" class="flex flex-col bg-white">
                
                <div class="p-6 bg-gray-50/50 overflow-y-auto max-h-[70vh] flex flex-col gap-5">
                    <div>
                        <InputLabel for="name" value="Account Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="email" value="Email Address" />
                        <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="role" value="User Role" />
                        <select id="role" v-model="form.role" class="mt-1 block w-full border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] rounded-md shadow-sm">
                            <option value="requestor">Requestor</option>
                            <option value="approver">Approver</option>
                            <option value="hr_admin">HR Admin</option>
                        </select>
                        <InputError :message="form.errors.role" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="department_id" value="Department / Area" />
                        <select id="department_id" v-model="form.department_id" class="mt-1 block w-full border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] rounded-md shadow-sm">
                            <option value="">Select Department</option>
                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                {{ dept.name }} ({{ dept.code }})
                            </option>
                        </select>
                        <InputError :message="form.errors.department_id" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="cost_center" value="Cost Center" />
                        <TextInput id="cost_center" v-model="form.cost_center" type="text" class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                        <InputError :message="form.errors.cost_center" class="mt-2" />
                    </div>
                    
                    <template v-if="!isEditMode">
                        <div>
                            <InputLabel for="password" value="Password" />
                            <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                            <InputError :message="form.errors.password" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="password_confirmation" value="Confirm Password" />
                            <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                        </div>
                    </template>
                </div>

                <div class="px-6 py-4 bg-gray-100 flex justify-end space-x-3 border-t border-gray-200">
                    <button type="button" @click="closeModal" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-bold rounded-md shadow-sm transition-colors focus:outline-none">
                        <X class="w-4 h-4 mr-2" /> Cancel
                    </button>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-md shadow-sm transition-colors disabled:opacity-50">
                        <Check class="w-4 h-4 mr-2" /> {{ isEditMode ? 'Save Changes' : 'Create Record' }}
                    </button>
                </div>

            </form>
        </Modal>

        <Modal :show="isPasswordModalOpen" @close="closePasswordModal" maxWidth="sm">
            <div class="bg-[#1369a8] px-6 py-4 border-b border-[#0b426e] flex items-center justify-between">
                <h2 class="text-lg font-black text-white flex items-center tracking-wide">
                    <Key class="w-5 h-5 mr-2 opacity-80" /> Change Password
                </h2>
                <button @click="closePasswordModal" class="text-white/70 hover:text-white transition-colors">
                    <X class="w-5 h-5" />
                </button>
            </div>
            
            <form @submit.prevent="submitPassword" class="flex flex-col bg-white">
                <div class="p-6 bg-gray-50/50 overflow-y-auto max-h-[60vh] flex flex-col gap-5">
                    <div class="bg-blue-50 text-blue-800 text-sm p-3 rounded-md border border-blue-100">
                        Resetting password for: <span class="font-bold">{{ userToManage?.name }}</span>
                    </div>

                    <div>
                        <InputLabel for="new_password" value="New Password" />
                        <TextInput id="new_password" v-model="passwordForm.password" type="password" class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                        <InputError :message="passwordForm.errors.password" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="new_password_confirmation" value="Confirm New Password" />
                        <TextInput id="new_password_confirmation" v-model="passwordForm.password_confirmation" type="password" class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-100 flex justify-end space-x-3 border-t border-gray-200">
                    <button type="button" @click="closePasswordModal" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-bold rounded-md shadow-sm transition-colors focus:outline-none">
                        <X class="w-4 h-4 mr-2" /> Cancel
                    </button>
                    <button type="submit" :disabled="passwordForm.processing" class="inline-flex items-center px-4 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold rounded-md shadow-sm transition-colors disabled:opacity-50">
                        <Check class="w-4 h-4 mr-2" /> Update Password
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
                    <h3 class="text-lg font-black text-gray-900">Delete User Account</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Are you sure you want to delete <span class="font-bold text-gray-800">{{ userToManage?.name }}</span>?<br>
                        This action is permanent and cannot be undone.
                    </p>
                </div>
                <div class="flex justify-center space-x-4 mt-8">
                    <button @click="closeDeleteModal" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-md transition-colors focus:outline-none">Cancel</button>
                    <button @click="deleteUser" class="px-6 py-2.5 text-white text-sm font-bold rounded-md shadow-sm bg-red-600 hover:bg-red-700 focus:ring-red-500 transition-colors">
                        Yes, Delete
                    </button>
                </div>
            </div>
        </Modal>

        <Transition enter-active-class="transform transition duration-300" enter-from-class="translate-y-2 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="toast.show" :class="['fixed top-6 right-6 z-[100] flex items-center p-4 space-x-3 bg-white border-l-4 rounded-lg shadow-lg', toast.type === 'error' ? 'border-red-500' : 'border-green-500']">
                <div :class="['w-8 h-8 flex items-center justify-center rounded-lg', toast.type === 'error' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600']">
                    <AlertTriangle v-if="toast.type === 'error'" class="w-5 h-5" />
                    <Check v-else class="w-5 h-5" />
                </div>
                <div class="text-sm font-bold text-gray-800">{{ toast.message }}</div>
            </div>
        </Transition>
    </AppLayout>
</template>