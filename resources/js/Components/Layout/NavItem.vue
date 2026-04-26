<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    item: Object
});
</script>

<template>
    <Link
        :href="item.href"
        :class="[
            item.active 
                ? 'bg-brand-navy text-white shadow-md shadow-brand-navy/20 ring-1 ring-brand-navy/50' 
                : 'text-gray-600 hover:bg-gray-100 hover:text-brand-navy active:bg-gray-200',
            'group flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]'
        ]"
    >
        <component 
            :is="item.icon" 
            :class="[
                item.active ? 'text-brand-blue drop-shadow-sm' : 'text-gray-400 group-hover:text-brand-navy',
                'mr-3 flex-shrink-0 w-5 h-5 transition-all duration-200'
            ]" 
        />
        {{ item.name }}
    </Link>
</template>