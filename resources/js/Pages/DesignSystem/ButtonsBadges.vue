<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Play, Search, Settings, Save, Trash2, Loader2 } from '@lucide/vue';

const isDemoLoading = ref(true);

const toggleLoader = () => {
  isDemoLoading.value = !isDemoLoading.value;
};
</script>

<template>
  <Head title="Sistem Desain - Tombol & Badge" />

  <AuthenticatedLayout>
    <div class="py-4 px-4 sm:px-4 lg:px-4">
      <div class="w-full space-y-4">
        
        <!-- Premium Header Panel -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
          <div class="space-y-1">
            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
              {{ __('Tombol & Badge') }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
              Daftar komponen standard button dan status badge untuk menjaga konsistensi elemen aksi.
            </p>
          </div>
        </div>

        <!-- Section Buttons -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm p-6 space-y-6">
          <div>
            <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Jenis & Varian Tombol</h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Semua aksi utama disarankan menggunakan tombol dengan tinggi standard `h-10` dan rounded `rounded-xl` (`12px`).</p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Button Styles -->
            <div class="space-y-4">
              <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-[10px]">Button Variants</h4>
              <div class="flex flex-wrap gap-3">
                <button class="h-10 px-5 text-sm font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm hover:shadow transition duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                  Primary Button
                </button>

                <button class="h-10 px-5 text-sm font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 transition duration-150 focus:outline-none">
                  Secondary Button
                </button>

                <button class="h-10 px-5 text-sm font-semibold rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 text-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800/80 dark:border-slate-700 dark:hover:border-slate-600 dark:text-slate-300 transition duration-150 focus:outline-none">
                  Outline Button
                </button>

                <button class="h-10 px-5 text-sm font-semibold rounded-xl bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition duration-150 focus:outline-none">
                  Ghost Button
                </button>

                <button class="h-10 px-5 text-sm font-semibold rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition duration-150 focus:outline-none focus:ring-2 focus:ring-rose-500">
                  Danger Button
                </button>
              </div>
            </div>

            <!-- Buttons with Icons -->
            <div class="space-y-4">
              <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-[10px]">Buttons with Icons</h4>
              <div class="flex flex-wrap gap-3">
                <button class="h-10 px-4 text-sm font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white flex items-center gap-2 shadow-sm transition">
                  <Save class="h-4 w-4" />
                  <span>Simpan Data</span>
                </button>

                <button class="h-10 px-4 text-sm font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 flex items-center gap-2 transition">
                  <Settings class="h-4 w-4 text-slate-400 dark:text-slate-500" />
                  <span>Konfigurasi</span>
                </button>

                <button class="h-10 px-4 text-sm font-semibold rounded-xl bg-rose-55 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:hover:bg-rose-950/60 dark:text-rose-400 flex items-center gap-2 transition">
                  <Trash2 class="h-4 w-4 text-rose-500" />
                  <span>Hapus Item</span>
                </button>

                <button class="h-10 w-10 flex items-center justify-center rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-500 hover:text-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition" title="Icon Only">
                  <Search class="h-4.5 w-4.5" />
                </button>
              </div>
            </div>

            <!-- Loading States -->
            <div class="space-y-4">
              <div class="flex justify-between items-center">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-[10px]">Loading & Interactive States</h4>
                <button @click="toggleLoader" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 underline uppercase tracking-wider">
                  Toggle State
                </button>
              </div>
              <div class="flex flex-wrap gap-3">
                <button :disabled="isDemoLoading" class="h-10 px-5 text-sm font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white flex items-center gap-2 transition disabled:opacity-75 disabled:cursor-not-allowed">
                  <Loader2 v-if="isDemoLoading" class="h-4 w-4 animate-spin text-white" />
                  <span>{{ isDemoLoading ? 'Mengirim berkas...' : 'Kirim Berkas' }}</span>
                </button>

                <button :disabled="isDemoLoading" class="h-10 px-5 text-sm font-semibold rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 flex items-center gap-2 transition disabled:opacity-75 disabled:cursor-not-allowed">
                  <Loader2 v-if="isDemoLoading" class="h-4 w-4 animate-spin text-slate-400" />
                  <span>Memuat data</span>
                </button>
              </div>
            </div>

            <!-- Disabled States -->
            <div class="space-y-4">
              <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-[10px]">Disabled States</h4>
              <div class="flex flex-wrap gap-3">
                <button disabled class="h-10 px-5 text-sm font-semibold rounded-xl bg-indigo-600 text-white opacity-40 cursor-not-allowed">
                  Primary Disabled
                </button>
                <button disabled class="h-10 px-5 text-sm font-semibold rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 cursor-not-allowed">
                  Secondary Disabled
                </button>
                <button disabled class="h-10 px-5 text-sm font-semibold rounded-xl bg-white border border-slate-100 text-slate-300 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-700 cursor-not-allowed">
                  Outline Disabled
                </button>
              </div>
            </div>

            <!-- Button Sizes -->
            <div class="space-y-4">
              <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-[10px]">Button Sizes</h4>
              <div class="flex items-center gap-3 flex-wrap">
                <button class="h-8 px-3 text-xs font-bold rounded-lg bg-indigo-600 text-white transition hover:bg-indigo-700">
                  Small (h-8)
                </button>
                <button class="h-10 px-4 text-sm font-semibold rounded-xl bg-indigo-600 text-white transition hover:bg-indigo-700">
                  Medium (h-10)
                </button>
                <button class="h-12 px-6 text-base font-bold rounded-2xl bg-indigo-600 text-white transition hover:bg-indigo-700 shadow-md">
                  Large (h-12)
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Section Badges -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm p-6 space-y-6">
          <div>
            <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Badge Status</h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Badge digunakan untuk menampilkan status data seperti disetujui, tertunda, ditolak, atau hak akses pengguna.</p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Filled Soft Badges -->
            <div class="space-y-4">
              <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-[10px]">Filled Soft Badges</h4>
              <div class="flex flex-wrap gap-2.5">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400">
                  ACTIVE
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400">
                  PENDING
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-450">
                  INACTIVE
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-400">
                  IN DEVELOPMENT
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400">
                  INFO
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                  NEUTRAL
                </span>
              </div>
            </div>

            <!-- Badges with Dot Indicator -->
            <div class="space-y-4">
              <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-[10px]">Badges with Dot Indicator</h4>
              <div class="flex flex-wrap gap-2.5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400">
                  <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                  Selesai
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400">
                  <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                  Proses Kerja
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-450">
                  <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                  Dibatalkan
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                  <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                  Draft
                </span>
              </div>
            </div>

            <!-- Role Access Badges -->
            <div class="space-y-4">
              <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-[10px]">Role Access Badges</h4>
              <div class="flex flex-wrap gap-2.5">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-100/40 dark:border-indigo-900/40 text-xs font-bold uppercase tracking-wider">
                  Super Admin
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-100/40 dark:border-amber-900/40 text-xs font-bold uppercase tracking-wider">
                  Kepala Unit
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-100/40 dark:border-emerald-900/40 text-xs font-bold uppercase tracking-wider">
                  Teknisi
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-100/40 dark:border-blue-900/40 text-xs font-bold uppercase tracking-wider">
                  Kepala Ruangan
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-350 border border-slate-200 dark:border-slate-700 text-xs font-bold uppercase tracking-wider">
                  Staf Umum
                </span>
              </div>
            </div>

            <!-- Pill Counter Badges -->
            <div class="space-y-4">
              <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-[10px]">Pill Counter Badges</h4>
              <div class="flex items-center gap-3">
                <span class="h-6 min-w-[24px] px-1.5 flex items-center justify-center rounded-full text-xs font-extrabold bg-amber-500 text-white shadow-sm">
                  5
                </span>
                <span class="h-6 min-w-[24px] px-1.5 flex items-center justify-center rounded-full text-xs font-extrabold bg-indigo-600 text-white shadow-sm">
                  12
                </span>
                <span class="h-6 min-w-[24px] px-1.5 flex items-center justify-center rounded-full text-xs font-extrabold bg-rose-600 text-white shadow-sm">
                  99+
                </span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
