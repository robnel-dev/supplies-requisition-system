<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { PackagePlus } from 'lucide-vue-next';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: Boolean,
    supply: Object,
});

const emit = defineEmits(['close']);
const { showToast } = useToast();

const form = useForm({
    supply_id: null,
    quantity: 1,
});

watch(() => props.supply, (newSupply) => {
    if (newSupply) {
        form.supply_id = newSupply.id;
        form.quantity = 1;
    }
});

const submit = () => {
    form.post(route('requestor.cart.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showToast('Item added to your request list.');
            emit('close');
        },
        onError: () => {
            showToast('Unable to add item to list. Please try again.', 'error');
        },
    });
};
</script>

<template>
    <!-- Transition wrapper -->

    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
        @click.self="$emit('close')">
        <transition name="modal" appear>
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-0 overflow-hidden">
                <div class="bg-brand-blue-dark px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-black text-white flex items-center tracking-wide">
                        <PackagePlus class="w-5 h-5 mr-2 opacity-80" /> Add to Item List
                    </h3>
                    <button @click="$emit('close')"
                        class="text-white hover:text-gray-300 font-bold text-xl">&times;</button>
                </div>

                <form @submit.prevent="submit" class="p-6">
                    <div v-if="supply" class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <div class="grid grid-cols-3 gap-2 text-sm mb-2">
                            <span class="text-gray-500 font-medium">Item Code:</span>
                            <span class="col-span-2 font-bold text-brand-navy">{{ supply.item_code }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-sm mb-2">
                            <span class="text-gray-500 font-medium">Category:</span>
                            <span class="col-span-2">{{ supply.category }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-sm mb-2">
                            <span class="text-gray-500 font-medium">Description:</span>
                            <span class="col-span-2">{{ supply.item_description }} <br>
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-sm">
                            <span class="text-gray-500 font-medium">Unit:</span>
                            <span class="col-span-2">{{ supply.unit || supply.unit_of_measure }}</span>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-brand-navy mb-2">Requested Quantity</label>
                        <input type="number" v-model="form.quantity" min="1"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-brand-blue focus:ring-brand-blue text-lg"
                            required>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="$emit('close')" class="btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="px-5 py-2.5 bg-brand-blue text-white rounded-md font-medium hover:bg-brand-navy transition flex items-center gap-2">
                            <PackagePlus class="w-5 h-5" />
                            Add Item
                        </button>
                    </div>
                </form>
            </div>
        </transition>
    </div>

</template>

<style>
/* Transition classes */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
    transform: scale(0.95);
}

.modal-enter-to,
.modal-leave-from {
    opacity: 1;
    transform: scale(1);
}
</style>