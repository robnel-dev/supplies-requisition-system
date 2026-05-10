<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, Eye, Clock } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatRequestDepartment } from '@/Utils/requestDisplay';

const props = defineProps({
    requests: Object,
});

const itemCount = (request) => request.items_count ?? request.items?.length ?? 0;

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Approvals" />

    <AppLayout>
        <PageHeader title="Pending Approvals" description="Review and approve supply requests from your department." />

        <!-- Table Card -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="table-header">
                        <tr>
                            <th class="px-6 py-4">Transaction ID</th>
                            <th class="px-6 py-4">Request Date</th>
                            <th class="px-6 py-4">Requestor</th>
                            <th class="px-6 py-4">Department</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-center">Items</th>
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
                                {{ req.user.name }}
                            </td>

                            <td class="px-6 py-4 text-gray-700">
                                {{ formatRequestDepartment(req) }}
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide bg-amber-100 text-amber-800">
                                    <Clock class="w-3 h-3" />
                                    Pending Approval
                                </span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-blue-dark/10 text-brand-blue-dark font-bold text-xs">
                                    {{ itemCount(req) }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <Link :href="route('approver.approvals.show', req.id)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-brand-blue-dark rounded-lg border border-brand-blue-dark/30
                                           hover:bg-blue-50 hover:text-brand-blue-dark transition-colors">
                                    <Eye class="w-3.5 h-3.5" />
                                    View Details
                                </Link>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="requests.data.length === 0">
                            <td colspan="7" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <ClipboardList class="w-12 h-12 opacity-30" />
                                    <p class="font-semibold text-gray-500">No pending approvals</p>
                                    <p class="text-sm">
                                        All requests from your department have been processed.
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
