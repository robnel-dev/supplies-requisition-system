<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, X, Check, Users as UsersIcon, UserCheck, Edit, Trash2, Key } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    users: Array,
    departments: Array,
});

const totalUsers = computed(() => props.users.length);
const activeUsers = computed(() => props.users.filter(u => u.is_active).length);

const isModalOpen = ref(false);

// 💡 NEW: Toast State Management
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
    email: '',
    role: 'requestor',
    department_id: '',
    cost_center: '',
    password: '',
    password_confirmation: '',
});

const openModal = () => isModalOpen.value = true;

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.post(route('admin.users.store'), {
        //  Trigger the toast ONLY when Inertia says the request was a full success
        onSuccess: () => {
            closeModal();
            showSuccessToast('User successfully created!');
        },
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Users" />

    <AppLayout>

        <div class="relative">

            <PageHeader title="User Management" description="Create and manage system users." />

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <div
                        class="bg-white border-l-4 border-[#1369a8] shadow-sm rounded-r-xl p-5 flex items-center min-w-[220px]">
                        <div class="p-3 rounded-full bg-[#1369a8]/10 text-[#1369a8] mr-4">
                            <UsersIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Users</p>
                            <p class="text-2xl font-black text-gray-800">{{ totalUsers }}</p>
                        </div>
                    </div>

                    <div
                        class="bg-white border-l-4 border-emerald-600 shadow-sm rounded-r-xl p-5 flex items-center min-w-[220px]">
                        <div class="p-3 rounded-full bg-emerald-50 text-emerald-600 mr-4">
                            <UserCheck class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Active Users</p>
                            <p class="text-2xl font-black text-gray-800">{{ activeUsers }}</p>
                        </div>
                    </div>
                </div>

                <button @click="openModal"
                    class="inline-flex items-center justify-center px-5 py-3 bg-[#1d62c7] hover:bg-[#1369a8] text-white text-sm font-bold rounded-lg shadow-md transition-colors focus:ring-2 focus:ring-[#1d62c7]/50 focus:outline-none whitespace-nowrap">
                    <Plus class="w-5 h-5 mr-2" />
                    Create User
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-[#1369a8] uppercase tracking-wider text-[11px] font-bold text-white">
                            <tr>
                                <th class="px-6 py-4">Account Name</th>
                                <th class="px-6 py-4">Email Address</th>
                                <th class="px-6 py-4">User Role</th>
                                <th class="px-6 py-4">Department / Area</th>
                                <th class="px-6 py-4">Cost Center</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-blue-50/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ user.name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ user.email }}</td>
                                <td class="px-6 py-4">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase',
                                        user.role === 'hr_admin' ? 'bg-[#1369a8] text-white' :
                                            user.role === 'approver' ? 'bg-brand-yellow/20 text-yellow-800' :
                                                'bg-blue-50 text-blue-700'
                                    ]">
                                        {{ user.role.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    {{ user.department ? `${user.department.name} (${user.department.code})` : '—' }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ user.cost_center }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="user.is_active"
                                        class="px-2 py-1 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-md text-[11px] font-bold uppercase tracking-wider">Active</span>
                                    <span v-else
                                        class="px-2 py-1 bg-red-50 text-red-600 border border-red-200 rounded-md text-[11px] font-bold uppercase tracking-wider">Disabled</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button class="text-[#1369a8] hover:text-[#0b426e] transition-colors mr-3"
                                        title="Edit User">
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button class="text-orange-500 hover:text-orange-600 transition-colors mr-3"
                                        title="Change Password">
                                        <Key class="w-4 h-4" />
                                    </button>
                                    <button class="text-red-500 hover:text-red-700 transition-colors"
                                        title="Disable/Delete">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    No users found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal">
            <div class="bg-[#1369a8] px-6 py-4 border-b border-[#0b426e] flex items-center justify-between">
                <h2 class="text-lg font-black text-white flex items-center tracking-wide">
                    <UsersIcon class="w-5 h-5 mr-2 opacity-80" />
                    Create New User
                </h2>
                <button @click="closeModal" class="text-white/70 hover:text-white transition-colors">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="p-6 bg-gray-50/50">
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <InputLabel for="name" value="Account Name" />
                        <TextInput id="name" v-model="form.name" type="text"
                            class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="email" value="Email Address" />
                        <TextInput id="email" v-model="form.email" type="email"
                            class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="role" value="User Role" />
                        <select id="role" v-model="form.role"
                            class="mt-1 block w-full border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] rounded-md shadow-sm">
                            <option value="requestor">Requestor</option>
                            <option value="approver">Approver</option>
                            <option value="hr_admin">HR Admin</option>
                        </select>
                        <InputError :message="form.errors.role" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="department_id" value="Department / Area" />
                        <select id="department_id" v-model="form.department_id"
                            class="mt-1 block w-full border-gray-300 focus:border-[#1d62c7] focus:ring-[#1d62c7] rounded-md shadow-sm">
                            <option value="">Select Department</option>
                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                {{ dept.name }} ({{ dept.code }})
                            </option>
                        </select>
                        <InputError :message="form.errors.department_id" class="mt-2" />
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel for="cost_center" value="Cost Center" />
                        <TextInput id="cost_center" v-model="form.cost_center" type="text"
                            class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                        <InputError :message="form.errors.cost_center" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="password" value="Password" />
                        <TextInput id="password" v-model="form.password" type="password"
                            class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="password_confirmation" value="Confirm Password" />
                        <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password"
                            class="mt-1 block w-full focus:border-[#1d62c7] focus:ring-[#1d62c7] shadow-sm" />
                    </div>

                    <div class="mt-4 pt-5 flex justify-end space-x-3 md:col-span-2 border-t border-gray-200">
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