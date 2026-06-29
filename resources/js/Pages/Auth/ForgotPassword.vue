<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head :title="__('auth.forgot_password.title')" />

        <!-- Form Title -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-slate-950 dark:text-white tracking-tight">
                {{ __('auth.forgot_password.title') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                {{ __('auth.forgot_password.desc') }}
            </p>
        </div>

        <div
            v-if="status"
            class="mb-6 text-sm font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 p-3 rounded-xl border border-emerald-100 dark:border-emerald-900/30"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Email Input Field -->
            <div>
                <label for="email" class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">
                    {{ __('global.email') }}
                </label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    :placeholder="__('auth.register.email_placeholder')"
                    class="border-b-2 border-slate-200 dark:border-slate-800 bg-transparent py-2.5 px-0 focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-0 outline-none w-full border-t-0 border-l-0 border-r-0 rounded-none transition duration-150 text-slate-900 dark:text-white text-base"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <!-- Submit action -->
            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full text-center py-4 bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-slate-200 text-white dark:text-slate-950 font-bold rounded-xl text-base tracking-wide transition duration-150 select-none outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 focus:ring-offset-white dark:focus:ring-offset-slate-900"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ __('auth.forgot_password.send_link_btn') }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
