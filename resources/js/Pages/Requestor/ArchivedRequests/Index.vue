<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Archive, Eye, Clock, XCircle } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatRequestDepartment } from '@/Utils/requestDisplay';

const props = defineProps({
    requests: Object,
});

const statusConfig = {
    rejected: { label: 'Rejected', color: 'bg-red-100 text-red-800', icon: XCircle },
    cancelled: { label: 'Cancelled', color: 'bg-gray-100 text-gray-600', icon: XCircle },
    archived: { label: 'Archived', color: 'bg-gray-100 text-gray-500', icon: Archive },
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

</script>

<template>

    <Head title="Archived Requests" />

    <AppLayout>

        <PageHeader title="Archived Requests"
            description="View your rejected, cancelled, and archived supply requests." />

        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
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
                        <tr v-for="req in requests.data" :key="req.id" class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-bold text-brand-blue-dark font-mono text-xs tracking-wide">
                                    {{ req.transaction_id }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                {{ formatDate(req.request_date) }}
                            </td>

                            <td class="px-6 py-4 text-gray-700">
                                {{ formatRequestDepartment(req) }}
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
                                <Link :href="route('requestor.requests.archived.show', req.id)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-brand-blue-dark rounded-lg border border-brand-blue-dark/30
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
                                    <Archive class="w-12 h-12 opacity-30" />
                                    <p class="font-semibold text-gray-500">No archived requests</p>
                                    <p class="text-sm">Rejected and cancelled requests will appear here.</p>
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
