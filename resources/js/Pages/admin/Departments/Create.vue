<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    hoRefs: {
        type: Array,
        default: () => [],
    },
    storeAreaOptions: {
        type: Array,
        default: () => [],
    },
});

const selectedReference = ref(null);

const form = useForm({
    external_department_reference_id: '',
    code: '',
    name: '',
    type: 'head_office',
    area: 'HO',
    cost_center: '',
    cost_center_source: 'manual',
});

const hasExternalReference = computed(() => Boolean(form.external_department_reference_id));
const costCenterLocked = computed(() => hasExternalReference.value && form.cost_center_source === 'external');

const referenceLabel = (reference) => {
    const company = reference.company_code ? ` (${reference.company_code})` : '';

    return `${reference.department_code} - ${reference.name}${company}`;
};

const resetFields = () => {
    form.external_department_reference_id = '';
    form.code = '';
    form.name = '';
    form.area = form.type === 'head_office' ? 'HO' : '';
    form.cost_center = '';
    form.cost_center_source = 'manual';
    selectedReference.value = null;
};

const selectHeadOfficeReference = () => {
    if (!form.external_department_reference_id) {
        selectedReference.value = null;
        form.cost_center_source = 'manual';
        return;
    }

    const reference = props.hoRefs.find((item) => Number(item.id) === Number(form.external_department_reference_id));

    selectedReference.value = reference || null;
    form.code = reference?.department_code || '';
    form.name = reference?.name || '';
    form.area = reference?.area || 'HO';
    form.cost_center = reference?.cost_center || '';
    form.cost_center_source = 'external';
};

const selectStoreArea = () => {
    const area = props.storeAreaOptions.find((item) => item.area === form.area);

    form.external_department_reference_id = '';
    selectedReference.value = null;
    form.code = area?.code || '';
    form.name = area?.name || '';
    form.cost_center = '';
    form.cost_center_source = 'manual';
};

const submit = () => {
    form.post(route('admin.departments.store'));
};
</script>

<template>
    <div class="max-w-2xl mx-auto p-6">
        <h1 class="text-xl font-semibold mb-6">Create Department</h1>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Department Type</label>
                <select v-model="form.type" @change="resetFields" class="input-base">
                    <option value="head_office">Head Office Function</option>
                    <option value="store">Store Area</option>
                </select>
                <p v-if="form.errors.type" class="error-text">{{ form.errors.type }}</p>
            </div>

            <div v-if="form.type === 'head_office'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Head Office Reference</label>
                <select v-model="form.external_department_reference_id" @change="selectHeadOfficeReference" class="input-base">
                    <option value="">Manual Entry</option>
                    <option v-for="reference in hoRefs" :key="reference.id" :value="reference.id">
                        {{ referenceLabel(reference) }}
                    </option>
                </select>
                <p v-if="form.errors.external_department_reference_id" class="error-text">
                    {{ form.errors.external_department_reference_id }}
                </p>
            </div>

            <div v-if="form.type === 'store'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Store Area</label>
                <select v-model="form.area" @change="selectStoreArea" class="input-base">
                    <option value="">Select Area</option>
                    <option v-for="area in storeAreaOptions" :key="area.area" :value="area.area">
                        {{ area.code }} - {{ area.name }}
                    </option>
                </select>
                <p v-if="form.errors.area" class="error-text">{{ form.errors.area }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department Code / Area Number</label>
                    <input v-model="form.code" type="text" class="input-base"
                        :readonly="hasExternalReference || form.type === 'store'"
                        :class="{ 'bg-gray-100': hasExternalReference || form.type === 'store' }" />
                    <p v-if="form.errors.code" class="error-text">{{ form.errors.code }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department / Area Name</label>
                    <input v-model="form.name" type="text" class="input-base"
                        :readonly="hasExternalReference || form.type === 'store'"
                        :class="{ 'bg-gray-100': hasExternalReference || form.type === 'store' }" />
                    <p v-if="form.errors.name" class="error-text">{{ form.errors.name }}</p>
                </div>
            </div>

            <div v-if="form.type === 'head_office'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cost Center</label>
                <div class="relative">
                    <input v-model="form.cost_center" type="text" class="input-base pr-28"
                        :readonly="costCenterLocked"
                        :class="{ 'bg-gray-100': costCenterLocked }" />
                    <span v-if="form.cost_center_source === 'external'"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                        External
                    </span>
                </div>
                <p v-if="form.errors.cost_center" class="error-text">{{ form.errors.cost_center }}</p>
                <button v-if="costCenterLocked" type="button" @click="form.cost_center_source = 'manual'" class="text-sm text-blue-600 font-semibold mt-2">
                    Override Cost Center
                </button>
            </div>

            <div v-else class="rounded-md border border-yellow-100 bg-yellow-50 px-3 py-2 text-sm font-semibold text-yellow-900">
                Store area departments do not hold one cost center. The store and cost center are selected in User Management.
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a :href="route('admin.departments.index')" class="btn-secondary">Cancel</a>
                <button type="submit" :disabled="form.processing" class="btn-primary">
                    {{ form.processing ? 'Saving...' : 'Create Department' }}
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
.input-base {
    @apply w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50;
}

.btn-secondary {
    @apply px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-50;
}

.error-text {
    @apply text-red-500 text-xs mt-1;
}
</style>
