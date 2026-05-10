<script setup>
import { Clock, CheckCircle2, XCircle } from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            pending: 0,
            approved: 0,
            rejected: 0,
        }),
    },
});
</script>

<template>
    <div>
        <PageHeader title="Approver Dashboard" description="Overview of approval activities and metrics." />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border-l-4 border-amber-400 shadow-sm rounded-r-xl p-5 flex items-center">
                <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
                    <Clock class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Pending Approvals</p>
                    <p class="text-2xl font-black text-gray-800">{{ stats.pending }}</p>
                </div>
            </div>

            <div class="bg-white border-l-4 border-green-400 shadow-sm rounded-r-xl p-5 flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <CheckCircle2 class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Approved This Month</p>
                    <p class="text-2xl font-black text-gray-800">{{ stats.approved }}</p>
                </div>
            </div>

            <div class="bg-white border-l-4 border-red-400 shadow-sm rounded-r-xl p-5 flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                    <XCircle class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Rejected This Month</p>
                    <p class="text-2xl font-black text-gray-800">{{ stats.rejected }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
