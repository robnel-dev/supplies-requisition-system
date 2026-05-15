<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Clock, CheckCircle2, XCircle, TrendingUp, ArrowRight,
    ClipboardCheck, History, Users, BarChart3, AlertCircle
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

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
};

// Approval rate display
const approvalRateColor = computed(() => {
    const r = s.value.approvalRate ?? 0;
    if (r >= 75) return 'text-green-600';
    if (r >= 50) return 'text-amber-600';
    return 'text-red-600';
});

// Chart max
const chartMax = computed(() => {
    const trend = s.value.monthlyTrend ?? [];
    return Math.max(...trend.map(t => t.approved + t.rejected), 1);
});

// Status helpers
const statusConfig = {
    pending_approval: { label: 'Pending', color: 'bg-amber-100 text-amber-700', dot: 'bg-amber-400' },
    approved: { label: 'Approved', color: 'bg-blue-100 text-blue-700', dot: 'bg-blue-500' },
    rejected: { label: 'Rejected', color: 'bg-red-100 text-red-700', dot: 'bg-red-500' },
    released: { label: 'Released', color: 'bg-green-100 text-green-700', dot: 'bg-green-500' },
};
const getStatus = (s) => statusConfig[s] ?? { label: s, color: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400' };
</script>

<template>
    <div class="space-y-6">
        <!-- ── Page Header ─────────────────────────────────────────────────── -->
        <PageHeader title="Approver Dashboard"
            description="Overview of approval activities, pending queue, and monthly metrics." />

        <!-- ── KPI Cards ───────────────────────────────────────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

            <!-- Pending Approvals -->
            <div
                class="group relative bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-900/5 overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 border-l-4 border-amber-400">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-amber-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 rounded-xl bg-amber-100">
                        <Clock class="w-5 h-5 text-amber-600" />
                    </div>
                    <span v-if="(s.pending ?? 0) > 0"
                        class="text-xs font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full border border-amber-200 animate-pulse">
                        Needs action
                    </span>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pending Approvals</p>
                <p class="text-3xl font-black text-brand-navy mb-3">{{ s.pending ?? 0 }}</p>
                <Link :href="route('approver.approvals.index')"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 hover:text-amber-700 group/link">
                    Review queue
                    <ArrowRight class="w-3 h-3 group-hover/link:translate-x-0.5 transition-transform" />
                </Link>
            </div>

            <!-- Approved This Month -->
            <div
                class="group relative bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-900/5 overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 border-l-4 border-green-400">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-green-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />
                <div class="p-2.5 rounded-xl bg-green-100 mb-4 w-fit">
                    <CheckCircle2 class="w-5 h-5 text-green-600" />
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Approved This Month</p>
                <p class="text-3xl font-black text-brand-navy mb-1">{{ s.approvedThisMonth ?? 0 }}</p>
                <p class="text-xs text-gray-400">requests approved</p>
            </div>

            <!-- Rejected This Month -->
            <div
                class="group relative bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-900/5 overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 border-l-4 border-red-400">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-red-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />
                <div class="p-2.5 rounded-xl bg-red-100 mb-4 w-fit">
                    <XCircle class="w-5 h-5 text-red-600" />
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Rejected This Month</p>
                <p class="text-3xl font-black text-brand-navy mb-1">{{ s.rejectedThisMonth ?? 0 }}</p>
                <p class="text-xs text-gray-400">requests rejected</p>
            </div>

            <!-- Approval Rate -->
            <div
                class="group relative bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-900/5 overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 border-l-4 border-brand-blue">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-blue-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />
                <div class="p-2.5 rounded-xl bg-blue-100 mb-4 w-fit">
                    <TrendingUp class="w-5 h-5 text-blue-600" />
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Approval Rate</p>
                <p :class="['text-3xl font-black mb-1', approvalRateColor]">{{ s.approvalRate ?? 0 }}%</p>
                <p class="text-xs text-gray-400">{{ s.totalHandled ?? 0 }} total handled</p>
            </div>
        </div>

        <!-- ── Monthly Trend + Quick Actions ──────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

            <!-- Monthly Approval Trend -->
            <div class="lg:col-span-3 bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-900/5">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-sm font-bold text-brand-navy">Monthly Trend</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Approved vs. Rejected — last 6 months</p>
                    </div>
                    <BarChart3 class="w-5 h-5 text-gray-300" />
                </div>

                <!-- Grouped bar chart -->
                <div class="flex items-end gap-3 h-36">
                    <template v-if="(s.monthlyTrend ?? []).length > 0">
                        <div v-for="(month, i) in s.monthlyTrend" :key="i"
                            class="flex-1 flex flex-col items-center gap-2">
                            <div class="w-full flex items-end gap-0.5" style="height: 96px;">
                                <!-- Approved bar -->
                                <div class="flex-1 rounded-t-md bg-green-500/80 transition-all duration-700 ease-out"
                                    :style="{ height: `${Math.max(2, (month.approved / chartMax) * 96)}px` }"
                                    :title="`Approved: ${month.approved}`" />
                                <!-- Rejected bar -->
                                <div class="flex-1 rounded-t-md bg-red-400/70 transition-all duration-700 ease-out"
                                    :style="{ height: `${Math.max(2, (month.rejected / chartMax) * 96)}px` }"
                                    :title="`Rejected: ${month.rejected}`" />
                            </div>
                            <span class="text-[10px] text-gray-400 font-medium">{{ month.label }}</span>
                        </div>
                    </template>
                    <div v-else class="flex-1 flex items-center justify-center">
                        <p class="text-sm text-gray-400">No trend data yet</p>
                    </div>
                </div>

                <!-- Legend -->
                <div class="flex items-center gap-4 mt-4">
                    <span class="flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-3 h-3 rounded bg-green-500/80"></span> Approved
                    </span>
                    <span class="flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-3 h-3 rounded bg-red-400/70"></span> Rejected
                    </span>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-900/5">
                <h3 class="text-sm font-bold text-brand-navy mb-4">Quick Actions</h3>
                <div class="space-y-2.5">
                    <Link :href="route('approver.approvals.index')"
                        class="group flex items-center gap-4 p-3.5 rounded-xl bg-amber-50 hover:bg-brand-navy transition-all duration-200">
                        <div class="p-2 rounded-lg bg-amber-100 group-hover:bg-amber-500/20">
                            <ClipboardCheck class="w-4 h-4 text-amber-600 group-hover:text-amber-200" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800 group-hover:text-white">Review Pending</p>
                            <p class="text-xs text-gray-400 group-hover:text-white/60">{{ s.pending ?? 0 }} awaiting
                                your decision</p>
                        </div>
                        <ArrowRight
                            class="w-4 h-4 text-gray-300 group-hover:text-white/60 group-hover:translate-x-0.5 transition-transform" />
                    </Link>

                    <Link :href="route('approver.approval-history.index')"
                        class="group flex items-center gap-4 p-3.5 rounded-xl bg-gray-50 hover:bg-brand-navy transition-all duration-200">
                        <div class="p-2 rounded-lg bg-gray-100 group-hover:bg-white/10">
                            <History class="w-4 h-4 text-gray-500 group-hover:text-gray-200" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800 group-hover:text-white">Approval History</p>
                            <p class="text-xs text-gray-400 group-hover:text-white/60">View past decisions</p>
                        </div>
                        <ArrowRight
                            class="w-4 h-4 text-gray-300 group-hover:text-white/60 group-hover:translate-x-0.5 transition-transform" />
                    </Link>
                </div>

                <!-- Approval Rate Donut -->
                <div class="mt-6 pt-5 border-t border-gray-100">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">This Month's Rate</p>
                    <div class="flex items-center gap-4">
                        <!-- Simple circular progress indicator -->
                        <div class="relative w-16 h-16 flex-shrink-0">
                            <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f3f4f6" stroke-width="3" />
                                <circle cx="18" cy="18" r="15.9" fill="none"
                                    :stroke="(s.approvalRate ?? 0) >= 75 ? '#22c55e' : (s.approvalRate ?? 0) >= 50 ? '#f59e0b' : '#ef4444'"
                                    stroke-width="3" stroke-linecap="round"
                                    :stroke-dasharray="`${(s.approvalRate ?? 0)}, 100`"
                                    class="transition-all duration-1000 ease-out" />
                            </svg>
                            <span
                                class="absolute inset-0 flex items-center justify-center text-xs font-black text-gray-800">
                                {{ s.approvalRate ?? 0 }}%
                            </span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">{{ s.approvedThisMonth ?? 0 }} approved</p>
                            <p class="text-xs text-gray-400">{{ s.rejectedThisMonth ?? 0 }} rejected this month</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Pending Request Queue Preview ──────────────────────────────── -->
        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-900/5">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-bold text-brand-navy">Pending Queue</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Latest requests awaiting your review</p>
                </div>
                <Link :href="route('approver.approvals.index')"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-blue hover:text-brand-navy transition-colors group">
                    View all
                    <ArrowRight class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" />
                </Link>
            </div>

            <div v-if="(s.pendingRequests ?? []).length > 0" class="divide-y divide-gray-50">
                <div v-for="req in s.pendingRequests" :key="req.id"
                    class="flex items-center gap-4 py-3 -mx-2 px-2 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <Clock class="w-4 h-4 text-amber-600" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ req.transaction_id ?? '#' + req.id }}
                        </p>
                        <p class="text-xs text-gray-400">{{ req.user?.name ?? '—' }} &bull; {{ req.items?.length ?? 0 }}
                            item(s)
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs text-gray-400">{{ formatDate(req.request_date) }}</p>
                        <Link :href="route('approver.approvals.show', req.id)"
                            class="mt-1 inline-flex items-center gap-1 text-[10px] font-semibold text-brand-blue hover:text-brand-navy transition-colors">
                            Review
                            <ArrowRight class="w-2.5 h-2.5" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center py-10 text-center">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mb-3">
                    <CheckCircle2 class="w-6 h-6 text-green-500" />
                </div>
                <p class="text-sm font-semibold text-gray-600">All caught up!</p>
                <p class="text-xs text-gray-400 mt-1">No pending requests at the moment.</p>
            </div>
        </div>
    </div>
</template>