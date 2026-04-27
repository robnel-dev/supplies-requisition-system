<script setup>
// Define the props that this component will accept
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: false, // Optional, in case some pages just need a title
    },
});
</script>

<template>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-brand-navy tracking-tight">
            {{ title }}
        </h1>
        
        <p v-if="description" class="mt-1 text-sm text-gray-500">
            {{ description }}
        </p>
    </div>
</template>