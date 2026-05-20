<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Archive,
    ArrowLeft,
    Ban,
    Building2,
    CalendarDays,
    CheckCheck,
    CheckCircle2,
    Clock,
    Edit3,
    FileText,
    Hash,
    Package,
    Pencil,
    RefreshCcw,
    Save,
    Send,
    Trash2,
    Truck,
    User,
    XCircle,
} from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { useToast } from '@/Composables/useToast';
import { formatRequestDepartment } from '@/Utils/requestDisplay';

const props = defineProps({
    request: Object,
});

const { showToast } = useToast();

const statusConfig = {
    approved: { label: 'Approved', color: 'bg-blue-100 text-blue-800 border-blue-200', icon: CheckCircle2 },
    released: { label: 'Released', color: 'bg-green-100 text-green-800 border-green-200', icon: Package },
    rejected: { label: 'Rejected', color: 'bg-red-100 text-red-800 border-red-200', icon: XCircle },
    archived: { label: 'Archived', color: 'bg-gray-100 text-gray-500 border-gray-200', icon: Archive },
};

const timelineIcons = {
    submitted: { icon: Send, color: 'bg-blue-500', ring: 'ring-blue-100' },
    approved: { icon: CheckCheck, color: 'bg-green-500', ring: 'ring-green-100' },
    rejected: { icon: XCircle, color: 'bg-red-500', ring: 'ring-red-100' },
    released: { icon: Package, color: 'bg-emerald-500', ring: 'ring-emerald-100' },
    archived: { icon: Archive, color: 'bg-gray-400', ring: 'ring-gray-100' },
    cancelled: { icon: Ban, color: 'bg-gray-400', ring: 'ring-gray-100' },
    reopened: { icon: RefreshCcw, color: 'bg-orange-400', ring: 'ring-orange-100' },
    item_removed: { icon: Trash2, color: 'bg-red-500', ring: 'ring-red-100' },
    release_details_updated: { icon: FileText, color: 'bg-sky-500', ring: 'ring-sky-100' },
};

const getStatus = (status) =>
    statusConfig[status] ?? { label: status, color: 'bg-gray-100 text-gray-600 border-gray-200', icon: Clock };

const getTimelineConfig = (action) =>
    timelineIcons[action] ?? { icon: Clock, color: 'bg-gray-400', ring: 'ring-gray-100' };

const formatDate = (date) => {
    if (!date) return '-';

    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDateShort = (date) => {
    if (!date) return '-';

    return new Date(date).toLocaleDateString('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const initialItemRows = () => (props.request.items ?? []).map((item) => ({
    id: item.id,
    budget_type: item.budget_type ?? '',
    allocated_quantity: item.budget_type === 'budgeted'
        ? (item.allocated_quantity ?? 0)
        : (item.allocated_quantity ?? ''),
}));

const fulfillmentForm = useForm({
    m3_ro_number: props.request.m3_ro_number ?? '',
    m3_dr_number: props.request.m3_dr_number ?? '',
    items: initialItemRows(),
});

const rejectForm = useForm({
    rejection_reason: '',
});

const isSaveModalOpen = ref(false);
const isReleaseModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const isArchiveModalOpen = ref(false);
const isProcessingAction = ref(false);

const items = computed(() => props.request.items ?? []);
const timeline = computed(() => props.request.timelines ?? []);
const editableItems = computed(() => items.value.map((item, index) => ({
    ...item,
    form: fulfillmentForm.items[index],
    index,
})));
const requestDepartmentLabel = computed(() => formatRequestDepartment(props.request));
const canEditDetails = computed(() => props.request.status === 'approved');
const canRelease = computed(() => props.request.status === 'approved');
const canReject = computed(() => props.request.status === 'approved');
const canArchive = computed(() =>
    props.request.status === 'released'
    && Boolean(props.request.m3_ro_number)
    && Boolean(props.request.m3_dr_number)
);
const archiveButtonClass = computed(() => canArchive.value
    ? 'inline-flex items-center px-5 py-3 bg-yellow-700 hover:bg-yellow-800 text-white text-sm font-bold rounded-lg shadow-md transition-colors focus:ring-2 focus:ring-yellow-700/40 focus:outline-none whitespace-nowrap disabled:opacity-50'
    : 'inline-flex items-center px-5 py-3 bg-gray-700 text-white text-sm font-bold rounded-lg shadow-md transition-colors focus:outline-none whitespace-nowrap disabled:opacity-60');
const actionDescription = computed(() => props.request.status === 'released'
    ? 'This released request is locked and can only be archived.'
    : 'Save allocation details and reference numbers before releasing.');

const savedItemsReady = computed(() => items.value.every((item) => {
    if (!['budgeted', 'unbudgeted'].includes(item.budget_type)) {
        return false;
    }

    return item.budget_type !== 'budgeted' || item.allocated_quantity !== null;
}));

const firstError = (errors, fallback) => Object.values(errors)[0] ?? fallback;

const itemError = (index, field) => fulfillmentForm.errors[`items.${index}.${field}`];

const handleBudgetTypeChange = (row) => {
    if (row.form.budget_type === 'unbudgeted') {
        row.form.allocated_quantity = '';
        return;
    }

    if (row.form.budget_type === 'budgeted' && (row.form.allocated_quantity === '' || row.form.allocated_quantity === null)) {
        row.form.allocated_quantity = 0;
    }
};

const displayBalance = (row) => {
    if (row.form.budget_type === 'unbudgeted') {
        return 0;
    }

    if (row.form.budget_type !== 'budgeted') {
        return '-';
    }

    const allocated = row.form.allocated_quantity === '' || row.form.allocated_quantity === null
        ? 0
        : Number(row.form.allocated_quantity);

    return Math.max((row.quantity ?? 0) - allocated, 0);
};

const budgetBadgeClass = (budgetType) =>
    budgetType === 'budgeted'
        ? 'bg-green-100 text-green-700'
        : 'bg-orange-100 text-orange-700';

const openSaveModal = () => {
    fulfillmentForm.clearErrors();
    isSaveModalOpen.value = true;
};

const saveDetails = () => {
    fulfillmentForm.patch(route('admin.releases.update', props.request.id), {
        preserveScroll: true,
        onSuccess: () => {
            fulfillmentForm.defaults({
                m3_ro_number: fulfillmentForm.m3_ro_number,
                m3_dr_number: fulfillmentForm.m3_dr_number,
                items: fulfillmentForm.items.map((item) => ({ ...item })),
            });
            fulfillmentForm.clearErrors();
            isSaveModalOpen.value = false;
            showToast('Release details saved.');
        },
        onError: (errors) => {
            showToast(firstError(errors, 'Could not save release details.'), 'error');
        },
    });
};

const openReleaseModal = () => {
    if (fulfillmentForm.isDirty) {
        showToast('Please save release details before releasing.', 'error');
        return;
    }

    if (!props.request.m3_ro_number || !props.request.m3_dr_number) {
        showToast('M3 RO Number and M3 DR Number are required before release.', 'error');
        return;
    }

    if (!savedItemsReady.value) {
        showToast('Please save budget type and HRD allocation details before release.', 'error');
        return;
    }

    isReleaseModalOpen.value = true;
};

const releaseRequest = () => {
    isProcessingAction.value = true;
    router.patch(route('admin.releases.release', props.request.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isReleaseModalOpen.value = false;
            showToast('Request released successfully.');
        },
        onError: (errors) => {
            showToast(firstError(errors, 'Could not release request.'), 'error');
        },
        onFinish: () => {
            isProcessingAction.value = false;
        },
    });
};

const openRejectModal = () => {
    if (fulfillmentForm.isDirty) {
        showToast('Please save or reload before rejecting this request.', 'error');
        return;
    }

    rejectForm.reset();
    rejectForm.clearErrors();
    isRejectModalOpen.value = true;
};

const rejectRequest = () => {
    rejectForm.patch(route('admin.releases.reject', props.request.id), {
        onSuccess: () => {
            isRejectModalOpen.value = false;
            showToast('Request rejected.');
        },
        onError: (errors) => {
            showToast(errors.rejection_reason ?? firstError(errors, 'Could not reject request.'), 'error');
        },
    });
};

const openArchiveModal = () => {
    if (fulfillmentForm.isDirty) {
        showToast('Please save release details before archiving.', 'error');
        return;
    }

    if (!canArchive.value) {
        showToast('Only released requests with M3 RO and M3 DR numbers can be archived.', 'error');
        return;
    }

    isArchiveModalOpen.value = true;
};

const archiveRequest = () => {
    isProcessingAction.value = true;
    router.patch(route('admin.releases.archive', props.request.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isArchiveModalOpen.value = false;
            showToast('Request archived.');
        },
        onError: (errors) => {
            showToast(firstError(errors, 'Could not archive request.'), 'error');
        },
        onFinish: () => {
            isProcessingAction.value = false;
        },
    });
};
</script>

<template>

    <Head :title="`Release ${request.transaction_id}`" />

    <AppLayout>
        <div class="mb-6">
            <Link :href="route('admin.releases.index')"
                class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-blue-dark transition-colors">
                <ArrowLeft class="w-4 h-4" />
                Back to Releases
            </Link>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">Release Details</h1>
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
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Control Number
                                </p>
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
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Department /
                                    Store</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ requestDepartmentLabel }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <CheckCheck class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Approved By</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ request.manager_approver?.name ?? request.approver?.name ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ formatDate(request.manager_approved_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-50 text-brand-blue-dark mt-0.5">
                                <Truck class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Released By</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ request.hr_admin_releaser?.name ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ formatDate(request.hr_admin_released_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 pb-6">
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-5 rounded-lg border border-gray-200 bg-gray-50/60 p-4">
                            <div>
                                <label for="m3_ro_number"
                                    class="block text-xs text-gray-500 font-bold uppercase tracking-wider">
                                    M3 RO Number
                                </label>
                                <TextInput id="m3_ro_number" v-model="fulfillmentForm.m3_ro_number" type="text"
                                    :disabled="!canEditDetails" class="mt-1 block w-full font-mono"
                                    :class="!canEditDetails ? 'bg-gray-100 text-gray-500' : ''"
                                    placeholder="Enter M3 RO number" />
                                <InputError :message="fulfillmentForm.errors.m3_ro_number" class="mt-2" />
                            </div>

                            <div>
                                <label for="m3_dr_number"
                                    class="block text-xs text-gray-500 font-bold uppercase tracking-wider">
                                    M3 DR Number
                                </label>
                                <TextInput id="m3_dr_number" v-model="fulfillmentForm.m3_dr_number" type="text"
                                    :disabled="!canEditDetails" class="mt-1 block w-full font-mono"
                                    :class="!canEditDetails ? 'bg-gray-100 text-gray-500' : ''"
                                    placeholder="Enter M3 delivery number" />
                                <InputError :message="fulfillmentForm.errors.m3_dr_number" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div v-if="request.rejection_reason"
                        class="mx-6 mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
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
                        <table class="w-full text-sm whitespace-nowrap">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <th class="px-5 py-3 text-left">#</th>
                                    <th class="px-5 py-3 text-left">Item Code</th>
                                    <th class="px-5 py-3 text-left">Description</th>
                                    <th class="px-5 py-3 text-center">Approved Qty</th>
                                    <th class="px-5 py-3 text-center">Unit</th>
                                    <th class="px-5 py-3 text-center">Budget Type</th>
                                    <th class="px-5 py-3 text-center">HRD Allocation</th>
                                    <th class="px-5 py-3 text-center">Balance</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="row in editableItems" :key="row.id" class="hover:bg-gray-50/60">
                                    <td class="px-5 py-3.5 text-gray-400 font-medium text-xs">
                                        {{ row.index + 1 }}
                                    </td>

                                    <td class="px-5 py-3.5">
                                        <span class="font-mono text-xs font-bold text-brand-blue-dark">
                                            {{ row.item_code || row.supply?.item_code }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-3.5 text-gray-700">
                                        <span>
                                            {{ row.item_description || row.supply?.item_description || '-' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-3.5 text-center">
                                        <span class="font-bold text-gray-900">
                                            {{ row.quantity }}
                                        </span>

                                        <span v-if="row.original_quantity && row.original_quantity !== row.quantity"
                                            class="block text-xs text-gray-400">
                                            was {{ row.original_quantity }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-3.5 text-center text-gray-600 capitalize">
                                        {{ row.item_unit || row.supply?.unit || '-' }}
                                    </td>

                                    <td class="px-5 py-3.5 text-center min-w-44">
                                        <div v-if="canEditDetails" class="flex flex-col items-center gap-1">
                                            <select v-model="row.form.budget_type" @change="handleBudgetTypeChange(row)"
                                                class="block w-full rounded-lg border-gray-300 py-2 text-sm focus:border-brand-blue-darker focus:ring-brand-blue-darker">
                                                <option value="">Select type</option>
                                                <option value="budgeted">Budgeted</option>
                                                <option value="unbudgeted">Unbudgeted</option>
                                            </select>

                                            <InputError :message="itemError(row.index, 'budget_type')" />
                                        </div>

                                        <span v-else-if="row.budget_type" :class="[
                                            'px-2 py-1 rounded text-xs font-bold uppercase',
                                            budgetBadgeClass(row.budget_type)
                                        ]">
                                            {{ row.budget_type }}
                                        </span>

                                        <span v-else class="text-xs text-gray-400 italic">
                                            -
                                        </span>
                                    </td>

                                    <td class="px-5 py-3.5 text-center min-w-36">
                                        <div v-if="canEditDetails" class="flex flex-col items-center gap-1">
                                            <TextInput v-model="row.form.allocated_quantity" type="number" min="0"
                                                :max="row.quantity" :disabled="row.form.budget_type === 'unbudgeted'"
                                                class="w-24 text-center py-2" :class="row.form.budget_type === 'unbudgeted'
                                                    ? 'bg-gray-100 text-gray-400'
                                                    : ''" />

                                            <InputError :message="itemError(row.index, 'allocated_quantity')" />
                                        </div>

                                        <span v-else class="font-bold text-gray-900">
                                            {{ row.allocated_quantity ?? '-' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-3.5 text-center">
                                        <span class="font-bold text-gray-900">
                                            {{ canEditDetails ? displayBalance(row) : (row.balance ?? '-') }}
                                        </span>
                                    </td>
                                </tr>

                                <tr v-if="items.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                        No items in this request.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-black text-gray-900">Request Actions</h2>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ actionDescription }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button v-if="canEditDetails" @click="openSaveModal" class="btn-secondary"
                                :disabled="fulfillmentForm.processing">
                                <Save class="w-4 h-4 mr-2" />
                                Save Changes
                            </button>
                            <button v-if="canRelease" @click="openReleaseModal" class="btn-primary">
                                <Truck class="w-4 h-4 mr-2" />
                                Release
                            </button>
                            <button v-if="canReject" @click="openRejectModal" class="btn-danger">
                                <XCircle class="w-4 h-4 mr-2" />
                                Reject
                            </button>
                            <button v-if="request.status === 'released'" @click="openArchiveModal" :class="archiveButtonClass"
                                :disabled="!canArchive || isProcessingAction">
                                <Archive class="w-4 h-4 mr-2" />
                                Archive
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

        <Modal :show="isSaveModalOpen" @close="isSaveModalOpen = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-blue-100">
                    <Save class="w-6 h-6 text-brand-blue-dark" />
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-black text-gray-900">Save Release Details</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Save M3 references, budget types, and HRD allocations for
                        <span class="font-bold text-gray-800">{{ request.transaction_id }}</span>?
                    </p>
                </div>
                <div class="flex justify-center gap-4 mt-8">
                    <button @click="isSaveModalOpen = false" class="btn-secondary"
                        :disabled="fulfillmentForm.processing">
                        Cancel
                    </button>
                    <button @click="saveDetails" class="btn-primary" :disabled="fulfillmentForm.processing">
                        <Save class="w-4 h-4 mr-2" />
                        {{ fulfillmentForm.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="isReleaseModalOpen" @close="isReleaseModalOpen = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-green-100">
                    <Truck class="w-6 h-6 text-green-700" />
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-black text-gray-900">Release Request</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        This will mark
                        <span class="font-bold text-gray-800">{{ request.transaction_id }}</span>
                        as released and keep it available for manual archiving.
                    </p>
                </div>
                <div class="flex justify-center gap-4 mt-8">
                    <button @click="isReleaseModalOpen = false" class="btn-secondary" :disabled="isProcessingAction">
                        Cancel
                    </button>
                    <button @click="releaseRequest" class="btn-primary" :disabled="isProcessingAction">
                        <Truck class="w-4 h-4 mr-2" />
                        {{ isProcessingAction ? 'Releasing...' : 'Release' }}
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

        <Modal :show="isArchiveModalOpen" @close="isArchiveModalOpen = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-gray-100">
                    <Archive class="w-6 h-6 text-gray-700" />
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-black text-gray-900">Archive Request</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Archive
                        <span class="font-bold text-gray-800">{{ request.transaction_id }}</span>?
                        This removes it from the active release queue.
                    </p>
                </div>
                <div class="flex justify-center gap-4 mt-8">
                    <button @click="isArchiveModalOpen = false" class="btn-secondary" :disabled="isProcessingAction">
                        Cancel
                    </button>
                    <button @click="archiveRequest" :class="archiveButtonClass" :disabled="isProcessingAction">
                        <Archive class="w-4 h-4 mr-2" />
                        {{ isProcessingAction ? 'Archiving...' : 'Archive' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
