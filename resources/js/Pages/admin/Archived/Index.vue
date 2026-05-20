<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, Clock, Eye, Search } from 'lucide-vue-next';
import { debounce } from 'lodash-es';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatRequestDepartment } from '@/Utils/requestDisplay';

const props = defineProps({
    requests: Object,
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const search = ref(props.filters?.search || '');

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

watch(search, debounce((value) => {
    router.get(
        route('admin.archived.index'),
        { search: value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300));
</script>

<template>
    <Head title="Archived Requests" />

    <AppLayout>
        <PageHeader title="Archived Requests" description="Review released requests that you archived." />

        <div class="flex justify-end mb-6">
            <div class="relative w-full sm:w-96">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Search class="h-5 w-5 text-gray-400" />
                </div>
                <input v-model="search" type="text" placeholder="Search control number, requestor, or department..."
                    class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-brand-blue-darker focus:border-brand-blue-darker sm:text-sm transition duration-150 ease-in-out" />
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="table-header">
                        <tr>
                            <th class="px-6 py-4">Control Number</th>
                            <th class="px-6 py-4">Requestor</th>
                            <th class="px-6 py-4">Department</th>
                            <th class="px-6 py-4">Released Date</th>
                            <th class="px-6 py-4">Archived Date</th>
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

                            <td class="px-6 py-4 text-gray-700">
                                {{ req.user?.name ?? '-' }}
                            </td>

                            <td class="px-6 py-4 text-gray-700">
                                {{ formatRequestDepartment(req) }}
                            </td>

                            <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                {{ formatDate(req.hr_admin_released_at) }}
                            </td>

                            <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                {{ formatDate(req.archived_at) }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide bg-gray-100 text-gray-600">
                                    <Archive class="w-3 h-3" />
                                    Archived
                                </span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <Link :href="route('admin.archived.show', req.id)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-brand-blue-dark rounded-lg border border-brand-blue-dark/30 hover:bg-blue-50 hover:text-brand-blue-dark transition-colors">
                                    <Eye class="w-3.5 h-3.5" />
                                    View
                                </Link>
                            </td>
                        </tr>

                        <tr v-if="requests.data.length === 0">
                            <td colspan="7" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <Clock class="w-12 h-12 opacity-30" />
                                    <p class="font-semibold text-gray-500">No archived requests</p>
                                    <p class="text-sm">Requests you archive after release will appear here.</p>
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
