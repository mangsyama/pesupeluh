<script setup>
import { 
    Trash2, 
    Scale, 
    Building2, 
    FileText, 
    CheckCircle2, 
    AlertTriangle, 
    PlusCircle,
    Truck,
    ShieldCheck
} from '@lucide/vue';

const props = defineProps({
    wasteData: {
        type: Object,
        default: () => ({
            tps_b3_capacity_percent: 46,
            tps_b3_current_kg: 368,
            tps_b3_max_kg: 800,
            tps_domestic_capacity_percent: 35,
            today_weights: {
                medis_infeksius_kg: 48.5,
                medis_tajam_kg: 12.0,
                patologi_kg: 6.2,
                domestik_kg: 115.0,
            },
            recent_logs: [],
            manifests: []
        })
    }
});

const emit = defineEmits(['openWasteModal']);
</script>

<template>
    <div class="space-y-4">
        <!-- Top Banner / Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- TPS B3 Capacity Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-transparent dark:border-slate-800 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider">Kapasitas TPS B3</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">
                        {{ wasteData.tps_b3_capacity_percent }}% Terisi
                    </span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">
                        {{ wasteData.tps_b3_current_kg }}
                    </span>
                    <span class="text-xs text-slate-400">/ {{ wasteData.tps_b3_max_kg }} kg Maksimal</span>
                </div>
                <!-- Capacity Progress Bar -->
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                    <div 
                        class="h-1.5 rounded-full transition-all duration-500"
                        :class="wasteData.tps_b3_capacity_percent > 80 ? 'bg-rose-500' : (wasteData.tps_b3_capacity_percent > 60 ? 'bg-amber-500' : 'bg-emerald-500')"
                        :style="{ width: `${wasteData.tps_b3_capacity_percent}%` }"
                    ></div>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 pt-0.5">
                    Jadwal pengangkutan: Setiap Kamis & Minggu
                </p>
            </div>

            <!-- Today's Total Weighed Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-transparent dark:border-slate-800 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider">Timbangan Hari Ini</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white">
                        Shift Pagi & Siang
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-amber-50/70 dark:bg-amber-500/10 p-2 rounded-xl border border-amber-200/50 dark:border-amber-500/20">
                        <p class="text-[10px] text-amber-700 dark:text-amber-300 font-semibold">Infeksius</p>
                        <p class="text-base font-extrabold text-slate-900 dark:text-white">{{ wasteData.today_weights.medis_infeksius_kg }} <span class="text-[10px]">kg</span></p>
                    </div>
                    <div class="bg-rose-50/70 dark:bg-rose-500/10 p-2 rounded-xl border border-rose-200/50 dark:border-rose-500/20">
                        <p class="text-[10px] text-rose-700 dark:text-rose-300 font-semibold">B3 Tajam</p>
                        <p class="text-base font-extrabold text-slate-900 dark:text-white">{{ wasteData.today_weights.medis_tajam_kg }} <span class="text-[10px]">kg</span></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-purple-50/70 dark:bg-purple-500/10 p-2 rounded-xl border border-purple-200/50 dark:border-purple-500/20">
                        <p class="text-[10px] text-purple-700 dark:text-purple-300 font-semibold">Patologi</p>
                        <p class="text-base font-extrabold text-slate-900 dark:text-white">{{ wasteData.today_weights.patologi_kg }} <span class="text-[10px]">kg</span></p>
                    </div>
                    <div class="bg-slate-100 dark:bg-slate-800 p-2 rounded-xl">
                        <p class="text-[10px] text-slate-600 dark:text-slate-400 font-semibold">Domestik</p>
                        <p class="text-base font-extrabold text-slate-900 dark:text-white">{{ wasteData.today_weights.domestik_kg }} <span class="text-[10px]">kg</span></p>
                    </div>
                </div>
            </div>

            <!-- Quick Action Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-transparent dark:border-slate-800 p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mb-2.5">
                        <Scale class="w-5 h-5" />
                    </div>
                    <h4 class="text-sm font-extrabold text-slate-900 dark:text-white">Input Timbangan Limbah B3</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Pencatatan berat timbangan limbah dari bangsal, IGD, lab, OK sebelum masuk penampungan TPS B3.
                    </p>
                </div>
                <button 
                    @click="emit('openWasteModal')"
                    class="mt-4 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-all"
                >
                    <PlusCircle class="w-4 h-4" />
                    <span>Form Timbangan Harian</span>
                </button>
            </div>
        </div>

        <!-- Section: Manifest Pengangkutan Limbah B3 Pihak Ketiga -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-transparent dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Truck class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Log Manifest Pengangkutan B3 (FESTRONIK KLHK)</h3>
                </div>
                <span class="text-[11px] text-slate-400">Sinkronisasi Regulasi Akreditasi Lingkungan Hidup</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] font-bold">
                        <tr>
                            <th class="py-2.5 px-3 rounded-l-xl">No. Manifest</th>
                            <th class="py-2.5 px-3">Transporter Berizin</th>
                            <th class="py-2.5 px-3">Tanggal Angkut</th>
                            <th class="py-2.5 px-3">Total Berat</th>
                            <th class="py-2.5 px-3">Driver / Armada</th>
                            <th class="py-2.5 px-3">Status Festronik</th>
                            <th class="py-2.5 px-3 rounded-r-xl">Penanggung Jawab</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="mnf in wasteData.manifests" :key="mnf.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-3 font-bold text-emerald-600 dark:text-emerald-400">{{ mnf.id }}</td>
                            <td class="py-3 px-3 font-semibold text-slate-800 dark:text-white">{{ mnf.transporter }}</td>
                            <td class="py-3 px-3 text-slate-600 dark:text-slate-300">{{ mnf.pickup_date }}</td>
                            <td class="py-3 px-3 font-extrabold text-slate-900 dark:text-white">{{ mnf.total_kg }} kg</td>
                            <td class="py-3 px-3 text-slate-600 dark:text-slate-300">{{ mnf.driver }}</td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200/50 dark:border-white/10">
                                    <ShieldCheck class="w-3 h-3" />
                                    {{ mnf.festronik_status }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-slate-600 dark:text-slate-300">{{ mnf.signed_by }}</td>
                        </tr>
                        <tr v-if="!wasteData.manifests || wasteData.manifests.length === 0">
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <Truck class="h-10 w-10 text-slate-200 dark:text-slate-700 mx-auto mb-2" />
                                <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Belum ada data manifest pengangkutan B3</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section: Riwayat Timbangan Terbaru -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-transparent dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-3">
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                <FileText class="w-4 h-4 text-slate-500" />
                <span>Log Input Timbangan Unit Terkini</span>
            </h3>

            <div class="space-y-2">
                <div 
                    v-if="!wasteData.recent_logs || wasteData.recent_logs.length === 0" 
                    class="py-10 text-center text-slate-400"
                >
                    <Trash2 class="h-10 w-10 text-slate-200 dark:text-slate-700 mx-auto mb-2" />
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Belum ada riwayat timbangan limbah</p>
                </div>
                <div 
                    v-for="log in wasteData.recent_logs" 
                    :key="log.id"
                    class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 rounded-xl bg-slate-50/60 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-xs gap-2"
                >
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                            <Scale class="w-4 h-4" />
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 dark:text-white">{{ log.unit_source }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ log.waste_type }} • {{ log.date }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-sm font-extrabold text-slate-900 dark:text-white">{{ log.weight_kg }} kg</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                            {{ log.status }}
                        </span>
                        <span class="text-[11px] text-slate-400">Petugas: {{ log.scale_officer }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

