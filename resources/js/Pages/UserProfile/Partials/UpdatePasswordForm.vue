<script setup>
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed, getCurrentInstance } from 'vue';
import { ShieldCheck, Eye, EyeOff, KeyRound, Save } from '@lucide/vue';

const { proxy } = getCurrentInstance();
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

const isPasswordDirty = computed(() => {
    return (
        (form.current_password && form.current_password.length > 0) ||
        (form.password && form.password.length > 0) ||
        (form.password_confirmation && form.password_confirmation.length > 0)
    );
});

const updatePassword = () => {
    if (!isPasswordDirty.value) return;

    proxy.$swal({
        title: 'Perbarui Kata Sandi?',
        text: 'Kata sandi akun Anda akan diganti dengan yang baru.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Perbarui',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            form.put(route('password.update'), {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                    proxy.$toast('Kata sandi berhasil diperbarui.', 'success');
                },
                onError: () => {
                    if (form.errors.password) {
                        form.reset('password', 'password_confirmation');
                        passwordInput.value?.focus();
                    }
                    if (form.errors.current_password) {
                        form.reset('current_password');
                        currentPasswordInput.value?.focus();
                    }
                },
            });
        }
    });
};
</script>

<template>
    <section>
        <form @submit.prevent="updatePassword">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                        Keamanan & Pembaruan Kata Sandi
                    </h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Pastikan akun Anda menggunakan kata sandi yang kuat untuk menjaga keamanan data.</p>
                </div>

                <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:p-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Kata Sandi Saat Ini -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Kata Sandi Saat Ini <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="current_password"
                                    ref="currentPasswordInput"
                                    v-model="form.current_password"
                                    :type="showCurrentPassword ? 'text' : 'password'"
                                    class="w-full h-10 px-3.5 pr-10 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    placeholder="Masukkan Kata Sandi Saat Ini..."
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
                            <InputError :message="form.errors.current_password" class="mt-1" />
                        </div>

                        <!-- Kata Sandi Baru -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Kata Sandi Baru <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="password"
                                    ref="passwordInput"
                                    v-model="form.password"
                                    :type="showNewPassword ? 'text' : 'password'"
                                    class="w-full h-10 px-3.5 pr-10 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    placeholder="Minimal 6 Karakter..."
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
                            <InputError :message="form.errors.password" class="mt-1" />
                        </div>

                        <!-- Konfirmasi Kata Sandi Baru -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Konfirmasi Kata Sandi Baru <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    class="w-full h-10 px-3.5 pr-10 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    placeholder="Ulangi Kata Sandi Baru..."
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
                            <InputError :message="form.errors.password_confirmation" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER ACTION CONTAINER (EXACTLY MATCHING USER MANAGEMENT EDIT) -->
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                <span class="text-xs font-medium text-slate-400 dark:text-slate-500">
                    {{ isPasswordDirty ? 'Ada perubahan kata sandi yang belum disimpan.' : 'Tidak ada perubahan pada kata sandi.' }}
                </span>
                <button
                    type="submit"
                    :disabled="!isPasswordDirty || form.processing"
                    :class="[
                        'px-6 py-2.5 inline-flex items-center justify-center gap-2 font-bold text-xs rounded-xl transition duration-150 shadow-sm border-0 cursor-pointer w-full sm:w-auto',
                        isPasswordDirty && !form.processing
                            ? 'bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900'
                            : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-600 opacity-50 cursor-not-allowed'
                    ]"
                >
                    <KeyRound class="h-4 w-4 shrink-0" />
                    <span>{{ form.processing ? 'Menyimpan...' : 'Perbarui Kata Sandi' }}</span>
                </button>
            </div>
        </form>
    </section>
</template>

