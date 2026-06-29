<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Search, Eye, EyeOff, UploadCloud, X } from '@lucide/vue';

const showPass = ref(false);
const demoText = ref('');
const demoSelect = ref('');
const demoCheckbox = ref([]);
const demoSwitch = ref(true);
const demoFile = ref(null);
const dragOver = ref(false);

const handleFileDrop = (e) => {
  e.preventDefault();
  dragOver.value = false;
  if (e.dataTransfer.files && e.dataTransfer.files[0]) {
    demoFile.value = e.dataTransfer.files[0];
  }
};

const handleFileSelect = (e) => {
  if (e.target.files && e.target.files[0]) {
    demoFile.value = e.target.files[0];
  }
};

const removeFile = () => {
  demoFile.value = null;
};
</script>

<template>
  <Head title="Sistem Desain - Formulir & Input" />

  <AuthenticatedLayout>
    <div class="py-4 px-4 sm:px-4 lg:px-4">
      <div class="w-full space-y-4">
        
        <!-- Premium Header Panel -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
          <div class="space-y-1">
            <h2 class="text-xl font-extrabold text-slate-955 dark:text-white leading-tight">
              {{ __('Formulir & Input') }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
              Panduan desain kolom input data, select menu, check box, switch, dan uploader berkas.
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          
          <!-- Left 2 Cols: Text inputs, Selects, Choices -->
          <div class="lg:col-span-2 space-y-4">
            
            <!-- Standard Fields -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm p-6 space-y-6">
              <div>
                <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Input Text & Select</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Desain input text, password, dropdown dan visualisasinya saat terjadi kesalahan (validation error).</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Text Input -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Nama Lengkap</label>
                  <input 
                    v-model="demoText"
                    type="text" 
                    placeholder="Contoh: Dr. Budi Santoso"
                    class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-800/80 rounded-xl bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-sm"
                  />
                  <p class="text-[10px] text-slate-400 dark:text-slate-500">Tuliskan nama lengkap beserta gelar medik bila ada.</p>
                </div>

                <!-- Password Input -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Kata Sandi Baru</label>
                  <div class="relative">
                    <input 
                      :type="showPass ? 'text' : 'password'" 
                      placeholder="Minimal 8 karakter"
                      class="w-full px-4 py-2.5 pr-10 border border-slate-200 dark:border-slate-800/80 rounded-xl bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm"
                    />
                    <button 
                      @click="showPass = !showPass"
                      type="button" 
                      class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    >
                      <EyeOff v-if="showPass" class="h-4 w-4" />
                      <Eye v-else class="h-4 w-4" />
                    </button>
                  </div>
                </div>

                <!-- Select Input -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Ruangan Tugas</label>
                  <select 
                    v-model="demoSelect"
                    class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-800/80 rounded-xl bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm"
                  >
                    <option value="">Pilih Ruangan...</option>
                    <option value="1">Lantai 1 - Tulip 101</option>
                    <option value="2">Lantai 2 - ICU Utama</option>
                    <option value="3">Lantai 3 - Poliklinik Gigi</option>
                  </select>
                </div>

                <!-- Search prefix -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Cari Data (Prefix Icon)</label>
                  <div class="relative">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input 
                      type="text" 
                      placeholder="Masukkan kata kunci pencarian..."
                      class="w-full pl-10 pr-4 py-2.5 border border-slate-200 dark:border-slate-800/80 rounded-xl bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm"
                    />
                  </div>
                </div>

                <!-- Monospace Number / NIP -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Nomor Induk Pegawai (NIP)</label>
                  <input 
                    type="text" 
                    maxlength="18"
                    placeholder="Contoh: 199308122019031002"
                    class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-800/80 rounded-xl bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-mono tracking-wider shadow-sm"
                  />
                </div>

                <!-- Phone Prefix -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Nomor Handphone (Prefix text)</label>
                  <div class="flex shadow-sm rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent transition-all">
                    <span class="inline-flex items-center px-3.5 bg-slate-50 dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-400 dark:text-slate-500 select-none">
                      +62
                    </span>
                    <input 
                      type="tel" 
                      maxlength="15"
                      placeholder="8123456789"
                      class="w-full px-4 py-2.5 bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-sm focus:outline-none"
                    />
                  </div>
                </div>

                <!-- Error State -->
                <div class="space-y-1.5 md:col-span-2 p-4 rounded-xl bg-rose-50/20 dark:bg-rose-950/10 border border-rose-100/40 dark:border-rose-900/20">
                  <div class="space-y-1.5">
                    <label class="text-xs font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                      <span>Email Instansi</span>
                      <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-450">Error State</span>
                    </label>
                    <input 
                      type="email" 
                      value="budi.santoso@email"
                      class="w-full px-4 py-2.5 border border-rose-300 dark:border-rose-800 rounded-xl bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all shadow-sm"
                    />
                    <p class="text-xs text-rose-600 dark:text-rose-400 font-medium">Format email instansi tidak valid. Gunakan @email.com</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right 1 Col: Checkbox, Switches, Uploaders -->
          <div class="space-y-4">
            
            <!-- Choices & Switch -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm p-6 space-y-6">
              <div>
                <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Pilihan & Toggles</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Pilihan checkbox, radio dan switch toggle.</p>
              </div>

              <div class="space-y-6">
                <!-- Switch toggle -->
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50/50 dark:bg-slate-855 border border-transparent dark:border-slate-800/60">
                  <div class="flex flex-col">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Mode Verifikasi Wajah</span>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500">Mungkinkan login via biometrik</span>
                  </div>
                  <button 
                    @click="demoSwitch = !demoSwitch"
                    type="button" 
                    :class="['w-10 h-6 flex items-center rounded-full p-0.5 transition-colors duration-200 focus:outline-none', demoSwitch ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-slate-800']"
                  >
                    <div :class="['bg-white w-5 h-5 rounded-full shadow-md transform transition-transform duration-200', demoSwitch ? 'translate-x-4' : 'translate-x-0']"></div>
                  </button>
                </div>

                <!-- Checkbox selection -->
                <div class="space-y-3">
                  <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Hak Layanan Penunjang (Checkbox)</label>
                  <div class="space-y-2">
                    <label class="flex items-center gap-3 cursor-pointer group">
                      <input 
                        type="checkbox" 
                        value="medik"
                        v-model="demoCheckbox"
                        class="h-4.5 w-4.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-950 dark:border-slate-800"
                      />
                      <span class="text-xs text-slate-600 dark:text-slate-400 group-hover:text-slate-800 dark:group-hover:text-slate-200 transition">Medik (IPSRS, Lab, dll)</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer group">
                      <input 
                        type="checkbox" 
                        value="non-medik"
                        v-model="demoCheckbox"
                        class="h-4.5 w-4.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-950 dark:border-slate-800"
                      />
                      <span class="text-xs text-slate-600 dark:text-slate-400 group-hover:text-slate-800 dark:group-hover:text-slate-200 transition">Non-Medik (Sanitasi, CSSD, dll)</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- File Uploader -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm p-6 space-y-6">
              <div>
                <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Uploader File Premium</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Layout drag & drop uploader pasfoto atau dokumen pendukung tiket.</p>
              </div>

              <div class="space-y-4">
                <div 
                  @dragover.prevent="dragOver = true"
                  @dragleave="dragOver = false"
                  @drop="handleFileDrop"
                  :class="[
                    'border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition flex flex-col items-center justify-center min-h-[170px]',
                    dragOver ? 'border-indigo-500 bg-indigo-50/20 dark:bg-indigo-950/10' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/40 dark:bg-slate-950/20'
                  ]"
                  @click="$refs.fileInputRef.click()"
                >
                  <input 
                    ref="fileInputRef"
                    type="file" 
                    class="hidden" 
                    accept="image/*"
                    @change="handleFileSelect"
                  />

                  <div class="h-12 w-12 rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-3">
                    <UploadCloud class="h-6 w-6" />
                  </div>
                  
                  <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Pilih gambar atau drag & drop file</p>
                  <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">PNG, JPG atau JPEG (maksimal 2MB)</p>
                </div>

                <!-- Uploaded preview mockup -->
                <div v-if="demoFile" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/50">
                  <div class="h-10 w-10 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 flex-shrink-0">
                    <UploadCloud class="h-5 w-5" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ demoFile.name }}</p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ (demoFile.size / 1024).toFixed(1) }} KB</p>
                  </div>
                  <button 
                    @click="removeFile"
                    class="h-7 w-7 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-rose-600 dark:bg-slate-800 dark:hover:bg-slate-700 transition"
                  >
                    <X class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
