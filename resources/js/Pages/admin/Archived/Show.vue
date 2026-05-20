<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Archive,
    ArrowLeft,
    Ban,
    Building2,
    CalendarDays,
    CheckCheck,
    Clock,
    Edit3,
    FileText,
    Hash,
    Package,
    RefreshCcw,
    Send,
    Trash2,
    Truck,
    User,
    XCircle,
} from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatRequestDepartment } from '@/Utils/requestDisplay';

const props = defineProps({
    request: Object,
});

const timelineIcons = {
    submitted: { icon: Send, color: 'bg-blue-500', ring: 'ring-blue-100' },
    approved: { icon: CheckCheck, color: 'bg-green-500', ring: 'ring-green-100' },
    rejected: { icon: XCircle, color: 'bg-red-500', ring: 'ring-red-100' },
    released: { icon: Package, color: 'bg-emerald-500', ring: 'ring-emerald-100' },
    archived: { icon: Archive, color: 'bg-gray-400', ring: 'ring-gray-100' },
    cancelled: { icon: Ban, color: 'bg-gray-400', ring: 'ring-gray-100' },
    reopened: { icon: RefreshCcw, color: 'bg-orange-400', ring: 'ring-orange-100' },
    item_removed: { icon: Trash2, color: 'bg-red-500', ring: 'ring-red-100' },
    release_details_updated: { icon: FileText, color: 'bg-sky-500', ring: 'ring-sky-100' },
};

const getTimelineConfig = (action) =>
    timelineIcons[action] ?? { icon: Clock, color: 'bg-gray-400', ring: 'ring-gray-100' };

const formatDate = (date) => {
    if (!date) return '-';

    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDateShort = (date) => {
    if (!date) return '-';

    return new Date(date).toLocaleDateString('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const budgetBadgeClass = (budgetType) =>
    budgetType === 'budgeted'
        ? 'bg-green-100 text-green-700'
        : 'bg-orange-100 text-orange-700';

const items = computed(() => props.request.items ?? []);
const timeline = computed(() => props.request.timelines ?? []);
const requestDepartmentLabel = computed(() => formatRequestDepartment(props.request));
</script>

<template>
    <Head :title="`Archived ${request.transaction_id}`" />

    <AppLayout>
        <div class="mb-6">
            <Link :href="route('admin.archived.index')"
                class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-blue-dark transition-colors">
                <ArrowLeft class="w-4 h-4" />
                Back to Archived Requests
            </Link>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">Archived Request Details</h1>
                <p class="text-sm text-gray-500 mt-1 font-mono tracking-wide">
                    {{ request.transaction_id }}
                </p>
            </div>

            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border leading-none self-start sm:self-center bg-gray-100 text-gray-500 border-gray-200">
                <Archive class="w-3.5 h-3.5" />
                Archived
            </span>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 flex flex-col gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="flex items-center justify-between bg-brand-blue-dark px-6 py-4">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <FileText class="w-4 h-4 opacity-80" />
                            Request Information
                        </h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <Hash class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Control Number</p>
                                <p class="text-sm font-bold text-gray-900 font-mono mt-0.5">
                                    {{ request.transaction_id }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <CalendarDays class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Request Date</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ formatDate(request.request_date) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <User class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Requestor</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ request.user?.name ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <Building2 class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Department / Store</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ requestDepartmentLabel }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <CheckCheck class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Approved By</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ request.manager_approver?.name ?? request.approver?.name ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ formatDate(request.manager_approved_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <Truck class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Released By</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ request.hr_admin_releaser?.name ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ formatDate(request.hr_admin_released_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <Archive class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Archived By</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ request.archiver?.name ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ formatDate(request.archived_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-emerald-50 text-emerald-700 mt-0.5">
                                <Hash class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">M3 RO Number</p>
                                <p class="text-sm font-bold text-gray-900 font-mono mt-0.5">
                                    {{ request.m3_ro_number ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-emerald-50 text-emerald-700 mt-0.5">
                                <Hash class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">M3 DR Number</p>
                                <p class="text-sm font-bold text-gray-900 font-mono mt-0.5">
                                    {{ request.m3_dr_number ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-brand-blue-dark px-6 py-4">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <Package class="w-4 h-4 opacity-80" />
                            Released Items
                            <span class="ml-auto bg-white/20 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ items.length }} item{{ items.length !== 1 ? 's' : '' }}
                            </span>
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm whitespace-nowrap">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <th class="px-5 py-3 text-left">#</th>
                                    <th class="px-5 py-3 text-left">Item Code</th>
                                    <th class="px-5 py-3 text-left">Description</th>
                                    <th class="px-5 py-3 text-center">Approved Qty</th>
                                    <th class="px-5 py-3 text-center">Unit</th>
                                    <th class="px-5 py-3 text-center">Budget Type</th>
                                    <th class="px-5 py-3 text-center">HRD Allocation</th>
                                    <th class="px-5 py-3 text-center">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, i) in items" :key="item.id" class="hover:bg-gray-50/60">
                                    <td class="px-5 py-3.5 text-gray-400 font-medium text-xs">{{ i + 1 }}</td>
                                    <td class="px-5 py-3.5">
                                        <span class="font-mono text-xs font-bold text-brand-blue-dark">
                                            {{ item.item_code || item.supply?.item_code }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-700">
                                        {{ item.item_description || item.supply?.item_description || '-' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="font-bold text-gray-900">{{ item.quantity }}</span>
                                        <span v-if="item.original_quantity && item.original_quantity !== item.quantity"
                                            class="block text-xs text-gray-400">
                                            was {{ item.original_quantity }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center text-gray-600 capitalize">
                                        {{ item.item_unit || item.supply?.unit || '-' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span v-if="item.budget_type" :class="[
                                            'px-2 py-1 rounded text-xs font-bold uppercase',
                                            budgetBadgeClass(item.budget_type)
                                        ]">
                                            {{ item.budget_type }}
                                        </span>
                                        <span v-else class="text-xs text-gray-400 italic">-</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-bold text-gray-900">
                                        {{ item.allocated_quantity ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-bold text-gray-900">
                                        {{ item.balance ?? '-' }}
                                    </td>
                                </tr>
                                <tr v-if="items.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                        No items in this request.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden sticky top-6">
                    <div class="bg-brand-blue-dark px-6 py-4">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <Clock class="w-4 h-4 opacity-80" />
                            Request Timeline
                        </h2>
                    </div>

                    <div class="p-6">
                        <div v-if="timeline.length > 0" class="relative">
                            <div class="absolute left-4 top-4 bottom-4 w-0.5 bg-gray-200" aria-hidden="true" />
                            <div class="flex flex-col gap-6">
                                <div v-for="event in timeline" :key="event.id" class="relative flex gap-4 items-start">
                                    <div :class="[
                                        'relative z-10 flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ring-4',
                                        getTimelineConfig(event.action).color,
                                        getTimelineConfig(event.action).ring,
                                    ]">
                                        <component :is="getTimelineConfig(event.action).icon" class="w-4 h-4 text-white" />
                                    </div>
                                    <div class="flex-1 min-w-0 pb-1">
                                        <p class="text-sm font-semibold text-gray-900 leading-snug">
                                            {{ event.description }}
                                        </p>
                                        <p v-if="event.performer" class="text-xs text-gray-500 mt-0.5">
                                            by {{ event.performer.name }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1 font-mono">
                                            {{ formatDateShort(event.created_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-gray-400">
                            <Clock class="w-8 h-8 mx-auto mb-2 opacity-30" />
                            <p class="text-sm">No timeline events yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
