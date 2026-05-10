<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft, Clock, CheckCircle2, XCircle, Package, Archive,
    Send, User, Building2, CalendarDays, Hash, AlertTriangle,
    FileText, CheckCheck, Ban, Pencil, RefreshCcw
} from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    supplyRequest: Object,
    departmentApprover: Object,
});

const { showToast } = useToast();

// ── Status config ───────────────────────────────────────────────────────────
const statusConfig = {
    draft: { label: 'Draft', color: 'bg-amber-100 text-amber-800 border-amber-200', icon: Pencil },
    pending_approval: { label: 'Pending Approval', color: 'bg-amber-100 text-amber-800 border-amber-200', icon: Clock },
    approved: { label: 'Approved', color: 'bg-blue-100 text-blue-800 border-blue-200', icon: CheckCircle2 },
    rejected: { label: 'Rejected', color: 'bg-red-100 text-red-800 border-red-200', icon: XCircle },
    released: { label: 'Released', color: 'bg-green-100 text-green-800 border-green-200', icon: Package },
    cancelled: { label: 'Cancelled', color: 'bg-gray-100 text-gray-600 border-gray-200', icon: XCircle },
    archived: { label: 'Archived', color: 'bg-gray-100 text-gray-500 border-gray-200', icon: Archive },
};

const getStatus = (status) =>
    statusConfig[status] ?? { label: status, color: 'bg-gray-100 text-gray-600', icon: Clock };

// ── Timeline icon config ────────────────────────────────────────────────────
const timelineIcons = {
    submitted: { icon: Send, color: 'bg-blue-500', ring: 'ring-blue-100' },
    approved: { icon: CheckCheck, color: 'bg-green-500', ring: 'ring-green-100' },
    rejected: { icon: XCircle, color: 'bg-red-500', ring: 'ring-red-100' },
    released: { icon: Package, color: 'bg-emerald-500', ring: 'ring-emerald-100' },
    cancelled: { icon: Ban, color: 'bg-gray-400', ring: 'ring-gray-100' },
    archived: { icon: Archive, color: 'bg-gray-400', ring: 'ring-gray-100' },
    reopened: { icon: RefreshCcw, color: 'bg-orange-400', ring: 'ring-orange-100' },
};

const getTimelineConfig = (action) =>
    timelineIcons[action] ?? { icon: Clock, color: 'bg-gray-400', ring: 'ring-gray-100' };

// ── Helpers ─────────────────────────────────────────────────────────────────
const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const formatDateShort = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const requestDepartmentLabel = computed(() => {
    const departmentName = props.supplyRequest.department?.name || '-';

    if (props.supplyRequest.department?.type !== 'store') {
        return departmentName;
    }

    const storeName = props.supplyRequest.user?.external_department_reference?.name
        || props.supplyRequest.user?.name;

    return storeName ? `${departmentName} - ${storeName}` : departmentName;
});

const isDraftRequest = computed(() => props.supplyRequest.status === 'draft');
const backLinkHref = computed(() => isDraftRequest.value
    ? route('requestor.catalog.index')
    : route('requestor.requests.index'));
const backLinkLabel = computed(() => isDraftRequest.value ? 'Back to Catalog' : 'Back to My Requests');

// Only pending_approval can be cancelled or edited
const canCancel = computed(() => props.supplyRequest.status === 'pending_approval');
const canEdit = computed(() => props.supplyRequest.status === 'pending_approval');

// ── Cancel Modal ─────────────────────────────────────────────────────────────
const isCancelModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isProcessing = ref(false);

const confirmCancel = () => {
    isProcessing.value = true;
    router.patch(route('requestor.requests.cancel', props.supplyRequest.id), {}, {
        onSuccess: () => { isCancelModalOpen.value = false; showToast('Request cancelled successfully.'); },
        onFinish: () => { isProcessing.value = false; },
    });
};

// ── Edit / Reopen ─────────────────────────────────────────────────────────────
const confirmEdit = () => {
    isProcessing.value = true;
    router.patch(route('requestor.requests.reopen', props.supplyRequest.id), {}, {
        onSuccess: () => {
            isEditModalOpen.value = false;
            // Server redirects to catalog — no client-side redirect needed
        },
        /**
         * FIX: Previously, any error here was caught by onError with a generic
         * errors object that wasn't properly keyed. The Vue code read
         * `errors.error` but the server was sending `errors.reopen`.
         *
         * Now: RequestController sends withErrors(['reopen' => $message]).
         * We read errors.reopen here. If it's missing, we fall back to a
         * safe default message.
         */
        onError: (errors) => {
            isEditModalOpen.value = false;
            const message = errors.reopen ?? errors.error ?? 'Could not reopen request. Please try again.';
            showToast(message, 'error');
        },
        onFinish: () => { isProcessing.value = false; },
    });
};
</script>

<template>

    <Head :title="`Request ${supplyRequest.transaction_id}`" />

    <AppLayout>

        <!-- Back link -->
        <div class="mb-6">
            <Link :href="backLinkHref"
                class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-blue-dark transition-colors">
                <ArrowLeft class="w-4 h-4" />
                {{ backLinkLabel }}
            </Link>
        </div>

        <!-- Page title row -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">Request Details</h1>
                <p class="text-sm text-gray-500 mt-1 font-mono tracking-wide">
                    {{ supplyRequest.transaction_id }}
                </p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">

                <!-- Edit button (only when pending) -->
                <button v-if="canEdit" @click="isEditModalOpen = true"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white bg-amber-500 rounded-lg border border-amber-600 hover:bg-amber-50 hover:text-amber-700 transition-colors">
                    <Pencil class="w-4 h-4" />
                    Edit Request
                </button>

                <!-- Cancel button (only when pending) -->
                <button v-if="canCancel" @click="isCancelModalOpen = true"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-lg border border-red-700 hover:bg-red-50 hover:text-red-700 transition-colors">
                    <Ban class="w-4 h-4" />
                    Cancel Request
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- ── Left Column: Details + Items ─────────────────────────── -->
            <div class="xl:col-span-2 flex flex-col gap-6">

                <!-- Request Info Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="flex items-center justify-between bg-brand-blue-dark px-6 py-4">

                        <!-- Left -->
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <FileText class="w-4 h-4 opacity-80" />
                            Request Information
                        </h2>

                        <!-- Right -->
                        <div class="flex items-center gap-2">
                            <!-- <span class="text-[11px] font-semibold uppercase tracking-wider text-white">
                                Status
                            </span> -->

                            <span :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border leading-none',
                                getStatus(supplyRequest.status).color
                            ]">
                                <component :is="getStatus(supplyRequest.status).icon" class="w-3.5 h-3.5" />
                                {{ getStatus(supplyRequest.status).label }}
                            </span>
                        </div>

                    </div>

                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">

                        <!-- Transaction ID -->
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <Hash class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Transaction ID
                                </p>
                                <p class="text-sm font-bold text-gray-900 font-mono mt-0.5">
                                    {{ supplyRequest.transaction_id }}
                                </p>
                            </div>
                        </div>

                        <!-- Request Date -->
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <CalendarDays class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Request Date</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ formatDate(supplyRequest.request_date) }}
                                </p>
                            </div>
                        </div>

                        <!-- Department -->
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <Building2 class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Department /
                                    Store</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ requestDepartmentLabel }}
                                </p>
                            </div>
                        </div>

                        <!-- Approver -->
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <User class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Assigned
                                    Approver</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ departmentApprover?.name ?? 'No approver assigned' }}
                                </p>
                                <p v-if="!departmentApprover" class="text-xs text-amber-600 mt-0.5">
                                    Contact HR Admin to assign an approver.
                                </p>
                            </div>
                        </div>

                        <!-- M3 RO Number (always shown) -->
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg mt-0.5"
                                :class="supplyRequest.m3_ro_number ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-50 text-gray-400'">
                                <Hash class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">M3 RO Number</p>
                                <p class="text-sm font-bold font-mono mt-0.5"
                                    :class="supplyRequest.m3_ro_number ? 'text-gray-900' : 'text-gray-400 italic font-normal'">
                                    {{ supplyRequest.m3_ro_number ?? 'Pending — to be filled by HR' }}
                                </p>
                            </div>
                        </div>

                        <!-- M3 DR Number (always shown) -->
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg mt-0.5"
                                :class="supplyRequest.m3_dr_number ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-50 text-gray-400'">
                                <Hash class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">M3 DR Number</p>
                                <p class="text-sm font-bold font-mono mt-0.5"
                                    :class="supplyRequest.m3_dr_number ? 'text-gray-900' : 'text-gray-400 italic font-normal'">
                                    {{ supplyRequest.m3_dr_number ?? 'Pending — to be filled by HR' }}
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Requested Items Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-brand-blue-dark px-6 py-4">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <Package class="w-4 h-4 opacity-80" />
                            Requested Items
                            <span class="ml-auto bg-white/20 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ supplyRequest.items.length }} item{{ supplyRequest.items.length !== 1 ? 's' : '' }}
                            </span>
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <th class="px-5 py-3 text-left">#</th>
                                    <th class="px-5 py-3 text-left">Item Code</th>
                                    <th class="px-5 py-3 text-left">Description</th>
                                    <th class="px-5 py-3 text-center">Qty</th>
                                    <th class="px-5 py-3 text-center">Unit</th>
                                    <th class="px-5 py-3 text-center">Budget Type</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, i) in supplyRequest.items" :key="item.id" class="hover:bg-gray-50/60">
                                    <td class="px-5 py-3.5 text-gray-400 font-medium text-xs">{{ i + 1 }}</td>
                                    <td class="px-5 py-3.5">
                                        <span class="font-mono text-xs font-bold text-brand-blue-dark">{{ item.item_code
                                        }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-700 max-w-xs">
                                        <span class="line-clamp-2">{{ item.item_description || '—' }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="font-bold text-gray-900">{{ item.quantity }}</span>
                                        <span v-if="item.original_quantity && item.original_quantity !== item.quantity"
                                            class="block text-xs text-gray-400">
                                            (was {{ item.original_quantity }})
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center text-gray-600 capitalize">{{ item.item_unit }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span v-if="item.budget_type" :class="[
                                            'px-2 py-1 rounded text-xs font-bold uppercase',
                                            item.budget_type === 'budgeted' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'
                                        ]">{{ item.budget_type }}</span>
                                        <span v-else class="text-xs text-gray-400 italic">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- ── Right Column: Timeline ──────────────────────────────────── -->
            <div class="xl:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden sticky top-6">
                    <div class="bg-brand-blue-dark px-6 py-4">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <Clock class="w-4 h-4 opacity-80" />
                            Request Timeline
                        </h2>
                    </div>

                    <div class="p-6">
                        <div v-if="supplyRequest.timelines.length > 0" class="relative">
                            <div class="absolute left-4 top-4 bottom-4 w-0.5 bg-gray-200" aria-hidden="true" />

                            <div class="flex flex-col gap-6">
                                <div v-for="(event, i) in supplyRequest.timelines" :key="event.id"
                                    class="relative flex gap-4 items-start">
                                    <div :class="[
                                        'relative z-10 flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ring-4',
                                        getTimelineConfig(event.action).color,
                                        getTimelineConfig(event.action).ring,
                                    ]">
                                        <component :is="getTimelineConfig(event.action).icon"
                                            class="w-4 h-4 text-white" />
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

        <!-- Cancel Confirm Modal -->
        <Modal :show="isCancelModalOpen" @close="isCancelModalOpen = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-red-100">
                    <AlertTriangle class="w-6 h-6 text-red-600" />
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-black text-gray-900">Cancel Request</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Are you sure you want to cancel
                        <span class="font-bold text-gray-800">{{ supplyRequest.transaction_id }}</span>?
                        <br>This action cannot be undone.
                    </p>
                </div>
                <div class="flex justify-center gap-4 mt-8">
                    <button @click="isCancelModalOpen = false" class="btn-secondary" :disabled="isProcessing">Keep
                        Request</button>
                    <button @click="confirmCancel" class="btn-danger" :disabled="isProcessing">
                        {{ isProcessing ? 'Cancelling...' : 'Yes, Cancel It' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Edit Confirm Modal -->
        <Modal :show="isEditModalOpen" @close="isEditModalOpen = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-amber-100">
                    <Pencil class="w-6 h-6 text-amber-600" />
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-black text-gray-900">Edit Request</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        This will reopen
                        <span class="font-bold text-gray-800">{{ supplyRequest.transaction_id }}</span>
                        for editing. You will be redirected to the Supplies Catalog.
                        <br><br>
                        <span class="text-amber-700 font-semibold">
                            After making your changes, you must re-submit the request.
                        </span>
                    </p>
                </div>
                <div class="flex justify-center gap-4 mt-8">
                    <button @click="isEditModalOpen = false" class="btn-secondary"
                        :disabled="isProcessing">Cancel</button>
                    <button @click="confirmEdit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-lg transition-colors disabled:opacity-50"
                        :disabled="isProcessing">
                        <RefreshCcw class="w-4 h-4" :class="isProcessing ? 'animate-spin' : ''" />
                        {{ isProcessing ? 'Reopening...' : 'Yes, Edit It' }}
                    </button>
                </div>
            </div>
        </Modal>

    </AppLayout>
</template>
