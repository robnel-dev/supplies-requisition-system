<script setup>
import { ref, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Sidebar from '@/Components/Layout/Sidebar.vue';
import Topbar from '@/Components/Layout/Topbar.vue';
import Footer from '@/Components/Layout/Footer.vue';
import Breadcrumbs from '@/Components/Layout/Breadcrumbs.vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import { Loader } from 'lucide-vue-next'; // <-- Lucide Loader

const isMobileSidebarOpen = ref(false);

const isPageLoading = ref(false);

router.on('start', () => {
    isPageLoading.value = true;
});

router.on('finish', () => {
    isPageLoading.value = false;
});

const page = usePage();

watch(() => page.url, () => {
    isMobileSidebarOpen.value = false;
});

watch(isMobileSidebarOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});
</script>

<template>
    <div class="flex h-screen bg-gray-50 overflow-hidden font-sans text-gray-900">

        <Sidebar :is-mobile-open="isMobileSidebarOpen" @close-mobile="isMobileSidebarOpen = false" />

        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

            <Topbar @open-mobile-menu="isMobileSidebarOpen = true" />

            <Transition enter-active-class="transition-opacity duration-150 ease-out" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200 ease-in"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="isPageLoading"
                    class="absolute inset-0 z-[200] flex items-center justify-center bg-white/60 backdrop-blur-[2px] pointer-events-none"
                    style="top: 80px; left: 288px;">
                    <div class="flex flex-col items-center gap-3">
                        <!-- Lucide Loader Spinner -->
                        <Loader class="w-12 h-12 text-brand-blue animate-spin" />
                        <span class="text-xs font-semibold text-gray-500 tracking-widest uppercase">Loading...</span>
                    </div>
                </div>
            </Transition>

            <main class="flex-1 overflow-y-auto flex flex-col bg-gray-100">

                <div class="flex-1 p-4 sm:p-6 lg:p-8">
                    <Breadcrumbs />
                    <slot />
                </div>

                <Footer />

            </main>
            <ToastNotification />
        </div>
    </div>
</template>