<script setup>
import { ref, computed, onMounted, onUnmounted, getCurrentInstance } from 'vue';
import { Link } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import { 
    FileText, 
    Activity, 
    TrendingUp, 
    AlertCircle, 
    ArrowUpRight, 
    Settings, 
    Clock, 
    CheckCircle2, 
    Timer, 
    Bot, 
    UserCheck, 
    Gauge, 
    Flame, 
    BarChart3, 
    PieChart, 
    Layers, 
    Info, 
    CheckCircle, 
    CalendarRange 
} from '@lucide/vue';
import { getDisplayDescription, getDisplayReporterName } from '@/Utils/sipuasHelper';

const { proxy } = getCurrentInstance();

const props = defineProps({
    dashboardStats: {
        type: Object,
        default: () => null
    },
    userRole: {
        type: String,
        default: 'GLOBAL'
    }
});

const isMounted = ref(false);
const isDark = ref(false);

const checkTheme = () => {
    if (typeof document !== 'undefined') {
        isDark.value = document.documentElement.classList.contains('dark');
    }
};

let themeObserver = null;

onMounted(() => {
    isMounted.value = true;
    checkTheme();

    if (typeof MutationObserver !== 'undefined') {
        themeObserver = new MutationObserver(() => {
            checkTheme();
        });
        themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }
});

onUnmounted(() => {
    if (themeObserver) {
        themeObserver.disconnect();
    }
});

const isLoading = computed(() => !props.dashboardStats);
const recentTicketsData = computed(() => props.dashboardStats?.recentTickets ?? []);
const breakdownDataData = computed(() => props.dashboardStats?.breakdownData ?? []);
const analyticsData = computed(() => props.dashboardStats?.analytics ?? null);

const selectedPeriod = ref('30'); // Default 30 Hari

const periodOptions = [
    { key: '7', label: '7 Hari', sub: '1 Minggu' },
    { key: '14', label: '14 Hari', sub: '2 Minggu' },
    { key: '30', label: '1 Bulan', sub: '30 Hari' },
    { key: '60', label: 'Semua Data', sub: 'Riwayat Penuh' },
];

const activePeriodData = computed(() => {
    const periods = analyticsData.value?.periods;
    if (periods && periods[selectedPeriod.value]) {
        return periods[selectedPeriod.value];
    }
    return {
        kpi: analyticsData.value?.kpi ?? {},
        trendData: analyticsData.value?.trendData ?? [],
        statusDistribution: analyticsData.value?.statusDistribution ?? {},
    };
});

const kpi = computed(() => {
    return activePeriodData.value?.kpi ?? {
        avgResponseMinutes: 0,
        avgResolutionHours: 0,
        slaComplianceRate: 100,
        autoDisposedCount: 0,
        manualDisposedCount: 0,
        pendingValidationCount: 0,
        autoDisposedRate: 0,
        manualDisposedRate: 0,
        totalTickets: 0,
        activeTickets: 0,
        completedTickets: 0,
    };
});

const currentTrendData = computed(() => activePeriodData.value?.trendData ?? []);

const statusDistribution = computed(() => activePeriodData.value?.statusDistribution ?? {
    COMPLETED: 0,
    IN_PROGRESS: 0,
    ASSIGNED: 0,
    PENDING: 0,
    PENDING_VALIDATION: 0,
    CANCEL: 0,
});

const activePeriodLabel = computed(() => {
    const opt = periodOptions.find(p => p.key === selectedPeriod.value);
    return opt ? opt.label : `${selectedPeriod.value} Hari`;
});

const trendSummary = computed(() => {
    const list = currentTrendData.value;
    const incoming = list.reduce((acc, cur) => acc + (cur.incoming || 0), 0);
    const completed = list.reduce((acc, cur) => acc + (cur.completed || 0), 0);
    return { incoming, completed };
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const now = new Date();
    
    const isToday = date.toDateString() === now.toDateString();
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    const isYesterday = date.toDateString() === yesterday.toDateString();
    
    const timeStr = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    
    if (isToday) {
        return (proxy?.__('pages.dashboard.today') ?? 'Hari ini') + ', ' + timeStr;
    } else if (isYesterday) {
        return (proxy?.__('pages.dashboard.yesterday') ?? 'Kemarin') + ', ' + timeStr;
    } else {
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) + ', ' + timeStr;
    }
};

const formatMinutes = (minutes) => {
    const num = Number(minutes) || 0;
    if (num <= 0) return { val: '0', unit: 'Menit' };
    if (num < 60) return { val: String(Math.round(num)), unit: 'Menit' };
    if (num < 1440) return { val: (num / 60).toFixed(1), unit: 'Jam' };
    return { val: (num / 1440).toFixed(1), unit: 'Hari' };
};

const formatHours = (hours) => {
    const num = Number(hours) || 0;
    if (num <= 0) return { val: '0', unit: 'Jam' };
    if (num < 48) return { val: num.toFixed(1), unit: 'Jam' };
    return { val: (num / 24).toFixed(1), unit: 'Hari' };
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

const getTicketSlaInfo = (ticket) => {
    const isAutoDispo = Boolean(ticket.validated_at && !ticket.validated_by);
    const isManualDispo = Boolean(ticket.validated_at && ticket.validated_by);
    
    let responseMinutes = null;
    if (ticket.responded_at && (ticket.validated_at || ticket.created_at)) {
        const start = new Date(ticket.validated_at || ticket.created_at);
        const resp = new Date(ticket.responded_at);
        responseMinutes = Math.max(1, Math.round((resp - start) / 60000));
    }

    return {
        isAutoDispo,
        isManualDispo,
        responseMinutes,
    };
};

const recentReports = computed(() => {
    return (recentTicketsData.value || []).map(ticket => {
        const slaInfo = getTicketSlaInfo(ticket);
        return {
            id: ticket.ticket_number || ticket.id,
            uuid: ticket.uuid,
            date: formatDate(ticket.created_at),
            rawDate: ticket.created_at,
            author: getDisplayReporterName(ticket),
            category: ticket.category?.name ?? '-',
            room: ticket.room?.name ?? '-',
            title: getDisplayDescription(ticket),
            status: ticket.status,
            slaInfo: slaInfo,
        };
    });
});

const displayedReports = computed(() => {
    if (!selectedPeriod.value || selectedPeriod.value === '60') {
        return recentReports.value;
    }
    const days = Number(selectedPeriod.value);
    const cutoff = new Date();
    cutoff.setDate(cutoff.getDate() - (days - 1));
    cutoff.setHours(0, 0, 0, 0);

    const filtered = recentReports.value.filter(r => {
        if (!r.rawDate) return true;
        return new Date(r.rawDate) >= cutoff;
    });

    return filtered.length > 0 ? filtered : recentReports.value;
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

// ==========================================
// APEXCHARTS CONFIGURATIONS
// ==========================================

// 1. Trend Area Chart (Tiket Masuk vs Selesai)
const trendChartSeries = computed(() => [
    {
        name: 'Tiket Masuk',
        data: currentTrendData.value.map(d => d.incoming)
    },
    {
        name: 'Tiket Selesai',
        data: currentTrendData.value.map(d => d.completed)
    }
]);

const trendChartOptions = computed(() => {
    const categories = currentTrendData.value.map(d => d.label);
    const dark = isDark.value;

    return {
        chart: {
            type: 'area',
            height: 270,
            toolbar: { show: false },
            fontFamily: 'Inter, system-ui, sans-serif',
            background: 'transparent',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 600,
            }
        },
        theme: {
            mode: dark ? 'dark' : 'light',
        },
        colors: ['#0284c7', '#059669'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.05,
                stops: [0, 95, 100]
            }
        },
        stroke: {
            curve: 'smooth',
            width: 2.5
        },
        dataLabels: {
            enabled: false
        },
        grid: {
            borderColor: dark ? '#334155' : '#f1f5f9',
            strokeDashArray: 4,
            padding: { top: 0, right: 10, bottom: 0, left: 10 }
        },
        xaxis: {
            categories: categories,
            labels: {
                style: {
                    colors: dark ? '#94a3b8' : '#64748b',
                    fontSize: '11px',
                    fontWeight: 500,
                },
                rotate: 0,
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                style: {
                    colors: dark ? '#94a3b8' : '#64748b',
                    fontSize: '11px',
                },
                formatter: (val) => Math.round(val)
            },
            min: 0,
            forceNiceScale: true
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '12px',
            fontWeight: 600,
            labels: {
                colors: dark ? '#cbd5e1' : '#475569'
            },
            markers: {
                radius: 12
            }
        },
        tooltip: {
            theme: dark ? 'dark' : 'light',
            shared: true,
            intersect: false,
            y: {
                formatter: (val) => `${val} Laporan`
            }
        }
    };
});

// 2. Status Donut Chart
const donutChartSeries = computed(() => {
    const sd = statusDistribution.value;
    const series = [
        sd.COMPLETED || 0,
        (sd.IN_PROGRESS || 0) + (sd.ASSIGNED || 0),
        sd.PENDING_VALIDATION || 0,
        sd.PENDING || 0,
        sd.CANCEL || 0,
    ];
    return series.some(v => v > 0) ? series : [1];
});

const donutChartOptions = computed(() => {
    const dark = isDark.value;
    const sd = statusDistribution.value;
    const hasData = [sd.COMPLETED, sd.IN_PROGRESS, sd.ASSIGNED, sd.PENDING_VALIDATION, sd.PENDING, sd.CANCEL].some(v => (v || 0) > 0);

    return {
        chart: {
            type: 'donut',
            height: 270,
            fontFamily: 'Inter, system-ui, sans-serif',
            background: 'transparent',
        },
        theme: {
            mode: dark ? 'dark' : 'light',
        },
        labels: hasData 
            ? ['Selesai', 'Dalam Proses', 'Menunggu Validasi', 'Tertunda', 'Dibatalkan']
            : ['Belum Ada Data'],
        colors: hasData 
            ? ['#059669', '#7c3aed', '#d97706', '#ea580c', '#e11d48']
            : ['#cbd5e1'],
        stroke: {
            colors: [dark ? '#0f172a' : '#ffffff'],
            width: 2
        },
        dataLabels: {
            enabled: hasData,
            formatter: (val) => `${Math.round(val)}%`,
            style: {
                fontSize: '11px',
                fontWeight: 'bold',
            },
            dropShadow: { enabled: false }
        },
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            fontSize: '11px',
            fontWeight: 500,
            labels: {
                colors: dark ? '#cbd5e1' : '#475569'
            },
            markers: { radius: 12 }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '12px',
                            fontWeight: 600,
                            color: dark ? '#94a3b8' : '#64748b',
                            offsetY: -4
                        },
                        value: {
                            show: true,
                            fontSize: '22px',
                            fontWeight: 800,
                            color: dark ? '#f8fafc' : '#0f172a',
                            offsetY: 6,
                            formatter: (val) => hasData ? val : '-'
                        },
                        total: {
                            show: true,
                            label: 'Total Tiket',
                            fontSize: '11px',
                            fontWeight: 600,
                            color: dark ? '#94a3b8' : '#64748b',
                            formatter: () => hasData ? String(kpi.value.totalTickets ?? 0) : '0'
                        }
                    }
                }
            }
        },
        tooltip: {
            theme: dark ? 'dark' : 'light',
            y: {
                formatter: (val) => hasData ? `${val} Laporan` : '0'
            }
        }
    };
});

// 3. Disposisi Audit Bar Chart
const dispoChartSeries = computed(() => [
    {
        name: 'Jumlah Tiket',
        data: [
            kpi.value.autoDisposedCount || 0,
            kpi.value.manualDisposedCount || 0,
            kpi.value.pendingValidationCount || 0,
        ]
    }
]);

const dispoChartOptions = computed(() => {
    const dark = isDark.value;

    return {
        chart: {
            type: 'bar',
            height: 250,
            toolbar: { show: false },
            fontFamily: 'Inter, system-ui, sans-serif',
            background: 'transparent',
        },
        theme: {
            mode: dark ? 'dark' : 'light',
        },
        colors: ['#d97706', '#0284c7', '#64748b'],
        plotOptions: {
            bar: {
                distributed: true,
                borderRadius: 6,
                columnWidth: '45%',
                dataLabels: {
                    position: 'top'
                }
            }
        },
        dataLabels: {
            enabled: true,
            offsetY: -18,
            style: {
                fontSize: '11px',
                fontWeight: 'bold',
                colors: [dark ? '#f8fafc' : '#0f172a']
            },
            formatter: (val) => String(val)
        },
        grid: {
            borderColor: dark ? '#334155' : '#f1f5f9',
            strokeDashArray: 4,
        },
        xaxis: {
            categories: ['Auto-Disposisi', 'Disposisi Mandiri', 'Pending Validasi'],
            labels: {
                style: {
                    colors: dark ? '#94a3b8' : '#64748b',
                    fontSize: '11px',
                    fontWeight: 600,
                }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                style: {
                    colors: dark ? '#94a3b8' : '#64748b',
                    fontSize: '11px',
                },
                formatter: (val) => Math.round(val)
            },
            min: 0,
            forceNiceScale: true
        },
        legend: { show: false },
        tooltip: {
            theme: dark ? 'dark' : 'light',
            y: {
                formatter: (val) => `${val} Tiket Disposisi`
            }
        }
    };
});
</script>

<template>
    <div class="space-y-4">
        <!-- Welcome Card Banner -->
        <div class="p-[1px] rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-800 dark:bg-none dark:bg-slate-800 shadow-sm">
            <div class="overflow-hidden bg-gradient-to-r from-emerald-600 to-emerald-800 dark:from-slate-900 dark:to-slate-900 rounded-[15px] text-white p-6 sm:p-8 relative flex items-center justify-between gap-4 sm:gap-6">
                <!-- Text Info -->
                <div class="relative z-10 flex-1 min-w-0 pr-20 sm:pr-24">
                    <h3 class="text-2xl font-extrabold mb-1">PESU PELUH</h3>
                    <p class="text-emerald-100 dark:text-slate-300 text-sm font-medium leading-relaxed break-words">
                        Pengendalian Terintegrasi Unit Penunjang Dalam Satu Sentuhan &mdash; Dashboard Manajemen Terpadu
                    </p>
                </div>

                <!-- Right White Logo -->
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

        <!-- Executive Period Control Toolbar -->
        <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-white/10 flex items-center justify-center flex-shrink-0 shadow-2xs">
                    <CalendarRange class="h-5 w-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">
                            Filter Rentang Waktu Analisis
                        </h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                            Sinkron Seluruh Grafik & Data
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Menampilkan performa SLA, respon teknisi, audit disposisi, dan grafik operasional untuk <strong>{{ activePeriodLabel }}</strong>
                    </p>
                </div>
            </div>

            <!-- Period Pill Buttons -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800/90 rounded-xl border border-slate-200/60 dark:border-slate-700/60 self-start sm:self-auto flex-wrap">
                <button
                    v-for="period in periodOptions"
                    :key="period.key"
                    type="button"
                    @click="selectedPeriod = period.key"
                    class="px-3 py-1.5 text-xs rounded-lg font-bold transition-all duration-200 flex items-center gap-1.5 cursor-pointer select-none"
                    :class="selectedPeriod === period.key
                        ? 'bg-emerald-600 text-white shadow-sm font-black'
                        : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-700/60'"
                >
                    <Clock v-if="selectedPeriod === period.key" class="h-3.5 w-3.5" />
                    <span>{{ period.label }}</span>
                </button>
            </div>
        </div>

        <!-- Section 1: Status & Volume Operasional (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Total Laporan -->
            <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between relative overflow-hidden group">
                <div class="space-y-1.5 flex-1 min-w-0 pr-2">
                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">
                        Total Laporan ({{ activePeriodLabel }})
                    </span>
                    <div v-if="isLoading" class="h-8 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-lg animate-pulse"></div>
                    <div v-else class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ kpi.totalTickets }}
                        </span>
                        <span class="text-xs font-bold text-slate-400">Tiket</span>
                    </div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                        Akumulasi laporan masuk pada periode aktif
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center flex-shrink-0 border border-emerald-100/80 dark:border-white/10 shadow-2xs">
                    <FileText class="h-6 w-6" />
                </div>
            </div>

            <!-- Card 2: Dalam Pengerjaan -->
            <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between relative overflow-hidden group">
                <div class="space-y-1.5 flex-1 min-w-0 pr-2">
                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">
                        Dalam Pengerjaan ({{ activePeriodLabel }})
                    </span>
                    <div v-if="isLoading" class="h-8 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-lg animate-pulse"></div>
                    <div v-else class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ (statusDistribution.IN_PROGRESS || 0) + (statusDistribution.ASSIGNED || 0) }}
                        </span>
                        <span class="inline-flex items-center text-[10px] font-bold px-1.5 py-0.5 rounded-full border bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-950/40 dark:text-violet-300 dark:border-violet-800/60">
                            Sedang Proses
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                        Tiket aktif ditangani oleh tim teknisi
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-violet-50 dark:bg-white/10 text-violet-600 dark:text-white flex items-center justify-center flex-shrink-0 border border-violet-100/80 dark:border-white/10 shadow-2xs">
                    <Clock class="h-6 w-6" />
                </div>
            </div>

            <!-- Card 3: Selesai Dikerjakan -->
            <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between relative overflow-hidden group">
                <div class="space-y-1.5 flex-1 min-w-0 pr-2">
                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">
                        Selesai Dikerjakan ({{ activePeriodLabel }})
                    </span>
                    <div v-if="isLoading" class="h-8 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-lg animate-pulse"></div>
                    <div v-else class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ kpi.completedTickets }}
                        </span>
                        <span class="inline-flex items-center text-[10px] font-bold px-1.5 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60">
                            {{ kpi.totalTickets > 0 ? Math.round((kpi.completedTickets / kpi.totalTickets) * 100) : 0 }}% Tuntas
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                        Laporan berhasil diperbaiki & diselesaikan
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center flex-shrink-0 border border-emerald-100/80 dark:border-white/10 shadow-2xs">
                    <CheckCircle2 class="h-6 w-6" />
                </div>
            </div>

            <!-- Card 4: Menunggu / Tertunda -->
            <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between relative overflow-hidden group">
                <div class="space-y-1.5 flex-1 min-w-0 pr-2">
                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">
                        Menunggu / Tertunda ({{ activePeriodLabel }})
                    </span>
                    <div v-if="isLoading" class="h-8 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-lg animate-pulse"></div>
                    <div v-else class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ (statusDistribution.PENDING_VALIDATION || 0) + (statusDistribution.PENDING || 0) }}
                        </span>
                        <span :class="[
                            'inline-flex items-center text-[10px] font-bold px-1.5 py-0.5 rounded-full border',
                            (statusDistribution.PENDING_VALIDATION || 0) > 0
                                ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60'
                                : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300'
                        ]">
                            {{ (statusDistribution.PENDING_VALIDATION || 0) }} Butuh Validasi
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                        Tiket menunggu respon unit / tertunda
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-amber-50 dark:bg-white/10 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0 border border-amber-100/80 dark:border-white/10 shadow-2xs">
                    <AlertCircle class="h-6 w-6" />
                </div>
            </div>
        </div>

        <!-- Section 2: Kinerja Waktu, SLA & Disposisi (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- KPI 1: Tingkat Kepatuhan SLA -->
            <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between relative overflow-hidden group">
                <div class="space-y-1.5 flex-1 min-w-0 pr-2">
                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">
                        Kepatuhan SLA Respon
                    </span>
                    <div v-if="isLoading" class="h-8 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-lg animate-pulse"></div>
                    <div v-else class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ kpi.slaComplianceRate }}%
                        </span>
                        <span :class="[
                            'inline-flex items-center text-[10px] font-bold px-1.5 py-0.5 rounded-full border',
                            kpi.slaComplianceRate >= 85 
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60'
                                : kpi.slaComplianceRate >= 70
                                    ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60'
                                    : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60'
                        ]">
                            {{ kpi.slaComplianceRate >= 85 ? 'Optimal' : (kpi.slaComplianceRate >= 70 ? 'Waspada' : 'Kritis') }}
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                        Target respon teknisi &le; 30 menit
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center flex-shrink-0 border border-emerald-100/80 dark:border-white/10 shadow-2xs">
                    <Gauge class="h-6 w-6" />
                </div>
            </div>

            <!-- KPI 2: Rata-Rata Waktu Respon Teknisi -->
            <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between relative overflow-hidden group">
                <div class="space-y-1.5 flex-1 min-w-0 pr-2">
                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">
                        Rata-Rata Waktu Respon
                    </span>
                    <div v-if="isLoading" class="h-8 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-lg animate-pulse"></div>
                    <div v-else class="flex items-baseline gap-1.5">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ formatMinutes(kpi.avgResponseMinutes).val }}
                        </span>
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                            {{ formatMinutes(kpi.avgResponseMinutes).unit }}
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                        Kecepatan tiba di lokasi sejak tiket masuk
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-blue-50 dark:bg-white/10 text-blue-600 dark:text-white flex items-center justify-center flex-shrink-0 border border-blue-100/80 dark:border-white/10 shadow-2xs">
                    <Timer class="h-6 w-6" />
                </div>
            </div>

            <!-- KPI 3: Rata-Rata Durasi Penyelesaian -->
            <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between relative overflow-hidden group">
                <div class="space-y-1.5 flex-1 min-w-0 pr-2">
                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">
                        Durasi Penyelesaian
                    </span>
                    <div v-if="isLoading" class="h-8 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-lg animate-pulse"></div>
                    <div v-else class="flex items-baseline gap-1.5">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ formatHours(kpi.avgResolutionHours).val }}
                        </span>
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                            {{ formatHours(kpi.avgResolutionHours).unit }}
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                        Waktu pengerjaan hingga selesai (netto)
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-violet-50 dark:bg-white/10 text-violet-600 dark:text-white flex items-center justify-center flex-shrink-0 border border-violet-100/80 dark:border-white/10 shadow-2xs">
                    <Clock class="h-6 w-6" />
                </div>
            </div>

            <!-- KPI 4: Disposisi Sistem vs Mandiri Unit -->
            <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between relative overflow-hidden group">
                <div class="space-y-1.5 flex-1 min-w-0 pr-2">
                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">
                        Auto-Disposisi Sistem
                    </span>
                    <div v-if="isLoading" class="h-8 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-lg animate-pulse"></div>
                    <div v-else class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-amber-600 dark:text-amber-400">
                            {{ kpi.autoDisposedCount }}
                        </span>
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                            / {{ kpi.manualDisposedCount }} mandiri
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                        {{ kpi.autoDisposedRate }}% terlewat batas validasi 5 menit
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-amber-50 dark:bg-white/10 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0 border border-amber-100/80 dark:border-white/10 shadow-2xs">
                    <Bot class="h-6 w-6" />
                </div>
            </div>
        </div>

        <!-- Section 3: Visual Interactive Charts (Tren 2 Kolom, Donut 1 Kolom) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Chart 1 (2 Kolom): Tren Harian Tiket Masuk vs Selesai -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <TrendingUp class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                <h4 class="text-base font-extrabold text-slate-900 dark:text-white">
                                    Tren Volume & Penyelesaian Tiket
                                </h4>
                            </div>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                Perbandingan harian laju laporan baru vs efektivitas tiket yang berhasil diselesaikan
                            </p>
                        </div>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border border-slate-100 dark:border-slate-700/60 self-start sm:self-auto">
                            <Clock class="h-3.5 w-3.5 text-emerald-500" />
                            <span>{{ activePeriodLabel }}</span>
                        </span>
                    </div>

                    <!-- ApexChart Render -->
                    <div class="w-full min-h-[270px]">
                        <div v-if="isLoading || !isMounted" class="h-[270px] w-full bg-slate-100 dark:bg-slate-800/60 rounded-xl animate-pulse flex items-center justify-center text-slate-400 text-xs">
                            Memuat grafik tren...
                        </div>
                        <VueApexCharts
                            v-else
                            type="area"
                            height="270"
                            :options="trendChartOptions"
                            :series="trendChartSeries"
                        />
                    </div>
                </div>

                <!-- Footer KPI Summary -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-4 border-t border-slate-100 dark:border-slate-800 text-xs mt-2">
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[11px]">Total Masuk ({{ activePeriodLabel }})</span>
                        <span class="font-extrabold text-slate-800 dark:text-slate-200">
                            {{ trendSummary.incoming }} Laporan
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[11px]">Total Selesai ({{ activePeriodLabel }})</span>
                        <span class="font-extrabold text-emerald-600 dark:text-emerald-400">
                            {{ trendSummary.completed }} Laporan
                        </span>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <span class="text-slate-400 dark:text-slate-500 block text-[11px]">Status Aktif Berjalan</span>
                        <span class="font-extrabold text-blue-600 dark:text-blue-400">
                            {{ kpi.activeTickets }} Laporan
                        </span>
                    </div>
                </div>
            </div>

            <!-- Chart 2 (1 Kolom): Komposisi Distribusi Status Tiket -->
            <div class="lg:col-span-1 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <PieChart class="h-4 w-4 text-indigo-500 dark:text-indigo-400" />
                            <h4 class="text-base font-extrabold text-slate-900 dark:text-white">
                                Distribusi Status
                            </h4>
                        </div>
                        <span class="text-[11px] font-bold text-slate-400">
                            Proporsi Operasional
                        </span>
                    </div>

                    <!-- Donut Chart Render -->
                    <div class="w-full min-h-[270px] flex items-center justify-center">
                        <div v-if="isLoading || !isMounted" class="h-[270px] w-full bg-slate-100 dark:bg-slate-800/60 rounded-xl animate-pulse flex items-center justify-center text-slate-400 text-xs">
                            Memuat diagram...
                        </div>
                        <VueApexCharts
                            v-else
                            type="donut"
                            height="270"
                            :options="donutChartOptions"
                            :series="donutChartSeries"
                        />
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mt-2">
                    <span>Tingkat Penyelesaian:</span>
                    <span class="font-extrabold text-emerald-600 dark:text-emerald-400">
                        {{ kpi.totalTickets > 0 ? Math.round((kpi.completedTickets / kpi.totalTickets) * 100) : 0 }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Section 4: Audit Disposisi 1 Kolom + Kategori Breakdown 2 Kolom -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Chart 3 (1 Kolom): Audit Efektivitas Validasi & Auto-Disposisi -->
            <div class="lg:col-span-1 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <BarChart3 class="h-4 w-4 text-amber-500" />
                            <h4 class="text-base font-extrabold text-slate-900 dark:text-white">
                                Kinerja Disposisi
                            </h4>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200/60">
                            Audit SLA 5m
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mb-4">
                        Membandingkan disposisi mandiri pimpinan unit vs tiket yang diambil alih oleh sistem
                    </p>

                    <!-- Bar Chart Render -->
                    <div class="w-full min-h-[250px]">
                        <div v-if="isLoading || !isMounted" class="h-[250px] w-full bg-slate-100 dark:bg-slate-800/60 rounded-xl animate-pulse flex items-center justify-center text-slate-400 text-xs">
                            Memuat audit disposisi...
                        </div>
                        <VueApexCharts
                            v-else
                            type="bar"
                            height="250"
                            :options="dispoChartOptions"
                            :series="dispoChartSeries"
                        />
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 space-y-1">
                    <div class="flex justify-between items-center">
                        <span>Rasio Auto-Disposisi Sistem:</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400">{{ kpi.autoDisposedRate }}%</span>
                    </div>
                </div>
            </div>

            <!-- Kategori Pelaporan Terbanyak (2 Kolom) -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <Layers class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            <h4 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight">
                                Volume Pelaporan Berdasarkan Kategori
                            </h4>
                        </div>
                        <span class="text-xs font-bold text-slate-400 dark:text-slate-500">
                            5 Kategori Terbanyak
                        </span>
                    </div>

                    <div class="space-y-3.5 pt-1">
                        <!-- Skeleton Loading Bars -->
                        <template v-if="isLoading">
                            <div v-for="n in 4" :key="'skel-cat-mgmt-' + n" class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <div class="h-4 w-28 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                    <div class="h-4 w-14 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
                                    <div class="h-full bg-slate-200/80 dark:bg-slate-700 rounded-full animate-pulse w-3/4"></div>
                                </div>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <div v-else-if="categoriesBreakdown.length === 0" class="text-center py-8 text-xs text-slate-400 dark:text-slate-500 font-semibold">
                            {{ __('Belum ada data volume pelaporan.') }}
                        </div>

                        <!-- Real Breakdown Data -->
                        <div v-else v-for="category in categoriesBreakdown" :key="category.name" class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
                                <span class="truncate" :title="category.name">{{ category.name }}</span>
                                <span class="font-bold">{{ category.count }} Laporan ({{ category.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
                                <div 
                                    :class="['h-full rounded-full transition-all duration-500', category.color]"
                                    :style="{ width: `${category.percentage}%` }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400">
                    <span>Distribusi Kategori Layanan RS</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300">Sinkronisasi Unit Terkait</span>
                </div>
            </div>
        </div>

        <!-- Section 5: Recent Reports Table & SLA Monitoring (Full Width 3 Kolom) -->
        <div class="w-full bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight">
                                Aktivitas Laporan Terbaru & Audit Waktu
                            </h4>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                {{ activePeriodLabel }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                            Pantau tiket terkini beserta status validasi, kedatangan teknisi, dan SLA pada {{ activePeriodLabel }}
                        </p>
                    </div>
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
                                <th class="pb-3 px-4">Pelapor</th>
                                <th class="pb-3 px-4">Kategori</th>
                                <th class="pb-3 px-4">Ruangan</th>
                                <th class="pb-3 px-4">Audit Waktu / Disposisi</th>
                                <th class="pb-3 pl-4 text-right">STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-300">
                            <!-- Skeleton Loading Rows -->
                            <template v-if="isLoading">
                                <tr v-for="n in 4" :key="'skel-rec-mgmt-' + n" class="align-middle">
                                    <td class="py-3.5 pr-4 space-y-1.5">
                                        <div class="h-4 w-20 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                        <div class="h-3 w-28 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="h-4 w-20 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="h-4 w-20 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                    </td>
                                    <td class="py-3.5 pl-4 text-right">
                                        <div class="h-5 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-full animate-pulse ml-auto"></div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <tr v-else-if="displayedReports.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    {{ __('Belum ada aktivitas laporan pada periode ini.') }}
                                </td>
                            </tr>

                            <!-- Data Rows -->
                            <tr v-else v-for="report in displayedReports" :key="report.id" class="align-middle hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="py-3.5 pr-4 whitespace-nowrap">
                                    <Link 
                                        v-if="report.uuid" 
                                        :href="route('tickets.show', report.uuid)" 
                                        class="font-bold text-slate-900 dark:text-white text-xs hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors inline-block"
                                        :title="`Buka tiket #${report.id}`"
                                    >
                                        #{{ report.id }}
                                    </Link>
                                    <div v-else class="font-bold text-slate-900 dark:text-white text-xs">#{{ report.id }}</div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">{{ report.date }}</div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
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

                                <!-- Audit Waktu & Disposisi -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span v-if="report.slaInfo?.isAutoDispo" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200/70 dark:border-amber-800/60" title="Didisposisikan otomatis oleh sistem">
                                            <Bot class="h-3 w-3 text-amber-500" /> Auto-Dispo
                                        </span>
                                        <span v-else-if="report.slaInfo?.isManualDispo" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 border border-sky-200/70 dark:border-sky-800/60" title="Didisposisikan langsung oleh pimpinan unit">
                                            <UserCheck class="h-3 w-3 text-sky-500" /> Dispo Unit
                                        </span>
                                        <span v-else-if="report.status === 'PENDING_VALIDATION'" class="text-[10px] font-medium text-amber-600 dark:text-amber-400 italic">
                                            Menunggu Disposisi
                                        </span>

                                        <!-- Response Speed Badge -->
                                        <span v-if="report.slaInfo?.responseMinutes !== null" :class="[
                                            'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border',
                                            report.slaInfo.responseMinutes <= 30
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200/70 dark:border-emerald-800/60'
                                                : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border-rose-200/70 dark:border-rose-800/60'
                                        ]" :title="`Respon teknisi dalam ${report.slaInfo.responseMinutes} menit`">
                                            <Timer class="h-3 w-3" /> {{ report.slaInfo.responseMinutes }}m
                                        </span>
                                    </div>
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

            <!-- Footer Info -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <span>Menampilkan riwayat laporan periode aktif</span>
                <span class="font-bold text-slate-700 dark:text-slate-300">{{ displayedReports.length }} Tiket Ditampilkan</span>
            </div>
        </div>
    </div>
</template>
