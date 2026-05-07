<script setup>
import { ref, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Sidebar from '@/Components/Layout/Sidebar.vue';
import Topbar from '@/Components/Layout/Topbar.vue';
import Footer from '@/Components/Layout/Footer.vue';
import Breadcrumbs from '@/Components/Layout/Breadcrumbs.vue';
import ToastNotification from '@/Components/ToastNotification.vue';

const isMobileSidebarOpen = ref(false);
const isPageLoading = ref(false);

/*
 * router.on('start') receives an event with event.detail.visit containing:
 *   - method: 'get' | 'post' | 'put' | 'patch' | 'delete'
 *   - url: the destination URL
 */
router.on('start', (event) => {
    if (event.detail.visit.method === 'get') {
        isPageLoading.value = true;
    }
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

        <div class="flex flex-col flex-1 min-w-0 overflow-hidden relative">

            <Topbar @open-mobile-menu="isMobileSidebarOpen = true" />

            <!-- Modern Page Loader -->
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                <div v-if="isPageLoading"
                    class="absolute inset-0 z-[200] flex items-center justify-center bg-white/65 backdrop-blur-sm">
                    <div class="flex flex-col items-center gap-5">

                        <!-- Modern Spinner -->
                        <div class="relative flex items-center justify-center w-20 h-20">

                            <!-- Glow Background -->
                            <div class="absolute w-16 h-16 rounded-full bg-brand-blue/10 animate-pulse"></div>

                            <!-- Outer Ring -->
                            <div class="absolute w-20 h-20 rounded-full border-[3px] border-gray-200"></div>

                            <!-- Animated Gradient Ring -->
                            <div class="absolute w-20 h-20 rounded-full border-[3px] border-transparent border-t-brand-blue border-r-sky-400 animate-spin"
                                style="animation-duration: 0.8s;"></div>

                            <!-- Inner Ring -->
                            <div class="absolute w-12 h-12 rounded-full border-2 border-gray-100"></div>

                            <!-- Inner Animated Dot -->
                            <div class="w-3 h-3 rounded-full bg-brand-blue animate-ping"></div>
                        </div>

                        <!-- Loading Text -->
                        <div class="flex flex-col items-center leading-none">
                            <span class="text-sm font-semibold text-gray-700 tracking-wide">
                                Loading
                            </span>

                            <div class="flex items-center gap-1 mt-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-brand-blue animate-bounce"></span>

                                <span
                                    class="w-1.5 h-1.5 rounded-full bg-brand-blue animate-bounce [animation-delay:0.15s]"></span>

                                <span
                                    class="w-1.5 h-1.5 rounded-full bg-brand-blue animate-bounce [animation-delay:0.3s]"></span>
                            </div>
                        </div>
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