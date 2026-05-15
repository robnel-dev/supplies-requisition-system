<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    item: Object,
});
</script>

<template>
    <Link :href="item.href" :class="[
        item.active
            ? 'bg-brand-navy text-white shadow-md shadow-brand-navy/20 ring-1 ring-brand-navy/50'
            : 'text-gray-600 hover:bg-gray-100 hover:text-brand-navy active:bg-gray-200',
        'group relative flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]'
    ]">
        <component :is="item.icon" :class="[
            item.active ? 'text-brand-blue drop-shadow-sm' : 'text-gray-400 group-hover:text-brand-navy',
            'mr-3 flex-shrink-0 w-5 h-5 transition-all duration-200'
        ]" />
        <span class="flex-1 truncate">{{ item.name }}</span>

        <!-- Notification Badge -->
        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 scale-50"
            enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-50">
            <span v-if="item.badge && item.badge > 0" :class="[
                item.active
                    ? 'bg-brand-yellow text-brand-navy'
                    : 'bg-red-500 text-white',
                'ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[10px] font-bold leading-none shadow-sm ring-1 ring-white/30'
            ]">
                {{ item.badge > 99 ? '99+' : item.badge }}
            </span>
        </Transition>
    </Link>
</template>