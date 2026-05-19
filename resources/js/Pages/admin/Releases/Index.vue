<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Archive,
    CheckCircle2,
    ClipboardList,
    Eye,
    Package,
    Search,
    Truck,
    X,
} from 'lucide-vue-next';
import { debounce } from 'lodash-es';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { useToast } from '@/Composables/useToast';
import { formatRequestDepartment } from '@/Utils/requestDisplay';

const props = defineProps({
    requests: Object,
    filters: {
        type: Object,
        default: () => ({ search: '', status: '' }),
    },
    stats: {
        type: Object,
        default: () => ({ pendingRelease: 0, released: 0 }),
    },
});

const { showToast } = useToast();

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const archiveTarget = ref(null);
const isArchiving = ref(false);

const hasFilters = computed(() => search.value !== '' || statusFilter.value !== '');

const statusConfig = {
    approved: {
        label: 'Approved',
        hint: 'Awaiting release',
        color: 'bg-blue-100 text-blue-800',
        icon: CheckCircle2,
    },
    released: {
        label: 'Released',
        hint: 'Ready to archive',
        color: 'bg-green-100 text-green-800',
        icon: Package,
    },
};

const getStatus = (status) =>
    statusConfig[status] ?? { label: status, hint: '', color: 'bg-gray-100 text-gray-600', icon: CheckCircle2 };

const itemCount = (request) => request.items_count ?? request.items?.length ?? 0;

const canArchive = (request) =>
    request.status === 'released' && Boolean(request.m3_ro_number) && Boolean(request.m3_dr_number);

const clearFilters = () => {
    search.value = '';
    statusFilter.value = '';
};

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

watch([search, statusFilter], debounce(([newSearch, newStatus]) => {
    router.get(
        route('admin.releases.index'),
        { search: newSearch, status: newStatus },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300));

const openArchiveModal = (request) => {
    if (!canArchive(request)) {
        showToast('Only released requests with M3 RO and M3 DR numbers can be archived.', 'error');
        return;
    }

    archiveTarget.value = request;
};

const closeArchiveModal = () => {
    archiveTarget.value = null;
};

const archiveRequest = () => {
    if (!archiveTarget.value) return;

    isArchiving.value = true;
    router.patch(route('admin.releases.archive', archiveTarget.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Request archived.');
            closeArchiveModal();
        },
        onError: (errors) => {
            showToast(errors.status ?? errors.m3_ro_number ?? 'Could not archive request.', 'error');
        },
        onFinish: () => {
            isArchiving.value = false;
        },
    });
};
</script>

<template>
    <Head title="Releases" />

    <AppLayout>
        <PageHeader title="Releases" description="Verify approved supply requests, release items, and archive completed releases." />

        <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center mb-8 gap-6">
            <div class="flex flex-col sm:flex-row gap-4 w-full xl:w-auto">
                <div class="card-stat border-l-4 border-brand-blue-dark">
                    <div class="p-3 rounded-full bg-brand-blue-dark/10 text-brand-blue-dark mr-4">
                        <Truck class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Pending Release</p>
                        <p class="text-2xl font-black text-gray-800">{{ stats.pendingRelease }}</p>
                    </div>
                </div>

                <div class="card-stat border-l-4 border-green-500">
                    <div class="p-3 rounded-full bg-green-100 text-green-700 mr-4">
                        <Package class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Released</p>
                        <p class="text-2xl font-black text-gray-800">{{ stats.released }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col md:flex-row items-center gap-4 border border-gray-200 w-full xl:w-auto">
                <button v-if="hasFilters" @click="clearFilters"
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-lg transition-colors whitespace-nowrap">
                    <X class="w-4 h-4 mr-2" />
                    Clear Filters
                </button>

                <select v-model="statusFilter"
                    class="block w-full sm:w-48 py-2.5 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-brand-blue-darker focus:border-brand-blue-darker sm:text-sm cursor-pointer">
                    <option value="">All Statuses</option>
                    <option value="approved">Pending Release</option>
                    <option value="released">Released</option>
                </select>

                <div class="relative w-full sm:w-80">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search class="h-5 w-5 text-gray-400" />
                    </div>
                    <input v-model="search" type="text" placeholder="Search control number, requestor, or department..."
                        class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-brand-blue-darker focus:border-brand-blue-darker sm:text-sm" />
                </div>
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
                            <th class="px-6 py-4">Final Status</th>
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
                                <p class="text-xs text-gray-400 mt-1">{{ getStatus(req.status).hint }}</p>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-blue-dark/10 text-brand-blue-dark font-bold text-xs">
                                    {{ itemCount(req) }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex items-center justify-center gap-2">
                                    <Link :href="route('admin.releases.show', req.id)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-brand-blue-dark rounded-lg border border-brand-blue-dark/30 hover:bg-blue-50 hover:text-brand-blue-dark transition-colors">
                                        <Eye class="w-3.5 h-3.5" />
                                        View Details
                                    </Link>

                                    <button type="button" @click="openArchiveModal(req)" :disabled="!canArchive(req)"
                                        :title="canArchive(req) ? 'Archive request' : 'Release and fill M3 RO/DR numbers before archiving'"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="canArchive(req)
                                            ? 'text-gray-700 bg-white border-gray-300 hover:bg-gray-100'
                                            : 'text-gray-400 bg-gray-50 border-gray-200'">
                                        <Archive class="w-3.5 h-3.5" />
                                        Archive
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="requests.data.length === 0">
                            <td colspan="7" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <ClipboardList class="w-12 h-12 opacity-30" />
                                    <p class="font-semibold text-gray-500">No release requests found</p>
                                    <p class="text-sm">Approved and released requests will appear here.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="requests.links" :from="requests.from" :to="requests.to" :total="requests.total"
                :queryParams="{ search, status: statusFilter }" />
        </div>

        <Modal :show="archiveTarget !== null" @close="closeArchiveModal" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-gray-100">
                    <AlertTriangle class="w-6 h-6 text-gray-700" />
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-black text-gray-900">Archive Request</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Archive
                        <span class="font-bold text-gray-800">{{ archiveTarget?.transaction_id }}</span>?
                        This removes it from the active release queue.
                    </p>
                </div>
                <div class="flex justify-center gap-4 mt-8">
                    <button @click="closeArchiveModal" class="btn-secondary" :disabled="isArchiving">Cancel</button>
                    <button @click="archiveRequest" class="btn-primary" :disabled="isArchiving">
                        <Archive class="w-4 h-4 mr-2" />
                        {{ isArchiving ? 'Archiving...' : 'Archive' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
