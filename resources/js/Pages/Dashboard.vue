<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AdminDashboard from '@/Pages/Admin/AdminDashboard.vue';
import RequestorDashboard from '@/Pages/Requestor/RequestorDashboard.vue';
import ApproverDashboard from '@/Pages/Approver/ApproverDashboard.vue';

const props = defineProps({
    dashboardStats: {
        type: Object,
        default: () => ({}),
    },
    recentActivity: {
        type: Array,
        default: () => [],
    },
});

const user = usePage().props.auth.user;
const role = user?.role;
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout>
        <AdminDashboard v-if="role === 'hr_admin'" :dashboard-stats="dashboardStats"
            :recent-activity="recentActivity" />

        <RequestorDashboard v-else-if="role === 'requestor'" :dashboard-stats="dashboardStats"
            :recent-activity="recentActivity" />

        <ApproverDashboard v-else-if="role === 'approver'" :dashboard-stats="dashboardStats"
            :recent-activity="recentActivity" />

        <div v-else class="text-center py-16 text-gray-400">
            <p class="text-sm">No dashboard available for your current role.</p>
        </div>
    </AppLayout>
</template>