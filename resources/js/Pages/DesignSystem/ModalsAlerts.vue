<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, getCurrentInstance } from 'vue';
import { X, ShieldAlert, CheckCircle2, AlertTriangle, AlertCircle } from '@lucide/vue';

const { proxy } = getCurrentInstance();
const activeModalSize = ref(null);

const openModal = (size) => {
  activeModalSize.value = size;
};

const closeModal = () => {
  activeModalSize.value = null;
};

// SweetAlert Demos using proxy.$swal (global standardized helper)
const triggerSuccessSwal = () => {
  proxy.$swal({
    title: 'Sukses!',
    text: 'Data pengguna baru berhasil disetujui dan diberikan hak akses.',
    icon: 'success',
    confirmButtonText: 'Selesai'
  });
};

const triggerErrorSwal = () => {
  proxy.$swal({
    title: 'Gagal Memproses!',
    text: 'Kamera tidak terdeteksi atau izin biometrik wajah ditolak.',
    icon: 'error',
    confirmButtonText: 'Coba Lagi',
    confirmButtonColor: '#e11d48' // custom color for error
  });
};

const triggerWarningSwal = () => {
  proxy.$swal({
    title: 'Konfigurasi Terbatas!',
    text: 'Divisi ini masih terhubung dengan beberapa unit penunjang aktif.',
    icon: 'warning',
    confirmButtonText: 'Mengerti',
    confirmButtonColor: '#d97706' // custom color for warning
  });
};

const triggerConfirmSwal = () => {
  proxy.$swal({
    title: 'Apakah Anda yakin?',
    text: 'Pengguna bernama Budi Santoso akan ditolak pendaftarannya secara permanen.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Tolak',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#64748b'
  }).then((result) => {
    if (result.isConfirmed) {
      proxy.$swal({
        title: 'Ditolak!',
        text: 'Pendaftaran pengguna telah resmi ditolak.',
        icon: 'success'
      });
    }
  });
};

const triggerToastSwal = () => {
  proxy.$toast('Pesan notifikasi berhasil muncul di pojok kanan atas!', 'success');
};

const triggerToastDirectSwal = () => {
  proxy.$swal({
    toast: true,
    position: 'top-end',
    icon: 'info',
    title: 'Notifikasi kustom dari $swal dengan parameter toast.',
    showConfirmButton: false,
    timer: 3000
  });
};
</script>

<template>
  <Head title="Sistem Desain - Modal & SweetAlert" />

  <AuthenticatedLayout>
    <div class="py-4 px-4 sm:px-4 lg:px-4">
      <div class="w-full space-y-4">
        
        <!-- Premium Header Panel -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
          <div class="space-y-1">
            <h2 class="text-xl font-extrabold text-slate-955 dark:text-white leading-tight">
              {{ __('Modal & SweetAlert') }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
              Uji coba jenis layout pop-up modal berbagai ukuran dan standarisasi tampilan dialog SweetAlert2.
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          
          <!-- Left: Local Modal Triggers -->
          <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm p-6 space-y-6">
            <div>
              <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Modal Pop-up (Vue Modal)</h3>
              <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Membuka dialog modular dengan backdrop blur-sm dan transisi animasi slide up.</p>
            </div>

            <div class="space-y-4">
              <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-[10px]">Modal Width Options</h4>
              <div class="flex flex-wrap gap-3">
                <button @click="openModal('sm')" class="h-10 px-4 text-xs font-bold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 transition">
                  Small (max-w-sm)
                </button>
                <button @click="openModal('md')" class="h-10 px-4 text-xs font-bold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 transition">
                  Medium (max-w-md)
                </button>
                <button @click="openModal('lg')" class="h-10 px-4 text-xs font-bold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 transition">
                  Large (max-w-lg)
                </button>
                <button @click="openModal('xl')" class="h-10 px-4 text-xs font-bold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 transition">
                  Extra Large (max-w-xl)
                </button>
                <button @click="openModal('2xl')" class="h-10 px-4 text-xs font-bold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 transition">
                  Double XL (max-w-2xl)
                </button>
              </div>
            </div>
          </div>

          <!-- Right: SweetAlert2 Triggers -->
          <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm p-6 space-y-6">
            <div>
              <h3 class="text-base font-extrabold text-slate-800 dark:text-white">SweetAlert2 (Dialog Alert)</h3>
              <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Integrasi dialog global menggunakan pustaka SweetAlert2, disesuaikan dengan skema warna indigo/slate.</p>
            </div>

            <div class="space-y-4">
              <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-[10px]">Trigger SweetAlert Demos</h4>
              <div class="flex flex-wrap gap-3">
                <button @click="triggerSuccessSwal" class="h-10 px-4 text-sm font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow transition flex items-center gap-2">
                  <CheckCircle2 class="h-4.5 w-4.5" />
                  <span>Success Alert</span>
                </button>

                <button @click="triggerErrorSwal" class="h-10 px-4 text-sm font-semibold rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow transition flex items-center gap-2">
                  <AlertCircle class="h-4.5 w-4.5" />
                  <span>Error Alert</span>
                </button>

                <button @click="triggerWarningSwal" class="h-10 px-4 text-sm font-semibold rounded-xl bg-amber-600 hover:bg-amber-700 text-white shadow transition flex items-center gap-2">
                  <AlertTriangle class="h-4.5 w-4.5" />
                  <span>Warning Alert</span>
                </button>

                <button @click="triggerConfirmSwal" class="h-10 px-4 text-sm font-semibold rounded-xl bg-slate-700 hover:bg-slate-800 text-white shadow transition flex items-center gap-2">
                  <ShieldAlert class="h-4.5 w-4.5" />
                  <span>Confirmation Swal</span>
                </button>

                <button @click="triggerToastSwal" class="h-10 px-4 text-sm font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow transition flex items-center gap-2">
                  <CheckCircle2 class="h-4.5 w-4.5" />
                  <span>Toast Shorthand</span>
                </button>

                <button @click="triggerToastDirectSwal" class="h-10 px-4 text-sm font-semibold rounded-xl bg-violet-600 hover:bg-violet-700 text-white shadow transition flex items-center gap-2">
                  <CheckCircle2 class="h-4.5 w-4.5" />
                  <span>Toast Direct ($swal)</span>
                </button>
              </div>
            </div>
          </div>

        </div>

        <!-- Teleport Vue Modal Component Mockup -->
        <Teleport to="body">
          <div 
            v-if="activeModalSize !== null" 
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
          >
            <!-- Backdrop overlay -->
            <div 
              @click="closeModal" 
              class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity duration-300"
            ></div>

            <!-- Modal card -->
            <div 
              :class="[
                'bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-3xl shadow-xl w-full relative z-10 overflow-hidden transform transition-all duration-300 scale-100 flex flex-col',
                activeModalSize === 'sm' ? 'max-w-sm' : '',
                activeModalSize === 'md' ? 'max-w-md' : '',
                activeModalSize === 'lg' ? 'max-w-lg' : '',
                activeModalSize === 'xl' ? 'max-w-xl' : '',
                activeModalSize === '2xl' ? 'max-w-2xl' : '',
              ]"
            >
              <!-- Header -->
              <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-850 flex items-center justify-between">
                <div class="flex flex-col">
                  <h3 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight">Detail Akses & Penugasan</h3>
                  <span class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Dimensi Modal: {{ activeModalSize }}</span>
                </div>
                <button 
                  @click="closeModal" 
                  class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-850 transition"
                >
                  <X class="h-5 w-5" />
                </button>
              </div>

              <!-- Body Content -->
              <div class="p-6 overflow-y-auto space-y-4 max-h-[60vh]">
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                  Ini adalah modal dengan lebar penyesuai tipe <code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-850 dark:text-slate-350 font-mono text-xs rounded">max-w-{{ activeModalSize }}</code>. Seluruh kontainer menggunakan radius sudut <code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-850 dark:text-slate-350 font-mono text-xs rounded">rounded-3xl</code> (24px) untuk keselarasan desain premium antarmuka Pesu Peluh.
                </p>

                <div class="p-4 bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-855 rounded-2xl space-y-2">
                  <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Panduan Standard Modal:</p>
                  <ul class="text-[11px] text-slate-505 dark:text-slate-400 list-disc pl-4 space-y-1">
                    <li>Gunakan `Teleport to="body"` agar tidak terganggu CSS positioning parent layout.</li>
                    <li>Sertakan tombol silang (X) di pojok kanan atas untuk navigasi kilat penutupan modal.</li>
                    <li>Selalu berikan penutup backdrop klik area luar modal.</li>
                  </ul>
                </div>
              </div>

              <!-- Footer -->
              <div class="px-6 py-4.5 bg-slate-50 dark:bg-slate-955/20 border-t border-slate-100 dark:border-slate-850 flex items-center justify-end gap-3">
                <button 
                  @click="closeModal" 
                  class="h-10 px-5 text-sm font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-750 dark:text-slate-300 transition"
                >
                  Tutup
                </button>
                <button 
                  @click="closeModal" 
                  class="h-10 px-5 text-sm font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition"
                >
                  Konfirmasi Aksi
                </button>
              </div>
            </div>
          </div>
        </Teleport>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
