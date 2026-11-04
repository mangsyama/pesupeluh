<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, Copy, Search, AlertCircle, ArrowLeft, Leaf } from '@lucide/vue';

const props = defineProps({
    ticket: {
        type: Object,
        default: () => ({})
    },
    id: {
        type: String,
        default: ''
    }
});

const ticketId = computed(() => {
    return props.ticket?.ticket_number || props.id || 'PS-2026-001';
});

const copied = ref(false);

const copyReceipt = () => {
    navigator.clipboard.writeText(ticketId.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};
</script>

<template>
    <Head title="Konfirmasi Aduan Sanitasi" />

    <div class="min-h-screen flex flex-col justify-between sm:justify-center items-center relative overflow-x-hidden font-sans bg-slate-100/70 dark:bg-slate-950 p-0 sm:p-6 text-slate-900 dark:text-slate-100">
        <main class="w-full sm:max-w-[420px] mx-auto z-10 flex flex-col flex-1 sm:flex-initial my-auto">
            <!-- Solid Container Card (Without Shadow) -->
            <div class="w-full flex-1 sm:flex-initial flex flex-col overflow-hidden sm:rounded-2xl sm:border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <!-- Card Body -->
                <div class="flex-1 flex flex-col justify-center p-6 sm:p-9 bg-white dark:bg-slate-900 text-center">
                    <div class="inline-flex items-center justify-center select-none pointer-events-none mb-3">
                        <img
                            src="/images/logo-sidebar.png"
                            alt="PESU PELUH Logo"
                            class="h-7 sm:h-8 w-auto object-contain mx-auto dark:brightness-0 dark:invert pointer-events-none"
                        />
                    </div>

                    <!-- Success Icon Badge -->
                    <div class="h-20 w-20 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-full border border-emerald-500/20 dark:border-emerald-500/30 flex items-center justify-center mx-auto mb-4 shadow-none">
                        <CheckCircle2 class="h-10 w-10" />
                    </div>

                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Aduan Terkirim!</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed font-medium">
                        Terima kasih atas partisipasi Anda. Laporan Anda telah dicatat oleh sistem dan diteruskan ke tim cleaning service & pengawas terkait.
                    </p>

                    <!-- Receipt Code Box -->
                    <div class="mt-6 bg-slate-50 dark:bg-slate-950 rounded-2xl p-4 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block mb-1">Nomor Registrasi Aduan</span>
                        <div class="flex items-center justify-center gap-2">
                            <span class="text-xl font-sans font-extrabold text-emerald-600 dark:text-emerald-400 tracking-wider">{{ ticketId }}</span>
                            <button
                                @click="copyReceipt"
                                class="p-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer focus:outline-none"
                                title="Salin Kode Aduan"
                            >
                                <Copy class="h-4 w-4" />
                            </button>
                        </div>
                        <span v-if="copied" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block mt-1">Kode berhasil disalin!</span>
                    </div>

                    <!-- Warning Catat / Simpan Nomor Registrasi -->
                    <div class="mt-4 p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/80 flex items-start gap-3 text-left">
                        <AlertCircle class="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                        <div class="text-xs leading-relaxed text-slate-700 dark:text-slate-300">
                            <strong class="text-amber-900 dark:text-amber-200 font-bold block mb-0.5">Catatan Penting:</strong>
                            Jika Anda ingin melacak progres pembersihan toilet ini, pastikan untuk <strong>menyimpan atau mencatat Nomor Registrasi</strong> di atas sebelum meninggalkan halaman ini.
                        </div>
                    </div>

                    <div class="mt-6 space-y-2.5">
                        <Link
                            :href="route('public.sanitasi.track', { ticket: ticketId })"
                            class="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm transition cursor-pointer active:scale-[0.99] shadow-sm"
                        >
                            <Search class="h-4 w-4" />
                            <span>Lacak Status Aduan Ini</span>
                        </Link>
                        <Link
                            :href="route('public.sanitasi.create')"
                            class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 font-semibold text-xs transition cursor-pointer active:scale-[0.99]"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali ke Halaman Pertama</span>
                        </Link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
