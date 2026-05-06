<script setup>
defineProps({
    text: { type: String, required: true },
    position: { type: String, default: 'top' }, // top, bottom, left, right
});
</script>

<template>
    <div class="relative group inline-flex">

        <!-- Trigger -->
        <slot />

        <!-- Tooltip -->
        <div
            class="pointer-events-none absolute z-50 whitespace-nowrap
                   opacity-0 scale-95 group-hover:opacity-100 group-hover:scale-100
                   group-focus-within:opacity-100 group-focus-within:scale-100
                   transition-all duration-150 ease-out">

            <div
                :class="[
                    'px-2.5 py-1.5 text-xs rounded-md shadow-lg text-white bg-gray-900',
                    position === 'top' && 'mb-2 bottom-full left-1/2 -translate-x-1/2',
                    position === 'bottom' && 'mt-2 top-full left-1/2 -translate-x-1/2',
                    position === 'left' && 'mr-2 right-full top-1/2 -translate-y-1/2',
                    position === 'right' && 'ml-2 left-full top-1/2 -translate-y-1/2',
                ]"
            >
                {{ text }}
            </div>
        </div>
    </div>
</template>