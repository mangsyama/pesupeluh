<script setup>
import { computed, getCurrentInstance } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    FileText, 
    Activity, 
    TrendingUp, 
    AlertCircle, 
    ArrowUpRight,
    Settings,
    Clock,
    CheckCircle2,
    PlusCircle,
    Wrench,
    Stethoscope,
    ShieldCheck,
    Zap,
    ArrowRight,
    Sparkles
} from '@lucide/vue';

const { proxy } = getCurrentInstance();

const props = defineProps({
    userRole: {
        type: String,
        default: 'GLOBAL'
    },
    dashboardStats: {
        type: Object,
        default: () => null
    }
});

const isLoading = computed(() => !props.dashboardStats);
const recentTicketsData = computed(() => props.dashboardStats?.recentTickets ?? []);
const breakdownDataData = computed(() => props.dashboardStats?.breakdownData ?? []);

const isReporter = computed(() => {
    const roleInStats = props.dashboardStats?.role;
    return roleInStats === 'REPORTER' || props.userRole === 'REPORTER' || props.userRole === 'STAFF';
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const now = new Date();
    
    // Check if today
    const isToday = date.toDateString() === now.toDateString();
    
    // Check if yesterday
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    const isYesterday = date.toDateString() === yesterday.toDateString();
    
    const timeStr = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    
    if (isToday) {
        return proxy.__('pages.dashboard.today') + ', ' + timeStr;
    } else if (isYesterday) {
        return proxy.__('pages.dashboard.yesterday') + ', ' + timeStr;
    } else {
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) + ', ' + timeStr;
    }
};

const statusConfig = {
    PENDING_VALIDATION: { label: 'Menunggu',     badge: 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50' },
    ASSIGNED:           { label: 'Ditugaskan',   badge: 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400 border border-blue-200/50' },
    IN_PROGRESS:        { label: 'Dikerjakan',   badge: 'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-400 border border-violet-200/50' },
    PENDING:            { label: 'Tertunda',     badge: 'bg-orange-50 text-orange-700 dark:bg-orange-950/30 dark:text-orange-400 border border-orange-200/50' },
    COMPLETED:          { label: 'Selesai',      badge: 'bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200/50 dark:border-white/20' },
    CANCEL:             { label: 'Dibatalkan',   badge: 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200/50' },
};

const getStatus = (status) => statusConfig[status] ?? { label: status || '-', badge: 'bg-slate-100 text-slate-600 border border-slate-200' };

const stats = computed(() => {
    const ds = props.dashboardStats;

    const getStatConfig = (stat, defaultType, defaultLabel) => {
        const typeConfig = {
            'total': { icon: FileText, color: 'text-emerald-600 dark:text-white', bg: 'bg-emerald-50 dark:bg-white/10' },
            'medik': { icon: Activity, color: 'text-emerald-600 dark:text-white', bg: 'bg-emerald-50 dark:bg-white/10' },
            'non_medik': { icon: Settings, color: 'text-emerald-600 dark:text-white', bg: 'bg-emerald-50 dark:bg-white/10' },
            'progress': { icon: Clock, color: 'text-emerald-600 dark:text-white', bg: 'bg-emerald-50 dark:bg-white/10' },
            'completed': { icon: CheckCircle2, color: 'text-emerald-600 dark:text-white', bg: 'bg-emerald-50 dark:bg-white/10' },
            'pending': { icon: AlertCircle, color: 'text-emerald-600 dark:text-white', bg: 'bg-emerald-50 dark:bg-white/10' },
        };

        const currentType = stat?.type || defaultType;
        const config = typeConfig[currentType] || typeConfig['total'];

        return {
            label: stat?.label || defaultLabel,
            value: String(stat?.value ?? 0),
            icon: config.icon,
            color: config.color,
            bg: config.bg,
        };
    };

    if (!ds) {
        return [
            getStatConfig(null, 'total', 'Total Laporan'),
            getStatConfig(null, 'medik', 'Penunjang Medik'),
            getStatConfig(null, 'non_medik', 'Penunjang Non-Medik'),
            getStatConfig(null, 'pending', 'Menunggu Verifikasi'),
        ];
    }

    return [
        getStatConfig(ds.stat1, 'total', 'Total Laporan'),
        getStatConfig(ds.stat2, 'medik', 'Penunjang Medik'),
        getStatConfig(ds.stat3, 'non_medik', 'Penunjang Non-Medik'),
        getStatConfig(ds.stat4, 'pending', 'Menunggu Verifikasi'),
    ].filter(Boolean);
});

const recentReports = computed(() => {
    return (recentTicketsData.value || []).map(ticket => {
        return {
            id: ticket.ticket_number,
            date: formatDate(ticket.created_at),
            author: ticket.reporter?.name ?? '-',
            category: ticket.category?.name ?? '-',
            room: ticket.room?.name ?? '-',
            title: ticket.problem_description,
            status: ticket.status
        };
    });
});

const categoriesBreakdown = computed(() => {
    return (breakdownDataData.value || []).map(item => {
        return {
            name: item.name,
            percentage: item.percentage,
            count: item.count,
            color: item.color || 'bg-emerald-600 dark:bg-emerald-500'
        };
    });
});
</script>

<template>
    <Head :title="__('Dashboard')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">

                <!-- Welcome Card -->
                <div class="p-[1px] rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-800 dark:bg-none dark:bg-slate-800 shadow-sm">
                    <div class="overflow-hidden bg-gradient-to-r from-emerald-600 to-emerald-800 dark:from-slate-900 dark:to-slate-900 rounded-[15px] text-white p-6 sm:p-8 relative flex items-center justify-between gap-4 sm:gap-6">
                        <!-- Text Info -->
                        <div class="relative z-10 flex-1 min-w-0 pr-20 sm:pr-24">
                            <h3 class="text-2xl font-extrabold mb-1">PESU PELUH</h3>
                            <p class="text-emerald-100 dark:text-slate-300 text-sm font-medium leading-relaxed break-words">
                                Pengendalian Terintegrasi Unit Penunjang Dalam Satu Sentuhan
                            </p>
                        </div>

                        <!-- Right White Logo (Consistent mobile size across all screens) -->
                        <div class="absolute right-6 sm:right-8 top-1/2 -translate-y-1/2 z-10 flex items-center justify-center h-16 w-16 opacity-85 hover:opacity-100 transition-opacity pointer-events-none select-none">
                            <img src="/images/logo-sidebar.png" alt="PESU PELUH" class="h-full w-full object-contain brightness-0 invert" />
                        </div>

                        <!-- Decorative background patterns -->
                        <div class="absolute inset-0 opacity-10 dark:opacity-5 pointer-events-none overflow-hidden select-none">
                            <div class="absolute -right-28 -top-28 w-80 h-80 border-2 border-white rounded-[80px] rotate-[15deg]"></div>
                            <div class="absolute -right-40 -top-40 w-80 h-80 border-2 border-white rounded-[100px] rotate-[15deg]"></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Access Reporting Section -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                    Akses Cepat
                                </h3>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 shadow-2xs">
                                    <Zap class="h-3 w-3 text-amber-500 fill-amber-500" />
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Pilih unit layanan penunjang untuk akses cepat ke layanan yang dibutuhkan
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Shortcut 1: IPSRS (Fasilitas & Sarpras) -->
                        <Link 
                            :href="route('services.units.show', 'ipsrs')"
                            class="group p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/40 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 hover:border-emerald-200 dark:hover:border-emerald-800/50 transition duration-200 flex items-start gap-3.5 relative overflow-hidden"
                        >
                            <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 border border-emerald-100 dark:border-white/20 text-emerald-600 dark:text-white flex items-center justify-center flex-shrink-0">
                                <Wrench class="h-5 w-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-extrabold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition flex items-center justify-between">
                                    <span>IPSRS (Fasilitas & Sarpras)</span>
                                    <ArrowRight class="h-3.5 w-3.5 text-slate-400 group-hover:translate-x-1 transition-transform" />
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2 leading-relaxed">
                                    Form Pelaporan Kendala Sarana Prasarana dan Fasilitas RS
                                </p>
                            </div>
                        </Link>

                        <!-- Shortcut 2: Layanan Penunjang Medik -->
                        <Link 
                            :href="route('services.medik')"
                            class="group p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/40 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 hover:border-emerald-200 dark:hover:border-emerald-800/50 transition duration-200 flex items-start gap-3.5 relative overflow-hidden"
                        >
                            <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 border border-emerald-100 dark:border-white/20 text-emerald-600 dark:text-white flex items-center justify-center flex-shrink-0">
                                <Stethoscope class="h-5 w-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-extrabold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition flex items-center justify-between">
                                    <span>Layanan Penunjang Medik</span>
                                    <ArrowRight class="h-3.5 w-3.5 text-slate-400 group-hover:translate-x-1 transition-transform" />
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2 leading-relaxed">
                                    Farmasi, Radiologi, Laboratorium, dan CSSD
                                </p>
                            </div>
                        </Link>

                        <!-- Shortcut 3: Layanan Penunjang Non-Medik -->
                        <Link 
                            :href="route('services.non-medik')"
                            class="group p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/40 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 hover:border-emerald-200 dark:hover:border-emerald-800/50 transition duration-200 flex items-start gap-3.5 relative overflow-hidden"
                        >
                            <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 border border-emerald-100 dark:border-white/20 text-emerald-600 dark:text-white flex items-center justify-center flex-shrink-0">
                                <ShieldCheck class="h-5 w-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-extrabold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition flex items-center justify-between">
                                    <span>Layanan Penunjang Non-Medik</span>
                                    <ArrowRight class="h-3.5 w-3.5 text-slate-400 group-hover:translate-x-1 transition-transform" />
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2 leading-relaxed">
                                    Gizi, Laundry, Kesling, dan IPSRS
                                </p>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Dashboard Content -->
                <div class="w-full space-y-4">
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div 
                                v-for="(stat, idx) in stats" 
                                :key="stat.label || idx"
                                class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between"
                            >
                                <div class="space-y-1 flex-1 min-w-0 pr-2">
                                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">{{ stat.label }}</span>
                                    
                                    <!-- Skeleton for Value when loading -->
                                    <div v-if="isLoading" class="h-9 w-16 bg-slate-200/80 dark:bg-slate-800 rounded-lg animate-pulse"></div>
                                    <div v-else class="text-3xl font-extrabold text-slate-900 dark:text-white leading-9">{{ stat.value }}</div>
                                </div>
                                <div :class="['h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0', stat.bg]">
                                    <component :is="stat.icon" :class="['h-6 w-6', stat.color]" />
                                </div>
                            </div>
                        </div>

                        <!-- Content Rows -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            
                            <!-- Left: Recent Reports Table -->
                            <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-4">
                                <div>
                                    <div class="flex items-start justify-between gap-3 mb-4">
                                        <h4 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                                            {{ isReporter ? 'Aktivitas Laporan Saya Terbaru' : 'Aktivitas Laporan Terbaru' }}
                                        </h4>
                                        <Link 
                                            :href="route('reports.history')" 
                                            prefetch
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap shrink-0 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-white/10 dark:text-white dark:hover:bg-white/20 border border-emerald-200/60 dark:border-white/10 transition duration-150"
                                        >
                                            <span>{{ __('pages.dashboard.view_all') }}</span>
                                            <ArrowUpRight class="h-3.5 w-3.5 shrink-0" />
                                        </Link>
                                    </div>
                                    
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                                    <th class="pb-3 pr-4">ID</th>
                                                    <th v-if="!isReporter" class="pb-3 px-4">Pelapor</th>
                                                    <th class="pb-3 px-4">Kategori</th>
                                                    <th class="pb-3 px-4">Ruangan</th>
                                                    <th class="pb-3 pl-4 text-right">STATUS</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-300">
                                                <!-- Skeleton Loading Rows -->
                                                <template v-if="isLoading">
                                                    <tr v-for="n in 4" :key="'skel-rec-' + n" class="align-middle">
                                                        <td class="py-3.5 pr-4 space-y-1.5">
                                                            <div class="h-4 w-20 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                            <div class="h-3 w-28 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                        </td>
                                                        <td v-if="!isReporter" class="py-3.5 px-4">
                                                            <div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                        </td>
                                                        <td class="py-3.5 px-4">
                                                            <div class="h-4 w-20 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                        </td>
                                                        <td class="py-3.5 px-4">
                                                            <div class="h-4 w-20 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                        </td>
                                                        <td class="py-3.5 pl-4 text-right">
                                                            <div class="h-5 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-full animate-pulse ml-auto"></div>
                                                        </td>
                                                    </tr>
                                                </template>

                                                <!-- Empty State -->
                                                <tr v-else-if="recentReports.length === 0">
                                                    <td :colspan="isReporter ? 4 : 5" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                                        {{ __('Belum ada aktivitas laporan terbaru.') }}
                                                    </td>
                                                </tr>

                                                <!-- Data Rows -->
                                                <tr v-else v-for="report in recentReports" :key="report.id" class="align-middle hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                                    <td class="py-3.5 pr-4 whitespace-nowrap">
                                                        <div class="font-bold text-slate-900 dark:text-white text-xs">#{{ report.id }}</div>
                                                        <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">{{ report.date }}</div>
                                                    </td>
                                                    <td v-if="!isReporter" class="py-3.5 px-4 whitespace-nowrap">
                                                        <div class="font-semibold text-slate-800 dark:text-slate-200 text-xs">{{ report.author }}</div>
                                                    </td>
                                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                                            {{ report.category }}
                                                        </span>
                                                    </td>
                                                    <td class="py-3.5 px-4 whitespace-nowrap text-xs font-medium text-slate-700 dark:text-slate-300">
                                                        {{ report.room }}
                                                    </td>
                                                    <td class="py-3.5 pl-4 text-right whitespace-nowrap">
                                                        <span :class="['inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border', getStatus(report.status).badge]">
                                                            {{ getStatus(report.status).label }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Category Volume breakdown -->
                            <div class="lg:col-span-1 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-4">
                                <div>
                                    <h4 class="text-base font-bold text-slate-900 dark:text-white mb-4">Volume Pelaporan</h4>
                                    <div class="space-y-4">
                                        <!-- Skeleton Loading Bars -->
                                        <template v-if="isLoading">
                                            <div v-for="n in 4" :key="'skel-cat-' + n" class="space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <div class="h-4 w-28 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                    <div class="h-4 w-14 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                </div>
                                                <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                                    <div class="h-full bg-slate-200/80 dark:bg-slate-700 rounded-full animate-pulse w-3/4"></div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Empty State -->
                                        <div v-else-if="categoriesBreakdown.length === 0" class="text-center py-8 text-xs text-slate-400 dark:text-slate-500 font-semibold">
                                            {{ __('Belum ada data volume pelaporan.') }}
                                        </div>

                                        <!-- Real Breakdown Data -->
                                        <div v-else v-for="category in categoriesBreakdown" :key="category.name" class="space-y-1">
                                            <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
                                                <span class="truncate max-w-[170px]" :title="category.name">{{ category.name }}</span>
                                                <span>{{ category.count }} {{ __('pages.dashboard.reports_count') }}</span>
                                            </div>
                                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                                <div 
                                                    :class="['h-full rounded-full', category.color]"
                                                    :style="{ width: `${category.percentage}%` }"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
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
