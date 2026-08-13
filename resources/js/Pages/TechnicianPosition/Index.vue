<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { 
    Radio, 
    UserCheck, 
    UserX, 
    User,
    Clock, 
    Phone, 
    MessageSquare, 
    MapPin, 
    Wrench, 
    Search,
    Activity,
    ExternalLink,
    Zap,
    ShieldCheck,
    ClipboardList,
    CheckCircle,
    XCircle,
    Loader2
} from '@lucide/vue';

const props = defineProps({
    technicians: {
        type: Array,
        default: () => []
    },
    units: {
        type: Array,
        default: () => []
    },
    currentUser: {
        type: Object,
        default: () => ({})
    }
});

const page = usePage();
const isSubmitting = ref(false);

// Find current logged-in technician data if applicable
const myTechData = computed(() => {
    return props.technicians.find(t => t.id === props.currentUser?.id);
});

// Stats summary
const stats = computed(() => {
    const total = props.technicians.length;
    const ready = props.technicians.filter(t => t.is_on_duty && t.duty_status === 'READY').length;
    const busy = props.technicians.filter(t => t.is_on_duty && (t.duty_status === 'BUSY' || t.active_tickets_count > 0)).length;
    const off = props.technicians.filter(t => !t.is_on_duty).length;
    return { total, ready, busy, off };
});

const getStatusBadge = (dutyStatus, activeTicketsCount, isOnDuty) => {
    if (!isOnDuty) {
        return { label: 'Tidak Hadir / Off', class: 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700', iconColor: 'bg-slate-400' };
    }
    if (dutyStatus === 'BUSY' || activeTicketsCount > 0) {
        return { label: 'Sedang Penanganan', class: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800/80', iconColor: 'bg-amber-500' };
    }
    return { label: 'Siap Bertugas (Ready)', class: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-white/10 dark:text-white dark:border-white/20', iconColor: 'bg-emerald-500 animate-pulse' };
};

const formatWaNumber = (phone) => {
    if (!phone) return '';
    let cleaned = phone.replace(/[^0-9]/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.slice(1);
    }
    return cleaned;
};

const toggleDutyStatus = (isOnDuty) => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;

    router.post(route('technicians.update-status'), {
        technician_id: props.currentUser.id,
        is_on_duty: isOnDuty,
        duty_status: isOnDuty ? 'READY' : 'OFF',
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};
</script>

<template>
    <Head title="Posisi Teknisi" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                
                <!-- Header Panel (Hidden on mobile) -->
                <div class="hidden sm:flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <MapPin class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                {{ __('Posisi & Status Teknisi') }}
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                {{ __('Pantau lokasi penugasan dan status kesiapan teknisi secara otomatis.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Prominent Technician Attendance Control Banner (ONLY visible for TECHNICIANS: role_id 10) -->
                <div v-if="currentUser?.role_id === 10" class="bg-emerald-600 text-white p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="space-y-1.5 max-w-xl">
                            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-md bg-emerald-700/60 text-xs font-bold text-emerald-100">
                                <Activity class="h-3.5 w-3.5" />
                                Presensi & Status Kehadiran Saya
                            </div>
                            <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                                Halo, {{ myTechData?.name || page.props.auth?.user?.name || 'Teknisi' }}!
                            </h3>
                            <p class="text-xs sm:text-sm text-emerald-100 leading-relaxed">
                                Konfirmasikan ketersediaan dan lokasi Anda saat ini agar tim penugasan dapat mendistribusikan laporan pemeliharaan secara efektif.
                            </p>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-4 rounded-xl flex flex-col items-center sm:items-end justify-center space-y-3 shrink-0 min-w-[260px] shadow-sm">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">Status Kehadiran:</span>
                                <span :class="[
                                    'font-black px-2.5 py-0.5 rounded-md text-xs uppercase tracking-wider',
                                    myTechData?.is_on_duty ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300'
                                ]">
                                    {{ myTechData?.is_on_duty ? 'Hadir / On Duty' : 'Tidak Hadir / Off' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2.5 w-full">
                                <button
                                    @click="toggleDutyStatus(true)"
                                    :disabled="isSubmitting || myTechData?.is_on_duty"
                                    class="flex-1 h-10 px-4 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all duration-150 shadow-xs active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed whitespace-nowrap"
                                    :class="myTechData?.is_on_duty ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500' : 'bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold'"
                                >
                                    <CheckCircle class="h-4 w-4 shrink-0" />
                                    <span>SAYA HADIR</span>
                                </button>
                                <button
                                    @click="toggleDutyStatus(false)"
                                    :disabled="isSubmitting || !myTechData?.is_on_duty"
                                    class="flex-1 h-10 px-4 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all duration-150 shadow-xs active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed whitespace-nowrap"
                                    :class="!myTechData?.is_on_duty ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500' : 'bg-rose-600 hover:bg-rose-500 text-white font-extrabold'"
                                >
                                    <XCircle class="h-4 w-4 shrink-0" />
                                    <span>TIDAK HADIR</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview Grid (4 Columns) - HIDDEN FOR TECHNICIANS (role_id 10) -->
                <div v-if="currentUser?.role_id !== 10" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <!-- Total Teknisi -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Teknisi</span>
                            <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ stats.total }}</div>
                        </div>
                        <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            <Wrench class="h-6 w-6" />
                        </div>
                    </div>

                    <!-- Ready (Siap) -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Ready (Siap)</span>
                            <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ stats.ready }}</div>
                        </div>
                        <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <UserCheck class="h-6 w-6" />
                        </div>
                    </div>

                    <!-- Busy (Penanganan) -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Busy (Penanganan)</span>
                            <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-0.5">{{ stats.busy }}</div>
                        </div>
                        <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">
                            <Clock class="h-6 w-6" />
                        </div>
                    </div>

                    <!-- Off (Tidak Hadir) -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Off (Tidak Hadir)</span>
                            <div class="text-3xl font-extrabold text-slate-600 dark:text-slate-300 mt-0.5">{{ stats.off }}</div>
                        </div>
                        <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                            <UserX class="h-6 w-6" />
                        </div>
                    </div>
                </div>

                <!-- Technician Cards Grid -->
                <div v-if="technicians.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4 mb-4">
                    <div
                        v-for="tech in technicians"
                        :key="tech.id"
                        class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:border-emerald-500/50 dark:hover:border-white/30 transition-all duration-200 flex flex-col justify-between space-y-4 relative overflow-hidden group"
                    >
                        <!-- Top header bar (Centered Avatar, Name & Unit) -->
                        <div class="flex flex-col items-center text-center space-y-2">
                            <div class="relative shrink-0">
                                <div class="h-14 w-14 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center border border-slate-200/60 dark:border-slate-800">
                                    <User class="h-7 w-7" />
                                </div>
                                <!-- Signal dot -->
                                <span :class="['absolute top-0 right-0 h-4 w-4 rounded-full border-2 border-white dark:border-slate-900 shadow-sm', getStatusBadge(tech.duty_status, tech.active_tickets_count, tech.is_on_duty).iconColor]"></span>
                            </div>

                            <div class="space-y-0.5 w-full">
                                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white leading-snug group-hover:text-emerald-600 dark:group-hover:text-white transition-colors duration-200 break-words">
                                    {{ tech.name }}
                                </h3>
                                <div v-if="tech.supporting_unit?.name" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-extrabold uppercase tracking-wide">
                                    {{ tech.supporting_unit.name }}
                                </div>
                            </div>
                        </div>

                        <!-- Info items (Status, Lokasi Terakhir, Tugas Aktif, & Total Tugas) -->
                        <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-transparent dark:border-slate-800/80 rounded-xl p-3.5 space-y-2.5 text-xs">
                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300 gap-2">
                                <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 font-medium shrink-0">
                                    <ShieldCheck class="h-3.5 w-3.5 text-emerald-500 shrink-0" />
                                    Status Kesiapan:
                                </span>
                                <span :class="[
                                    'font-bold px-2 py-0.5 rounded-md text-[11px] uppercase tracking-wide text-right whitespace-normal leading-tight',
                                    getStatusBadge(tech.duty_status, tech.active_tickets_count, tech.is_on_duty).class
                                ]">
                                    {{ getStatusBadge(tech.duty_status, tech.active_tickets_count, tech.is_on_duty).label }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 font-medium">
                                    <MapPin class="h-3.5 w-3.5 text-emerald-500" />
                                    Lokasi Terakhir:
                                </span>
                                <span class="font-semibold truncate max-w-[160px] text-slate-800 dark:text-white" :title="tech.current_location">
                                    {{ tech.current_location }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 font-medium">
                                    <Zap class="h-3.5 w-3.5 text-amber-500" />
                                    Beban Tugas Aktif:
                                </span>
                                <span class="font-bold px-2 py-0.5 rounded-md bg-amber-100/70 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 text-[11px]">
                                    {{ tech.active_tickets_count || 0 }} Laporan
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 font-medium">
                                    <ClipboardList class="h-3.5 w-3.5 text-blue-500" />
                                    Total Penugasan:
                                </span>
                                <span class="font-bold px-2 py-0.5 rounded-md bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px]">
                                    {{ tech.total_tickets_count || 0 }} Laporan
                                </span>
                            </div>
                        </div>

                        <!-- Direct Actions -->
                        <div class="flex items-center gap-2 pt-1">
                            <template v-if="tech.phone_number">
                                <a
                                    :href="'https://wa.me/' + formatWaNumber(tech.phone_number) + '?text=Halo%20Pak%20' + encodeURIComponent(tech.name) + ',%20perlu%20konfirmasi%20mengenai%20penugasan%20pemeliharaan.'"
                                    target="_blank"
                                    class="flex-1 h-10 rounded-xl bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 text-white text-xs font-bold flex items-center justify-center gap-1.5 transition-colors duration-150 shadow-sm"
                                >
                                    <MessageSquare class="h-3.5 w-3.5" />
                                    WA Langsung
                                </a>

                                <a
                                    :href="'tel:' + tech.phone_number"
                                    title="Hubungi No HP"
                                    class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center shrink-0 transition-colors duration-150"
                                >
                                    <Phone class="h-4 w-4" />
                                </a>
                            </template>
                            <template v-else>
                                <button
                                    disabled
                                    title="Nomor telepon belum diisi di profil"
                                    class="flex-1 h-10 rounded-xl bg-slate-100 dark:bg-slate-800/50 text-slate-400 dark:text-slate-600 text-xs font-medium flex items-center justify-center gap-1.5 cursor-not-allowed"
                                >
                                    <MessageSquare class="h-3.5 w-3.5" />
                                    No HP Belum Diisi
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-12 text-center text-slate-400 dark:text-slate-500">
                    <UserX class="h-12 w-12 mx-auto text-slate-300 dark:text-slate-700 mb-3" />
                    <p class="text-sm font-bold">Belum ada data teknisi yang terdaftar.</p>
                </div>

            </div>
        </div>
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
