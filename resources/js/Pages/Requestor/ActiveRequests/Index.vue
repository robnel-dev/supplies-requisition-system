<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, Eye, ChevronRight, Clock, CheckCircle2, XCircle, Package, Send, Archive } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    requests: Object,
});

const statusConfig = {
    pending_approval: {
        label: 'Pending Approval',
        color: 'bg-amber-100 text-amber-800',
        icon: Clock,
    },
    approved: {
        label: 'Approved',
        color: 'bg-blue-100 text-blue-800',
        icon: CheckCircle2,
    },
    released: {
        label: 'Released',
        color: 'bg-green-100 text-green-800',
        icon: Package,
    },
};

const getStatus = (status) =>
    statusConfig[status] ?? { label: status, color: 'bg-gray-100 text-gray-600', icon: Clock };

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const requestDepartmentLabel = (request) => {
    const departmentName = request.department?.name || '-';

    if (request.department?.type !== 'store') {
        return departmentName;
    }

    const storeName = request.user?.external_department_reference?.name || request.user?.name;

    return storeName ? `${departmentName} - ${storeName}` : departmentName;
};
</script>

<template>

    <Head title="Active Requests" />

    <AppLayout>

        <PageHeader title="Active Requests" description="Track your pending and approved supply requests." />

        <!-- Table Card -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="table-header">
                        <tr>
                            <th class="px-6 py-4">Transaction ID</th>
                            <th class="px-6 py-4">Request Date</th>
                            <th class="px-6 py-4">Department</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="req in requests.data" :key="req.id" class="hover:bg-blue-50/40 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-bold text-brand-blue-dark font-mono text-xs tracking-wide">
                                    {{ req.transaction_id }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                {{ formatDate(req.request_date) }}
                            </td>

                            <td class="px-6 py-4 text-gray-700">
                                {{ requestDepartmentLabel(req) }}
                            </td>

                            <td class="px-6 py-4">
                                <span :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide',
                                    getStatus(req.status).color
                                ]">
                                    <component :is="getStatus(req.status).icon" class="w-3 h-3" />
                                    {{ getStatus(req.status).label }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <Link :href="route('requestor.requests.show', req.id)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-brand-blue-dark rounded-lg border border-brand-blue-dark/30
                                           hover:bg-blue-50 hover:text-brand-blue-dark transition-colors">
                                    <Eye class="w-3.5 h-3.5" />
                                    View Details
                                </Link>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="requests.data.length === 0">
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <ClipboardList class="w-12 h-12 opacity-30" />
                                    <p class="font-semibold text-gray-500">No active requests</p>
                                    <p class="text-sm">
                                        Go to the
                                        <Link :href="route('requestor.catalog.index')"
                                            class="text-brand-blue-dark font-bold hover:underline">
                                            Supplies Catalog
                                        </Link>
                                        to create your first request.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="requests.links" :from="requests.from" :to="requests.to" :total="requests.total" />
        </div>

    </AppLayout>
</template>
