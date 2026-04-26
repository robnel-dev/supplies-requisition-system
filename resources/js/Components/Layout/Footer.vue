<script setup>
const currentYear = new Date().getFullYear();

// Array of tech stacks for clean rendering
const techStacks = ['Laravel 12', 'Vue 3', 'Inertia.js', 'Tailwind CSS'];
</script>

<template>
    <footer class="bg-white border-t border-gray-200 py-5 px-4 sm:px-6 lg:px-8 mt-auto">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            
            <div class="text-sm text-gray-500 flex flex-col sm:flex-row items-center gap-1.5 text-center sm:text-left">
                <span>&copy; {{ currentYear }} Supplies Requisition System</span>
                <span class="hidden sm:inline-block text-gray-300" aria-hidden="true">|</span>
                <span>
                    Developed by 
                    <span class="font-semibold text-brand-navy hover:text-brand-blue transition-colors cursor-default">
                        John Ronnel Robledo
                    </span>
                </span>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-2">
                <span class="text-xs text-gray-400 mr-1 italic">Powered by:</span>
                
                <span 
                    v-for="tech in techStacks" 
                    :key="tech" 
                    class="px-2 py-1 text-[10px] font-bold text-gray-500 uppercase tracking-wider bg-gray-50 border border-gray-200 rounded-md shadow-sm select-none"
                >
                    {{ tech }}
                </span>

                <span class="hidden sm:inline-block w-px h-4 bg-gray-300 ml-2" aria-hidden="true"></span>
                
                <span class="ml-2 font-mono text-[10px] font-bold text-brand-blue tracking-widest">
                    V1.0
                </span>
            </div>

        </div>
    </footer>
</template>