<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
// Added 'Archive' icon for Supplies Management
import { LayoutDashboard, Package, CheckSquare, Users, Building, X, BaggageClaim  } from 'lucide-vue-next';
import NavItem from '@/Components/Layout/NavItem.vue';

const props = defineProps({
    isMobileOpen: Boolean
});
const emit = defineEmits(['close-mobile']);

const roleLabels = {
    requestor: 'Requestor',
    approver: 'Approver',
    hr_admin: 'HR Admin'
};

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => user.value?.role ?? 'requestor');

const formattedUserRole = computed(() => {
    return roleLabels[userRole.value] || userRole.value;
});


const allMenuOptions = [
    { name: 'Dashboard', href: route('dashboard'), active: route().current('dashboard'), icon: LayoutDashboard, roles: ['requestor', 'approver', 'hr_admin'] },
    { name: 'Supplies Catalog', href: route('requestor.catalog.index'), active: route().current('requestor.catalog.index'), icon: Package, roles: ['requestor'] },
    { name: 'Approval Queue', href: '#', active: route().current('approvals.queue'), icon: CheckSquare, roles: ['approver'] },

    // Admin Routes Connected Here
    { name: 'Supplies', href: route('admin.supplies.index'), active: route().current('admin.supplies.*'), icon: BaggageClaim , roles: ['hr_admin'] },
    { name: 'Departments', href: route('admin.departments.index'), active: route().current('admin.departments.*'), icon: Building, roles: ['hr_admin'] },
    { name: 'Users', href: route('admin.users.index'), active: route().current('admin.users.*'), icon: Users, roles: ['hr_admin'] },
];

const filteredNavigation = computed(() => {
    return allMenuOptions.filter(item => item.roles.includes(userRole.value));
});
</script>

<template>
    <div v-show="isMobileOpen" class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden transition-opacity"
        @click="emit('close-mobile')">
    </div>

    <aside :class="[
        isMobileOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full',
        'fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-gray-200/60 shadow-[4px_0_24px_rgba(0,0,0,0.02)] transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 flex flex-col'
    ]">

        <div class="flex items-center gap-3 sm:gap-4 h-24 px-4 sm:px-6 border-b border-gray-100 bg-white">

            <svg class="w-10 h-10 sm:w-12 sm:h-12 flex-shrink-0 drop-shadow-sm" viewBox="0 0 40 40" fill="none"
                xmlns="http://www.w3.org/2000/svg">

                <rect x="2" y="2" width="22" height="28" rx="2" class="fill-brand-navy" />
                <rect x="9" y="0" width="8" height="4" rx="1" class="fill-brand-yellow" />
                <rect x="4" y="6" width="18" height="22" rx="1" fill="white" />

                <rect x="7" y="10" width="10" height="2" rx="1" class="fill-brand-navy" opacity="0.15" />
                <rect x="7" y="14" width="7" height="2" rx="1" class="fill-brand-navy" opacity="0.15" />
                <rect x="7" y="18" width="12" height="2" rx="1" class="fill-brand-navy" opacity="0.15" />

                <path d="M 14 9 L 16 11 L 20 6" stroke="#0EA5E9" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
                <path d="M 14 17 L 16 19 L 20 14" stroke="#0EA5E9" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />

                <rect x="16" y="18" width="16" height="6" class="fill-brand-navy" />
                <rect x="14" y="24" width="20" height="14" rx="1" class="fill-brand-blue" />

                <path d="M 14 24 L 10 16 H 20 L 24 24 Z" class="fill-brand-blue" opacity="0.85" />
                <path d="M 24 24 L 28 16 H 38 L 34 24 Z" class="fill-brand-blue" opacity="0.85" />

                <rect x="22" y="24" width="4" height="14" class="fill-brand-yellow" />
                <rect x="16" y="27" width="4" height="3" rx="0.5" fill="white" opacity="0.9" />

            </svg>

            <div class="flex flex-col justify-center flex-1 min-w-0">
                <span
                    class="text-xl sm:text-[22px] font-black text-brand-navy leading-none tracking-tight mb-1 sm:mb-1.5">
                    SUPPLIES
                </span>
                <span class="text-[10px] sm:text-xs font-semibold text-brand-blue/80 uppercase tracking-[0.15em] mt-1">
                    Requisition System
                </span>
            </div>

            <button class="lg:hidden ml-auto text-gray-400 hover:text-brand-navy transition-colors flex-shrink-0 p-1"
                @click="emit('close-mobile')">
                <X class="w-6 h-6" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-2">
            <div class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-4 px-3">
                Main Menu
            </div>
            <NavItem v-for="item in filteredNavigation" :key="item.name" :item="item" />
        </nav>

        <div class="p-4 border-t border-gray-100 bg-gray-50/50 m-4 rounded-xl">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-full bg-brand-navy flex items-center justify-center text-white font-bold text-sm shadow-inner">
                    {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                </div>
                <div class="flex flex-col overflow-hidden">
                    <span class="text-sm font-bold text-gray-900 truncate">{{ user?.name || 'Guest User' }}</span>
                    <span class="text-xs font-medium text-brand-blue capitalize">{{ formattedUserRole }}</span>
                </div>
            </div>
        </div>
    </aside>
</template>