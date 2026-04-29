<script setup>
import { Users, Package, FileText, CheckCircle } from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue'

// Placeholder stats for the admin. Later, we'll pass these via Inertia props.
const stats = [
    { label: 'Listed Supplies', value: '100', icon: Package, color: 'text-blue-500' },
    { label: 'Pending Requests', value: '12', icon: FileText, color: 'text-red-500' },
    { label: 'Completed Requests', value: '50', icon: CheckCircle, color: 'text-green-500' },
];
</script>

<template>
    <div>
        <PageHeader title="Admin Dashboard" description="System overview and pending releases" />

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="stat in stats" :key="stat.label"
                class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6 transition-all hover:shadow-md hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div class="text-gray-500 text-xs font-bold uppercase tracking-wider">{{ stat.label }}</div>
                    <component :is="stat.icon" class="w-5 h-5" :class="stat.color" />
                </div>
                <div class="mt-4 text-3xl font-black text-brand-navy">{{ stat.value }}</div>
            </div>
        </div>

        <div class="mt-8 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6">
            <h3 class="text-lg font-bold text-brand-navy mb-4">Recent System Activity</h3>
            <div class="text-sm text-gray-500 py-8 text-center border-2 border-dashed border-gray-100 rounded-lg">
                Activity feed will be displayed here (Releases, User Additions, etc.)
            </div>
        </div>
    </div>
</template>