<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Sun, Moon, Languages, LayoutDashboard, FileText, User, X, ChevronRight, ChevronLeft, ChevronDown, Settings, LogOut, Activity, Users, FileBarChart2, History, Shield, ShieldAlert, ArrowLeft, Database, Search, Building2, Layers, MapPin, Hospital, Palette, Play, Type, Bell, Clock, CheckCircle2, AlertTriangle, AlertCircle, HelpCircle } from '@lucide/vue';

const sidebarOpen = ref(false);
const isDark = ref(false);
const sidebarCollapsed = ref(false);
const sidebarNav = ref(null);

const saveSidebarScroll = () => {
    if (sidebarNav.value && !sidebarCollapsed.value) {
        sessionStorage.setItem('sidebar-scroll', sidebarNav.value.scrollTop);
    }
};

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const closeSidebar = () => {
    sidebarOpen.value = false;
};

const handleDemoToast = (event) => {
    if (event.detail) {
        showNotificationToast(event.detail);
    }
};

const customAlert = ref({
    show: false,
    title: '',
    text: '',
    icon: 'success', // success, error, warning, question
    confirmText: 'OK',
    cancelText: '',
    onConfirm: null,
    onCancel: null
});

const handleCustomAlert = (event) => {
    if (event.detail && event.detail.options) {
        const opts = event.detail.options;
        customAlert.value = {
            show: true,
            title: opts.title || '',
            text: opts.text || '',
            icon: opts.icon || 'success',
            confirmText: opts.confirmText || 'OK',
            cancelText: opts.cancelText || '',
            onConfirm: () => {
                customAlert.value.show = false;
                if (event.detail.callback) {
                    event.detail.callback({ isConfirmed: true });
                }
            },
            onCancel: () => {
                customAlert.value.show = false;
                if (event.detail.callback) {
                    event.detail.callback({ isConfirmed: false, isDismissed: true });
                }
            }
        };
    }
};

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
    window.addEventListener('theme-changed', () => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    sidebarCollapsed.value = localStorage.getItem('sidebar-collapsed') === 'true';

    // Restore scroll position synchronously only if expanded to avoid layout bugs when collapsed
    if (!sidebarCollapsed.value) {
        const savedScroll = sessionStorage.getItem('sidebar-scroll');
        if (savedScroll && sidebarNav.value) {
            sidebarNav.value.scrollTop = parseInt(savedScroll, 10);
        }
    }

    registerNotificationListeners();
    window.addEventListener('show-demo-toast', handleDemoToast);
    window.addEventListener('trigger-custom-alert', handleCustomAlert);
});

onUnmounted(() => {
    window.removeEventListener('show-demo-toast', handleDemoToast);
    window.removeEventListener('trigger-custom-alert', handleCustomAlert);
});

const toggleSidebarCollapse = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem('sidebar-collapsed', sidebarCollapsed.value ? 'true' : 'false');
    
    if (sidebarNav.value) {
        if (sidebarCollapsed.value) {
            // Reset scroll to 0 when collapsed to keep icons fully visible
            sidebarNav.value.scrollTop = 0;
        } else {
            // Restore scroll when expanded
            const savedScroll = sessionStorage.getItem('sidebar-scroll');
            if (savedScroll) {
                sidebarNav.value.scrollTop = parseInt(savedScroll, 10);
            }
        }
    }
};

const getInitialOpenMenus = () => {
    const defaults = {
        'menu.user_management': route().current('users.approvals') ||
            route().current('users.admin') ||
            route().current('users.management') ||
            route().current('users.unit-head') ||
            route().current('users.technician') ||
            route().current('users.room-head') ||
            route().current('users.reporter'),
        'menu.service_management': route().current('service-management.rooms') ||
            route().current('service-management.categories') ||
            route().current('service-management.supporting-units'),
        'menu.design_components': route().current('design-system.index') ||
            route().current('design-system.buttons-badges') ||
            route().current('design-system.forms') ||
            route().current('design-system.modals-alerts') ||
            route().current('design-system.tables') ||
            route().current('design-system.cards')
    };

    if (typeof window !== 'undefined') {
        try {
            const saved = localStorage.getItem('open-menus');
            if (saved) {
                const parsed = JSON.parse(saved);
                const merged = {};
                for (const key in defaults) {
                    merged[key] = defaults[key] || parsed[key] || false;
                }
                return merged;
            }
        } catch (e) {
            console.error('Error parsing open-menus from localStorage:', e);
        }
    }
    return defaults;
};

const openMenus = ref(getInitialOpenMenus());

const toggleMenu = (label) => {
    if (openMenus.value[label] !== undefined) {
        openMenus.value[label] = !openMenus.value[label];
    } else {
        openMenus.value[label] = true;
    }
    if (typeof window !== 'undefined') {
        localStorage.setItem('open-menus', JSON.stringify(openMenus.value));
    }
};

const isChildActive = (children) => {
    return children.some(child => route().current(child.routeName));
};

const isRouteActive = (item) => {
    if (route().current(item.routeName)) {
        return true;
    }
    if (item.routeName === 'settings.index' && route().current('profile.edit')) {
        return true;
    }
    return false;
};

const menuGroups = [
    {
        title: 'menu.main_menu',
        items: [
            { label: 'menu.dashboard', routeName: 'dashboard', icon: LayoutDashboard }
        ]
    },
    {
        title: 'menu.services_reports',
        items: [
            { label: 'menu.supporting_services', routeName: 'services.index', icon: Activity },
            { label: 'menu.reporting_history', routeName: 'reports.history', icon: History },
            { label: 'menu.reports_export', routeName: 'reports.index', icon: FileBarChart2 }
        ]
    },
    {
        title: 'menu.master_data',
        items: [
            { 
                label: 'menu.service_management', 
                icon: Database,
                children: [
                    { label: 'menu.room_management', routeName: 'service-management.rooms', icon: MapPin },
                    { label: 'menu.damage_categories', routeName: 'service-management.categories', icon: Layers },
                    { label: 'menu.supporting_units', routeName: 'service-management.supporting-units', icon: Building2 }
                ]
            },
            { 
                label: 'menu.user_management', 
                icon: Users,
                children: [
                    { label: 'menu.approval', routeName: 'users.approvals', icon: ShieldAlert },
                    { label: 'menu.super_admin', routeName: 'users.admin', icon: Shield },
                    { label: 'menu.management', routeName: 'users.management', icon: Users },
                    { label: 'menu.unit_head', routeName: 'users.unit-head', icon: User },
                    { label: 'menu.technician', routeName: 'users.technician', icon: User },
                    { label: 'menu.room_head', routeName: 'users.room-head', icon: User },
                    { label: 'menu.reporter', routeName: 'users.reporter', icon: User }
                ]
            }
        ]
    },
    {
        title: 'menu.system',
        items: [
            { label: 'menu.settings', routeName: 'settings.index', icon: Settings }
        ]
    },
    {
        title: 'menu.design_system_group',
        items: [
            { 
                label: 'menu.design_components', 
                icon: Palette,
                children: [
                    { label: 'menu.ds_overview', routeName: 'design-system.index', icon: LayoutDashboard },
                    { label: 'menu.ds_buttons', routeName: 'design-system.buttons-badges', icon: Play },
                    { label: 'menu.ds_forms', routeName: 'design-system.forms', icon: Type },
                    { label: 'menu.ds_modals', routeName: 'design-system.modals-alerts', icon: FileText },
                    { label: 'menu.ds_tables', routeName: 'design-system.tables', icon: Database },
                    { label: 'menu.ds_cards', routeName: 'design-system.cards', icon: Layers }
                ]
            }
        ]
    }
];

const triggerSupportBack = () => {
    window.dispatchEvent(new CustomEvent('services-back-clicked'));
};

const page = usePage();
const backRoute = computed(() => {
    if (route().current('profile.edit')) {
        return route('settings.index');
    }
    if (route().current('tickets.show')) {
        return route('reports.history');
    }
    if (route().current('services.units.show') && page.props.unit) {
        const isMedik = page.props.unit.division?.name?.toLowerCase().includes('medik') && 
                       !page.props.unit.division?.name?.toLowerCase().includes('non-medik');
        return isMedik ? route('services.medik') : route('services.non-medik');
    }
    return route('services.index');
});

const globalSearchQuery = ref('');
const showSearchResults = ref(false);

// Notification state
const showMobileNotifications = ref(false);
const showDesktopNotifications = ref(false);
const showMobileProfileDropdown = ref(false);
const pendingApprovalsCount = ref(page.props.auth?.pending_approvals_count ?? 0);
const notifications = ref(page.props.notifications ?? []);

watch(
    () => page.props.notifications,
    (value) => {
        notifications.value = value ?? [];
    }
);

watch(
    () => page.props.auth?.pending_approvals_count,
    (value) => {
        pendingApprovalsCount.value = value ?? 0;
    }
);

const unreadCount = computed(() => notifications.value.filter(notification => !notification.read_at).length);

const normalizeNotificationPayload = (notification) => {
    const title = notification.title ?? notification.data?.title ?? null;
    const message = notification.message ?? notification.data?.message ?? null;
    const route = notification.route ?? notification.data?.route ?? null;
    const type = notification.type ?? notification.data?.type ?? 'user';

    return {
        id: notification.id ?? `notif-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
        type,
        title,
        message,
        route,
        user_id: notification.user_id ?? notification.data?.user_id ?? null,
        read_at: notification.read_at ?? null,
        created_at: notification.created_at ?? new Date().toISOString(),
        time: notification.created_at ? new Date(notification.created_at).toLocaleString() : null,
    };
};

const toasts = ref([]);

const removeToast = (id) => {
    toasts.value = toasts.value.filter(t => t.id !== id);
};

const showNotificationToast = (normalized) => {
    const id = Date.now() + Math.random();
    toasts.value.push({
        id,
        title: normalized.title,
        message: normalized.message,
        type: normalized.type,
        route: normalized.route
    });

    // Auto-remove after 6 seconds
    setTimeout(() => {
        removeToast(id);
    }, 6000);
};

const registerNotificationListeners = () => {
    if (typeof window !== 'undefined' && window.Echo && page.props.auth?.user?.id) {
        const channelName = `App.Models.User.${page.props.auth.user.id}`;
        console.debug('[Echo] subscribing to private channel', channelName);

        window.Echo.private(channelName)
            .notification((notification) => {
                console.debug('[Echo] notification received', notification);
                const normalized = normalizeNotificationPayload(notification);
                notifications.value.unshift(normalized);

                // Show real-time visual toast
                showNotificationToast(normalized);

                if (normalized.type === 'user' || normalized.route === route('users.approvals')) {
                    pendingApprovalsCount.value += 1;
                }
            });
    }
};

const markAsRead = (notif) => {
    if (!notif.id) return;

    // Mark locally first for instant UI feedback
    const idx = notifications.value.findIndex(n => n.id === notif.id);
    if (idx !== -1) {
        notifications.value[idx].read_at = new Date().toISOString();
    }

    // Send to server
    router.post(route('notifications.markAsRead', { id: notif.id }), {}, {
        preserveScroll: true,
        preserveState: true,
    });

    // Navigate if route exists
    if (notif.route) {
        showDesktopNotifications.value = false;
        showMobileNotifications.value = false;
        router.visit(notif.route);
    }
};

const markAllAsRead = () => {
    // Mark all locally first
    notifications.value.forEach(n => {
        if (!n.read_at) {
            n.read_at = new Date().toISOString();
        }
    });

    // Send to server
    router.post(route('notifications.markAllAsRead'), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const goToMobileNotifications = () => {
    showMobileNotifications.value = true;
    showMobileProfileDropdown.value = false;
};

const goToDesktopNotifications = () => {
    showDesktopNotifications.value = !showDesktopNotifications.value;
    showMobileNotifications.value = false;
};

const closeMobileProfileDropdown = () => {
    showMobileProfileDropdown.value = false;
    showMobileNotifications.value = false;
};

const onMobileProfileOpenChange = (value) => {
    showMobileProfileDropdown.value = value;
    if (value) {
        showMobileNotifications.value = false;
    }
};

const searchableItems = [
    { label: 'Dashboard', routeName: 'dashboard', description: 'Ringkasan data & status utama' },
    { label: 'Layanan Penunjang', routeName: 'services.index', description: 'Pelaporan Medik & Non-Medik' },
    { label: 'Laporan & Export', routeName: 'reports.index', description: 'Unduh laporan PDF & CSV' },
    { label: 'Riwayat Pelaporan', routeName: 'reports.history', description: 'Daftar riwayat tiket' },
    { label: 'Manajemen Ruangan', routeName: 'service-management.rooms', description: 'Pengelolaan data master ruangan dan lokasi' },
    { label: 'Kategori Kerusakan', routeName: 'service-management.categories', description: 'Pengelolaan data master kategori kerusakan aset' },
    { label: 'Layanan Penunjang (Managemen Layanan)', routeName: 'service-management.supporting-units', description: 'Pengelolaan data divisi dan unit penunjang' },
    { label: 'Persetujuan Registrasi', routeName: 'users.approvals', description: 'Persetujuan pendaftar pengguna baru' },
    { label: 'Super Admin', routeName: 'users.admin', description: 'Manajemen pengguna administrator' },
    { label: 'Manajemen', routeName: 'users.management', description: 'Manajemen pengguna direksi & manajemen' },
    { label: 'Kepala Unit', routeName: 'users.unit-head', description: 'Manajemen pengguna kepala unit' },
    { label: 'Teknisi Lapangan', routeName: 'users.technician', description: 'Manajemen pengguna teknisi lapangan' },
    { label: 'Kepala Ruangan', routeName: 'users.room-head', description: 'Manajemen pengguna kepala ruangan' },
    { label: 'Staf / Pelapor', routeName: 'users.reporter', description: 'Manajemen pengguna staf umum / pelapor' },
    { label: 'Pengaturan Profil', routeName: 'settings.index', description: 'Ubah sandi, tema, dan profil' },
    { label: 'Sistem Desain - Ringkasan', routeName: 'design-system.index', description: 'Ringkasan panduan warna, tema dark mode, & tipografi' },
    { label: 'Sistem Desain - Tombol & Badge', routeName: 'design-system.buttons-badges', description: 'Koleksi komponen tombol, animasi loading, & badge status' },
    { label: 'Sistem Desain - Formulir & Input', routeName: 'design-system.forms', description: 'Koleksi komponen input form, select, checkbox, & upload file' },
    { label: 'Sistem Desain - Modal & Alert', routeName: 'design-system.modals-alerts', description: 'Koleksi modal popup transisi & notifikasi SweetAlert2' },
    { label: 'Sistem Desain - Tabel & Pagination', routeName: 'design-system.tables', description: 'Desain layout tabel data, pagination, & state data kosong' },
    { label: 'Sistem Desain - Kartu Statistik', routeName: 'design-system.cards', description: 'Koleksi layout kartu data statistik & visualisasi grid' },
];

const mobilePageTitles = [
    { routeName: 'dashboard', label: 'Dashboard' },
    { routeName: 'services.index', label: 'Layanan Penunjang' },
    { routeName: 'services.medik', label: 'Layanan Penunjang Medik' },
    { routeName: 'services.non-medik', label: 'Layanan Penunjang Non-Medik' },
    { routeName: 'services.units.show', label: 'Unit Penunjang' },
    { routeName: 'reports.index', label: 'Laporan & Export' },
    { routeName: 'reports.history', label: 'Riwayat Pelaporan' },
    { routeName: 'tickets.show', label: 'Detail Tiket' },
    { routeName: 'service-management.rooms', label: 'Manajemen Ruangan' },
    { routeName: 'service-management.categories', label: 'Kategori Kerusakan' },
    { routeName: 'service-management.supporting-units', label: 'Layanan Penunjang' },
    { routeName: 'users.approvals', label: 'Persetujuan Registrasi' },
    { routeName: 'users.admin', label: 'Super Admin' },
    { routeName: 'users.management', label: 'Manajemen' },
    { routeName: 'users.unit-head', label: 'Kepala Unit' },
    { routeName: 'users.technician', label: 'Teknisi Lapangan' },
    { routeName: 'users.room-head', label: 'Kepala Ruangan' },
    { routeName: 'users.reporter', label: 'Staf / Pelapor' },
    { routeName: 'profile.edit', label: 'Profil Saya' },
    { routeName: 'settings.index', label: 'Pengaturan Profil' },
    { routeName: 'design-system.index', label: 'Sistem Desain - Ringkasan' },
    { routeName: 'design-system.buttons-badges', label: 'Sistem Desain - Tombol & Badge' },
    { routeName: 'design-system.forms', label: 'Sistem Desain - Formulir & Input' },
    { routeName: 'design-system.modals-alerts', label: 'Sistem Desain - Modal & Alert' },
    { routeName: 'design-system.tables', label: 'Sistem Desain - Tabel & Pagination' },
    { routeName: 'design-system.cards', label: 'Sistem Desain - Kartu Statistik' },
];

const currentPageTitle = computed(() => {
    const match = mobilePageTitles.find(item => route().current(item.routeName));
    return match ? match.label : '';
});

const filteredSearchItems = computed(() => {
    if (!globalSearchQuery.value.trim()) return [];
    const query = globalSearchQuery.value.toLowerCase();
    return searchableItems.filter(item => 
        item.label.toLowerCase().includes(query) || 
        item.description.toLowerCase().includes(query)
    );
});

const getGroupInitials = (title) => {
    if (title === 'Menu Utama') return 'MU';
    if (title === 'Layanan & Laporan') return 'LL';
    if (title === 'Master Data') return 'MD';
    if (title === 'Sistem') return 'S';
    return title.substring(0, 2).toUpperCase();
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-slate-950">

        <!-- Navbar -->
        <nav class="relative sticky top-0 z-40 w-full h-20 mobile-nav-gradient shadow-none border-none -mb-4 transition-all duration-300 ease-in-out">
            <div :class="['w-full px-4 lg:pr-4 transition-all duration-300 ease-in-out', sidebarCollapsed ? 'lg:pl-[96px]' : 'lg:pl-[304px]']">
                <div class="flex h-20 items-center justify-between gap-3">

                    <!-- Left: Back Button / Hamburger and Global Search -->
                    <div class="flex items-center flex-1">
                        <!-- Tombol Back (Desktop - Hanya tampil di sub-halaman layanan penunjang atau profile) -->
                        <Link
                            v-if="route().current('services.medik') || route().current('services.non-medik') || route().current('profile.edit') || route().current('services.units.show') || route().current('tickets.show')"
                            :href="backRoute"
                            @click="route().current('profile.edit') || route().current('services.units.show') || route().current('tickets.show') ? null : triggerSupportBack"
                            class="hidden lg:inline-flex items-center justify-center h-11 w-11 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100/80 dark:hover:bg-slate-800/80 transition duration-150 focus:outline-none shadow-sm border border-white dark:border-slate-800 mr-3 flex-shrink-0"
                            title="Kembali"
                        >
                            <ArrowLeft class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                        </Link>

                        <!-- Mobile Controls (Hamburger & Back Button) -->
                        <div class="lg:hidden flex items-center gap-3 mr-4 flex-shrink-0">
                            <!-- Tombol Back jika di dalam sub-halaman layanan penunjang atau profile (Mobile) -->
                            <Link
                                v-if="route().current('services.medik') || route().current('services.non-medik') || route().current('profile.edit') || route().current('services.units.show') || route().current('tickets.show')"
                                :href="backRoute"
                                @click="route().current('profile.edit') || route().current('services.units.show') || route().current('tickets.show') ? null : triggerSupportBack"
                                class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-md border border-white dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 transition duration-150 focus:outline-none"
                                aria-label="Kembali"
                            >
                                <ArrowLeft class="h-6 w-6 text-slate-700 dark:text-slate-200" />
                            </Link>
                            <!-- Hamburger — jika di halaman biasa (Mobile) -->
                            <button
                                v-else
                                @click="toggleSidebar"
                                type="button"
                                id="sidebar-toggle-btn"
                                class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-md border border-white dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 transition duration-150 focus:outline-none"
                                :aria-label="sidebarOpen ? 'Tutup Sidebar' : 'Buka Sidebar'"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            <div class="min-w-0">
                                <span v-if="currentPageTitle" class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    {{ currentPageTitle }}
                                </span>
                            </div>
                        </div>

                        <!-- Global Search (Desktop Only) -->
                        <div class="hidden lg:block relative flex-1">
                            <div class="relative">
                                <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-slate-500" />
                                <input
                                    v-model="globalSearchQuery"
                                    type="text"
                                    @focus="showSearchResults = true"
                                    @blur="setTimeout(() => { showSearchResults = false }, 200)"
                                    :placeholder="__('menu.search_placeholder')"
                                    class="w-full h-11 pl-10 pr-10 border border-white dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150 placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm"
                                />
                                <!-- Clear Button -->
                                <button
                                    v-if="globalSearchQuery"
                                    @click="globalSearchQuery = ''"
                                    type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 rounded-lg"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                             </div>
 
                             <!-- Floating Results Panel -->
                             <div v-if="showSearchResults && filteredSearchItems.length > 0" class="absolute left-0 right-0 mt-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-lg z-50 overflow-hidden py-1.5 max-h-72 overflow-y-auto">
                                 <div class="px-3.5 py-1 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800 mb-1">
                                     {{ __('menu.search_results') }}
                                 </div>
                                 <Link 
                                     v-for="item in filteredSearchItems" 
                                     :key="item.routeName"
                                     :href="route(item.routeName)"
                                     class="flex flex-col px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition duration-150"
                                 >
                                     <span class="text-xs font-bold text-slate-900 dark:text-white">{{ __(item.label) }}</span>
                                     <span class="text-[10px] text-slate-400 dark:text-slate-500 leading-normal">{{ item.description }}</span>
                                 </Link>
                             </div>
                             <div v-else-if="showSearchResults && globalSearchQuery.trim() !== ''" class="absolute left-0 right-0 mt-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-lg z-50 p-4 text-center text-xs text-slate-400 dark:text-slate-500">
                                 {{ __('menu.search_empty') }}
                             </div>
                        </div>
                    </div>

                    <!-- Kanan: User Dropdown & Mode Toggles -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <!-- Desktop Toggles (Tema, Notifikasi & Bahasa) -->
                        <div class="hidden lg:flex items-center gap-3">
                            <!-- Switch Bahasa (Desktop) -->
                            <Link
                                :href="route('lang.switch', $page.props.locale === 'id' ? 'en' : 'id')"
                                class="inline-flex items-center justify-center h-11 px-4 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100/80 dark:hover:bg-slate-800/80 transition duration-150 focus:outline-none gap-2 text-sm font-semibold select-none shadow-sm border border-white dark:border-slate-800"
                                :title="__('Switch Language')"
                            >
                                <Languages class="h-4.5 w-4.5 text-slate-400 dark:text-slate-500" />
                                <span class="uppercase text-[11px] font-extrabold px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-100/30 dark:border-emerald-900/40">
                                    {{ $page.props.locale === 'id' ? 'ID' : 'EN' }}
                                </span>
                            </Link>

                            <!-- Switch Tema (Desktop) -->
                            <button
                                @click="toggleTheme"
                                type="button"
                                class="inline-flex items-center justify-center h-11 w-11 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100/80 dark:hover:bg-slate-800/80 transition duration-150 focus:outline-none shadow-sm border border-white dark:border-slate-800"
                                :title="__('Switch Theme')"
                            >
                                <Sun v-if="!isDark" class="h-5 w-5 text-amber-500" />
                                <Moon v-else class="h-5 w-5 text-emerald-400" />
                            </button>

                            <!-- Notifikasi (Desktop) -->
                            <Dropdown align="right" width="96" :open="showDesktopNotifications" @update:open="showDesktopNotifications = $event">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="relative inline-flex items-center justify-center h-11 w-11 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100/80 dark:hover:bg-slate-800/80 transition duration-150 focus:outline-none shadow-sm border border-white dark:border-slate-800"
                                        title="Notifikasi"
                                    >
                                        <Bell class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                        <span 
                                            v-if="unreadCount > 0 && !showDesktopNotifications"
                                            class="absolute -top-1 -right-1 h-5 min-w-5 px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold ring-2 ring-slate-50 dark:ring-slate-950"
                                        >
                                            {{ unreadCount }}
                                        </span>
                                    </button>
                                </template>
                                <template #content>
                                     <!-- Header -->
                                     <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                                         <div class="flex items-center gap-2">
                                             <span class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('Notifications') }}</span>
                                             <span 
                                                 v-if="unreadCount > 0" 
                                                 class="h-5 min-w-5 px-1.5 flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold"
                                             >{{ unreadCount }}</span>
                                         </div>
                                     </div>

                                     <!-- Notification List -->
                                     <div class="max-h-80 overflow-y-auto">
                                         <div v-if="notifications.length === 0" class="py-12 text-center text-xs text-slate-400 dark:text-slate-500 font-medium">
                                             {{ __('No notifications') }}
                                         </div>
                                         <div 
                                             v-else
                                             v-for="notif in notifications" 
                                             :key="notif.id"
                                             @click="markAsRead(notif)"
                                             :class="[
                                                 'flex gap-3 px-4 py-3 border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition cursor-pointer',
                                                 !notif.read_at ? 'bg-emerald-50/30 dark:bg-emerald-950/10' : ''
                                             ]"
                                         >
                                             <!-- Icon -->
                                             <div :class="[
                                                 'h-9 w-9 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5',
                                                 notif.type === 'ticket' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500' :
                                                 notif.type === 'progress' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-500' :
                                                 notif.type === 'done' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500' :
                                                 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500'
                                             ]">
                                                 <Bell v-if="notif.type === 'ticket'" class="h-4 w-4" />
                                                 <Clock v-else-if="notif.type === 'progress'" class="h-4 w-4" />
                                                 <CheckCircle2 v-else-if="notif.type === 'done'" class="h-4 w-4" />
                                                 <User v-else class="h-4 w-4" />
                                             </div>
                                             <!-- Content -->
                                             <div class="flex-1 min-w-0">
                                                 <div class="flex items-start justify-between gap-2">
                                                     <p :class="['text-xs font-semibold truncate', !notif.read_at ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300']">{{ notif.title }}</p>
                                                     <span v-if="!notif.read_at" class="h-2 w-2 rounded-full bg-emerald-500 flex-shrink-0 mt-1"></span>
                                                 </div>
                                                 <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed mt-0.5 line-clamp-2">{{ notif.message }}</p>
                                                 <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-medium">{{ notif.time }}</p>
                                             </div>
                                         </div>
                                     </div>

                                     <!-- Footer -->
                                     <div v-if="unreadCount > 0" class="px-4 py-2.5 border-t border-slate-100 dark:border-slate-800 text-center">
                                         <button @click="markAllAsRead" class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('Mark All as Read') }}</button>
                                     </div>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Dropdown Profil -->
                        <div class="relative">
                            <Dropdown align="right" width="56" :open="showMobileProfileDropdown" @update:open="onMobileProfileOpenChange">
                                <template #trigger>
                                    <button
                                        type="button"
                                        @click.stop="showMobileProfileDropdown = !showMobileProfileDropdown"
                                        class="relative inline-flex items-center justify-center h-12 rounded-full bg-white dark:bg-slate-800 shadow-md border border-white dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 transition duration-150 focus:outline-none
                                               w-12 lg:w-auto lg:h-11 lg:rounded-xl lg:pl-4 lg:pr-3 lg:gap-2 lg:bg-white lg:dark:bg-slate-900 lg:shadow-sm lg:border lg:border-white lg:dark:border-slate-800 lg:hover:bg-slate-100/80 lg:dark:hover:bg-slate-800/80"
                                    >
                                        <!-- Nama — hanya tampil di desktop -->
                                        <span class="hidden lg:block whitespace-nowrap text-sm font-medium text-slate-700 dark:text-slate-300">{{ $page.props.auth.user.name }}</span>
                                        <!-- Avatar icon User (Sekarang kembali di kanan nama pada desktop) -->
                                        <span class="relative h-8 w-8 lg:h-7 lg:w-7 rounded-full bg-transparent lg:bg-slate-100 lg:dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center flex-shrink-0 transition-all duration-150">
                                            <User class="h-4.5 w-4.5 lg:h-4 lg:w-4" />
                                        </span>
                                        <span
                                            v-if="unreadCount > 0 && !showMobileNotifications && !showMobileProfileDropdown"
                                            class="absolute -top-1 -right-1 h-5 min-w-5 px-1 inline-flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold ring-2 ring-slate-50 dark:ring-slate-950 lg:hidden"
                                        >{{ unreadCount }}</span>
                                        <!-- Chevron di paling kanan — hanya tampil di desktop -->
                                        <svg class="hidden lg:block h-4 w-4 text-slate-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <!-- Info user di header dropdown -->
                                    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{{ $page.props.auth.user.name }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ $page.props.auth.user.email }}</p>
                                    </div>
                                    
                                    <!-- Profil -->
                                    <Link
                                        :href="route('profile.edit')"
                                        prefetch
                                        class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800/80 transition duration-150"
                                    >
                                        <User class="h-4 w-4 text-slate-400" />
                                        <span>{{ __('Profile') }}</span>
                                    </Link>

                                    <!-- Notifikasi (Mobile) -->
                                    <button
                                        @click.stop="goToMobileNotifications"
                                        type="button"
                                        class="lg:hidden flex items-center justify-between w-full px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800/80 transition duration-150 text-left focus:outline-none"
                                    >
                                        <div class="flex items-center gap-3">
                                            <Bell class="h-4 w-4 text-slate-400" />
                                            <span class="font-medium">Notifikasi</span>
                                        </div>
                                        <span 
                                            v-if="unreadCount > 0" 
                                            class="h-5 min-w-5 px-1.5 flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold"
                                        >{{ unreadCount }}</span>
                                    </button>

                                    <!-- Switch Bahasa -->
                                    <Link
                                        :href="route('lang.switch', $page.props.locale === 'id' ? 'en' : 'id')"
                                        class="lg:hidden flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800/80 transition duration-150"
                                        :title="__('Switch Language')"
                                    >
                                        <Languages class="h-4 w-4 text-slate-400" />
                                        <span class="flex-1 text-left font-medium">{{ __('Language') }}</span>
                                        <span class="text-[10px] font-extrabold uppercase bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 rounded border border-emerald-100/30 dark:border-emerald-900/40">
                                            {{ $page.props.locale === 'id' ? 'ID' : 'EN' }}
                                        </span>
                                    </Link>

                                    <!-- Switch Tema -->
                                    <button
                                        @click.stop="toggleTheme"
                                        type="button"
                                        class="lg:hidden flex items-center justify-between w-full px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800/80 transition duration-150 text-left focus:outline-none"
                                    >
                                        <div class="flex items-center gap-3">
                                            <Sun v-if="!isDark" class="h-4 w-4 text-amber-500" />
                                            <Moon v-else class="h-4 w-4 text-emerald-400" />
                                            <span class="font-medium">{{ __('Dark Mode') }}</span>
                                        </div>
                                        <div 
                                            :class="[
                                                'w-8 h-4 rounded-full p-0.5 transition-colors duration-200',
                                                isDark ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-slate-800'
                                            ]"
                                        >
                                            <div 
                                                :class="[
                                                    'bg-white w-3 h-3 rounded-full shadow-md transform transition-transform duration-200',
                                                    isDark ? 'translate-x-4' : 'translate-x-0'
                                                ]"
                                            />
                                        </div>
                                    </button>

                                    <!-- Logout -->
                                    <Link
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition duration-150"
                                    >
                                        <LogOut class="h-4 w-4" />
                                        <span>{{ __('Log out') }}</span>
                                    </Link>
                                </template>
                            </Dropdown>
                            <!-- Overlay to close notification panel on outside click -->
                            <Transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0"
                                enter-to-class="opacity-100"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <div
                                    v-if="showMobileNotifications"
                                    class="fixed inset-0 z-40 lg:hidden"
                                    @click="showMobileNotifications = false"
                                    aria-hidden="true"
                                />
                            </Transition>
                            <!-- Mobile notification panel inside profile wrapper for proper positioning -->
                            <Transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 scale-95 translate-y-1"
                                enter-to-class="opacity-100 scale-100 translate-y-0"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="opacity-100 scale-100 translate-y-0"
                                leave-to-class="opacity-0 scale-95 translate-y-1"
                            >
                                <div v-if="showMobileNotifications" @click.stop class="lg:hidden absolute right-0 top-full mt-2.5 z-50 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden w-[calc(100vw-2rem)]">
                                 <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                     <div class="flex items-center gap-3">
                                         <Bell class="h-4 w-4 text-slate-500 dark:text-slate-400" />
                                         <span class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('Notifications') }}</span>
                                         <span 
                                             v-if="unreadCount > 0" 
                                             class="h-5 min-w-5 px-1.5 flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold"
                                         >{{ unreadCount }}</span>
                                     </div>
                                     <button
                                         @click.stop="showMobileNotifications = false"
                                         type="button"
                                         class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2 rounded-md"
                                         aria-label="Tutup notifikasi"
                                     >
                                         <X class="h-4 w-4" />
                                     </button>
                                 </div>
                                 <div class="max-h-80 overflow-y-auto">
                                     <div v-if="notifications.length === 0" class="py-12 text-center text-xs text-slate-400 dark:text-slate-500 font-medium">
                                         {{ __('No notifications') }}
                                     </div>
                                     <div
                                         v-else
                                         v-for="notif in notifications"
                                         :key="notif.id"
                                         @click="markAsRead(notif)"
                                         :class="[
                                             'flex gap-3 px-4 py-3 border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition cursor-pointer',
                                             !notif.read_at ? 'bg-emerald-50/30 dark:bg-emerald-950/10' : ''
                                         ]"
                                     >
                                         <div :class="[
                                             'h-9 w-9 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5',
                                             notif.type === 'ticket' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-500' :
                                             notif.type === 'progress' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-500' :
                                             notif.type === 'done' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500' :
                                             'bg-violet-50 dark:bg-violet-950/40 text-violet-500'
                                         ]">
                                             <Bell v-if="notif.type === 'ticket'" class="h-4 w-4" />
                                             <Clock v-else-if="notif.type === 'progress'" class="h-4 w-4" />
                                             <CheckCircle2 v-else-if="notif.type === 'done'" class="h-4 w-4" />
                                             <User v-else class="h-4 w-4" />
                                         </div>
                                         <div class="flex-1 min-w-0">
                                             <div class="flex items-start justify-between gap-2">
                                                 <p :class="['text-xs font-semibold truncate', !notif.read_at ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300']">{{ notif.title }}</p>
                                                 <span v-if="!notif.read_at" class="h-2 w-2 rounded-full bg-emerald-500 flex-shrink-0 mt-1"></span>
                                             </div>
                                             <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed mt-0.5 line-clamp-2">{{ notif.message }}</p>
                                             <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-medium">{{ notif.time }}</p>
                                         </div>
                                     </div>
                                 </div>
                                 <div v-if="unreadCount > 0" class="px-4 py-2.5 border-t border-slate-100 dark:border-slate-800 text-center">
                                     <button @click="markAllAsRead" class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('Mark All as Read') }}</button>
                                 </div>
                             </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>


        </nav>

        <!-- Toast Floating Container -->
        <div class="fixed top-24 right-6 z-[9999] flex flex-col gap-3 w-80 max-w-[calc(100vw-3rem)] pointer-events-none">
            <TransitionGroup
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 translate-x-4"
                enter-to-class="translate-y-0 opacity-100 translate-x-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0 translate-x-4"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    @click="toast.route ? router.visit(toast.route) : null"
                    :class="[
                        'pointer-events-auto flex gap-3 p-4 rounded-2xl border shadow-lg cursor-pointer transition-all duration-200 hover:scale-[1.02]',
                        'bg-white/95 dark:bg-slate-900/95 backdrop-blur-md',
                        'border-slate-100 dark:border-slate-800/80',
                        toast.route ? 'hover:border-indigo-500/50 dark:hover:border-indigo-400/50' : ''
                    ]"
                >
                    <!-- Icon -->
                    <div :class="[
                        'h-9 w-9 rounded-xl flex items-center justify-center flex-shrink-0',
                        toast.type === 'ticket' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-500' :
                        toast.type === 'progress' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-500' :
                        toast.type === 'done' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500' :
                        'bg-violet-50 dark:bg-violet-950/40 text-violet-500'
                    ]">
                        <Bell v-if="toast.type === 'ticket'" class="h-4.5 w-4.5" />
                        <Clock v-else-if="toast.type === 'progress'" class="h-4.5 w-4.5" />
                        <CheckCircle2 v-else-if="toast.type === 'done'" class="h-4.5 w-4.5" />
                        <User v-else class="h-4.5 w-4.5" />
                    </div>
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-extrabold text-slate-900 dark:text-white leading-normal">{{ toast.title }}</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed mt-1 line-clamp-3">{{ toast.message }}</p>
                    </div>
                    <!-- Close Button -->
                    <button @click.stop="removeToast(toast.id)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 h-5 w-5 flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition flex-shrink-0">
                        <X class="h-3 w-3" />
                    </button>
                </div>
            </TransitionGroup>
        </div>

        <!-- Custom Alert Modal (Glassmorphism Swal Alternative) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="customAlert.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                    <!-- Backdrop overlay -->
                    <div @click="customAlert.cancelText ? customAlert.onCancel() : customAlert.onConfirm()" class="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity"></div>

                    <!-- Modal Card -->
                    <div class="relative bg-white/95 dark:bg-slate-900/95 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden p-7 flex flex-col items-center text-center transform transition-all duration-200 scale-100 backdrop-blur-md">
                        <!-- Status Icon -->
                        <div :class="[
                            'h-20 w-20 rounded-full flex items-center justify-center mb-5 flex-shrink-0',
                            customAlert.icon === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500' :
                            customAlert.icon === 'error' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-500' :
                            customAlert.icon === 'warning' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-500' :
                            'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500'
                        ]">
                            <CheckCircle2 v-if="customAlert.icon === 'success'" class="h-10 w-10" />
                            <AlertCircle v-else-if="customAlert.icon === 'error'" class="h-10 w-10" />
                            <AlertTriangle v-else-if="customAlert.icon === 'warning'" class="h-10 w-10" />
                            <HelpCircle v-else class="h-10 w-10" />
                        </div>

                        <!-- Info Content -->
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight px-2">{{ customAlert.title }}</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-3 leading-relaxed px-1">{{ customAlert.text }}</p>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3 w-full mt-6">
                            <button
                                v-if="customAlert.cancelText"
                                @click="customAlert.onCancel"
                                class="flex-1 h-11 text-sm font-bold rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 transition duration-150 focus:outline-none"
                            >
                                {{ customAlert.cancelText }}
                            </button>
                            <button
                                v-if="customAlert.confirmText"
                                @click="customAlert.onConfirm"
                                :class="[
                                    'flex-1 h-11 text-sm font-bold rounded-xl text-white shadow-sm transition duration-150 focus:outline-none',
                                    customAlert.icon === 'success' ? 'bg-emerald-600 hover:bg-emerald-700' :
                                    customAlert.icon === 'error' ? 'bg-rose-600 hover:bg-rose-700' :
                                    customAlert.icon === 'warning' ? 'bg-amber-600 hover:bg-amber-700' :
                                    'bg-emerald-600 hover:bg-emerald-700'
                                ]"
                            >
                                {{ customAlert.confirmText }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Layout Body -->
        <div class="flex w-full relative">

            <!-- Mobile Sidebar Overlay -->
            <div
                v-if="sidebarOpen"
                @click="closeSidebar"
                class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm lg:hidden"
                aria-hidden="true"
            />

            <!-- Sidebar -->
            <aside
                :class="[
                    'fixed top-0 left-0 h-screen z-50 flex flex-col transition-all duration-300 ease-in-out',
                    'bg-white dark:bg-slate-900',
                    'shadow-lg lg:shadow-sm dark:shadow-none lg:border-r lg:border-white dark:border-slate-800/80',
                    sidebarCollapsed ? 'w-72 lg:w-20' : 'w-72',
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
                ]"
            >
                <!-- Sidebar Header (Mobile & Desktop) -->
                <div :class="['flex items-center border-b border-slate-100 dark:border-slate-800 select-none px-4 py-5 transition-all duration-300', sidebarCollapsed ? 'justify-center' : 'justify-between']">
                    <!-- If collapsed, show logo and reveal expand icon on hover -->
                    <div v-if="sidebarCollapsed">
                        <button 
                            @click="toggleSidebarCollapse"
                            class="relative group h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-200"
                            title="Expand Sidebar"
                        >
                            <img src="/images/logo-sidebar.png" alt="PESU PELUH" class="h-full w-full object-contain hue-rotate-[280deg] saturate-[1.2] dark:hue-rotate-0 dark:saturate-100 dark:brightness-0 dark:invert transition-all duration-200 group-hover:opacity-0" />
                            <ChevronRight class="absolute inset-0 m-auto h-5.5 w-5.5 text-emerald-600 dark:text-emerald-400 opacity-0 group-hover:opacity-100 transition-all duration-200" />
                        </button>
                    </div>
                    
                    <Link v-else :href="route('dashboard')" prefetch class="flex items-center gap-3">
                        <!-- Left Logo (Rounded Square) -->
                        <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 overflow-hidden">
                            <img src="/images/logo-sidebar.png" alt="PESU PELUH" class="h-full w-full object-contain hue-rotate-[280deg] saturate-[1.2] dark:hue-rotate-0 dark:saturate-100 dark:brightness-0 dark:invert" />
                        </div>
                        <!-- Right Text Content -->
                        <div class="flex flex-col">
                            <span class="text-sm font-bold tracking-widest text-emerald-700 dark:text-emerald-400 uppercase leading-none">Pesu Peluh</span>
                            <span class="text-[9px] font-semibold text-slate-400 dark:text-slate-500 mt-1 leading-tight tracking-normal max-w-[185px]">
                                Pengendalian Terintegrasi Unit Penunjang Dalam Satu Sentuhan
                            </span>
                        </div>
                    </Link>
                    
                    <!-- Desktop collapse toggle & Close button (Mobile only) -->
                    <div class="flex items-center gap-2">
                        <button 
                            v-if="!sidebarCollapsed"
                            @click="toggleSidebarCollapse" 
                            class="hidden lg:flex items-center justify-center p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                            title="Minimize Sidebar"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>
                        <button @click="closeSidebar" class="lg:hidden p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>
                <!-- Nav Items — hanya link halaman, tanpa user info -->
                <nav 
                    ref="sidebarNav"
                    @scroll="saveSidebarScroll"
                    :class="['flex-1 overflow-y-auto transition-all duration-300', sidebarCollapsed ? 'px-2 py-2 space-y-2' : 'px-4 py-4 space-y-6']"
                >
                    <div v-for="(group, groupIndex) in menuGroups" :key="group.title" :class="sidebarCollapsed ? 'space-y-2' : 'space-y-1.5'">
                        
                        <!-- Judul Kategori Kelompok -->
                        <div 
                            v-if="!sidebarCollapsed"
                            class="px-3 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500/80 mb-2 block transition-all"
                        >
                            {{ __(group.title) }}
                        </div>
                        <!-- Collapsed: simple line separator (skip first group) -->
                        <div 
                            v-else-if="groupIndex > 0"
                            class="border-b border-slate-100 dark:border-slate-800 -mx-2 my-2"
                        />

                        <!-- Daftar Item di Kelompok -->
                        <template v-for="item in group.items" :key="item.label">

                            <!-- External link (buka tab baru) -->
                            <a
                                v-if="item.external"
                                :href="item.href"
                                target="_blank"
                                @click="closeSidebar"
                                :class="[
                                    sidebarCollapsed 
                                        ? 'h-11 w-11 mx-auto flex items-center justify-center rounded-xl transition-all duration-150' 
                                        : 'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150',
                                    'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-emerald-600 dark:hover:text-emerald-400'
                                ]"
                                :title="sidebarCollapsed ? __(item.label) : ''"
                            >
                                <component :is="item.icon" class="h-5 w-5 flex-shrink-0 text-slate-400 group-hover:text-emerald-500 transition duration-150" />
                                <span v-if="!sidebarCollapsed" class="flex-1 min-w-0 truncate">{{ __(item.label) }}</span>
                                <ChevronRight v-if="!sidebarCollapsed" class="h-3.5 w-3.5 opacity-0 group-hover:opacity-100 transition duration-150 text-slate-400" />
                            </a>

                            <!-- Dropdown Menu Item (bila ada children) -->
                            <div v-else-if="item.children" :class="sidebarCollapsed ? '' : 'space-y-1'">
                                
                                <!-- Collapsed: wrapped in a bordered group container -->
                                <div 
                                    v-if="sidebarCollapsed"
                                    :class="[
                                        'flex flex-col items-center gap-1 rounded-xl mx-1 transition-all duration-200',
                                        openMenus[item.label] 
                                            ? 'border border-slate-200 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-800/20 py-2' 
                                            : 'border border-transparent py-0'
                                    ]"
                                >
                                    <!-- Parent icon button -->
                                    <button
                                        @click="toggleMenu(item.label)"
                                        :class="[
                                            'relative group h-11 w-11 flex items-center justify-center rounded-xl transition-all duration-150 focus:outline-none',
                                            isChildActive(item.children)
                                                ? 'bg-emerald-50/50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300'
                                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-emerald-600 dark:hover:text-emerald-400'
                                        ]"
                                        :title="__(item.label)"
                                    >
                                        <component
                                            :is="item.icon"
                                            :class="[
                                                'h-5 w-5 flex-shrink-0 transition duration-150',
                                                isChildActive(item.children)
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : 'text-slate-400 group-hover:text-emerald-500'
                                            ]"
                                        />
                                        <!-- Tiny dot when collapsed & closed -->
                                        <span 
                                            v-if="item.label === 'menu.user_management' && !openMenus[item.label] && pendingApprovalsCount > 0"
                                            class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-amber-500 ring-2 ring-white dark:ring-slate-900"
                                        />
                                        <!-- Small chevron indicator on hover -->
                                        <ChevronDown 
                                            :class="[
                                                'absolute -bottom-0.5 left-1/2 -translate-x-1/2 h-3 w-3 transition-all duration-200',
                                                openMenus[item.label] 
                                                    ? 'opacity-60 rotate-180' 
                                                    : 'opacity-0 group-hover:opacity-50',
                                                isChildActive(item.children)
                                                    ? 'text-emerald-500 dark:text-emerald-400'
                                                    : 'text-slate-400'
                                            ]"
                                        />
                                    </button>
                                    <!-- Child icons (collapsed) -->
                                    <template v-if="openMenus[item.label]">
                                        <Link
                                            v-for="child in item.children"
                                            :key="child.label"
                                            :href="route(child.routeName)"
                                            prefetch
                                            @click="closeSidebar"
                                            :class="[
                                                'relative h-9 w-9 flex items-center justify-center rounded-lg transition-all duration-150',
                                                route().current(child.routeName)
                                                    ? 'bg-emerald-600 text-white shadow-sm dark:bg-emerald-950/50 dark:text-emerald-400 dark:shadow-none'
                                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-emerald-600 dark:hover:text-emerald-400'
                                            ]"
                                            :title="__(child.label)"
                                        >
                                            <component
                                                v-if="child.icon"
                                                :is="child.icon"
                                                :class="[
                                                    'h-4 w-4 flex-shrink-0 transition duration-150',
                                                    route().current(child.routeName)
                                                        ? 'text-white dark:text-emerald-400'
                                                        : 'text-slate-400 group-hover:text-emerald-500'
                                                ]"
                                            />
                                            <!-- Tiny Dot indicator for pending items when collapsed -->
                                            <span 
                                                v-if="child.routeName === 'users.approvals' && openMenus[item.label] && pendingApprovalsCount > 0"
                                                class="absolute top-1 right-1 h-2 w-2 rounded-full bg-amber-500 ring-2 ring-white dark:ring-slate-900"
                                            />
                                        </Link>
                                    </template>
                                </div>

                                <!-- Expanded: standard dropdown -->
                                <template v-else>
                                    <button
                                        @click="toggleMenu(item.label)"
                                        :class="[
                                            'w-full group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 text-left focus:outline-none',
                                            isChildActive(item.children)
                                                ? 'bg-emerald-50/50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300'
                                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-emerald-600 dark:hover:text-emerald-400'
                                        ]"
                                    >
                                        <component
                                            :is="item.icon"
                                            :class="[
                                                'h-5 w-5 flex-shrink-0 transition duration-150',
                                                isChildActive(item.children)
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : 'text-slate-400 group-hover:text-emerald-500'
                                            ]"
                                        />
                                        <span class="flex-1 min-w-0 truncate">{{ __(item.label) }}</span>
                                        
                                        <!-- Right slot: Badge or Chevron -->
                                        <div class="relative w-5 h-5 flex items-center justify-center ml-auto flex-shrink-0">
                                            <!-- Dropdown Arrow SVG (hidden when closed and badge is present, shown on hover/open) -->
                                            <svg 
                                                :class="[
                                                    'h-4 w-4 transition-all duration-200 absolute',
                                                    openMenus[item.label] ? 'rotate-180' : '',
                                                    isChildActive(item.children)
                                                        ? 'text-emerald-600 dark:text-emerald-400'
                                                        : 'text-slate-400 group-hover:text-emerald-500',
                                                    (item.label === 'menu.user_management' && !openMenus[item.label] && pendingApprovalsCount > 0)
                                                        ? 'opacity-0 scale-75 group-hover:opacity-100 group-hover:scale-100'
                                                        : 'opacity-100 scale-100'
                                                ]" 
                                                xmlns="http://www.w3.org/2000/svg" 
                                                viewBox="0 0 20 20" 
                                                fill="currentColor"
                                            >
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
 
                                            <!-- Parent Badge when Closed (hidden on hover, shown by default) -->
                                            <span 
                                                v-if="item.label === 'menu.user_management' && !openMenus[item.label] && pendingApprovalsCount > 0"
                                                class="w-5 h-5 flex items-center justify-center rounded-full text-[10px] font-extrabold bg-amber-500 text-white shadow-sm absolute transition-all duration-200 group-hover:opacity-0 group-hover:scale-75"
                                            >
                                                {{ pendingApprovalsCount }}
                                            </span>
                                        </div>
                                    </button>
                                    
                                    <!-- Submenu Items (expanded) -->
                                    <div v-show="openMenus[item.label]" class="space-y-1 mt-1">
                                        <Link
                                            v-for="child in item.children"
                                            :key="child.label"
                                            :href="route(child.routeName)"
                                            prefetch
                                            @click="closeSidebar"
                                            :class="[
                                                'group flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition duration-150',
                                                route().current(child.routeName)
                                                    ? 'bg-emerald-600 text-white shadow-sm font-semibold dark:bg-emerald-950/40 dark:text-emerald-400 dark:shadow-none'
                                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-emerald-600 dark:hover:text-emerald-400'
                                            ]"
                                        >
                                            <component
                                                v-if="child.icon"
                                                :is="child.icon"
                                                :class="[
                                                    'h-4 w-4 flex-shrink-0 transition duration-150 ml-6',
                                                    route().current(child.routeName)
                                                        ? 'text-white dark:text-emerald-400'
                                                        : 'text-slate-400 group-hover:text-emerald-500'
                                                ]"
                                            />
                                            <span class="flex-1 min-w-0 truncate">{{ __(child.label) }}</span>
                                            <span 
                                                v-if="child.routeName === 'users.approvals' && openMenus[item.label] && pendingApprovalsCount > 0"
                                                class="ml-auto w-5 h-5 flex items-center justify-center rounded-full text-[10px] font-extrabold bg-amber-500 text-white shadow-sm flex-shrink-0"
                                            >
                                                {{ pendingApprovalsCount }}
                                            </span>
                                        </Link>
                                    </div>
                                </template>
                            </div>
 
                            <!-- Internal Inertia link -->
                            <Link
                                v-else
                                :href="route(item.routeName)"
                                prefetch
                                @click="closeSidebar"
                                :class="[
                                    sidebarCollapsed 
                                        ? 'h-11 w-11 mx-auto flex items-center justify-center rounded-xl transition-all duration-150' 
                                        : 'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-150',
                                    isRouteActive(item)
                                        ? 'bg-emerald-600 text-white shadow-sm font-semibold dark:bg-emerald-950/40 dark:text-emerald-400 dark:shadow-none'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-emerald-600 dark:hover:text-emerald-400'
                                ]"
                                :title="sidebarCollapsed ? __(item.label) : ''"
                            >
                                <component
                                    :is="item.icon"
                                    :class="[
                                        'h-5 w-5 flex-shrink-0 transition duration-150',
                                        isRouteActive(item)
                                            ? 'text-white dark:text-emerald-400'
                                            : 'text-slate-400 group-hover:text-emerald-500'
                                    ]"
                                />
                                <span v-if="!sidebarCollapsed" class="flex-1 min-w-0 truncate">{{ __(item.label) }}</span>
                                <ChevronRight
                                    v-if="isRouteActive(item) && !sidebarCollapsed"
                                    class="h-3.5 w-3.5 text-white dark:text-emerald-400"
                                />
                            </Link>

                        </template>
                    </div>
                </nav>
            </aside>

            <!-- Main Content -->
            <main :class="['flex-1 w-full min-h-[calc(100vh-4rem)] lg:min-h-screen transition-all duration-300 ease-in-out', sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-72']">
                <!-- Page Heading slot (jika ada) -->
                <header
                    v-if="$slots.header"
                    class="w-full bg-white dark:bg-slate-900 shadow-sm dark:shadow-none dark:border-b dark:border-slate-800"
                >
                    <div class="w-full px-4 sm:px-6 py-5">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Default slot content -->
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.mobile-nav-gradient {
    background: linear-gradient(
        to bottom,
        rgb(241 245 249) 0%,
        rgba(241, 245, 249, 0.98) 15%,
        rgba(241, 245, 249, 0.92) 30%,
        rgba(241, 245, 249, 0.80) 45%,
        rgba(241, 245, 249, 0.60) 60%,
        rgba(241, 245, 249, 0.35) 75%,
        rgba(241, 245, 249, 0.12) 90%,
        transparent 100%
    );
}

.dark .mobile-nav-gradient {
    background: linear-gradient(
        to bottom,
        rgb(2 6 23) 0%,
        rgb(2 6 23 / 98%) 15%,
        rgb(2 6 23 / 92%) 30%,
        rgb(2 6 23 / 80%) 45%,
        rgb(2 6 23 / 60%) 60%,
        rgb(2 6 23 / 35%) 75%,
        rgb(2 6 23 / 12%) 90%,
        transparent 100%
    );
}
</style>
