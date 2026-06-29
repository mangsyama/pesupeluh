<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Palette, Play, Type, FileText, Database, Layers, ArrowRight } from '@lucide/vue';

const colorGroups = [
  {
    name: 'Primary (Indigo)',
    description: 'Warna utama untuk aksi primer, fokus, dan identitas brand.',
    colors: [
      { name: 'Indigo 50', bg: 'bg-indigo-50 dark:bg-indigo-950/20', text: 'text-indigo-600 dark:text-indigo-400' },
      { name: 'Indigo 100', bg: 'bg-indigo-100 dark:bg-indigo-950/40', text: 'text-indigo-700 dark:text-indigo-300' },
      { name: 'Indigo 500', bg: 'bg-indigo-500', text: 'text-white' },
      { name: 'Indigo 600', bg: 'bg-indigo-600', text: 'text-white', isPrimary: true },
      { name: 'Indigo 700', bg: 'bg-indigo-700', text: 'text-white' },
      { name: 'Indigo 900', bg: 'bg-indigo-900 dark:bg-indigo-950', text: 'text-indigo-200' },
    ]
  },
  {
    name: 'Neutral (Slate)',
    description: 'Warna untuk teks, latar belakang, border, dan elemen struktural.',
    colors: [
      { name: 'Slate 50', bg: 'bg-slate-50 dark:bg-slate-950', text: 'text-slate-600 dark:text-slate-400' },
      { name: 'Slate 100', bg: 'bg-slate-100 dark:bg-slate-900', text: 'text-slate-700 dark:text-slate-300' },
      { name: 'Slate 200', bg: 'bg-slate-200 dark:bg-slate-800', text: 'text-slate-800 dark:text-slate-200' },
      { name: 'Slate 400', bg: 'bg-slate-400 dark:bg-slate-600', text: 'text-white' },
      { name: 'Slate 600', bg: 'bg-slate-600 dark:bg-slate-400', text: 'text-white' },
      { name: 'Slate 900', bg: 'bg-slate-900 dark:bg-slate-100', text: 'text-white' },
    ]
  },
  {
    name: 'Success (Emerald)',
    description: 'Digunakan untuk status positif, persetujuan sukses, dan badge aktif.',
    colors: [
      { name: 'Emerald 50', bg: 'bg-emerald-50 dark:bg-emerald-950/20', text: 'text-emerald-700 dark:text-emerald-400' },
      { name: 'Emerald 100', bg: 'bg-emerald-100 dark:bg-emerald-950/40', text: 'text-emerald-800 dark:text-emerald-300' },
      { name: 'Emerald 500', bg: 'bg-emerald-500', text: 'text-white' },
      { name: 'Emerald 600', bg: 'bg-emerald-600', text: 'text-white' },
    ]
  },
  {
    name: 'Warning (Amber)',
    description: 'Untuk status tertunda (pending), perhatian, dan peringatan non-kritikal.',
    colors: [
      { name: 'Amber 50', bg: 'bg-amber-50 dark:bg-amber-950/20', text: 'text-amber-700 dark:text-amber-400' },
      { name: 'Amber 100', bg: 'bg-amber-100 dark:bg-amber-950/40', text: 'text-amber-800 dark:text-amber-300' },
      { name: 'Amber 500', bg: 'bg-amber-500', text: 'text-white' },
      { name: 'Amber 600', bg: 'bg-amber-600', text: 'text-white' },
    ]
  },
  {
    name: 'Danger (Rose)',
    description: 'Untuk penolakan registrasi, pembatalan, error, dan penghapusan data.',
    colors: [
      { name: 'Rose 50', bg: 'bg-rose-50 dark:bg-rose-950/20', text: 'text-rose-700 dark:text-rose-400' },
      { name: 'Rose 100', bg: 'bg-rose-100 dark:bg-rose-950/40', text: 'text-rose-800 dark:text-rose-300' },
      { name: 'Rose 500', bg: 'bg-rose-500', text: 'text-white' },
      { name: 'Rose 600', bg: 'bg-rose-600', text: 'text-white' },
    ]
  }
];

const subpages = [
  { name: 'Tombol & Badge', routeName: 'design-system.buttons-badges', desc: 'Preview jenis tombol, status disabled/loading, serta badge status.', icon: Play, color: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400' },
  { name: 'Formulir & Input', routeName: 'design-system.forms', desc: 'Bermacam input fields, textareas, select dropdown, checkbox, dan switch.', icon: Type, color: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400' },
  { name: 'Modal & SweetAlert', routeName: 'design-system.modals-alerts', desc: 'Demo modal popup aneka ukuran dengan transisi halus dan pemicu Alert.', icon: FileText, color: 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400' },
  { name: 'Tabel & Halaman', routeName: 'design-system.tables', desc: 'Layout standard table, pagination terintegrasi, dan card data kosong.', icon: Database, color: 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400' },
  { name: 'Desain Kartu', routeName: 'design-system.cards', desc: 'Layout card penampil status angka, detail grid informasi, dan layout panel filter.', icon: Layers, color: 'bg-violet-50 text-violet-600 dark:bg-violet-950/40 dark:text-violet-400' },
];
</script>

<template>
  <Head title="Sistem Desain - Ringkasan & Warna" />

  <AuthenticatedLayout>
    <div class="py-4 px-4 sm:px-4 lg:px-4">
      <div class="w-full space-y-4">
        
        <!-- Premium Header Panel -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
          <div class="space-y-1">
            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
              {{ __('Sistem Desain') }} - {{ __('Ringkasan & Warna') }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
              Panduan visual terpadu untuk standarisasi styling, warna, dan komponen di Pesu Peluh.
            </p>
          </div>
        </div>

        <!-- Quick Navigation Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <Link 
            v-for="page in subpages" 
            :key="page.routeName"
            :href="route(page.routeName)"
            class="group bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/80 hover:border-indigo-100 dark:hover:border-indigo-900 p-5 rounded-2xl shadow-sm hover:shadow-md transition duration-200 flex flex-col justify-between"
          >
            <div>
              <div :class="['h-10 w-10 rounded-xl flex items-center justify-center mb-4 transition duration-200 group-hover:scale-105', page.color]">
                <component :is="page.icon" class="h-5 w-5" />
              </div>
              <h3 class="text-sm font-bold text-slate-805 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                {{ page.name }}
              </h3>
              <p class="text-[11px] text-slate-400 dark:text-slate-550 mt-1 leading-normal">
                {{ page.desc }}
              </p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-50 dark:border-slate-800/50 flex items-center text-xs font-semibold text-indigo-600 dark:text-indigo-400 group-hover:gap-1.5 transition-all">
              <span>Buka Panel</span>
              <ArrowRight class="h-3.5 w-3.5 ml-1" />
            </div>
          </Link>
        </div>

        <!-- Colors Grid -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/80 rounded-2xl shadow-sm p-6 space-y-6">
          <div>
            <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Palet Warna Resmi</h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Semua warna mendukung variasi dark mode secara mulus menggunakan class Tailwind.</p>
          </div>

          <div class="space-y-8">
            <div v-for="group in colorGroups" :key="group.name" class="space-y-3">
              <div class="flex flex-col md:flex-row md:items-center justify-between gap-1">
                <h4 class="text-xs font-bold text-slate-805 dark:text-slate-200 uppercase tracking-wider">{{ group.name }}</h4>
                <p class="text-[11px] text-slate-450 dark:text-slate-500">{{ group.description }}</p>
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                <div 
                  v-for="color in group.colors" 
                  :key="color.name" 
                  class="border border-slate-100 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm"
                >
                  <div :class="['h-16 w-full flex items-end justify-end p-2', color.bg]">
                    <span v-if="color.isPrimary" class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-white text-indigo-600 dark:bg-slate-900 dark:text-indigo-400 shadow-sm border border-indigo-100 dark:border-slate-800">Primary</span>
                  </div>
                  <div class="p-2.5 bg-white dark:bg-slate-900/50">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ color.name }}</p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono mt-0.5 truncate">{{ color.bg }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Typography -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/80 rounded-2xl shadow-sm p-6 space-y-6">
          <div>
            <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Tipografi (Typography)</h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Menggunakan font utama Inter/Outfit untuk keterbacaan tinggi di berbagai jenis layar.</p>
          </div>

          <div class="border border-slate-100 dark:border-slate-800 rounded-xl divide-y divide-slate-100 dark:divide-slate-800 overflow-hidden">
            <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div class="w-full md:w-1/3">
                <span class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">H1 / H2 Header</span>
                <p class="text-xs text-slate-400 font-mono mt-0.5">text-2xl / text-xl font-extrabold text-slate-900</p>
              </div>
              <div class="flex-1">
                <h2 class="text-xl font-extrabold text-slate-905 dark:text-white">Pesu Peluh Integrated</h2>
              </div>
            </div>

            <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div class="w-full md:w-1/3">
                <span class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">Sub Header</span>
                <p class="text-xs text-slate-400 font-mono mt-0.5">text-base font-bold text-slate-800</p>
              </div>
              <div class="flex-1">
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Manajemen Kamar & Ruangan</h3>
              </div>
            </div>

            <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div class="w-full md:w-1/3">
                <span class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">Body Normal</span>
                <p class="text-xs text-slate-400 font-mono mt-0.5">text-sm text-slate-600</p>
              </div>
              <div class="flex-1">
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                  Unit penunjang medik dan non-medik memainkan peran penting dalam memastikan operasional rumah sakit berjalan lancar dan efisien setiap hari.
                </p>
              </div>
            </div>

            <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div class="w-full md:w-1/3">
                <span class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">Caption / Small Text</span>
                <p class="text-xs text-slate-400 font-mono mt-0.5">text-xs text-slate-500 / text-[11px] text-slate-400</p>
              </div>
              <div class="flex-1">
                <p class="text-xs text-slate-500 dark:text-slate-405">
                  Terakhir dimodifikasi pada 24 Juni 2026 pukul 13:35 WIB
                </p>
              </div>
            </div>

            <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div class="w-full md:w-1/3">
                <span class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">Monospace (Code/NIP)</span>
                <p class="text-xs text-slate-400 font-mono mt-0.5">font-mono text-xs tracking-wider</p>
              </div>
              <div class="flex-1">
                <span class="font-mono text-xs text-slate-800 dark:text-slate-200 tracking-wider">NIP. 199308122019031002</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
