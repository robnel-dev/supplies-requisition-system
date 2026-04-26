<script setup>
import { Link } from '@inertiajs/vue3';
import bgImage from '../../images/BG_Image.jpg'

</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center py-12 px-4 sm:px-6 lg:px-8 overflow-hidden">
        <!-- Real background image (office supplies / requisition theme) -->
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-10" 
             :style="{ backgroundImage: `url(${bgImage})` }">
        </div>

        <!-- Gradient overlay to soften and add brand colors -->
        <div class="absolute inset-0 bg-gradient-to-br from-brand-navy/30 via-white/20 to-brand-blue/30"></div>

        <!-- Subtle pattern overlay (supplies theme) -->
        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M10 10h40v40H10z\' fill=\'none\' stroke=\'%23159cdb\' stroke-width=\'0.5\' stroke-opacity=\'0.5\'/%3E%3Ccircle cx=\'30\' cy=\'30\' r=\'4\' fill=\'%23fcb503\' fill-opacity=\'0.3\'/%3E%3C/svg%3E'); background-repeat: repeat; background-size: 30px;"></div>

        <!-- Glassmorphic card container -->
        <div class="relative w-full max-w-md z-10">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-2xl p-8 sm:p-10 border border-white/30 transition-all duration-300 hover:shadow-brand-blue/20">
                <!-- Header with logo -->
                <div class="text-center mb-8">
                    <Link href="/" class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-brand-blue/15 to-brand-yellow/15 shadow-inner mb-4">
                        <svg class="w-10 h-10 text-brand-navy" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="2" y="2" width="22" height="28" rx="2" class="fill-brand-navy" />
                            <rect x="9" y="0" width="8" height="4" rx="1" class="fill-brand-yellow" />
                            <rect x="4" y="6" width="18" height="22" rx="1" fill="white" />
                            <rect x="7" y="10" width="10" height="2" rx="1" class="fill-brand-navy" opacity="0.15" />
                            <rect x="7" y="14" width="7" height="2" rx="1" class="fill-brand-navy" opacity="0.15" />
                            <rect x="7" y="18" width="12" height="2" rx="1" class="fill-brand-navy" opacity="0.15" />
                            <path d="M 14 9 L 16 11 L 20 6" stroke="#0EA5E9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M 14 17 L 16 19 L 20 14" stroke="#0EA5E9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <rect x="16" y="18" width="16" height="6" class="fill-brand-navy" />
                            <rect x="14" y="24" width="20" height="14" rx="1" class="fill-brand-blue" />
                            <path d="M 14 24 L 10 16 H 20 L 24 24 Z" class="fill-brand-blue" opacity="0.85" />
                            <path d="M 24 24 L 28 16 H 38 L 34 24 Z" class="fill-brand-blue" opacity="0.85" />
                            <rect x="22" y="24" width="4" height="14" class="fill-brand-yellow" />
                            <rect x="16" y="27" width="4" height="3" rx="0.5" fill="white" opacity="0.9" />
                        </svg>
                    </Link>
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Welcome Back</h2>
                    <p class="text-sm text-gray-600 mt-2">Enter your credentials to proceed</p>
                </div>

                <!-- Slot for child content (Login, etc.) -->
                <slot />
            </div>
        </div>
    </div>
</template>