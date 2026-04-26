<script setup>
import { Link } from '@inertiajs/vue3';
import { Menu, Bell, LogOut } from 'lucide-vue-next';

const emit = defineEmits(['open-mobile-menu']);
</script>

<template>
    <header
        class="bg-white/90 backdrop-blur-sm border-b border-gray-200/80 shadow-sm h-20 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-30 transition-all sticky top-0">

        <div class="flex items-center">
            <button @click="emit('open-mobile-menu')"
                class="text-gray-400 hover:text-brand-navy lg:hidden p-2 -ml-2 rounded-md transition-colors focus:outline-none">
                <span class="sr-only">Open sidebar</span>
                <Menu class="w-6 h-6" />
            </button>
        </div>

        <div class="flex items-center gap-2 sm:gap-4">

            <button
                class="text-gray-400 hover:text-brand-blue relative p-2 transition-all duration-200 focus:outline-none rounded-full hover:bg-blue-50 hover:shadow-inner">
                <span class="sr-only">View notifications</span>
                <Bell class="w-5 h-5" />
                <span v-if="hasUnreadNotifications"
                    class="absolute top-1 right-1.5 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white shadow-sm">
                </span>
            </button>

            <div class="hidden sm:block h-6 w-px bg-gray-200 mx-1" aria-hidden="true"></div>

            <Link :href="route('logout')" method="post" as="button"
                class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-500 rounded-lg hover:text-red-600 hover:bg-red-50 transition-all duration-200 focus:outline-none group active:scale-95">
                <LogOut class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" />
                <span class="hidden sm:inline-block">Log Out</span>
            </Link>

        </div>
    </header>
</template>