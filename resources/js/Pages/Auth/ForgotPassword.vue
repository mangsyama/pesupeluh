<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle } from '@lucide/vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    // Feature currently disabled
};
</script>

<template>
    <GuestLayout>
        <Head :title="__('auth.forgot_password.title')" />

        <!-- Form Title -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-emerald-700 dark:text-emerald-400 tracking-tight">
                {{ __('auth.forgot_password.title') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                {{ __('auth.forgot_password.desc') }}
            </p>
        </div>

        <!-- Warning Alert Banner for Disabled Feature -->
        <div class="mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 flex items-start gap-3">
            <AlertTriangle class="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
            <div class="text-xs text-amber-800 dark:text-amber-300 leading-relaxed font-semibold">
                Fitur atur ulang kata sandi mandiri via email sementara belum tersedia. Silakan hubungi Administrator sistem untuk bantuan pemulihan kata sandi akun Anda.
            </div>
        </div>

        <div
            v-if="status"
            class="mb-6 text-sm font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 p-3 rounded-xl border border-emerald-100 dark:border-emerald-900/30"
        >
            {{ status }}
        </div>

        <form @submit.prevent class="space-y-6">
            <!-- Email Input Field -->
            <div>
                <label for="email" class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">
                    {{ __('global.email') }}
                </label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    disabled
                    autocomplete="username"
                    :placeholder="__('auth.register.email_placeholder')"
                    class="border-b-2 border-slate-200 dark:border-slate-800 bg-transparent py-2.5 px-0 opacity-60 cursor-not-allowed outline-none w-full border-t-0 border-l-0 border-r-0 rounded-none transition duration-150 text-slate-900 dark:text-white text-base"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <!-- Submit action -->
            <div class="pt-2 space-y-4">
                <button
                    type="button"
                    disabled
                    class="w-full text-center py-4 bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 font-bold rounded-xl text-base tracking-wide cursor-not-allowed select-none outline-none border border-slate-300 dark:border-slate-700/50"
                >
                    {{ __('auth.forgot_password.send_link_btn') }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
