<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Layout/Sidebar.vue';
import Topbar from '@/Components/Layout/Topbar.vue';
import Footer from '@/Components/Layout/Footer.vue';
import Breadcrumbs from '@/Components/Layout/Breadcrumbs.vue';
import ToastNotification from '@/Components/ToastNotification.vue'

const isMobileSidebarOpen = ref(false);
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