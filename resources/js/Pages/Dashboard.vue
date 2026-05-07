<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AdminDashboard from '@/Pages/Admin/AdminDashboard.vue';
import RequestorDashboard from '@/Pages/Requestor/RequestorDashboard.vue';

/**
 * FIX: The original Dashboard.vue only rendered AdminDashboard for hr_admin.
 * Requestors and approvers who log in would see a completely blank page.
 *
 * Now each role gets their appropriate dashboard component.
 * Approver dashboard is a placeholder until Phase 3 is built.
 */
const user = usePage().props.auth.user;
const role = user?.role;
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout>
        <AdminDashboard v-if="role === 'hr_admin'" />
        <RequestorDashboard v-else-if="role === 'requestor'" />

        <!-- Placeholder for approver dashboard (Phase 3) -->
        <div v-else-if="role === 'approver'" class="text-center py-16 text-gray-400">
            <p class="text-xl font-bold">Approver Dashboard</p>
            <p class="text-sm mt-2">Coming soon — approval queue will appear here.</p>
        </div>

        <!-- Fallback for unknown roles -->
        <div v-else class="text-center py-16 text-gray-400">
            <p class="text-sm">No dashboard available for your current role.</p>
        </div>
    </AppLayout>
</template>