<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, Lock, LogIn } from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Email Field -->
            <div>
                <InputLabel for="email" value="Email address" class="text-gray-700 font-medium" />
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Mail class="h-5 w-5 text-gray-400 group-focus-within:text-brand-blue transition-colors" />
                    </div>
                    <TextInput
                        id="email"
                        type="email"
                        class="pl-10 block w-full border-gray-300 focus:border-brand-blue focus:ring-brand-blue rounded-lg transition-all duration-200"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="user@guess.com.ph"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Password Field -->
            <div>
                <InputLabel for="password" value="Password" class="text-gray-700 font-medium" />
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Lock class="h-5 w-5 text-gray-400" />
                    </div>
                    <TextInput
                        id="password"
                        type="password"
                        class="pl-10 block w-full border-gray-300 focus:border-brand-blue focus:ring-brand-blue rounded-lg transition-all duration-200"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <!-- Submit Button -->
            <PrimaryButton
                class="w-full justify-center py-2.5 bg-brand-navy hover:bg-brand-navy/90 focus:ring-brand-blue rounded-lg font-semibold transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                <LogIn class="w-4 h-4 mr-2" v-if="!form.processing" />
                <span v-if="!form.processing">Sign in</span>
                <span v-else class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Authenticating...
                </span>
            </PrimaryButton>
        </form>

        <div class="mt-8 text-center text-xs text-gray-400 border-t border-gray-100 pt-6">
            <p>Secure login • Supplies Requisition System v1.0</p>
            <p class="mt-1">© {{ new Date().getFullYear() }} — GUESS</p>
        </div>
    </GuestLayout>
</template>