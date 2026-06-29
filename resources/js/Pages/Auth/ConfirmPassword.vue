<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff } from '@lucide/vue';

const showPassword = ref(false);

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="__('global.confirm_password')" />

        <!-- Form Title -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-slate-950 dark:text-white tracking-tight">
                {{ __('auth.confirm_password.title') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                {{ __('auth.confirm_password.desc') }}
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Password Input Field -->
            <div>
                <label for="password" class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">
                    {{ __('global.password') }}
                </label>
                <div class="relative">
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        autofocus
                        placeholder="••••••••"
                        class="border-b-2 border-slate-200 dark:border-slate-800 bg-transparent py-2.5 pe-10 ps-0 focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-0 outline-none w-full border-t-0 border-l-0 border-r-0 rounded-none transition duration-150 text-slate-900 dark:text-white text-base"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none"
                    >
                        <Eye v-if="!showPassword" class="h-5 w-5" />
                        <EyeOff v-else class="h-5 w-5" />
                    </button>
                </div>
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <!-- Submit action -->
            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full text-center py-4 bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-slate-200 text-white dark:text-slate-950 font-bold rounded-xl text-base tracking-wide transition duration-150 select-none outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 focus:ring-offset-white dark:focus:ring-offset-slate-900"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ __('auth.confirm_password.confirm_btn') }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
