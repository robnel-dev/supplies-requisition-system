<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AdminDashboard from '@/Pages/Admin/AdminDashboard.vue';

// 1. Get user role from Inertia Auth props. 
// We default to 'admin' right now so you can see it working before we setup the database.
const user = usePage().props.auth.user;
const role = user?.role || 'hr_admin'; 
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout>
        <AdminDashboard v-if="role === 'hr_admin'" />

    </AppLayout>
</template>