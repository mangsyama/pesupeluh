<script setup>
import { computed } from 'vue';
import { 
    Bell, 
    Wrench, 
    UserPlus, 
    UserCheck, 
    Clock, 
    CheckCircle2, 
    CheckCircle, 
    AlertTriangle, 
    AlertCircle, 
    FileText, 
    Activity, 
    ShieldAlert
} from '@lucide/vue';

const props = defineProps({
    notification: {
        type: Object,
        required: true
    },
    // Optional variant: 'dropdown' for top bar, 'list' for full page
    variant: {
        type: String,
        default: 'dropdown'
    }
});

// Helper to determine notification category
const category = computed(() => {
    const notif = props.notification;
    const type = notif.type || notif.data?.type || '';
    const title = (notif.title || notif.data?.title || '').toLowerCase();
    const priority = (notif.priority || notif.data?.priority || '').toLowerCase();

    if (priority === 'urgent' || title.includes('sla') || title.includes('terlambat') || title.includes('peringatan validasi') || title.includes('urgent')) {
        return 'urgent_warning';
    }
    if (type === 'user' || title.includes('pendaftaran') || title.includes('pengguna') || title.includes('user')) {
        return 'user_registration';
    }
    if (type === 'system' || title.includes('gateway') || title.includes('server') || title.includes('sistem')) {
        return 'system_alert';
    }
    if (title.includes('ditugaskan') || title.includes('tugas baru') || title.includes('disposisi')) {
        return 'ticket_assignment';
    }
    if (title.includes('selesai') || title.includes('completed') || type === 'done') {
        return 'ticket_completed';
    }
    if (title.includes('pending') || title.includes('ditangguhkan') || title.includes('paused')) {
        return 'ticket_paused';
    }
    return 'ticket_general';
});

// Stylings mapping per notification type & role relevance
const styling = computed(() => {
    switch (category.value) {
        case 'urgent_warning':
            return {
                iconComponent: AlertTriangle,
                bgClass: 'bg-red-600 text-white shadow-sm border border-red-500',
                badgeText: '🔴 URGENT',
                badgeClass: 'bg-red-600 text-white font-extrabold tracking-wider animate-pulse',
                cardBorderClass: 'border-l-4 border-l-red-600 bg-red-50/30 dark:bg-red-950/20 border-red-200 dark:border-red-900/60',
                roleTag: 'Penting & Urgent',
                roleTagClass: 'bg-red-600 text-white font-bold',
            };
        case 'user_registration':
            return {
                iconComponent: UserPlus,
                bgClass: 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200/80 dark:border-indigo-900/60',
                badgeText: 'User',
                badgeClass: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
                cardBorderClass: 'border-slate-200/60 dark:border-slate-800/80',
                roleTag: 'Admin & Verifikator',
                roleTagClass: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
            };
        case 'ticket_assignment':
            return {
                iconComponent: Wrench,
                bgClass: 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200/80 dark:border-amber-900/60',
                badgeText: 'Penugasan',
                badgeClass: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
                cardBorderClass: 'border-slate-200/60 dark:border-slate-800/80',
                roleTag: 'Teknisi',
                roleTagClass: 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            };
        case 'ticket_completed':
            return {
                iconComponent: CheckCircle2,
                bgClass: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-900/60',
                badgeText: 'Selesai',
                badgeClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
                cardBorderClass: 'border-slate-200/60 dark:border-slate-800/80',
                roleTag: 'Pelapor & Ka. Ruangan',
                roleTagClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            };
        case 'ticket_paused':
            return {
                iconComponent: AlertCircle,
                bgClass: 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200/80 dark:border-amber-900/60',
                badgeText: 'Pending',
                badgeClass: 'bg-amber-500 text-white',
                cardBorderClass: 'border-slate-200/60 dark:border-slate-800/80',
                roleTag: 'Pending Alat',
                roleTagClass: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
            };
        case 'system_alert':
            return {
                iconComponent: Activity,
                bgClass: 'bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 border border-sky-200/80 dark:border-sky-900/60',
                badgeText: 'System',
                badgeClass: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
                cardBorderClass: 'border-slate-200/60 dark:border-slate-800/80',
                roleTag: 'Sistem',
                roleTagClass: 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
            };
        default:
            return {
                iconComponent: Bell,
                bgClass: 'bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800',
                badgeText: 'Tiket',
                badgeClass: 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                cardBorderClass: 'border-slate-200/60 dark:border-slate-800/80',
                roleTag: 'Laporan',
                roleTagClass: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            };
    }
});
</script>

<template>
    <div :class="[
        'flex gap-3 transition cursor-pointer relative',
        variant === 'dropdown' ? 'px-4 py-3 border-b border-slate-100 dark:border-slate-800/60 hover:bg-slate-50/80 dark:hover:bg-slate-800/40' : 'p-4 rounded-xl shadow-2xs hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition',
        variant === 'list' ? styling.cardBorderClass : '',
        !notification.read_at ? (variant === 'dropdown' ? 'bg-emerald-50/20 dark:bg-white/5' : 'bg-emerald-50/20 dark:bg-slate-800/40') : ''
    ]">
        <!-- Icon Container -->
        <div :class="[
            'rounded-xl flex items-center justify-center shrink-0 shadow-xs',
            variant === 'dropdown' ? 'h-9 w-9 mt-0.5' : 'h-10 w-10 mt-0.5',
            styling.bgClass
        ]">
            <component :is="styling.iconComponent" :class="variant === 'dropdown' ? 'h-4.5 w-4.5' : 'h-5 w-5'" />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-1.5 min-w-0">
                    <p :class="[
                        'font-bold truncate',
                        variant === 'dropdown' ? 'text-xs' : 'text-xs sm:text-sm',
                        !notification.read_at ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300'
                    ]">
                        {{ notification.title }}
                    </p>
                    <span 
                        v-if="styling.badgeText"
                        :class="['px-1.5 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider shrink-0', styling.badgeClass]"
                    >
                        {{ styling.badgeText }}
                    </span>
                </div>

                <!-- Unread Indicator dot (Dropdown Mode) -->
                <span 
                    v-if="!notification.read_at && variant === 'dropdown'" 
                    :class="['h-2 w-2 rounded-full shrink-0 mt-1', category === 'sla_warning' ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500 dark:bg-white']"
                />
            </div>

            <!-- Message Body -->
            <p :class="[
                'text-slate-500 dark:text-slate-400 leading-relaxed mt-0.5',
                variant === 'dropdown' ? 'text-[11px] line-clamp-2' : 'text-xs'
            ]">
                {{ notification.message }}
            </p>

            <!-- Metadata Footer -->
            <div class="flex items-center justify-between gap-2 mt-1.5">
                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                    {{ notification.time || 'Baru saja' }}
                </span>

                <span 
                    v-if="variant === 'list'"
                    :class="['px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider', styling.roleTagClass]"
                >
                    {{ styling.roleTag }}
                </span>
            </div>
        </div>
    </div>
</template>
