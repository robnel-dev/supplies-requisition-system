<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Trash2, ScrollText, ShoppingCart } from 'lucide-vue-next';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: Boolean,
    cart: Object,
});

const { showToast } = useToast();

const emit = defineEmits(['close']);

const checkoutForm = useForm({});

const confirmDialog = ref({
    show: false,
    title: '',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    destructive: false,
    type: '',
    item: null,
});

const openConfirmDialog = ({ title, message, confirmText = 'Confirm', cancelText = 'Cancel', destructive = false, type, item = null }) => {
    confirmDialog.value = {
        show: true,
        title,
        message,
        confirmText,
        cancelText,
        destructive,
        type,
        item,
    };
};

const closeConfirmDialog = () => {
    confirmDialog.value.show = false;
};

const updateQuantity = (item, newQuantity) => {
    if (newQuantity < 1) return;
    router.put(route('requestor.cart.update', item.id), { quantity: newQuantity }, { preserveScroll: true });
};

const promptRemoveItem = (item) => {
    openConfirmDialog({
        title: 'Remove item from request list',
        message: `Are you sure you want to remove "${item.item_description}" from your current request?`,
        confirmText: 'Remove item',
        cancelText: 'Keep item',
        destructive: true,
        type: 'remove',
        item,
    });
};

const promptSubmitRequest = () => {
    openConfirmDialog({
        title: 'Submit request for approval',
        message: 'Once you submit, this request will be sent for approval and you will not be able to edit the items until it is processed.',
        confirmText: 'Submit request',
        cancelText: 'Cancel',
        destructive: false,
        type: 'checkout',
    });
};

const handleConfirmAction = () => {
    if (confirmDialog.value.type === 'remove' && confirmDialog.value.item) {
        router.delete(route('requestor.cart.destroy', confirmDialog.value.item.id), {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Item removed from your request list.');
            },
            onError: () => {
                showToast('Unable to remove the item. Please try again.', 'error');
            },
        });
    }

    if (confirmDialog.value.type === 'checkout') {
        checkoutForm.post(route('requestor.cart.checkout'), {
            onSuccess: () => {
                showToast('Request submitted successfully.');
                emit('close');
            },
            onError: () => {
                showToast('Could not submit the request. Please try again.', 'error');
            },
        });
    }

    closeConfirmDialog();
};
</script>

<template>
    <!-- Overlay with transition and click.self to close -->
    <transition name="fade">
        <div v-if="show" class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm transition-opacity"
            @click.self="$emit('close')">
            <!-- Slide-over panel with transition -->
            <transition name="slide">
                <div class="fixed inset-y-0 right-0 w-full max-w-md bg-white shadow-2xl flex flex-col transform transition-transform duration-300"
                    v-if="show">
                    <!-- Header -->
                    <div class="p-5 bg-brand-navy text-white flex justify-between items-center">
                        <h2 class="text-lg font-black text-white flex items-center tracking-wide">
                            <ScrollText class="w-5 h-5 mr-2" /> Request List Draft
                        </h2>
                        <button @click="$emit('close')"
                            class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 overflow-y-auto p-5 space-y-4">
                        <div v-if="!cart?.items?.length"
                            class="flex flex-col items-center justify-center mt-16 text-center">

                            <div class="bg-gray-100 rounded-full p-6 mb-4">
                                <ShoppingCart class="w-10 h-10 text-gray-400" />
                            </div>

                            <h2 class="text-lg font-semibold text-gray-700 mb-1">
                                Your request list is empty
                            </h2>

                            <p class="text-sm text-gray-500 mb-4">
                                Start adding items to keep track of your requests.
                            </p>

                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="item in cart.items" :key="item.id"
                                class="border rounded-xl p-4 bg-gray-50 flex items-center justify-between gap-4">

                                <!-- Item Info -->
                                <div class="flex-1 space-y-1">
                                    <p class="text-xs text-gray-400">{{ item.item_code }} <span
                                            class="text-xs text-gray-500 ml-2">({{ item.item_unit }})</span></p>
                                    <p class="font-medium text-gray-800 leading-snug">
                                        {{ item.item_description }}
                                    </p>
                                </div>

                                <!-- Controls -->
                                <div class="flex items-center gap-3">

                                    <!-- Quantity Control -->
                                    <div class="flex items-center border rounded-lg overflow-hidden shadow-sm">
                                        <button @click="updateQuantity(item, item.quantity - 1)"
                                            class="px-3 py-1.5 bg-red-500 hover:bg-red-700 text-white text-sm transition">
                                            −
                                        </button>

                                        <span class="px-4 text-sm font-semibold text-gray-800 bg-white">
                                            {{ item.quantity }}
                                        </span>

                                        <button @click="updateQuantity(item, item.quantity + 1)"
                                            class="px-3 py-1.5 bg-green-600 hover:bg-green-800 text-white text-sm transition">
                                            +
                                        </button>
                                    </div>

                                    <!-- Remove Button -->
                                    <button @click="promptRemoveItem(item)"
                                        class="p-2 rounded-lg bg-red-100 hover:bg-red-200 text-red-600 transition">
                                        <Trash2 class="w-5 h-5" />
                                    </button>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-5 border-t bg-white">
                        <button @click="promptSubmitRequest"
                            :disabled="checkoutForm.processing || !cart || !cart.items || cart.items.length === 0"
                            class="w-full py-3 rounded-xl font-semibold text-white bg-blue-600 hover:bg-blue-700 active:scale-[0.98] transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Submit Request
                        </button>
                    </div>

                </div>
            </transition>
        </div>
    </transition>

    <ConfirmDialog :show="confirmDialog.show" :title="confirmDialog.title" :message="confirmDialog.message"
        :confirm-text="confirmDialog.confirmText" :cancel-text="confirmDialog.cancelText"
        :destructive="confirmDialog.destructive" @close="closeConfirmDialog" @confirm="handleConfirmAction" />
</template>

<style scoped>
/* Fade for overlay */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-enter-to,
.fade-leave-from {
    opacity: 1;
}

/* Slide-over panel transition */
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.25s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}

.slide-enter-to,
.slide-leave-from {
    transform: translateX(0);
}
</style>