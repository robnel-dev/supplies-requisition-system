<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, CheckCircle2, Clock, Eye, History, Package, Search, XCircle } from 'lucide-vue-next';
import { debounce } from 'lodash-es';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatRequestDepartment } from '@/Utils/requestDisplay';

const props = defineProps({
    requests: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

watch(search, debounce((value) => {
    router.get(
        route('approver.approval-history.index'),
        { search: value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300));

const statusConfig = {
    approved: { label: 'Approved', color: 'bg-blue-100 text-blue-800', icon: CheckCircle2 },
    rejected: { label: 'Rejected', color: 'bg-red-100 text-red-800', icon: XCircle },
    released: { label: 'Released', color: 'bg-green-100 text-green-800', icon: Package },
    archived: { label: 'Archived', color: 'bg-gray-100 text-gray-500', icon: Archive },
    cancelled: { label: 'Cancelled', color: 'bg-gray-100 text-gray-600', icon: XCircle },
};

const getStatus = (status) =>
    statusConfig[status] ?? { label: status, color: 'bg-gray-100 text-gray-600', icon: Clock };

const itemCount = (request) => request.items_count ?? request.items?.length ?? 0;

const formatDate = (date) => {
    if (!date) return '-';

    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Approval History" />

    <AppLayout>
        <PageHeader title="Approval History" description="Review requests you have approved or rejected." />

        <div class="flex justify-end mb-6">
            <div class="relative w-full sm:w-80">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Search class="h-5 w-5 text-gray-400" />
                </div>
                <input v-model="search" type="text" placeholder="Search control number or requestor..."
                    class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-brand-blue-darker focus:border-brand-blue-darker sm:text-sm transition duration-150 ease-in-out" />
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="table-header">
                        <tr>
                            <th class="px-6 py-4">Control Number</th>
                            <th class="px-6 py-4">Request Date</th>
                            <th class="px-6 py-4">Requestor</th>
                            <th class="px-6 py-4">Department</th>
                            <th class="px-6 py-4">Current Status</th>
                            <th class="px-6 py-4 text-center">Items</th>
                            <th class="px-6 py-4">Date Acted On</th>
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
                                {{ req.user?.name ?? '-' }}
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
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-blue-dark/10 text-brand-blue-dark font-bold text-xs">
                                    {{ itemCount(req) }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                {{ formatDate(req.acted_at) }}
                            </td>

                            <td class="px-6 py-4 text-center">
                                <Link :href="route('approver.approval-history.show', req.id)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-brand-blue-dark rounded-lg border border-brand-blue-dark/30
                                           hover:bg-blue-50 hover:text-brand-blue-dark transition-colors">
                                    <Eye class="w-3.5 h-3.5" />
                                    View Details
                                </Link>
                            </td>
                        </tr>

                        <tr v-if="requests.data.length === 0">
                            <td colspan="8" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <History class="w-12 h-12 opacity-30" />
                                    <p class="font-semibold text-gray-500">No approval history</p>
                                    <p class="text-sm">
                                        Requests you approve or reject will appear here.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="requests.links" :from="requests.from" :to="requests.to" :total="requests.total"
                :queryParams="{ search }" />
        </div>
    </AppLayout>
</template>
