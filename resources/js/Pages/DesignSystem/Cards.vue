<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Ticket, CheckCircle2, Clock, ShieldAlert, ArrowUpRight, ArrowDownRight, RefreshCw } from '@lucide/vue';

const stats = [
  { label: 'Total Tiket Kerusakan', value: '148', change: '+12.4%', isPositive: true, icon: Ticket, color: 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-950/40' },
  { label: 'Selesai Diperbaiki', value: '92', change: '+8.2%', isPositive: true, icon: CheckCircle2, color: 'text-emerald-600 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-950/40' },
  { label: 'Menunggu Persetujuan', value: '18', change: '-4.1%', isPositive: false, icon: Clock, color: 'text-amber-600 bg-amber-50 dark:text-amber-400 dark:bg-amber-950/40' },
  { label: 'Belum Ditugaskan', value: '6', change: '0.0%', isPositive: true, isNeutral: true, icon: ShieldAlert, color: 'text-rose-600 bg-rose-50 dark:text-rose-400 dark:bg-rose-950/40' }
];

const mockUser = {
  name: 'Budi Santoso',
  nip: '199308122019031002',
  email: 'budi.santoso@email.com',
  phone: '081234567890',
  role: 'Teknisi Lapangan',
  supportingUnit: 'IPSRS (Instalasi Pemeliharaan Sarana RS)',
  room: 'Lantai 1 - Bengkel IPSRS',
  status: 'ACTIVE',
  joinedDate: '24 Juni 2026'
};
</script>

<template>
  <Head title="Sistem Desain - Desain Kartu" />

  <AuthenticatedLayout>
    <div class="py-4 px-4 sm:px-4 lg:px-4">
      <div class="w-full space-y-4">
        
        <!-- Premium Header Panel -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
          <div class="space-y-1">
            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
              {{ __('Desain Kartu') }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
              Kumpulan pola layout card statistik, detail panel data, serta list spesifikasi informasi.
            </p>
          </div>
        </div>

        <!-- Summary Stats Cards Grid -->
        <div class="space-y-3">
          <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-[10px]">Dashboard Stat Cards</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div 
              v-for="stat in stats" 
              :key="stat.label"
              class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow transition duration-200 flex justify-between items-start"
            >
              <div class="space-y-2">
                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 leading-tight block truncate">{{ stat.label }}</span>
                <span class="text-2xl font-extrabold text-slate-900 dark:text-white leading-none block">{{ stat.value }}</span>
                
                <!-- Trend Badge -->
                <span 
                  v-if="stat.isNeutral" 
                  class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-50 dark:bg-slate-800 text-slate-500"
                >
                  Tetap
                </span>
                <span 
                  v-else 
                  :class="[
                    'inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-extrabold',
                    stat.isPositive ? 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-450' : 'bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-455'
                  ]"
                >
                  <ArrowUpRight class="h-3 w-3" />
                  <ArrowDownRight class="h-3 w-3" />
                  {{ stat.change }}
                </span>
              </div>
              
              <div :class="['h-11 w-11 rounded-xl flex items-center justify-center flex-shrink-0', stat.color]">
                <component :is="stat.icon" class="h-5.5 w-5.5" />
              </div>
            </div>
          </div>
        </div>

        <!-- Content cards grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          
          <!-- Left: Detail Key Value card -->
          <div class="lg:col-span-2 space-y-4">
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
              <!-- Header -->
              <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/20 dark:bg-slate-900/40">
                <div class="flex items-center gap-3">
                  <div class="h-10 w-10 rounded-full bg-slate-105 dark:bg-slate-800 text-slate-650 dark:text-slate-350 flex items-center justify-center font-bold text-sm">
                    BS
                  </div>
                  <div class="flex flex-col">
                    <h4 class="text-sm font-extrabold text-slate-900 dark:text-white leading-tight">Detail Profil Pengguna</h4>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Informasi lengkap penugasan & data pribadi</p>
                  </div>
                </div>
                
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-455">
                  AKTIF
                </span>
              </div>

              <!-- Body: Grid List -->
              <div class="p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-xs">
                  <div class="space-y-1">
                    <dt class="font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-[10px]">Nama Lengkap</dt>
                    <dd class="text-slate-850 dark:text-slate-200 font-semibold">{{ mockUser.name }}</dd>
                  </div>

                  <div class="space-y-1">
                    <dt class="font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-[10px]">Nomor Induk Pegawai (NIP)</dt>
                    <dd class="text-slate-850 dark:text-slate-200 font-mono tracking-wider">{{ mockUser.nip }}</dd>
                  </div>

                  <div class="space-y-1">
                    <dt class="font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-[10px]">Alamat Email</dt>
                    <dd class="text-slate-855 dark:text-slate-200 font-semibold">{{ mockUser.email }}</dd>
                  </div>

                  <div class="space-y-1">
                    <dt class="font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-[10px]">Nomor Handphone</dt>
                    <dd class="text-slate-855 dark:text-slate-200 font-semibold">{{ mockUser.phone }}</dd>
                  </div>

                  <div class="space-y-1">
                    <dt class="font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-[10px]">Hak Akses / Role</dt>
                    <dd class="text-slate-855 dark:text-slate-200 font-semibold">{{ mockUser.role }}</dd>
                  </div>

                  <div class="space-y-1">
                    <dt class="font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-[10px]">Tanggal Terdaftar</dt>
                    <dd class="text-slate-855 dark:text-slate-200 font-semibold">{{ mockUser.joinedDate }}</dd>
                  </div>

                  <div class="space-y-1 md:col-span-2">
                    <dt class="font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-[10px]">Unit Kerja Penunjang</dt>
                    <dd class="text-slate-855 dark:text-slate-200 font-semibold">{{ mockUser.supportingUnit }}</dd>
                  </div>

                  <div class="space-y-1 md:col-span-2">
                    <dt class="font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-[10px]">Ruangan Lokasi Utama</dt>
                    <dd class="text-slate-855 dark:text-slate-200 font-semibold">{{ mockUser.room }}</dd>
                  </div>
                </dl>
              </div>
              
              <!-- Footer -->
              <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/20 dark:bg-slate-900/40 flex items-center justify-end gap-3">
                <button class="h-9 px-4 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-705 dark:bg-slate-800 dark:hover:bg-slate-755 dark:text-slate-350 transition">
                  Nonaktifkan User
                </button>
                <button class="h-9 px-4 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                  Edit Kredensial
                </button>
              </div>
            </div>
          </div>

          <!-- Right: Small Info panel card -->
          <div class="space-y-4">
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
              <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-[10px]">Informasi Penugasan Aktif</h4>
              
              <!-- Ticket Info item -->
              <div class="p-4 rounded-xl bg-indigo-50/40 dark:bg-indigo-950/20 border border-indigo-100/40 dark:border-indigo-900/20 flex gap-3.5 items-start">
                <div class="h-9 w-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center flex-shrink-0 mt-0.5">
                  <Ticket class="h-4.5 w-4.5" />
                </div>
                <div class="flex-1 min-w-0 space-y-1">
                  <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Tiket #SR-20260601</span>
                  <h5 class="text-xs font-bold text-slate-850 dark:text-white leading-tight truncate">AC Bocor & Kurang Dingin</h5>
                  <p class="text-[11px] text-slate-450 dark:text-slate-500 leading-normal line-clamp-2">Ruang Poliklinik Gigi Utama mengalami kebocoran pipa AC sejak tadi pagi...</p>
                  <div class="pt-2 flex items-center gap-1.5 text-[10px] font-bold text-slate-450">
                    <Clock class="h-3.5 w-3.5" />
                    <span>Dibuat: 24 Juni 2026</span>
                  </div>
                </div>
              </div>

              <!-- Activity item -->
              <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-950/40 border border-transparent dark:border-slate-800/80 flex gap-3.5 items-start">
                <div class="h-9 w-9 rounded-lg bg-slate-100 dark:bg-slate-850 text-slate-500 dark:text-slate-450 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <RefreshCw class="h-4.5 w-4.5" />
                </div>
                <div class="flex-1 min-w-0 space-y-0.5">
                  <span class="text-[10px] font-bold text-slate-450 dark:text-slate-550 uppercase tracking-wider">Sistem Aktivitas</span>
                  <p class="text-xs text-slate-800 dark:text-slate-300 font-semibold leading-normal">Budi Santoso mengganti status tiket menjadi <span class="text-indigo-600 dark:text-indigo-400">Proses Kerja</span></p>
                  <span class="text-[9px] text-slate-400 dark:text-slate-550 block">5 menit yang lalu</span>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
