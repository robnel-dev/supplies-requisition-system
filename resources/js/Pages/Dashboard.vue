<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AdminDashboard from '@/Pages/Admin/AdminDashboard.vue';
import RequestorDashboard from '@/Pages/Requestor/RequestorDashboard.vue';
import ApproverDashboard from '@/Pages/Approver/ApproverDashboard.vue';

defineProps({
    approverStats: Object,
});

const user = usePage().props.auth.user;
// The shared dashboard route renders the correct landing page for each role.
const role = user?.role;
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <AdminDashboard v-if="role === 'hr_admin'" />
        <RequestorDashboard v-else-if="role === 'requestor'" />
        <ApproverDashboard v-else-if="role === 'approver'" :stats="approverStats" />

        <div v-else class="text-center py-16 text-gray-400">
            <p class="text-sm">No dashboard available for your current role.</p>
        </div>
    </AppLayout>
</template>
