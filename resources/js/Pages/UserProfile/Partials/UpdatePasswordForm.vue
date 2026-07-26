<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ShieldCheck, Eye, EyeOff } from '@lucide/vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-md font-bold text-slate-955 dark:text-white flex items-center gap-2.5">
                <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-white/10 flex items-center justify-center flex-shrink-0">
                    <ShieldCheck class="h-4 w-4 text-emerald-600 dark:text-white" />
                </div>
                <span>{{ __('pages.profile.password_title') }}</span>
            </h2>

            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                {{ __('pages.profile.password_desc') }}
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Kata Sandi saat ini -->
                <div>
                    <InputLabel for="current_password" :value="__('pages.profile.current_password')" />

                    <div class="relative mt-1">
                        <TextInput
                            id="current_password"
                            ref="currentPasswordInput"
                            v-model="form.current_password"
                            :type="showCurrentPassword ? 'text' : 'password'"
                            class="block w-full pr-10"
                            autocomplete="current-password"
                        />
                        <button 
                            type="button"
                            @click="showCurrentPassword = !showCurrentPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition focus:outline-none"
                            tabindex="-1"
                        >
                            <EyeOff v-if="showCurrentPassword" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <InputError
                        :message="form.errors.current_password"
                        class="mt-2"
                    />
                </div>

                <!-- Kata Sandi baru -->
                <div>
                    <InputLabel for="password" :value="__('pages.profile.new_password')" />

                    <div class="relative mt-1">
                        <TextInput
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            :type="showNewPassword ? 'text' : 'password'"
                            class="block w-full pr-10"
                            autocomplete="new-password"
                        />
                        <button 
                            type="button"
                            @click="showNewPassword = !showNewPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition focus:outline-none"
                            tabindex="-1"
                        >
                            <EyeOff v-if="showNewPassword" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <!-- Konfirmasi Kata Sandi -->
                <div>
                    <InputLabel
                        for="password_confirmation"
                        :value="__('global.confirm_password')"
                    />

                    <div class="relative mt-1">
                        <TextInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            class="block w-full pr-10"
                            autocomplete="new-password"
                        />
                        <button 
                            type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition focus:outline-none"
                            tabindex="-1"
                        >
                            <EyeOff v-if="showConfirmPassword" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <InputError
                        :message="form.errors.password_confirmation"
                        class="mt-2"
                    />
                </div>
            </div>

            <div class="flex items-center justify-end gap-4">
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-xs font-semibold text-emerald-600 dark:text-white"
                    >
                        {{ __('pages.profile.saved') }}
                    </p>
                </Transition>

                <PrimaryButton :disabled="form.processing">{{ __('pages.profile.btn_save') }}</PrimaryButton>
            </div>
        </form>
    </section>
</template>
