<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Search, Eye, Plus, ChevronLeft, ChevronRight, Inbox } from '@lucide/vue';

const showEmptyState = ref(false);
const searchQuery = ref('');
const statusFilter = ref('');

const demoUsers = [
  { id: 1, name: 'Budi Santoso', email: 'budi.santoso@email.com', role: 'Super Admin', room: 'Lantai 1 - Tulip 101', status: 'ACTIVE', nip: '199308122019031002' },
  { id: 2, name: 'Siti Rahma', email: 'siti.rahma@email.com', role: 'Kepala Unit', room: 'Lantai 2 - IPSRS Office', status: 'ACTIVE', nip: '198711042013082001' },
  { id: 3, name: 'Ahmad Fauzi', email: 'ahmad.fauzi@email.com', role: 'Teknisi', room: 'Lantai 1 - Bengkel IPSRS', status: 'ACTIVE', nip: '199105232018011003' },
  { id: 4, name: 'Rani Wijaya', email: 'rani.wijaya@email.com', role: 'Kepala Ruangan', room: 'Lantai 3 - Dahlia 304', status: 'PENDING', nip: '199504152020122002' },
  { id: 5, name: 'Dedi Kurniawan', email: 'dedi.kurniawan@email.com', role: 'Staf / Pelapor', room: 'Lantai 2 - ICU Utama', status: 'INACTIVE', nip: '199402282019031001' }
];

const toggleState = () => {
  showEmptyState.value = !showEmptyState.value;
};
</script>

<template>
  <Head title="Sistem Desain - Tabel & Halaman" />

  <AuthenticatedLayout>
    <div class="py-4 px-4 sm:px-4 lg:px-4">
      <div class="w-full space-y-4">
        
        <!-- Premium Header Panel -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
          <div class="space-y-1">
            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
              {{ __('Tabel & Halaman') }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
              Standarisasi tata letak tabel data, pagination, filter bar, dan visualisasi state kosong.
            </p>
          </div>
          
          <div class="flex-shrink-0">
            <button 
              @click="toggleState"
              class="h-10 px-4 text-xs font-bold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 transition border border-transparent dark:border-slate-800"
            >
              Toggle: {{ showEmptyState ? 'Tabel Data' : 'Data Kosong' }}
            </button>
          </div>
        </div>

        <!-- Main Container -->
        <div class="space-y-4">
          
          <!-- Table State: Active Data -->
          <div v-if="!showEmptyState" class="space-y-4">
            
            <!-- Search and Filter Panel -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-transparent dark:border-slate-800 shadow-sm flex flex-col md:flex-row justify-between gap-4">
              <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <!-- Search input -->
                <div class="relative w-full sm:w-64">
                  <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                  <input 
                    v-model="searchQuery"
                    type="text" 
                    placeholder="Cari nama atau NIP..."
                    class="w-full h-10 pl-10 pr-4 border border-slate-200 dark:border-slate-800/80 rounded-xl bg-white dark:bg-slate-950 text-slate-805 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition shadow-sm"
                  />
                </div>

                <!-- Filter select -->
                <select 
                  v-model="statusFilter"
                  class="h-10 px-4 border border-slate-200 dark:border-slate-800/85 rounded-xl bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition shadow-sm"
                >
                  <option value="">Semua Status</option>
                  <option value="ACTIVE">Aktif</option>
                  <option value="PENDING">Tertunda</option>
                  <option value="INACTIVE">Nonaktif</option>
                </select>
              </div>

              <!-- Action Button -->
              <button class="h-10 px-4 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center gap-2 shadow-sm transition">
                <Plus class="h-4 w-4" />
                <span>Tambah Pengguna</span>
              </button>
            </div>

            <!-- Datatable -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                  <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800/80 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                      <th class="px-6 py-4">Informasi Pengguna</th>
                      <th class="px-6 py-4">Nomor Induk (NIP)</th>
                      <th class="px-6 py-4">Unit / Ruangan</th>
                      <th class="px-6 py-4">Status</th>
                      <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-350">
                    <tr 
                      v-for="user in demoUsers" 
                      :key="user.id" 
                      class="hover:bg-slate-50/40 dark:hover:bg-slate-850/30 transition duration-150"
                    >
                      <!-- User Info Card cell -->
                      <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                          <div class="h-9 w-9 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">
                            {{ user.name.charAt(0) }}
                          </div>
                          <div class="flex flex-col min-w-0">
                            <span class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ user.name }}</span>
                            <span class="text-[10px] text-slate-450 dark:text-slate-500 truncate mt-0.5">{{ user.email }}</span>
                          </div>
                        </div>
                      </td>
                      <!-- NIP Monospace -->
                      <td class="px-6 py-4 font-mono text-xs text-slate-650 dark:text-slate-400 tracking-wider">
                        {{ user.nip }}
                      </td>
                      <!-- Room & Role -->
                      <td class="px-6 py-4 flex flex-col">
                        <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ user.room }}</span>
                        <span class="text-[10px] text-slate-450 mt-0.5">{{ user.role }}</span>
                      </td>
                      <!-- Badge Status -->
                      <td class="px-6 py-4">
                        <span 
                          v-if="user.status === 'ACTIVE'" 
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-450"
                        >
                          AKTIF
                        </span>
                        <span 
                          v-else-if="user.status === 'PENDING'" 
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-450"
                        >
                          PENDING
                        </span>
                        <span 
                          v-else 
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-455"
                        >
                          NONAKTIF
                        </span>
                      </td>
                      <!-- Actions -->
                      <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-1.5">
                          <button class="h-8 w-8 flex items-center justify-center rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-500 hover:text-slate-800 dark:bg-slate-850 dark:hover:bg-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition" title="Lihat Detail">
                            <Eye class="h-4 w-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Footer Pagination Layout -->
              <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-450 bg-slate-50/20 dark:bg-slate-900/40">
                <span class="hidden sm:inline">
                  Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-200">1</span> sampai <span class="font-semibold text-slate-700 dark:text-slate-200">5</span> dari <span class="font-semibold text-slate-700 dark:text-slate-200">24</span> data master
                </span>
                
                <div class="flex items-center gap-1.5 ml-auto">
                  <button disabled class="h-8 px-3 rounded-lg border border-slate-150 dark:border-slate-800/80 flex items-center gap-1 opacity-50 cursor-not-allowed text-[11px] font-semibold text-slate-400">
                    <ChevronLeft class="h-3.5 w-3.5" />
                    <span>Sebelumnya</span>
                  </button>
                  <button class="h-8 w-8 flex items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-[11px]">1</button>
                  <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 transition font-bold text-[11px]">2</button>
                  <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 transition font-bold text-[11px]">3</button>
                  <button class="h-8 px-3 rounded-lg border border-slate-200 hover:border-slate-350 dark:border-slate-800 dark:hover:border-slate-700 flex items-center gap-1 text-[11px] font-semibold text-slate-600 dark:text-slate-300 transition">
                    <span>Selanjutnya</span>
                    <ChevronRight class="h-3.5 w-3.5" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Table State: Empty Data Card Visual -->
          <div v-else class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-12 text-center flex flex-col items-center justify-center shadow-sm max-w-xl mx-auto space-y-4">
            <div class="h-16 w-16 rounded-full bg-slate-50 dark:bg-slate-950 text-slate-400 dark:text-slate-500 flex items-center justify-center">
              <Inbox class="h-8 w-8" />
            </div>
            <div class="space-y-1">
              <h3 class="text-sm font-extrabold text-slate-800 dark:text-white">Data Master Pengguna Kosong</h3>
              <p class="text-xs text-slate-400 dark:text-slate-500 max-w-sm leading-relaxed">Belum ada data pendaftar pengguna baru dalam sistem atau tidak ditemukan hasil pencarian yang sesuai.</p>
            </div>
            <button class="h-10 px-5 text-xs font-bold rounded-xl bg-indigo-650 hover:bg-indigo-755 text-white shadow-sm hover:shadow transition">
              Tambahkan Data Baru
            </button>
          </div>

        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
