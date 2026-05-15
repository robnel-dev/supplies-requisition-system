<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Package, Users, Building2, CheckCircle2, Clock,
    TrendingUp, ArrowRight, BarChart3, Truck, AlertCircle,
    ExternalLink, RefreshCw
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

// ── Helpers ──────────────────────────────────────────────────────────────────

const statusConfig = {
    draft: { label: 'Draft', color: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400' },
    pending_approval: { label: 'Pending Approval', color: 'bg-amber-100 text-amber-700', dot: 'bg-amber-400' },
    approved: { label: 'Approved', color: 'bg-blue-100 text-blue-700', dot: 'bg-blue-500' },
    rejected: { label: 'Rejected', color: 'bg-red-100 text-red-700', dot: 'bg-red-500' },
    released: { label: 'Released', color: 'bg-green-100 text-green-700', dot: 'bg-green-500' },
    cancelled: { label: 'Cancelled', color: 'bg-gray-100 text-gray-500', dot: 'bg-gray-300' },
    archived: { label: 'Archived', color: 'bg-purple-100 text-purple-700', dot: 'bg-purple-400' },
};

const getStatus = (status) => statusConfig[status] ?? { label: status, color: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400' };

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';

// Bar chart max value for scaling
const chartMax = computed(() => {
    const vals = (s.value.monthlyReleased ?? []).map(m => m.count);
    return Math.max(...vals, 1);
});

// Released vs last month delta
const releaseDelta = computed(() => {
    const curr = s.value.releasedThisMonth ?? 0;
    const prev = s.value.releasedLastMonth ?? 0;
    if (prev === 0) return null;
    const pct = Math.round(((curr - prev) / prev) * 100);
    return { pct, up: pct >= 0 };
});

// Requests by status totals for the donut-style breakdown
const statusBreakdown = computed(() => {
    const map = s.value.requestsByStatus ?? {};
    return Object.entries(map).map(([key, count]) => ({
        ...getStatus(key),
        key,
        count,
    })).sort((a, b) => b.count - a.count);
});

const totalRequests = computed(() => statusBreakdown.value.reduce((sum, r) => sum + r.count, 0));
</script>

<template>
    <div class="space-y-6">
        <!-- ── Page Header ─────────────────────────────────────────────────── -->
        <PageHeader title="Admin Dashboard"
            description="System overview, pending releases, and operational insights." />

        <!-- ── KPI Stat Cards ──────────────────────────────────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

            <!-- Pending Release -->
            <div
                class="group relative bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-900/5 overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-amber-50/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none" />
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 rounded-xl bg-amber-100">
                        <Clock class="w-5 h-5 text-amber-600" />
                    </div>
                    <span v-if="(s.pendingRelease ?? 0) > 0"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200 animate-pulse">
                        Action needed
                    </span>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pending Release</p>
                <p class="text-3xl font-black text-brand-navy mb-3">{{ s.pendingRelease ?? 0 }}</p>
                <Link :href="route('admin.supplies.index')"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 hover:text-amber-700 transition-colors group/link">
                    View queue
                    <ArrowRight class="w-3 h-3 group-hover/link:translate-x-0.5 transition-transform" />
                </Link>
            </div>

            <!-- Released This Month -->
            <div
                class="group relative bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-900/5 overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-green-50/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none" />
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 rounded-xl bg-green-100">
                        <Truck class="w-5 h-5 text-green-600" />
                    </div>
                    <span v-if="releaseDelta"
                        :class="[releaseDelta.up ? 'text-green-600 bg-green-50 border-green-200' : 'text-red-600 bg-red-50 border-red-200', 'inline-flex items-center gap-0.5 text-xs font-semibold px-2 py-0.5 rounded-full border']">
                        <TrendingUp v-if="releaseDelta.up" class="w-3 h-3" />
                        <TrendingUp v-else class="w-3 h-3 rotate-180" />
                        {{ Math.abs(releaseDelta.pct) }}%
                    </span>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Released This Month</p>
                <p class="text-3xl font-black text-brand-navy mb-3">{{ s.releasedThisMonth ?? 0 }}</p>
                <p class="text-xs text-gray-400">Last month: {{ s.releasedLastMonth ?? 0 }}</p>
            </div>

            <!-- Active Supplies -->
            <div
                class="group relative bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-900/5 overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-blue-50/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none" />
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 rounded-xl bg-blue-100">
                        <Package class="w-5 h-5 text-blue-600" />
                    </div>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Active Supplies</p>
                <p class="text-3xl font-black text-brand-navy mb-3">{{ s.totalSupplies ?? 0 }}</p>
                <Link :href="route('admin.supplies.index')"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 transition-colors group/link">
                    Manage catalog
                    <ArrowRight class="w-3 h-3 group-hover/link:translate-x-0.5 transition-transform" />
                </Link>
            </div>

            <!-- System Users -->
            <div
                class="group relative bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-900/5 overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-purple-50/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none" />
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 rounded-xl bg-purple-100">
                        <Users class="w-5 h-5 text-purple-600" />
                    </div>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">System Users</p>
                <p class="text-3xl font-black text-brand-navy mb-3">{{ s.totalUsers ?? 0 }}</p>
                <p class="text-xs text-gray-400">{{ s.activeDepartments ?? 0 }} active departments</p>
            </div>
        </div>

        <!-- ── Middle Row: Chart + Status Breakdown ────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

            <!-- Monthly Release Bar Chart (spans 3 cols) -->
            <div class="lg:col-span-3 bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-900/5">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-sm font-bold text-brand-navy">Monthly Releases</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Last 6 months</p>
                    </div>
                    <BarChart3 class="w-5 h-5 text-gray-300" />
                </div>

                <!-- Bar chart -->
                <div class="flex items-end gap-3 h-36">
                    <template v-if="(s.monthlyReleased ?? []).length > 0">
                        <div v-for="(month, i) in s.monthlyReleased" :key="i"
                            class="flex-1 flex flex-col items-center gap-2">
                            <span class="text-xs font-bold text-brand-navy">{{ month.count }}</span>
                            <div class="w-full rounded-t-lg bg-brand-navy/10 relative overflow-hidden"
                                style="height: 96px;">
                                <div class="absolute bottom-0 left-0 right-0 bg-brand-navy rounded-t-lg transition-all duration-700 ease-out"
                                    :style="{ height: `${Math.max(4, (month.count / chartMax) * 96)}px` }" />
                            </div>
                            <span class="text-[10px] text-gray-400 font-medium">{{ month.label }}</span>
                        </div>
                    </template>
                    <div v-else class="flex-1 flex items-center justify-center">
                        <p class="text-sm text-gray-400">No release data yet</p>
                    </div>
                </div>
            </div>

            <!-- Requests by Status (spans 2 cols) -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-900/5">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-sm font-bold text-brand-navy">Requests by Status</h3>
                        <p class="text-xs text-gray-400 mt-0.5">All time · {{ totalRequests }} total</p>
                    </div>
                </div>

                <div v-if="statusBreakdown.length > 0" class="space-y-3">
                    <div v-for="item in statusBreakdown" :key="item.key" class="flex items-center gap-3">
                        <span :class="['w-2 h-2 rounded-full flex-shrink-0', item.dot]" />
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-medium text-gray-600 truncate">{{ item.label }}</span>
                                <span class="text-xs font-bold text-gray-900 ml-2">{{ item.count }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700" :class="item.dot"
                                    :style="{ width: `${totalRequests > 0 ? (item.count / totalRequests) * 100 : 0}%` }" />
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="flex items-center justify-center h-32">
                    <p class="text-sm text-gray-400">No request data yet</p>
                </div>
            </div>
        </div>

        <!-- ── Bottom Row: Quick Actions + Recent Releases ─────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

            <!-- Quick Actions -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-900/5">
                <h3 class="text-sm font-bold text-brand-navy mb-4">Quick Actions</h3>
                <div class="space-y-2.5">
                    <Link :href="route('admin.supplies.index')"
                        class="group flex items-center gap-4 p-3.5 rounded-xl bg-gray-50 hover:bg-brand-navy hover:text-white transition-all duration-200 cursor-pointer">
                        <div class="p-2 rounded-lg bg-blue-100 group-hover:bg-blue-500/20 transition-colors">
                            <Package class="w-4 h-4 text-blue-600 group-hover:text-blue-200" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold group-hover:text-white text-gray-800">Manage Supplies</p>
                            <p class="text-xs text-gray-400 group-hover:text-white/60">Add, edit, or deactivate items
                            </p>
                        </div>
                        <ArrowRight
                            class="w-4 h-4 text-gray-300 group-hover:text-white/60 group-hover:translate-x-0.5 transition-transform" />
                    </Link>

                    <Link :href="route('admin.departments.index')"
                        class="group flex items-center gap-4 p-3.5 rounded-xl bg-gray-50 hover:bg-brand-navy hover:text-white transition-all duration-200 cursor-pointer">
                        <div class="p-2 rounded-lg bg-green-100 group-hover:bg-green-500/20 transition-colors">
                            <Building2 class="w-4 h-4 text-green-600 group-hover:text-green-200" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold group-hover:text-white text-gray-800">Departments</p>
                            <p class="text-xs text-gray-400 group-hover:text-white/60">Manage cost center departments
                            </p>
                        </div>
                        <ArrowRight
                            class="w-4 h-4 text-gray-300 group-hover:text-white/60 group-hover:translate-x-0.5 transition-transform" />
                    </Link>

                    <Link :href="route('admin.users.index')"
                        class="group flex items-center gap-4 p-3.5 rounded-xl bg-gray-50 hover:bg-brand-navy hover:text-white transition-all duration-200 cursor-pointer">
                        <div class="p-2 rounded-lg bg-purple-100 group-hover:bg-purple-500/20 transition-colors">
                            <Users class="w-4 h-4 text-purple-600 group-hover:text-purple-200" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold group-hover:text-white text-gray-800">User Management</p>
                            <p class="text-xs text-gray-400 group-hover:text-white/60">Add users and assign roles</p>
                        </div>
                        <ArrowRight
                            class="w-4 h-4 text-gray-300 group-hover:text-white/60 group-hover:translate-x-0.5 transition-transform" />
                    </Link>
                </div>
            </div>

            <!-- Recent Releases -->
            <div class="lg:col-span-3 bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-900/5">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-brand-navy">Recent Releases</h3>
                    <CheckCircle2 class="w-4 h-4 text-green-400" />
                </div>

                <div v-if="(s.recentReleases ?? []).length > 0" class="space-y-3">
                    <div v-for="release in s.recentReleases" :key="release.id"
                        class="group flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 transition-colors cursor-default">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                            <Truck class="w-4 h-4 text-green-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ release.transaction_id ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ release.user?.name ?? '—' }} &bull; {{ release.department?.name ?? '—' }}
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="text-xs text-gray-400">{{ formatDate(release.hr_admin_released_at) }}</span>
                            <div class="flex justify-end mt-1">
                                <span
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold text-green-700 bg-green-100 px-1.5 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    Released
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                        <Truck class="w-6 h-6 text-gray-300" />
                    </div>
                    <p class="text-sm font-semibold text-gray-500">No releases yet</p>
                    <p class="text-xs text-gray-400 mt-1">Released requests will appear here.</p>
                </div>
            </div>
        </div>

        <!-- ── Recent System Activity ──────────────────────────────────────── -->
        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-900/5">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-bold text-brand-navy">Recent System Activity</h3>
                <RefreshCw class="w-4 h-4 text-gray-300" />
            </div>

            <div v-if="recentActivity.length > 0" class="divide-y divide-gray-50">
                <div v-for="activity in recentActivity" :key="activity.id"
                    class="flex items-center gap-4 py-3 hover:bg-gray-50/80 -mx-2 px-2 rounded-lg transition-colors">
                    <div :class="['w-2 h-2 rounded-full flex-shrink-0', getStatus(activity.status).dot]" />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ activity.transaction_id ?? '#' +
                            activity.id }}
                        </p>
                        <p class="text-xs text-gray-400">{{ activity.user?.name ?? '—' }} &bull; {{
                            activity.department?.name ??
                            '—' }}</p>
                    </div>
                    <span
                        :class="['text-[10px] font-semibold px-2 py-1 rounded-full flex-shrink-0', getStatus(activity.status).color]">
                        {{ getStatus(activity.status).label }}
                    </span>
                    <span class="text-xs text-gray-400 flex-shrink-0 hidden sm:block">{{ formatDate(activity.updated_at)
                        }}</span>
                </div>
            </div>
            <div v-else
                class="text-sm text-gray-400 text-center py-10 border-2 border-dashed border-gray-100 rounded-xl">
                System activity will appear here once requests are submitted.
            </div>
        </div>
    </div>
</template>