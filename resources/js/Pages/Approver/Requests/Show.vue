<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft, Clock, CheckCircle2, XCircle, Package, Archive,
    Send, User, Building2, CalendarDays, Hash, AlertTriangle,
    FileText, CheckCheck, Ban, Pencil, RefreshCcw, Trash2, Edit3,
    Save, RotateCcw
} from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { useToast } from '@/Composables/useToast';
import { formatRequestDepartment } from '@/Utils/requestDisplay';

const props = defineProps({
    request: Object,
    departmentApprover: Object,
});

const { showToast } = useToast();

const statusConfig = {
    draft: { label: 'Draft', color: 'bg-amber-100 text-amber-800 border-amber-200', icon: Pencil },
    pending_approval: { label: 'Pending Approval', color: 'bg-amber-100 text-amber-800 border-amber-200', icon: Clock },
    approved: { label: 'Approved', color: 'bg-blue-100 text-blue-800 border-blue-200', icon: CheckCircle2 },
    rejected: { label: 'Rejected', color: 'bg-red-100 text-red-800 border-red-200', icon: XCircle },
    released: { label: 'Released', color: 'bg-green-100 text-green-800 border-green-200', icon: Package },
    cancelled: { label: 'Cancelled', color: 'bg-gray-100 text-gray-600 border-gray-200', icon: XCircle },
    archived: { label: 'Archived', color: 'bg-gray-100 text-gray-500 border-gray-200', icon: Archive },
};

const timelineIcons = {
    submitted: { icon: Send, color: 'bg-blue-500', ring: 'ring-blue-100' },
    approved: { icon: CheckCheck, color: 'bg-green-500', ring: 'ring-green-100' },
    rejected: { icon: XCircle, color: 'bg-red-500', ring: 'ring-red-100' },
    released: { icon: Package, color: 'bg-emerald-500', ring: 'ring-emerald-100' },
    cancelled: { icon: Ban, color: 'bg-gray-400', ring: 'ring-gray-100' },
    archived: { icon: Archive, color: 'bg-gray-400', ring: 'ring-gray-100' },
    reopened: { icon: RefreshCcw, color: 'bg-orange-400', ring: 'ring-orange-100' },
    item_updated: { icon: Edit3, color: 'bg-sky-500', ring: 'ring-sky-100' },
    item_removed: { icon: Trash2, color: 'bg-red-500', ring: 'ring-red-100' },
};

const getStatus = (status) =>
    statusConfig[status] ?? { label: status, color: 'bg-gray-100 text-gray-600 border-gray-200', icon: Clock };

const getTimelineConfig = (action) =>
    timelineIcons[action] ?? { icon: Clock, color: 'bg-gray-400', ring: 'ring-gray-100' };

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const formatDateShort = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const items = computed(() => props.request.items ?? []);
const timeline = computed(() => props.request.timelines ?? []);
const requestDepartmentLabel = computed(() => formatRequestDepartment(props.request));
const canModify = computed(() => props.request.status === 'pending_approval');

const editingQuantity = ref(null);
const quantityForm = useForm({ quantity: '' });

const startEditingQuantity = (item) => {
    editingQuantity.value = item.id;
    quantityForm.clearErrors();
    quantityForm.quantity = item.quantity;
};

const cancelEditing = () => {
    editingQuantity.value = null;
    quantityForm.reset();
    quantityForm.clearErrors();
};

const saveQuantity = (item) => {
    quantityForm.patch(route('approver.approvals.items.update', [props.request.id, item.id]), {
        preserveScroll: true,
        onSuccess: () => {
            editingQuantity.value = null;
            quantityForm.reset();
            showToast('Quantity updated successfully.');
        },
        onError: (errors) => {
            showToast(errors.quantity ?? errors.status ?? 'Could not update quantity.', 'error');
        },
    });
};

const isApproveModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const isRemovingItem = ref(false);
const itemPendingRemoval = ref(null);
const isRemoveItemModalOpen = computed(() => itemPendingRemoval.value !== null);
const rejectForm = useForm({ rejection_reason: '' });

const approveRequest = () => {
    router.patch(route('approver.approvals.approve', props.request.id), {}, {
        onError: (errors) => {
            isApproveModalOpen.value = false;
            showToast(errors.items ?? errors.status ?? 'Could not approve request.', 'error');
        },
    });
};

const openRejectModal = () => {
    rejectForm.reset();
    rejectForm.clearErrors();
    isRejectModalOpen.value = true;
};

const rejectRequest = () => {
    rejectForm.patch(route('approver.approvals.reject', props.request.id), {
        onError: (errors) => {
            showToast(errors.rejection_reason ?? errors.status ?? 'Could not reject request.', 'error');
        },
    });
};

const promptRemoveItem = (item) => {
    itemPendingRemoval.value = item;
};

const removeItem = () => {
    if (!itemPendingRemoval.value) return;

    isRemovingItem.value = true;
    router.delete(route('approver.approvals.items.destroy', [props.request.id, itemPendingRemoval.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            itemPendingRemoval.value = null;
            showToast('Item removed successfully.');
        },
        onError: (errors) => {
            showToast(errors.items ?? errors.status ?? 'Could not remove item.', 'error');
        },
        onFinish: () => {
            isRemovingItem.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Approval ${request.transaction_id}`" />

    <AppLayout>
        <div class="mb-6">
            <Link :href="route('approver.approvals.index')"
                class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-blue-dark transition-colors">
                <ArrowLeft class="w-4 h-4" />
                Back to Approvals
            </Link>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">Request Details</h1>
                <p class="text-sm text-gray-500 mt-1 font-mono tracking-wide">
                    {{ request.transaction_id }}
                </p>
            </div>

            <span :class="[
                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border leading-none self-start sm:self-center',
                getStatus(request.status).color
            ]">
                <component :is="getStatus(request.status).icon" class="w-3.5 h-3.5" />
                {{ getStatus(request.status).label }}
            </span>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 flex flex-col gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="flex items-center justify-between bg-brand-blue-dark px-6 py-4">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <FileText class="w-4 h-4 opacity-80" />
                            Request Information
                        </h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <Hash class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Transaction ID</p>
                                <p class="text-sm font-bold text-gray-900 font-mono mt-0.5">
                                    {{ request.transaction_id }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <CalendarDays class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Request Date</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ formatDate(request.request_date) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <User class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Requestor</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ request.user?.name ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <Building2 class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Department / Store</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ requestDepartmentLabel }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <User class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Assigned Approver</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ request.approver?.name ?? departmentApprover?.name ?? 'No approver assigned' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg mt-0.5"
                                :class="request.m3_ro_number ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-50 text-gray-400'">
                                <Hash class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">M3 RO Number</p>
                                <p class="text-sm font-bold font-mono mt-0.5"
                                    :class="request.m3_ro_number ? 'text-gray-900' : 'text-gray-400 italic font-normal'">
                                    {{ request.m3_ro_number ?? 'Pending - to be filled by HR' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg mt-0.5"
                                :class="request.m3_dr_number ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-50 text-gray-400'">
                                <Hash class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">M3 DR Number</p>
                                <p class="text-sm font-bold font-mono mt-0.5"
                                    :class="request.m3_dr_number ? 'text-gray-900' : 'text-gray-400 italic font-normal'">
                                    {{ request.m3_dr_number ?? 'Pending - to be filled by HR' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-if="request.rejection_reason" class="mx-6 mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3">
                            <AlertTriangle class="w-5 h-5 text-red-600 mt-0.5" />
                            <div>
                                <h3 class="font-bold text-red-800">Rejection Reason</h3>
                                <p class="text-sm text-red-700 mt-1">{{ request.rejection_reason }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-brand-blue-dark px-6 py-4">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <Package class="w-4 h-4 opacity-80" />
                            Requested Items
                            <span class="ml-auto bg-white/20 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ items.length }} item{{ items.length !== 1 ? 's' : '' }}
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
                                    <th v-if="canModify" class="px-5 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, i) in items" :key="item.id" class="hover:bg-gray-50/60">
                                    <td class="px-5 py-3.5 text-gray-400 font-medium text-xs">{{ i + 1 }}</td>
                                    <td class="px-5 py-3.5">
                                        <span class="font-mono text-xs font-bold text-brand-blue-dark">
                                            {{ item.item_code || item.supply?.item_code }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-700 min-w-64 max-w-md">
                                        <span class="line-clamp-2">
                                            {{ item.item_description || item.supply?.item_description || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center min-w-36">
                                        <form v-if="editingQuantity === item.id" @submit.prevent="saveQuantity(item)"
                                            class="flex flex-col items-center gap-1">
                                            <div class="flex items-center justify-center gap-2">
                                                <TextInput v-model="quantityForm.quantity" type="number" min="1" max="9999"
                                                    class="w-20 text-center py-1.5" />
                                                <button type="submit" :disabled="quantityForm.processing"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-green-700 hover:bg-green-50 disabled:opacity-50">
                                                    <Save class="w-4 h-4" />
                                                </button>
                                                <button type="button" @click="cancelEditing"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:bg-gray-100">
                                                    <RotateCcw class="w-4 h-4" />
                                                </button>
                                            </div>
                                            <InputError :message="quantityForm.errors.quantity" />
                                        </form>
                                        <div v-else>
                                            <span class="font-bold text-gray-900">{{ item.quantity }}</span>
                                            <span v-if="item.original_quantity && item.original_quantity !== item.quantity"
                                                class="block text-xs text-gray-400">
                                                was {{ item.original_quantity }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-center text-gray-600 capitalize">
                                        {{ item.item_unit || item.supply?.unit || '-' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span v-if="item.budget_type" :class="[
                                            'px-2 py-1 rounded text-xs font-bold uppercase',
                                            item.budget_type === 'budgeted' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'
                                        ]">
                                            {{ item.budget_type }}
                                        </span>
                                        <span v-else class="text-xs text-gray-400 italic">-</span>
                                    </td>
                                    <td v-if="canModify" class="px-5 py-3.5">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="startEditingQuantity(item)"
                                                :disabled="editingQuantity === item.id || quantityForm.processing"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-blue-700 hover:bg-blue-50 disabled:opacity-50"
                                                title="Edit quantity">
                                                <Edit3 class="w-4 h-4" />
                                            </button>
                                            <button @click="promptRemoveItem(item)"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-red-700 hover:bg-red-50"
                                                title="Remove item">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="items.length === 0">
                                    <td :colspan="canModify ? 7 : 6" class="px-6 py-12 text-center text-gray-400">
                                        No items in this request.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="canModify" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-black text-gray-900">Approval Decision</h2>
                            <p class="text-sm text-gray-500 mt-1">
                                Review item quantities before approving or rejecting this request.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button @click="isApproveModalOpen = true" class="btn-primary">
                                <CheckCheck class="w-4 h-4 mr-2" />
                                Approve
                            </button>
                            <button @click="openRejectModal" class="btn-danger">
                                <XCircle class="w-4 h-4 mr-2" />
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden sticky top-6">
                    <div class="bg-brand-blue-dark px-6 py-4">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <Clock class="w-4 h-4 opacity-80" />
                            Request Timeline
                        </h2>
                    </div>

                    <div class="p-6">
                        <div v-if="timeline.length > 0" class="relative">
                            <div class="absolute left-4 top-4 bottom-4 w-0.5 bg-gray-200" aria-hidden="true" />
                            <div class="flex flex-col gap-6">
                                <div v-for="event in timeline" :key="event.id" class="relative flex gap-4 items-start">
                                    <div :class="[
                                        'relative z-10 flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ring-4',
                                        getTimelineConfig(event.action).color,
                                        getTimelineConfig(event.action).ring,
                                    ]">
                                        <component :is="getTimelineConfig(event.action).icon" class="w-4 h-4 text-white" />
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

        <Modal :show="isApproveModalOpen" @close="isApproveModalOpen = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-green-100">
                    <CheckCheck class="w-6 h-6 text-green-700" />
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-black text-gray-900">Approve Request</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        This will mark
                        <span class="font-bold text-gray-800">{{ request.transaction_id }}</span>
                        as approved and send it to the next fulfillment step.
                    </p>
                </div>
                <div class="flex justify-center gap-4 mt-8">
                    <button @click="isApproveModalOpen = false" class="btn-secondary">Cancel</button>
                    <button @click="approveRequest" class="btn-primary">Approve</button>
                </div>
            </div>
        </Modal>

        <Modal :show="isRemoveItemModalOpen" @close="itemPendingRemoval = null" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-red-100">
                    <Trash2 class="w-6 h-6 text-red-600" />
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-black text-gray-900">Remove Item</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Remove
                        <span class="font-bold text-gray-800">
                            {{ itemPendingRemoval?.item_code || itemPendingRemoval?.supply?.item_code }}
                        </span>
                        from this request?
                    </p>
                </div>
                <div class="flex justify-center gap-4 mt-8">
                    <button @click="itemPendingRemoval = null" class="btn-secondary" :disabled="isRemovingItem">
                        Cancel
                    </button>
                    <button @click="removeItem" class="btn-danger" :disabled="isRemovingItem">
                        {{ isRemovingItem ? 'Removing...' : 'Remove Item' }}
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="isRejectModalOpen" @close="isRejectModalOpen = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-red-100">
                    <AlertTriangle class="w-6 h-6 text-red-600" />
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-black text-gray-900">Reject Request</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        A rejection reason is required and will be visible to the requestor.
                    </p>
                </div>

                <form @submit.prevent="rejectRequest" class="mt-6">
                    <label for="rejection_reason" class="block text-sm font-bold text-gray-700 mb-2">
                        Rejection Reason
                    </label>
                    <textarea id="rejection_reason" v-model="rejectForm.rejection_reason" rows="4" required
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-blue focus:ring-brand-blue text-sm"
                        placeholder="Explain why this request is being rejected."></textarea>
                    <InputError :message="rejectForm.errors.rejection_reason" class="mt-2" />

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="isRejectModalOpen = false" class="btn-secondary"
                            :disabled="rejectForm.processing">
                            Cancel
                        </button>
                        <button type="submit" class="btn-danger" :disabled="rejectForm.processing">
                            {{ rejectForm.processing ? 'Rejecting...' : 'Reject Request' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
