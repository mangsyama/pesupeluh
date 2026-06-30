<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head :title="__('auth.verify_email.title')" />

        <!-- Form Title -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-emerald-700 dark:text-emerald-400 tracking-tight">
                {{ __('auth.verify_email.title') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                {{ __('auth.verify_email.desc') }}
            </p>
        </div>

        <div
            class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-sm font-semibold text-emerald-600 dark:text-emerald-400"
            v-if="verificationLinkSent"
        >
            {{ __('auth.verify_email.link_sent_msg') }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Submit action -->
            <div>
                <button
                    type="submit"
                    class="w-full text-center py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-base tracking-wide transition duration-150 select-none outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 focus:ring-offset-white dark:focus:ring-offset-slate-900"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ __('auth.verify_email.resend_btn') }}
                </button>
            </div>

            <!-- Logout Link -->
            <div class="text-center pt-2">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition duration-150 outline-none select-none"
                >
                    {{ __('auth.verify_email.logout_btn') }}
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
