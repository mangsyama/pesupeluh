<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Search, User, Shield, ShieldAlert, Layers, Users, Calendar, Phone, Wrench, MapPin, Edit2, Trash2, UserX, UserCheck, Plus, X, KeyRound, RotateCcw, ChevronDown, Check } from '@lucide/vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
    rooms: {
        type: Array,
        default: () => [],
    },
    supportingUnits: {
        type: Array,
        default: () => [],
    },
    allPermissionKeys: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const { proxy } = getCurrentInstance();
const searchQuery = ref('');
const currentTab = ref('all');

const __ = (key) => {
    const translations = page.props.translations || {};
    if (translations[key] !== undefined) {
        return translations[key];
    }
    const parts = key.split('.');
    let current = translations;
    for (const part of parts) {
        if (current && current[part] !== undefined) {
            current = current[part];
        } else {
            return key;
        }
    }
    return current;
};

const roleTabs = [
    { key: 'all', label: __('pages.user_management.tabs.all'), icon: Users },
    { key: 'admin', label: __('pages.user_management.tabs.admin'), icon: Shield },
    { key: 'management', label: __('pages.user_management.tabs.management'), icon: Wrench },
    { key: 'unit_head', label: __('pages.user_management.tabs.unit_head'), icon: MapPin },
    { key: 'technician', label: __('pages.user_management.tabs.technician'), icon: Wrench },
    { key: 'room_head', label: __('pages.user_management.tabs.room_head'), icon: Layers },
    { key: 'reporter', label: __('pages.user_management.tabs.reporter'), icon: User },
];

const filteredUsers = computed(() => {
    return props.users.filter(user => {
        let matchesTab = true;
        if (currentTab.value === 'admin') matchesTab = user.role_id === 1;
        else if (currentTab.value === 'management') matchesTab = [2, 3, 4].includes(user.role_id);
        else if (currentTab.value === 'unit_head') matchesTab = user.role_id === 5;
        else if (currentTab.value === 'technician') matchesTab = user.role_id === 6;
        else if (currentTab.value === 'room_head') matchesTab = user.role_id === 7;
        else if (currentTab.value === 'reporter') matchesTab = user.role_id === 8;

        if (!matchesTab) return false;

        if (!searchQuery.value.trim()) return true;

        const q = searchQuery.value.toLowerCase();
        const name = (user.name || '').toLowerCase();
        const email = (user.email || '').toLowerCase();
        const nip = (user.nip || '').toLowerCase();

        return name.includes(q) || email.includes(q) || nip.includes(q);
    });
});

const getStatusBadge = (isActive) => {
    return isActive
        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/50'
        : 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    name: '',
    nip: '',
    username: '',
    email: '',
    phone_number: '',
    password: '',
    role_id: props.roles && props.roles.length > 0 ? props.roles[0].id : null,
    supporting_unit_id: '',
    room_id: '',
    telegram_chat_id: '',
});

// Custom Dropdowns State
const isRoleDropdownOpen = ref(false);
const isUnitDropdownOpen = ref(false);
const isRoomDropdownOpen = ref(false);

const selectedRoleLabel = computed(() => {
    if (!form.role_id) return '';
    const r = (props.roles || []).find(item => item.id === form.role_id);
    return r ? proxy.__('roles.' + r.name) : '';
});

const selectedUnitLabel = computed(() => {
    if (!form.supporting_unit_id) return proxy.__('pages.user_management.form.no_unit');
    const u = (props.supportingUnits || []).find(item => item.id === form.supporting_unit_id);
    return u ? u.name : proxy.__('pages.user_management.form.no_unit');
});

const selectedRoomLabel = computed(() => {
    if (!form.room_id) return proxy.__('pages.user_management.form.no_room');
    const rm = (props.rooms || []).find(item => item.id === form.room_id);
    return rm ? rm.name : proxy.__('pages.user_management.form.no_room');
});

const closeAllDropdowns = () => {
    isRoleDropdownOpen.value = false;
    isUnitDropdownOpen.value = false;
    isRoomDropdownOpen.value = false;
};

const toggleRoleDropdown = (e) => {
    e?.stopPropagation();
    isRoleDropdownOpen.value = !isRoleDropdownOpen.value;
    isUnitDropdownOpen.value = false;
    isRoomDropdownOpen.value = false;
};

const selectRole = (id) => {
    form.role_id = id;
    isRoleDropdownOpen.value = false;
};

const toggleUnitDropdown = (e) => {
    e?.stopPropagation();
    isUnitDropdownOpen.value = !isUnitDropdownOpen.value;
    isRoleDropdownOpen.value = false;
    isRoomDropdownOpen.value = false;
};

const selectUnit = (id) => {
    form.supporting_unit_id = id;
    isUnitDropdownOpen.value = false;
};

const toggleRoomDropdown = (e) => {
    e?.stopPropagation();
    isRoomDropdownOpen.value = !isRoomDropdownOpen.value;
    isRoleDropdownOpen.value = false;
    isUnitDropdownOpen.value = false;
};

const selectRoom = (id) => {
    form.room_id = id;
    isRoomDropdownOpen.value = false;
};

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.id = null;
    form.role_id = props.roles && props.roles.length > 0 ? props.roles[0].id : null;
    closeAllDropdowns();
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    form.clearErrors();
    form.id = user.id;
    form.name = user.name;
    form.nip = user.nip || '';
    form.username = user.username || '';
    form.email = user.email;
    form.phone_number = user.phone_number || '';
    form.password = '';
    form.role_id = user.role_id || (props.roles && props.roles.length > 0 ? props.roles[0].id : null);
    form.supporting_unit_id = user.supporting_unit_id || '';
    form.room_id = user.room_id || '';
    form.telegram_chat_id = user.telegram_chat_id || '';
    closeAllDropdowns();
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('users.update', form.id), {
            onSuccess: () => {
                showModal.value = false;
                const rawRoleName = form.role_id ? props.roles.find(r => r.id === form.role_id)?.name || '' : '';
                const displayRole = rawRoleName ? proxy.__('roles.' + rawRoleName) : '';
                proxy.$toast(proxy.__('pages.user_management.alerts.update_success').replace('{role}', displayRole), 'success');
            },
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
                const rawRoleName = form.role_id ? props.roles.find(r => r.id === form.role_id)?.name || '' : '';
                const displayRole = rawRoleName ? proxy.__('roles.' + rawRoleName) : '';
                proxy.$toast(proxy.__('pages.user_management.alerts.add_success').replace('{role}', displayRole), 'success');
            },
        });
    }
};

const toggleStatus = (user) => {
    const isCurrentlyActive = user.is_active;
    const actionTitle = isCurrentlyActive ? 'Tangguhkan Akun?' : 'Aktifkan Kembali Akun?';
    const actionText = isCurrentlyActive
        ? `Status akun ${user.name} akan dinonaktifkan (suspend) dan tidak dapat lagi digunakan untuk login.`
        : `Status akun ${user.name} akan diaktifkan kembali sehingga pengguna dapat login ke sistem.`;
    const confirmBtnText = isCurrentlyActive ? 'Ya, Tangguhkan' : 'Ya, Aktifkan';
    const confirmBtnColor = isCurrentlyActive ? '#f59e0b' : '#059669';
    const dialogIcon = isCurrentlyActive ? 'warning' : 'question';

    proxy.$swal({
        title: actionTitle,
        text: actionText,
        icon: dialogIcon,
        showCancelButton: true,
        confirmButtonColor: confirmBtnColor,
        cancelButtonColor: '#64748b',
        confirmButtonText: confirmBtnText,
        cancelButtonText: proxy.__('pages.user_management.alerts.cancel') || 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(route('users.toggle-active', user.id), {}, {
                onSuccess: () => {
                    const displayRole = user.role?.name ? proxy.__('roles.' + user.role.name) : '';
                    proxy.$toast(proxy.__('pages.user_management.alerts.status_update_success').replace('{role}', displayRole), 'success');
                },
            });
        }
    });
};

const deleteUser = (user) => {
    if (user.email === page.props.auth.user.email) {
        proxy.$swal({
            title: proxy.__('pages.user_management.alerts.action_denied'),
            text: proxy.__('pages.user_management.alerts.cannot_delete_self'),
            icon: 'error',
            confirmButtonColor: '#4f46e5',
        });
        return;
    }

    const displayRole = user.role?.name ? proxy.__('roles.' + user.role.name) : '';
    proxy.$swal({
        title: proxy.__('pages.user_management.alerts.are_you_sure'),
        text: proxy.__('pages.user_management.alerts.revoke_warning').replace('{name}', user.name).replace('{role}', displayRole),
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: proxy.__('pages.user_management.alerts.yes_revoke'),
        cancelButtonText: proxy.__('pages.user_management.alerts.cancel'),
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('users.destroy', user.id), {
                onSuccess: () => {
                    proxy.$toast(proxy.__('pages.user_management.alerts.revoke_success').replace('{role}', displayRole), 'success');
                },
            });
        }
    });
};

const editUser = (user) => {
    openEditModal(user);
};

// ===== Permission Modal =====
const showPermissionModal = ref(false);
const permissionUser = ref(null);
const permissionChecked = ref([]);
const permissionUseDefault = ref(true);
const permissionProcessing = ref(false);

const getRoleDefaultPermissions = (roleId) => {
    const role = props.roles.find(r => r.id === roleId);
    if (role && role.page_permissions) {
        return Array.isArray(role.page_permissions) ? role.page_permissions : JSON.parse(role.page_permissions);
    }
    return [];
};

const openPermissionModal = (user) => {
    permissionUser.value = user;
    const roleDefaults = getRoleDefaultPermissions(user.role_id);

    if (user.page_permissions && Array.isArray(user.page_permissions) && user.page_permissions.length > 0) {
        // User has custom override
        permissionUseDefault.value = false;
        permissionChecked.value = [...user.page_permissions];
    } else {
        // Using role defaults
        permissionUseDefault.value = true;
        permissionChecked.value = [...roleDefaults];
    }
    showPermissionModal.value = true;
};

const isRoleDefault = (key) => {
    if (!permissionUser.value) return false;
    const roleDefaults = getRoleDefaultPermissions(permissionUser.value.role_id);
    return roleDefaults.includes(key);
};

const togglePermission = (key) => {
    if (permissionUseDefault.value) {
        // Switch to custom mode when user changes anything
        permissionUseDefault.value = false;
    }
    const idx = permissionChecked.value.indexOf(key);
    if (idx >= 0) {
        permissionChecked.value.splice(idx, 1);
    } else {
        permissionChecked.value.push(key);
    }
};

const resetToDefault = () => {
    if (!permissionUser.value) return;
    permissionUseDefault.value = true;
    permissionChecked.value = [...getRoleDefaultPermissions(permissionUser.value.role_id)];
};

const savePermissions = () => {
    if (!permissionUser.value) return;
    permissionProcessing.value = true;

    router.put(route('users.update-permissions', permissionUser.value.id), {
        page_permissions: permissionChecked.value,
        use_role_default: permissionUseDefault.value,
    }, {
        onSuccess: () => {
            showPermissionModal.value = false;
            permissionProcessing.value = false;
            proxy.$toast('Hak akses pengguna berhasil diperbarui.', 'success');
        },
        onError: () => {
            permissionProcessing.value = false;
        },
    });
};
</script>

<template>
    <Head :title="__('pages.user_management.user_list_title')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                <!-- Header Panel (ALWAYS VISIBLE) -->
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="space-y-1">
                        <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                            {{ __('pages.user_management.user_list_title') }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                            {{ __('pages.user_management.user_list_desc') }}
                        </p>
                    </div>
                    <button
                        @click="openAddModal"
                        class="w-full sm:w-auto h-10 inline-flex items-center justify-center gap-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm rounded-xl transition duration-150 whitespace-nowrap shadow-none border-0"
                    >
                        <Plus class="h-4 w-4" />
                        {{ __('pages.user_management.add_user_btn') }}
                    </button>
                </div>

                <!-- Table Card Container -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-4">
                    <!-- Filter Tabs & Search Header (ALWAYS VISIBLE) -->
                    <div class="flex flex-col gap-4 p-5 border-b border-slate-100 dark:border-slate-800/60">
                        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-7 gap-2 bg-slate-100/80 dark:bg-slate-950/45 p-1 rounded-xl">
                            <button
                                v-for="tab in roleTabs"
                                :key="tab.key"
                                @click="currentTab = tab.key"
                                :class="['w-full px-4 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-2 whitespace-nowrap', currentTab === tab.key ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                            >
                                <component :is="tab.icon" class="h-3.5 w-3.5" />
                                {{ tab.label }}
                            </button>
                        </div>
                        <div class="relative w-full">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                :placeholder="__('pages.user_management.search_all_placeholder')"
                                class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150 shadow-none"
                            />
                        </div>
                    </div>

                    <!-- Table Rows Scoped Deferred Skeleton Loading -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                    <th class="px-6 py-4">{{ __('pages.user_management.table.name_email') }}</th>
                                    <th class="px-6 py-4 text-center">{{ __('pages.user_management.table.role') }}</th>
                                    <th class="px-6 py-4">{{ __('pages.user_management.table.nip') }}</th>
                                    <th class="px-6 py-4">{{ __('pages.user_management.table.phone') }}</th>
                                    <th class="px-6 py-4">{{ __('pages.user_management.table.placement') }}</th>
                                    <th class="px-6 py-4 text-center">{{ __('pages.user_management.table.status') }}</th>
                                    <th class="px-6 py-4 text-center">{{ __('pages.user_management.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                                <tr v-if="filteredUsers.length === 0">
                                            <td colspan="7" class="px-6 py-16 text-center text-slate-400 dark:text-slate-500">
                                                {{ __('pages.user_management.table.empty_role').replace('{role}', __('pages.user_management.user_list_title')) }}
                                            </td>
                                        </tr>
                                        <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors duration-150">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-9 w-9 shrink-0 aspect-square rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-300">
                                                        <img v-if="user.profile_photo_path" :src="user.profile_photo_path" class="h-full w-full object-cover" />
                                                        <User v-else class="h-4.5 w-4.5" />
                                                    </div>
                                                    <div>
                                                        <div class="font-semibold text-slate-950 dark:text-white">{{ user.name }}</div>
                                                        <div class="text-xs text-slate-400 dark:text-slate-500">{{ user.email }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center text-xs font-bold text-slate-600 dark:text-slate-400">
                                                {{ user.role?.name ? __('roles.' + user.role.name) : '-' }}
                                            </td>
                                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-xs">{{ user.nip || '-' }}</td>
                                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-xs">
                                                <div>{{ user.phone_number || '-' }}</div>
                                                <div v-if="user.telegram_chat_id" class="text-[10px] text-violet-650 dark:text-violet-400 font-semibold flex items-center gap-0.5 mt-0.5" title="Telegram Chat ID terkonfigurasi">
                                                    <span>ID: {{ user.telegram_chat_id }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-xs">
                                                <div v-if="user.supporting_unit" class="font-semibold text-slate-700 dark:text-slate-300">{{ user.supporting_unit.name }}</div>
                                                <div v-if="user.room" class="text-[11px] text-slate-500 dark:text-slate-400">{{ user.room.name }}</div>
                                                <span v-if="!user.supporting_unit && !user.room" class="text-slate-400 dark:text-slate-600">-</span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold', getStatusBadge(user.is_active)]">
                                                    {{ user.is_active ? __('global.verified') : __('global.suspended') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center text-xs text-slate-500 dark:text-slate-400">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <button
                                                        @click="openPermissionModal(user)"
                                                        class="p-2 rounded-md bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-950/40 dark:text-violet-400 dark:hover:bg-violet-900/60 border border-violet-200/50 dark:border-violet-900/40 transition duration-150"
                                                        title="Atur Akses Halaman"
                                                    >
                                                        <KeyRound class="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        @click="editUser(user)"
                                                        class="p-2 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 transition duration-150"
                                                        :title="__('Edit')"
                                                    >
                                                        <Edit2 class="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        @click="toggleStatus(user)"
                                                        :class="[
                                                            'p-2 rounded-md border transition duration-150',
                                                            user.is_active
                                                                ? 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-400 border-amber-200/50 dark:border-amber-900/40'
                                                                : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200/50 dark:border-emerald-900/40'
                                                        ]"
                                                        :title="user.is_active ? __('pages.user_management.suspend_account') : __('pages.user_management.activate_account')"
                                                    >
                                                        <UserX v-if="user.is_active" class="h-3.5 w-3.5" />
                                                        <UserCheck v-else class="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        @click="deleteUser(user)"
                                                        class="p-2 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 transition duration-150"
                                                        :title="__('pages.user_management.alerts.yes_revoke')"
                                                    >
                                                        <Trash2 class="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showModal" @click="closeAllDropdowns" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm">
                <div class="w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 max-h-[90vh] flex flex-col" @click.stop>
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 rounded-t-2xl shrink-0">
                        <h3 class="text-base font-bold text-slate-955 dark:text-white">
                            {{ isEditing ? __('pages.user_management.edit_user_title') : __('pages.user_management.add_user_title') }}
                        </h3>
                        <button type="button" @click="showModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="flex flex-col flex-1 overflow-hidden min-h-0">
                        <div class="p-6 space-y-4 overflow-y-auto custom-scrollbar flex-1">
                            <!-- 1. Nama Lengkap -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.full_name') }}</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all duration-150"
                                    :placeholder="__('pages.user_management.form.full_name_placeholder')"
                                />
                                <div v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</div>
                            </div>

                            <!-- 2. NIP -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.nip') }}</label>
                                <input
                                    v-model="form.nip"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all duration-150"
                                    :placeholder="__('pages.user_management.form.nip_placeholder')"
                                />
                                <div v-if="form.errors.nip" class="text-xs text-red-500 mt-1">{{ form.errors.nip }}</div>
                            </div>

                            <!-- 3. Nama Pengguna (Username) -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.username') }}</label>
                                <input
                                    v-model="form.username"
                                    type="text"
                                    class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all duration-150"
                                    :placeholder="__('pages.user_management.form.username_placeholder')"
                                />
                                <div v-if="form.errors.username" class="text-xs text-red-500 mt-1">{{ form.errors.username }}</div>
                            </div>

                            <!-- 4. Email -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.email') }}</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all duration-150"
                                    :placeholder="__('pages.user_management.form.email_placeholder')"
                                />
                                <div v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</div>
                            </div>

                            <!-- 5. Nomor HP -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.phone') }}</label>
                                <input
                                    v-model="form.phone_number"
                                    type="text"
                                    class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all duration-150"
                                    :placeholder="__('pages.user_management.form.phone_placeholder')"
                                />
                                <div v-if="form.errors.phone_number" class="text-xs text-red-500 mt-1">{{ form.errors.phone_number }}</div>
                            </div>

                            <!-- 6. Kata Sandi -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                    {{ isEditing ? __('pages.user_management.form.password_optional') : __('pages.user_management.form.password') }}
                                </label>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    :required="!isEditing"
                                    class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all duration-150"
                                    :placeholder="isEditing ? __('pages.user_management.form.password_placeholder_edit') : __('pages.user_management.form.password_placeholder_add')"
                                />
                                <div v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</div>
                            </div>

                            <!-- 7. Peran Spesifik (Role) -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.specific_role') }}</label>
                                <div class="relative">
                                    <button
                                        type="button"
                                        @click="toggleRoleDropdown"
                                        class="w-full h-10 px-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150"
                                    >
                                        <span v-if="selectedRoleLabel" class="text-slate-800 dark:text-slate-100 truncate font-medium">
                                            {{ selectedRoleLabel }}
                                        </span>
                                        <span v-else class="text-slate-400 dark:text-slate-500">
                                            Pilih Peran...
                                        </span>
                                        <ChevronDown :class="['h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0 ml-2', isRoleDropdownOpen ? 'rotate-180 text-emerald-500' : '']" />
                                    </button>

                                    <div
                                        v-if="isRoleDropdownOpen"
                                        class="relative z-10 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-1 shadow-md"
                                    >
                                        <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                                            <button
                                                v-for="role in (props.roles || [])"
                                                :key="role.id"
                                                type="button"
                                                @click.stop="selectRole(role.id)"
                                                class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30"
                                                :class="form.role_id === role.id ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-700 dark:text-slate-300'"
                                            >
                                                <span class="truncate">{{ __('roles.' + role.name) }}</span>
                                                <Check v-if="form.role_id === role.id" class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="form.errors.role_id" class="text-xs text-red-500 mt-1">{{ form.errors.role_id }}</div>
                            </div>

                            <!-- 8. Unit Penunjang -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.supporting_unit') }}</label>
                                <div class="relative">
                                    <button
                                        type="button"
                                        @click="toggleUnitDropdown"
                                        class="w-full h-10 px-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150"
                                    >
                                        <span class="truncate font-medium text-slate-800 dark:text-slate-100">
                                            {{ selectedUnitLabel }}
                                        </span>
                                        <ChevronDown :class="['h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0 ml-2', isUnitDropdownOpen ? 'rotate-180 text-emerald-500' : '']" />
                                    </button>

                                    <div
                                        v-if="isUnitDropdownOpen"
                                        class="relative z-10 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-1 shadow-md"
                                    >
                                        <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                                            <button
                                                type="button"
                                                @click.stop="selectUnit('')"
                                                class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30"
                                                :class="!form.supporting_unit_id ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-700 dark:text-slate-300'"
                                            >
                                                <span class="truncate">{{ __('pages.user_management.form.no_unit') }}</span>
                                                <Check v-if="!form.supporting_unit_id" class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                            </button>
                                            <button
                                                v-for="unit in (supportingUnits || [])"
                                                :key="unit.id"
                                                type="button"
                                                @click.stop="selectUnit(unit.id)"
                                                class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30"
                                                :class="form.supporting_unit_id === unit.id ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-700 dark:text-slate-300'"
                                            >
                                                <span class="truncate">{{ unit.name }}</span>
                                                <Check v-if="form.supporting_unit_id === unit.id" class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="form.errors.supporting_unit_id" class="text-xs text-red-500 mt-1">{{ form.errors.supporting_unit_id }}</div>
                            </div>

                            <!-- 9. Ruangan -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.room') }}</label>
                                <div class="relative">
                                    <button
                                        type="button"
                                        @click="toggleRoomDropdown"
                                        class="w-full h-10 px-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150"
                                    >
                                        <span class="truncate font-medium text-slate-800 dark:text-slate-100">
                                            {{ selectedRoomLabel }}
                                        </span>
                                        <ChevronDown :class="['h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0 ml-2', isRoomDropdownOpen ? 'rotate-180 text-emerald-500' : '']" />
                                    </button>

                                    <div
                                        v-if="isRoomDropdownOpen"
                                        class="relative z-10 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-1 shadow-md"
                                    >
                                        <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                                            <button
                                                type="button"
                                                @click.stop="selectRoom('')"
                                                class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30"
                                                :class="!form.room_id ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-700 dark:text-slate-300'"
                                            >
                                                <span class="truncate">{{ __('pages.user_management.form.no_room') }}</span>
                                                <Check v-if="!form.room_id" class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                            </button>
                                            <button
                                                v-for="room in (rooms || [])"
                                                :key="room.id"
                                                type="button"
                                                @click.stop="selectRoom(room.id)"
                                                class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30"
                                                :class="form.room_id === room.id ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-700 dark:text-slate-300'"
                                            >
                                                <span class="truncate">{{ room.name }}</span>
                                                <Check v-if="form.room_id === room.id" class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="form.errors.room_id" class="text-xs text-red-500 mt-1">{{ form.errors.room_id }}</div>
                            </div>

                            <!-- 10. Telegram Chat ID -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Telegram Chat ID (Personal)</label>
                                <input
                                    v-model="form.telegram_chat_id"
                                    type="text"
                                    class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all duration-150"
                                    placeholder="Masukkan Chat ID Telegram (contoh: 987654321)"
                                />
                                <div v-if="form.errors.telegram_chat_id" class="text-xs text-red-500 mt-1">{{ form.errors.telegram_chat_id }}</div>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Dapatkan via bot pembantu @userinfobot di Telegram</p>
                            </div>
                        </div>

                        <!-- Fixed Bottom Footer (pinned outside scrollable body) -->
                        <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/50 flex-shrink-0">
                            <button
                                type="button"
                                @click="showModal = false"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150"
                            >
                                {{ __('pages.user_management.alerts.cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm rounded-xl transition duration-150 disabled:opacity-50 border-0 shadow-none"
                            >
                                {{ form.processing ? __('pages.user_management.alerts.saving') : __('Save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Permission Modal -->
        <Teleport to="body">
            <div v-if="showPermissionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm">
                <div class="w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 max-h-[90vh] flex flex-col">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 rounded-t-2xl flex-shrink-0">
                        <div>
                            <h3 class="text-base font-bold text-slate-955 dark:text-white">
                                Pengaturan Akses Halaman
                            </h3>
                            <p v-if="permissionUser" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ permissionUser.name }}
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400 ml-1">
                                    {{ permissionUser.role?.name ? __('roles.' + permissionUser.role.name) : '-' }}
                                </span>
                            </p>
                        </div>
                        <button type="button" @click="showPermissionModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
                        <!-- Mode indicator -->
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-2">
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors',
                                    permissionUseDefault
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/50'
                                        : 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50'
                                ]">
                                    {{ permissionUseDefault ? 'Menggunakan Default Role' : 'Custom Override' }}
                                </span>
                            </div>
                            <button
                                v-if="!permissionUseDefault"
                                @click="resetToDefault"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
                            >
                                <RotateCcw class="h-3 w-3" />
                                Reset ke Default
                            </button>
                        </div>

                        <!-- Permission groups -->
                        <div class="space-y-5">
                            <div v-for="group in allPermissionKeys" :key="group.group">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2.5 flex items-center gap-2">
                                    <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>
                                    {{ group.group }}
                                    <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label
                                        v-for="perm in group.permissions"
                                        :key="perm.key"
                                        :class="[
                                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl border cursor-pointer transition-all duration-150 select-none group',
                                            permissionChecked.includes(perm.key)
                                                ? 'bg-emerald-50/70 dark:bg-emerald-950/20 border-emerald-300 dark:border-emerald-800/50 shadow-sm'
                                                : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                                        ]"
                                    >
                                        <div class="relative flex items-center justify-center">
                                            <input
                                                type="checkbox"
                                                :checked="permissionChecked.includes(perm.key)"
                                                @change="togglePermission(perm.key)"
                                                class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-emerald-600 focus:ring-emerald-500 dark:bg-slate-800 cursor-pointer"
                                            />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <span :class="[
                                                'text-sm font-medium',
                                                permissionChecked.includes(perm.key)
                                                    ? 'text-slate-900 dark:text-white font-semibold'
                                                    : 'text-slate-600 dark:text-slate-400'
                                            ]">
                                                {{ perm.label }}
                                            </span>
                                        </div>
                                        <span
                                            v-if="isRoleDefault(perm.key)"
                                            class="flex-shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                                        >
                                            default
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/50 flex-shrink-0">
                        <button
                            type="button"
                            @click="showPermissionModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150"
                        >
                            Batal
                        </button>
                        <button
                            @click="savePermissions"
                            :disabled="permissionProcessing"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm rounded-xl transition duration-150 border-0 shadow-none disabled:opacity-50 flex items-center gap-2"
                        >
                            <KeyRound v-if="!permissionProcessing" class="h-3.5 w-3.5" />
                            {{ permissionProcessing ? 'Menyimpan...' : 'Simpan Akses' }}
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
