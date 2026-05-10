<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AdminDashboard from '@/Pages/Admin/AdminDashboard.vue';
import RequestorDashboard from '@/Pages/Requestor/RequestorDashboard.vue';

const user = usePage().props.auth.user;
// The shared dashboard route renders the correct landing page for each role.
const role = user?.role;
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout>
        <AdminDashboard v-if="role === 'hr_admin'" />
        <RequestorDashboard v-else-if="role === 'requestor'" />

        <div v-else-if="role === 'approver'" class="text-center py-16 text-gray-400">
            <p class="text-xl font-bold">Approver Dashboard</p>
            <p class="text-sm mt-2">Coming soon — approval queue will appear here.</p>
        </div>

        <div v-else class="text-center py-16 text-gray-400">
            <p class="text-sm">No dashboard available for your current role.</p>
        </div>
    </AppLayout>
</template>
