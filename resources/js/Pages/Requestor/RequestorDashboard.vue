<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Package, ClipboardList, CheckCircle2, Truck,
    XCircle, ArrowRight, Clock, PlusCircle, Archive,
    AlertCircle, FileText, ShoppingCart
} from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    dashboardStats: {
        type: Object,
        default: () => ({}),
    },
    recentActivity: {
        type: Array,
        default: () => [],
    },
});

const s = computed(() => props.dashboardStats);

// ── Status configuration ──────────────────────────────────────────────────────
const statusConfig = {
    draft: { label: 'Draft', color: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400', icon: FileText },
    pending_approval: { label: 'Pending Approval', color: 'bg-amber-100 text-amber-700', dot: 'bg-amber-400', icon: Clock },
    approved: { label: 'Approved', color: 'bg-blue-100 text-blue-700', dot: 'bg-blue-500', icon: CheckCircle2 },
    rejected: { label: 'Rejected', color: 'bg-red-100 text-red-700', dot: 'bg-red-500', icon: XCircle },
    released: { label: 'Released', color: 'bg-green-100 text-green-700', dot: 'bg-green-500', icon: Truck },
    cancelled: { label: 'Cancelled', color: 'bg-gray-100 text-gray-500', dot: 'bg-gray-300', icon: XCircle },
    archived: { label: 'Archived', color: 'bg-purple-100 text-purple-700', dot: 'bg-purple-400', icon: Archive },
};
const getStatus = (key) => statusConfig[key] ?? { label: key, color: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400', icon: FileText };

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
};

// Request lifecycle steps for visual tracker
const lifecycleSteps = [
    { key: 'draft', label: 'Draft' },
    { key: 'pending_approval', label: 'Submitted' },
    { key: 'approved', label: 'Approved' },
    { key: 'released', label: 'Released' },
];

const getStepForStatus = (status) => {
    const map = { draft: 0, pending_approval: 1, approved: 2, released: 3 };
    return map[status] ?? -1;
};

// Most recent active request for the status tracker
const latestActiveRequest = computed(() => {
    return props.recentActivity.find(r =>
        ['draft', 'pending_approval', 'approved'].includes(r.status)
    );
});
</script>

<template>
    <div class="space-y-6">
        <!-- ── Page Header ─────────────────────────────────────────────────── -->
        <PageHeader title="My Dashboard" description="Track your supply requests, submissions, and delivery status." />

        <!-- ── Draft CTA Banner (shown when draft exists) ─────────────────── -->
        <Transition enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
            <div v-if="s.hasDraft"
                class="relative bg-gradient-to-r from-brand-navy to-blue-700 rounded-2xl p-5 shadow-lg overflow-hidden">
                <!-- Decorative blob -->
                <div class="absolute -right-8 -top-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none" />
                <div class="absolute -right-2 top-8 w-24 h-24 rounded-full bg-white/5 pointer-events-none" />

                <div class="relative flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-white/15 flex-shrink-0">
                        <ShoppingCart class="w-6 h-6 text-white" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white">You have a draft request</p>
                        <p class="text-xs text-white/70 mt-0.5">
                            {{ s.draftItemCount }} item{{ s.draftItemCount !== 1 ? 's' : '' }} in your cart — ready to
                            submit
                        </p>
                    </div>
                    <Link :href="route('requestor.catalog.index')"
                        class="flex-shrink-0 inline-flex items-center gap-2 bg-brand-yellow text-brand-navy text-sm font-bold px-4 py-2 rounded-xl hover:bg-yellow-400 transition-all active:scale-95 shadow-md">
                        Continue
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                </div>
            </div>
        </Transition>

        <!-- ── Approved Ready-for-Pickup Alert ───────────────────────────── -->
        <Transition enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
            <div v-if="(s.approved ?? 0) > 0"
                class="flex items-center gap-4 bg-blue-50 border border-blue-200 rounded-2xl p-4">
                <div class="p-2.5 rounded-xl bg-blue-100 flex-shrink-0">
                    <CheckCircle2 class="w-5 h-5 text-blue-600 animate-bounce" />
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-blue-800">
                        {{ s.approved }} request{{ s.approved !== 1 ? 's' : '' }} approved!
                    </p>
                    <p class="text-xs text-blue-600 mt-0.5">Your request(s) are approved and awaiting release by HR.</p>
                </div>
                <Link :href="route('requestor.requests.index')"
                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1 group">
                    View
                    <ArrowRight class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" />
                </Link>
            </div>
        </Transition>

        <!-- ── KPI Cards ───────────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3">

            <div
                class="group bg-white rounded-2xl p-4 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md hover:-translate-y-0.5 border-l-4 border-amber-400">
                <div class="p-2 rounded-lg bg-amber-100 w-fit mb-3">
                    <Clock class="w-4 h-4 text-amber-600" />
                </div>
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Pending</p>
                <p class="text-2xl font-black text-brand-navy mt-1">{{ s.pending ?? 0 }}</p>
            </div>

            <div
                class="group bg-white rounded-2xl p-4 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md hover:-translate-y-0.5 border-l-4 border-blue-400">
                <div class="p-2 rounded-lg bg-blue-100 w-fit mb-3">
                    <CheckCircle2 class="w-4 h-4 text-blue-600" />
                </div>
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Approved</p>
                <p class="text-2xl font-black text-brand-navy mt-1">{{ s.approved ?? 0 }}</p>
            </div>

            <div
                class="group bg-white rounded-2xl p-4 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md hover:-translate-y-0.5 border-l-4 border-green-400">
                <div class="p-2 rounded-lg bg-green-100 w-fit mb-3">
                    <Truck class="w-4 h-4 text-green-600" />
                </div>
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Released</p>
                <p class="text-2xl font-black text-brand-navy mt-1">{{ s.released ?? 0 }}</p>
            </div>

            <div
                class="group bg-white rounded-2xl p-4 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md hover:-translate-y-0.5 border-l-4 border-red-300">
                <div class="p-2 rounded-lg bg-red-100 w-fit mb-3">
                    <XCircle class="w-4 h-4 text-red-500" />
                </div>
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Rejected</p>
                <p class="text-2xl font-black text-brand-navy mt-1">{{ s.rejected ?? 0 }}</p>
            </div>

            <div
                class="group bg-white rounded-2xl p-4 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md hover:-translate-y-0.5 border-l-4 border-gray-300 col-span-2 sm:col-span-1">
                <div class="p-2 rounded-lg bg-gray-100 w-fit mb-3">
                    <FileText class="w-4 h-4 text-gray-500" />
                </div>
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Total</p>
                <p class="text-2xl font-black text-brand-navy mt-1">{{ s.total ?? 0 }}</p>
            </div>
        </div>

        <!-- ── Middle Row: Status Tracker + Quick Actions ─────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

            <!-- Latest Request Tracker -->
            <div class="lg:col-span-3 bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-900/5">
                <h3 class="text-sm font-bold text-brand-navy mb-6">Latest Request Status</h3>

                <template v-if="latestActiveRequest">
                    <!-- Transaction ID -->
                    <div class="mb-6">
                        <p class="text-xs text-gray-400">Transaction ID</p>
                        <p class="text-base font-bold text-brand-navy">{{ latestActiveRequest.transaction_id ?? '#' +
                            latestActiveRequest.id }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ latestActiveRequest.department?.name ?? '—' }} &bull;
                            {{ formatDate(latestActiveRequest.updated_at) }}</p>
                    </div>

                    <!-- Step progress bar -->
                    <div class="relative">
                        <!-- Track line -->
                        <div class="absolute top-4 left-4 right-4 h-0.5 bg-gray-200">
                            <div class="h-full bg-brand-navy transition-all duration-700 ease-out"
                                :style="{ width: `${(getStepForStatus(latestActiveRequest.status) / (lifecycleSteps.length - 1)) * 100}%` }" />
                        </div>

                        <div class="relative flex justify-between">
                            <div v-for="(step, i) in lifecycleSteps" :key="step.key"
                                class="flex flex-col items-center gap-2">
                                <div :class="[
                                    'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all duration-500 z-10',
                                    i <= getStepForStatus(latestActiveRequest.status)
                                        ? 'bg-brand-navy border-brand-navy text-white shadow-md'
                                        : 'bg-white border-gray-200 text-gray-400'
                                ]">
                                    <CheckCircle2 v-if="i < getStepForStatus(latestActiveRequest.status)"
                                        class="w-4 h-4" />
                                    <span v-else>{{ i + 1 }}</span>
                                </div>
                                <span :class="[
                                    'text-[10px] font-semibold text-center max-w-[60px] leading-tight',
                                    i === getStepForStatus(latestActiveRequest.status) ? 'text-brand-navy' : 'text-gray-400'
                                ]">{{ step.label }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <Link :href="route('requestor.requests.show', latestActiveRequest.id)"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-blue hover:text-brand-navy transition-colors group">
                            View details
                            <ArrowRight class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" />
                        </Link>
                    </div>
                </template>

                <!-- No active request state -->
                <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                        <ClipboardList class="w-7 h-7 text-gray-300" />
                    </div>
                    <p class="text-sm font-semibold text-gray-500">No active requests</p>
                    <p class="text-xs text-gray-400 mt-1">Start a new request from the catalog.</p>
                    <Link :href="route('requestor.catalog.index')"
                        class="mt-4 inline-flex items-center gap-2 bg-brand-navy text-white text-xs font-bold px-4 py-2 rounded-xl hover:bg-brand-navy/90 transition-all active:scale-95">
                        <PlusCircle class="w-4 h-4" /> Browse Catalog
                    </Link>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-900/5">
                <h3 class="text-sm font-bold text-brand-navy mb-4">Quick Actions</h3>
                <div class="space-y-2.5">
                    <Link :href="route('requestor.catalog.index')"
                        class="group flex items-center gap-4 p-3.5 rounded-xl bg-brand-navy/5 hover:bg-brand-navy transition-all duration-200">
                        <div class="p-2 rounded-lg bg-brand-navy/10 group-hover:bg-white/10">
                            <Package class="w-4 h-4 text-brand-navy group-hover:text-white" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800 group-hover:text-white">Browse Catalog</p>
                            <p class="text-xs text-gray-400 group-hover:text-white/60">Add items to your request</p>
                        </div>
                        <ArrowRight
                            class="w-4 h-4 text-gray-300 group-hover:text-white/60 group-hover:translate-x-0.5 transition-transform" />
                    </Link>

                    <Link :href="route('requestor.requests.index')"
                        class="group flex items-center gap-4 p-3.5 rounded-xl bg-gray-50 hover:bg-brand-navy transition-all duration-200">
                        <div class="p-2 rounded-lg bg-gray-100 group-hover:bg-white/10">
                            <ClipboardList class="w-4 h-4 text-gray-500 group-hover:text-white" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800 group-hover:text-white">Active Requests</p>
                            <p class="text-xs text-gray-400 group-hover:text-white/60">{{ (s.pending ?? 0) + (s.approved
                                ?? 0) }} in
                                progress</p>
                        </div>
                        <ArrowRight
                            class="w-4 h-4 text-gray-300 group-hover:text-white/60 group-hover:translate-x-0.5 transition-transform" />
                    </Link>

                    <Link :href="route('requestor.requests.archived')"
                        class="group flex items-center gap-4 p-3.5 rounded-xl bg-gray-50 hover:bg-brand-navy transition-all duration-200">
                        <div class="p-2 rounded-lg bg-gray-100 group-hover:bg-white/10">
                            <Archive class="w-4 h-4 text-gray-500 group-hover:text-white" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800 group-hover:text-white">Archived Requests</p>
                            <p class="text-xs text-gray-400 group-hover:text-white/60">Past requests & history</p>
                        </div>
                        <ArrowRight
                            class="w-4 h-4 text-gray-300 group-hover:text-white/60 group-hover:translate-x-0.5 transition-transform" />
                    </Link>
                </div>
            </div>
        </div>

        <!-- ── Recent Activity ─────────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-900/5">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-bold text-brand-navy">Recent Activity</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Your latest request updates</p>
                </div>
                <Link :href="route('requestor.requests.index')"
                    class="text-xs font-semibold text-brand-blue hover:text-brand-navy transition-colors flex items-center gap-1 group">
                    View all
                    <ArrowRight class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" />
                </Link>
            </div>

            <div v-if="recentActivity.length > 0" class="divide-y divide-gray-50">
                <div v-for="activity in recentActivity" :key="activity.id"
                    class="flex items-center gap-4 py-3 -mx-2 px-2 rounded-lg hover:bg-gray-50 transition-colors">
                    <div :class="['w-2 h-2 rounded-full flex-shrink-0', getStatus(activity.status).dot]" />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ activity.transaction_id ?? '#' +
                            activity.id
                            }}</p>
                        <p class="text-xs text-gray-400">{{ activity.department?.name ?? '—' }}</p>
                    </div>
                    <span
                        :class="['text-[10px] font-semibold px-2 py-1 rounded-full flex-shrink-0', getStatus(activity.status).color]">
                        {{ getStatus(activity.status).label }}
                    </span>
                    <span class="text-xs text-gray-400 flex-shrink-0 hidden sm:block">{{ formatDate(activity.updated_at)
                        }}</span>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center py-10 text-center">
                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                    <ClipboardList class="w-6 h-6 text-gray-300" />
                </div>
                <p class="text-sm font-semibold text-gray-500">No requests yet</p>
                <p class="text-xs text-gray-400 mt-1 mb-4">Browse the catalog to create your first supply request.</p>
                <Link :href="route('requestor.catalog.index')"
                    class="inline-flex items-center gap-2 bg-brand-navy text-white text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-brand-navy/90 transition-all active:scale-95">
                    <PlusCircle class="w-4 h-4" /> New Request
                </Link>
            </div>
        </div>
    </div>
</template>