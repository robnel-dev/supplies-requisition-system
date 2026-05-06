<script setup>
import { Check, AlertTriangle, X } from 'lucide-vue-next'
import { useToast } from '@/Composables/useToast'

const { toast, hideToast } = useToast()
</script>

<template>
    <Transition enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="toast.show" :class="['fixed top-6 right-6 z-[100] flex items-center w-full max-w-sm p-4 space-x-3 text-gray-800 bg-white border-l-4 rounded-lg shadow-[0_4px_20px_rgba(0,0,0,0.15)]',
            toast.type === 'error' ? 'border-red-500' : 'border-green-500']">
            <div :class="['inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg',
                toast.type === 'error' ? 'text-red-600 bg-red-100' : 'text-green-600 bg-green-100']">
                <AlertTriangle v-if="toast.type === 'error'" class="w-5 h-5" />
                <Check v-else class="w-5 h-5" />
            </div>
            <div class="text-sm font-bold">{{ toast.message }}</div>
            <button @click="hideToast"
                class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8 items-center justify-center transition-colors">
                <X class="w-4 h-4" />
            </button>
        </div>
    </Transition>
</template>