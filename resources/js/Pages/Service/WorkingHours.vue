<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import TimePickerInput from '@/Components/TimePickerInput.vue';
import { 
    Clock, 
    Save,
    Info,
    X,
    CheckCircle2,
    Zap,
    Bot,
    Loader2
} from '@lucide/vue';

const { proxy } = getCurrentInstance();

const props = defineProps({
    units: {
        type: Array,
        default: () => []
    },
    workingHours: {
        type: Array,
        default: () => []
    },
    currentUser: {
        type: Object,
        default: () => ({})
    }
});

const showInfoModal = ref(false);

const isAdmin = computed(() => props.currentUser?.role_id === 1);

// Auto-select user's supporting unit if available
const userUnitId = computed(() => props.currentUser?.supporting_unit_id);
const selectedUnitForHours = ref(userUnitId.value ? userUnitId.value : (props.units[0]?.id || 1));

const currentSelectedUnit = computed(() => {
    return props.units.find(u => Number(u.id) === Number(selectedUnitForHours.value)) || props.units[0];
});

const dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

// Get current ISO day (1=Monday ... 7=Sunday)
const todayIsoDay = computed(() => {
    const jsDay = new Date().getDay(); // 0=Sunday, 1=Monday...
    return jsDay === 0 ? 7 : jsDay;
});

const hoursForm = useForm({
    supporting_unit_id: selectedUnitForHours.value,
    hours: []
});

const loadHoursForSelectedUnit = () => {
    hoursForm.supporting_unit_id = selectedUnitForHours.value;
    hoursForm.hours = dayNames.map((day, idx) => {
        const existing = props.workingHours.find(
            wh => Number(wh.supporting_unit_id) === Number(selectedUnitForHours.value) && Number(wh.day_of_week) === (idx + 1)
        );
        return {
            day_of_week: idx + 1,
            start_time: existing?.start_time ? String(existing.start_time).substring(0, 5) : '07:30',
            end_time: existing?.end_time ? String(existing.end_time).substring(0, 5) : '15:00',
            is_active: existing ? Boolean(existing.is_active) : (idx < 5)
        };
    });
};

watch([selectedUnitForHours, () => props.workingHours], () => {
    loadHoursForSelectedUnit();
}, { immediate: true });

const showConfirmModal = ref(false);

const openConfirmModal = () => {
    showConfirmModal.value = true;
};

const executeSaveHours = () => {
    hoursForm.supporting_unit_id = selectedUnitForHours.value;
    hoursForm.post(route('technicians.update-working-hours'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showConfirmModal.value = false;
            if (proxy?.$toast) {
                proxy.$toast('Jadwal jam operasional berhasil disimpan.', 'success');
            }
        },
        onError: () => {
            if (proxy?.$toast) {
                proxy.$toast('Gagal menyimpan jam operasional.', 'error');
            }
        }
    });
};
</script>

<template>
    <Head title="Jam Operasional" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">

                <!-- Header Panel (IDENTICAL TO USERMANAGEMENT/INDEX.VUE) -->
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Clock class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                {{ __('Jam Operasional') }}
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                {{ __('Atur jadwal jam kerja disposisi manual Kepala Unit.') }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showInfoModal = true"
                        class="w-full sm:w-auto h-10 inline-flex items-center justify-center gap-2 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm rounded-xl transition duration-150 whitespace-nowrap border-0 shadow-none cursor-pointer"
                    >
                        <Info class="h-4 w-4 text-slate-600 dark:text-slate-300" />
                        <span>Panduan Jam Operasional</span>
                    </button>
                </div>

                <!-- Main Container Unit Penunjang -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                    
                    <!-- Header Row Unit Penunjang (Padding 2, Tanpa Bottom Border) -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-2">
                        <!-- Admin View: SearchableSelect Component -->
                        <div v-if="isAdmin" class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <label class="text-sm font-extrabold uppercase text-slate-800 dark:text-white shrink-0">
                                {{ __('PILIH UNIT PENUNJANG') }}
                            </label>
                            <div class="w-full sm:w-64">
                                <SearchableSelect
                                    v-model="selectedUnitForHours"
                                    :options="units"
                                    value-key="id"
                                    label-key="name"
                                    placeholder="Pilih Unit Penunjang..."
                                />
                            </div>
                        </div>

                        <!-- Ka. Unit View: Readonly Title -->
                        <div v-else class="flex items-center gap-2.5 text-sm font-extrabold uppercase text-slate-800 dark:text-white">
                            <span>{{ __('UNIT PENUNJANG') }}</span>
                            <span class="px-3 py-1 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-700 dark:text-white font-extrabold uppercase text-xs">
                                {{ currentSelectedUnit?.name }}
                            </span>
                        </div>

                        <div class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                            *Jadwal berlaku khusus untuk unit penunjang ini secara mandiri.
                        </div>
                    </div>

                    <!-- Days Schedule Table (Responsive Horizontal Scroll Enabled) -->
                    <div class="w-full overflow-x-auto border border-transparent dark:border-slate-800 rounded-xl shadow-sm">
                        <table class="w-full min-w-[680px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50/80 dark:bg-slate-950/60 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-extrabold text-[11px] border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="p-4 whitespace-nowrap min-w-[130px]">Hari</th>
                                    <th class="p-4 whitespace-nowrap min-w-[240px]">Status Ka. Unit Standby</th>
                                    <th class="p-4 whitespace-nowrap min-w-[150px] text-center">Jam Mulai Disposisi (Buka)</th>
                                    <th class="p-4 whitespace-nowrap min-w-[150px] text-center">Jam Selesai Disposisi (Tutup)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                <tr 
                                    v-for="(h, idx) in hoursForm.hours" 
                                    :key="idx" 
                                    :class="[
                                        'transition-colors duration-150',
                                        h.day_of_week === todayIsoDay 
                                            ? 'bg-emerald-50/30 dark:bg-emerald-950/20' 
                                            : 'hover:bg-slate-50/50 dark:hover:bg-slate-950/40'
                                    ]"
                                >
                                    <td class="p-4 font-bold text-slate-800 dark:text-white text-xs whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span>{{ dayNames[idx] }}</span>
                                            <span v-if="h.day_of_week === todayIsoDay" class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-100 text-emerald-800 dark:bg-white/10 dark:text-white shrink-0">
                                                HARI INI
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        <label class="relative inline-flex items-center cursor-pointer select-none">
                                            <input type="checkbox" v-model="h.is_active" class="sr-only peer">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:after:border-slate-600 peer-checked:bg-emerald-600 dark:peer-checked:bg-white"></div>
                                            <span class="ml-2.5 text-xs font-bold text-slate-700 dark:text-slate-300">
                                                {{ h.is_active ? 'Ka. Unit Standby (Jam Normal)' : 'Off / Auto-Disposisi Full Day' }}
                                            </span>
                                        </label>
                                    </td>
                                    <td class="p-4 whitespace-nowrap text-center">
                                        <TimePickerInput
                                            v-model="h.start_time"
                                            :disabled="!h.is_active"
                                            placeholder="07:30"
                                        />
                                    </td>
                                    <td class="p-4 whitespace-nowrap text-center">
                                        <TimePickerInput
                                            v-model="h.end_time"
                                            :disabled="!h.is_active"
                                            placeholder="15:00"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Action Submit Button -->
                    <div class="flex justify-end pt-2">
                        <button
                            type="button"
                            @click="openConfirmModal"
                            :disabled="hoursForm.processing"
                            class="w-full sm:w-auto h-11 px-6 text-xs font-bold rounded-xl text-white shadow-sm flex items-center justify-center gap-2 transition duration-200 disabled:opacity-50 bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 cursor-pointer"
                        >
                            <Save class="h-4 w-4" />
                            <span>{{ hoursForm.processing ? __('Menyimpan...') : __('Simpan Jam Operasional') }}</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- Confirmation Modal Sebelum Menyimpan Jam Operasional -->
        <Teleport to="body">
            <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-spa-fade-in">
                <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
                    <!-- Header Modal Konfirmasi -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">
                            Konfirmasi Perubahan Jam Operasional
                        </h3>
                        <button type="button" @click="showConfirmModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition cursor-pointer">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Isi Rincian Perubahan -->
                    <div class="p-6 space-y-4 text-xs">
                        <div v-if="isAdmin" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-slate-500 font-medium">Unit Penunjang:</span>
                            <span class="font-extrabold text-emerald-600 dark:text-emerald-400 uppercase text-xs">
                                {{ currentSelectedUnit?.name }}
                            </span>
                        </div>

                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
                            Apakah Anda yakin ingin menyimpan ringkasan jadwal jam operasional berikut?
                        </p>

                        <!-- Ringkasan Jadwal 7 Hari (Tampil Lengkap Tanpa Scroll) -->
                        <div class="divide-y divide-slate-100 dark:divide-slate-800 border border-slate-100 dark:border-slate-800 rounded-xl overflow-hidden bg-slate-50/40 dark:bg-slate-950/40">
                            <div 
                                v-for="(h, idx) in hoursForm.hours" 
                                :key="idx"
                                class="p-2.5 flex items-center justify-between text-xs"
                            >
                                <span class="font-bold text-slate-800 dark:text-white">{{ dayNames[idx] }}</span>
                                <div v-if="h.is_active" class="flex items-center gap-1.5 font-extrabold text-emerald-700 dark:text-emerald-400 text-[11px]">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <span>Standby ({{ h.start_time }} - {{ h.end_time }})</span>
                                </div>
                                <div v-else class="flex items-center gap-1.5 font-bold text-rose-600 dark:text-rose-400 text-[11px]">
                                    <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                    <span>OFF (Auto-Disposisi)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Konfirmasi (Dengan Loading State) -->
                    <div class="px-6 py-4 bg-slate-50/60 dark:bg-slate-950/60 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="showConfirmModal = false"
                            :disabled="hoursForm.processing"
                            class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition cursor-pointer disabled:opacity-50"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="executeSaveHours"
                            :disabled="hoursForm.processing"
                            class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl shadow-sm transition cursor-pointer flex items-center gap-2 disabled:opacity-50"
                        >
                            <Loader2 v-if="hoursForm.processing" class="h-3.5 w-3.5 animate-spin" />
                            <Save v-else class="h-3.5 w-3.5" />
                            <span>{{ hoursForm.processing ? 'Menyimpan...' : 'Ya, Simpan Jam' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Info Modal Penjelasan Alur Jam Operasional -->
        <Teleport to="body">
            <div v-if="showInfoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-spa-fade-in">
                <div class="w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
                    <!-- Modal Header (Tanpa Ikon) -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">
                            Panduan Jam Operasional & Auto-Disposisi
                        </h3>
                        <button type="button" @click="showInfoModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition cursor-pointer">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4 text-xs text-slate-600 dark:text-slate-300 leading-relaxed max-h-[75vh] overflow-y-auto">
                        <div class="p-4 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/30 space-y-1.5">
                            <div class="font-extrabold text-emerald-900 dark:text-emerald-300 flex items-center gap-2">
                                <Clock class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                <span>1. Jam Kerja Normal (Disposisi Manual)</span>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-normal">
                                Pada jam kerja aktif yang diatur di tabel (misal 07:30 - 15:00), setiap laporan dari staf ruangan akan masuk ke antrean <strong>Validasi Ka. Unit</strong>. Kepala Unit bertugas meninjau laporan dan memilih teknisi secara manual.
                            </p>
                        </div>

                        <div class="p-4 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/30 space-y-1.5">
                            <div class="font-extrabold text-amber-900 dark:text-amber-300 flex items-center gap-2">
                                <Bot class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                <span>2. Di Luar Jam Kerja / Off-Hours (Auto-Disposisi)</span>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-normal">
                                Saat laporan masuk di luar jam kerja (misal jam 16:00 atau saat hari di-uncheck / OFF), sistem <strong>otomatis mendisposisikan laporan</strong> ke seluruh teknisi piket unit tersebut tanpa perlu menunggu Kepala Unit.
                            </p>
                        </div>

                        <div class="p-4 rounded-xl bg-rose-50/60 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/30 space-y-1.5">
                            <div class="font-extrabold text-rose-900 dark:text-rose-300 flex items-center gap-2">
                                <Zap class="h-4 w-4 text-rose-600 dark:text-rose-400" />
                                <span>3. Laporan Darurat (Code Red / Emergency)</span>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-normal">
                                Khusus laporan berkategori <strong>EMERGENCY</strong> (misal kebakaran, mati listrik total, kebocoran gas), sistem akan <strong>selalu melompati disposisi manual</strong> secara otomatis kapan pun laporan dikirim.
                            </p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 flex justify-end">
                        <button type="button" @click="showInfoModal = false" class="px-5 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl transition cursor-pointer">
                            Saya Mengerti
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes spa-fade-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-spa-fade-in {
  animation: spa-fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
</style>
