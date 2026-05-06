<script setup>
import { computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import { AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm Action',
    },
    message: {
        type: String,
        default: 'Are you sure you want to continue?',
    },
    confirmText: {
        type: String,
        default: 'Confirm',
    },
    cancelText: {
        type: String,
        default: 'Cancel',
    },
    destructive: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);

const buttonClass = computed(() => {
    return props.destructive
        ? 'inline-flex items-center justify-center rounded-full bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-red-100/50 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition'
        : 'inline-flex items-center justify-center rounded-full bg-brand-navy px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-blue/10 hover:bg-[#0f3f72] focus:outline-none focus:ring-2 focus:ring-brand-blue focus:ring-offset-2 transition';
});

const close = () => emit('close');
const confirm = () => emit('confirm');
</script>

<template>
    <Modal :show="show" maxWidth="sm" @close="close">
        <div
            class="flex flex-col gap-6 rounded-[28px] bg-white/95 p-6 sm:p-8 shadow-2xl shadow-slate-900/5 ring-1 ring-slate-200">
            <div class="flex items-start gap-4">
                <span
                    class="mt-1 flex h-11 w-11 items-center justify-center rounded-3xl bg-brand-blue/10 text-brand-blue">
                    <AlertTriangle class="h-5 w-5" />
                </span>

                <div class="min-w-0">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ title }}</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ message }}</p>
                </div>
            </div>

            <div class="border-t border-slate-200/80 pt-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="close"
                        class="inline-flex items-center justify-center rounded-full bg-brand-navy px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-blue/10 hover:bg-[#0f3f72] transition">
                        {{ cancelText }}
                    </button>
                    <button type="button" :class="buttonClass" @click="confirm">
                        {{ confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>
