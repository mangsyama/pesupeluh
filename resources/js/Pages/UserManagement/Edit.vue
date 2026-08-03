<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import {
    User,
    UserCheck,
    UserX,
    Save,
    Shield,
    KeyRound,
    RotateCcw,
    Check,
    Edit2
} from '@lucide/vue';

const props = defineProps({
    targetUser: {
        type: Object,
        required: true
    },
    roles: {
        type: Array,
        default: () => []
    },
    rooms: {
        type: Array,
        default: () => []
    },
    supportingUnits: {
        type: Array,
        default: () => []
    },
    allPermissionKeys: {
        type: Array,
        default: () => []
    }
});

const { proxy } = getCurrentInstance();

const showPhotoModal = ref(false);

// ===== PROFILE FORM & DIRTY TRACKING =====
const initialProfileState = ref({
    name: props.targetUser.name || '',
    nip: props.targetUser.nip || '',
    username: props.targetUser.username || '',
    email: props.targetUser.email || '',
    phone_number: props.targetUser.phone_number || '',
    password: '',
    role_id: props.targetUser.role_id || 11,
    supporting_unit_id: props.targetUser.supporting_unit_id || '',
    room_id: props.targetUser.room_id || '',
    is_active: props.targetUser.is_active ? 1 : 0,
});

const profileForm = useForm({
    name: props.targetUser.name || '',
    nip: props.targetUser.nip || '',
    username: props.targetUser.username || '',
    email: props.targetUser.email || '',
    phone_number: props.targetUser.phone_number || '',
    password: '',
    role_id: props.targetUser.role_id || 11,
    supporting_unit_id: props.targetUser.supporting_unit_id || '',
    room_id: props.targetUser.room_id || '',
    is_active: props.targetUser.is_active ? 1 : 0,
});

const isProfileDirty = computed(() => {
    const init = initialProfileState.value;
    return (
        profileForm.name !== init.name ||
        profileForm.nip !== init.nip ||
        profileForm.username !== init.username ||
        profileForm.email !== init.email ||
        profileForm.phone_number !== init.phone_number ||
        (profileForm.password && profileForm.password.trim().length > 0) ||
        profileForm.role_id !== init.role_id ||
        (profileForm.supporting_unit_id || '') !== (init.supporting_unit_id || '') ||
        (profileForm.room_id || '') !== (init.room_id || '') ||
        profileForm.is_active !== init.is_active
    );
});

// ===== PERMISSIONS FORM & DIRTY TRACKING =====
const initialUseRoleDefault = props.targetUser.page_permissions === null || props.targetUser.page_permissions === undefined;
const initialPermissions = Array.isArray(props.targetUser.page_permissions) ? [...props.targetUser.page_permissions] : [];

const useRoleDefault = ref(initialUseRoleDefault);
const selectedPermissions = ref([...initialPermissions]);

const permissionForm = useForm({
    page_permissions: [...initialPermissions],
    use_role_default: initialUseRoleDefault,
});

// Helper for Role Default Permissions
const getRoleDefaultPermissions = (roleId) => {
    const role = (props.roles || []).find(r => Number(r.id) === Number(roleId));
    let perms = [];
    if (role && role.page_permissions) {
        perms = Array.isArray(role.page_permissions)
            ? [...role.page_permissions]
            : (typeof role.page_permissions === 'string' ? JSON.parse(role.page_permissions) : []);
    }
    if (!perms.includes('technicians.position')) perms.push('technicians.position');
    if ([1, 5].includes(Number(roleId)) && !perms.includes('service-management.working-hours')) {
        perms.push('service-management.working-hours');
    }
    if (Number(roleId) === 1) {
        if (!perms.includes('admin.qr-code.index')) perms.push('admin.qr-code.index');
        if (!perms.includes('admin.wa-gateway.index')) perms.push('admin.wa-gateway.index');
    }
    return perms;
};

const activeRoleDefaultPermissions = computed(() => {
    return getRoleDefaultPermissions(profileForm.role_id);
});

const isPermissionChecked = (key) => {
    if (useRoleDefault.value) {
        return activeRoleDefaultPermissions.value.includes(key);
    }
    return selectedPermissions.value.includes(key);
};

const isPermissionDirty = computed(() => {
    if (useRoleDefault.value !== initialUseRoleDefault) return true;
    if (useRoleDefault.value && initialUseRoleDefault) return false;

    const currentSorted = [...selectedPermissions.value].sort();
    const initSorted = [...initialPermissions].sort();

    if (currentSorted.length !== initSorted.length) return true;
    return currentSorted.some((val, idx) => val !== initSorted[idx]);
});

const roleOptions = computed(() => {
    return (props.roles || []).map(r => ({
        id: r.id,
        name: proxy.__('roles.' + r.name)
    }));
});

const unitOptions = computed(() => [
    { id: '', name: 'Tanpa Unit Penunjang' },
    ...(props.supportingUnits || [])
]);

const roomOptions = computed(() => [
    { id: '', name: 'Tanpa Ruangan' },
    ...(props.rooms || []).map(r => {
        const b = r.building_name ? (/^gedung/i.test(r.building_name.trim()) ? r.building_name.trim() : `Gedung ${r.building_name.trim()}`) : null;
        const f = r.location_floor ? (/^lantai/i.test(r.location_floor.trim()) || /^lt\./i.test(r.location_floor.trim()) ? r.location_floor.trim() : `Lantai ${r.location_floor.trim()}`) : null;
        const details = [b, f].filter(Boolean).join(' - ');
        return {
            ...r,
            location_floor: details
        };
    })
]);

const togglePermission = (key) => {
    if (useRoleDefault.value) {
        selectedPermissions.value = [...activeRoleDefaultPermissions.value];
        useRoleDefault.value = false;
    }
    const index = selectedPermissions.value.indexOf(key);
    if (index > -1) {
        selectedPermissions.value.splice(index, 1);
    } else {
        selectedPermissions.value.push(key);
    }
};

const resetToRoleDefault = () => {
    useRoleDefault.value = true;
    selectedPermissions.value = [];
};

// Submissions with Confirmation Dialogs
const submitProfileUpdate = () => {
    if (!isProfileDirty.value || profileForm.processing) return;

    proxy.$swal({
        title: 'Apakah Anda yakin?',
        text: `Simpan perubahan data profil & akun untuk ${props.targetUser.name}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Simpan Perubahan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            profileForm.put(route('users.update', props.targetUser.uuid || props.targetUser.id), {
                onSuccess: () => {
                    initialProfileState.value = {
                        name: profileForm.name,
                        nip: profileForm.nip,
                        username: profileForm.username,
                        email: profileForm.email,
                        phone_number: profileForm.phone_number,
                        password: '',
                        role_id: profileForm.role_id,
                        supporting_unit_id: profileForm.supporting_unit_id,
                        room_id: profileForm.room_id,
                        is_active: profileForm.is_active,
                    };
                    profileForm.password = '';
                    proxy.$toast('Data profil & akun pengguna berhasil diperbarui.', 'success');
                }
            });
        }
    });
};

const submitPermissionUpdate = () => {
    if (!isPermissionDirty.value || permissionForm.processing) return;

    proxy.$swal({
        title: 'Apakah Anda yakin?',
        text: `Simpan perubahan hak akses halaman untuk ${props.targetUser.name}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Simpan Hak Akses',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            permissionForm.use_role_default = useRoleDefault.value;
            permissionForm.page_permissions = selectedPermissions.value;

            permissionForm.put(route('users.update-permissions', props.targetUser.uuid || props.targetUser.id), {
                onSuccess: () => {
                    proxy.$toast('Hak akses halaman pengguna berhasil diperbarui.', 'success');
                }
            });
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <Head :title="`Edit Pengguna - ${targetUser.name}`" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">

                <!-- Header Panel (SAME LAYOUT & SIZE AS INDEX) -->
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Edit2 class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                    Edit Data & Hak Akses Pengguna
                                </h2>
                                <span 
                                    :class="[
                                        'inline-flex items-center justify-center px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase leading-none border',
                                        profileForm.is_active
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300 border-emerald-200/50'
                                            : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300 border-amber-200/50'
                                    ]"
                                >
                                    {{ profileForm.is_active ? 'Aktif (Verified)' : 'Nonaktif / Suspended' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Kelola profil, peran akses, dan hak akses halaman pengguna.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- CONTAINER 1: DATA PROFIL, AKUN, PERAN & STATUS PENGGUNA -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm mb-4">
                <div class="p-6 space-y-8">

                    <!-- SEKSI 1.1: PROFIL & INFORMASI AKUN -->
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Data Profil & Akun Pengguna
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Perbarui informasi identitas pribadi dan login pengguna.</p>
                        </div>

                        <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:p-5">
                            <div class="flex flex-col sm:flex-row items-stretch gap-6">
                                <!-- Foto Pengguna -->
                                <div class="flex flex-col items-center justify-between shrink-0 w-full sm:w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-sm">
                                    <div
                                        class="w-full flex-1 min-h-[160px] rounded-lg overflow-hidden bg-slate-50 dark:bg-slate-950/50 flex items-center justify-center cursor-pointer relative group"
                                        @click="targetUser.profile_photo_path && (showPhotoModal = true)"
                                    >
                                        <img v-if="targetUser.profile_photo_path" :src="targetUser.profile_photo_path" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                        <User v-else class="h-14 w-14 text-slate-400" />
                                        <div v-if="targetUser.profile_photo_path" class="absolute inset-0 bg-slate-950/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-bold uppercase tracking-wider">Perbesar</div>
                                    </div>
                                    <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 mt-2 text-center truncate w-full">Terdaftar: {{ formatDate(targetUser.created_at) }}</span>
                                </div>

                                <!-- Form Inputs Profil -->
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                                    <!-- Nama Lengkap -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Nama Lengkap <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="profileForm.name"
                                            type="text"
                                            class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                            placeholder="Masukkan Nama Lengkap..."
                                        />
                                        <div v-if="profileForm.errors.name" class="text-[10px] text-red-500 font-semibold">{{ profileForm.errors.name }}</div>
                                    </div>

                                    <!-- NIP -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            NIP / Pegawai ID <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="profileForm.nip"
                                            type="text"
                                            class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                            placeholder="Masukkan NIP..."
                                        />
                                        <div v-if="profileForm.errors.nip" class="text-[10px] text-red-500 font-semibold">{{ profileForm.errors.nip }}</div>
                                    </div>

                                    <!-- Username -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Username
                                        </label>
                                        <input
                                            v-model="profileForm.username"
                                            type="text"
                                            class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                            placeholder="Masukkan Username..."
                                        />
                                        <div v-if="profileForm.errors.username" class="text-[10px] text-red-500 font-semibold">{{ profileForm.errors.username }}</div>
                                    </div>

                                    <!-- Email -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Email <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="profileForm.email"
                                            type="email"
                                            class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                            placeholder="Masukkan Alamat Email..."
                                        />
                                        <div v-if="profileForm.errors.email" class="text-[10px] text-red-500 font-semibold">{{ profileForm.errors.email }}</div>
                                    </div>

                                    <!-- Nomor HP -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Nomor Telepon / WhatsApp
                                        </label>
                                        <input
                                            v-model="profileForm.phone_number"
                                            type="text"
                                            class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                            placeholder="08xxxxxxxxxx"
                                        />
                                        <div v-if="profileForm.errors.phone_number" class="text-[10px] text-red-500 font-semibold">{{ profileForm.errors.phone_number }}</div>
                                    </div>

                                    <!-- Password Baru -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Password Baru <span class="text-slate-400 font-normal">(Kosongkan jika tidak diubah)</span>
                                        </label>
                                        <input
                                            v-model="profileForm.password"
                                            type="password"
                                            class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                            placeholder="Minimal 6 karakter..."
                                        />
                                        <div v-if="profileForm.errors.password" class="text-[10px] text-red-500 font-semibold">{{ profileForm.errors.password }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 1.2: PERAN AKSES & PENEMPATAN -->
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Peran Akses Sistem & Penempatan
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Atur hak akses peran serta penempatan unit/ruangan kerja pengguna.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Select Role -->
                            <div class="space-y-1.5 md:col-span-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Peran Akses Sistem <span class="text-red-400">*</span>
                                </label>
                                <SearchableSelect
                                    v-model="profileForm.role_id"
                                    :options="roleOptions"
                                    :searchable="false"
                                    :absolute="false"
                                    value-key="id"
                                    label-key="name"
                                    placeholder="Pilih Peran Akses..."
                                />
                                <div v-if="profileForm.errors.role_id" class="text-[10px] text-red-500 font-semibold mt-1">{{ profileForm.errors.role_id }}</div>
                            </div>

                            <!-- Select Unit Penunjang -->
                            <div class="space-y-1.5 md:col-span-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Unit Penunjang
                                </label>
                                <SearchableSelect
                                    v-model="profileForm.supporting_unit_id"
                                    :options="unitOptions"
                                    :searchable="true"
                                    :absolute="false"
                                    value-key="id"
                                    label-key="name"
                                    placeholder="Tanpa Unit Penunjang"
                                    search-placeholder="Cari unit penunjang..."
                                />
                                <div v-if="profileForm.errors.supporting_unit_id" class="text-[10px] text-red-500 font-semibold mt-1">{{ profileForm.errors.supporting_unit_id }}</div>
                            </div>

                            <!-- Select Ruangan -->
                            <div class="space-y-1.5 md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Penempatan Ruangan
                                </label>
                                <SearchableSelect
                                    v-model="profileForm.room_id"
                                    :options="roomOptions"
                                    :searchable="true"
                                    :absolute="false"
                                    value-key="id"
                                    label-key="name"
                                    subtitle-key="location_floor"
                                    placeholder="Tanpa Ruangan"
                                    search-placeholder="Cari nama ruangan..."
                                />
                                <div v-if="profileForm.errors.room_id" class="text-[10px] text-red-500 font-semibold mt-1">{{ profileForm.errors.room_id }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 1.3: STATUS KEAKTIFAN AKUN -->
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Status Keaktifan Akun
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Atur kelayakan akses login pengguna ke dalam sistem.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button
                                type="button"
                                @click="profileForm.is_active = 1"
                                :class="[
                                    'p-4 rounded-xl border transition-all text-left flex items-start gap-3 cursor-pointer',
                                    profileForm.is_active === 1
                                        ? 'border-emerald-600 bg-emerald-500/10 text-emerald-900 dark:text-emerald-300 font-extrabold'
                                        : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20 text-slate-600 dark:text-slate-400'
                                ]"
                            >
                                <UserCheck class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" />
                                <div class="space-y-0.5">
                                    <div class="text-xs font-bold uppercase tracking-wide">Aktif (Verified)</div>
                                    <div class="text-[11px] font-normal leading-relaxed opacity-80">Pengguna dapat login dan mengakses fitur sistem sesuai perannya.</div>
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="profileForm.is_active = 0"
                                :class="[
                                    'p-4 rounded-xl border transition-all text-left flex items-start gap-3 cursor-pointer',
                                    profileForm.is_active === 0
                                        ? 'border-amber-600 bg-amber-500/10 text-amber-900 dark:text-amber-300 font-extrabold'
                                        : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20 text-slate-600 dark:text-slate-400'
                                ]"
                            >
                                <UserX class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
                                <div class="space-y-0.5">
                                    <div class="text-xs font-bold uppercase tracking-wide">Nonaktif / Suspended</div>
                                    <div class="text-[11px] font-normal leading-relaxed opacity-80">Akses login ditutup sementara. Pengguna tidak dapat menggunakan sistem.</div>
                                </div>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Footer Actions Card 1 -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between gap-3">
                    <span class="text-xs font-medium text-slate-400 dark:text-slate-500">
                        {{ isProfileDirty ? 'Ada perubahan data profil yang belum disimpan.' : 'Tidak ada perubahan pada data profil.' }}
                    </span>
                    <button 
                        type="button" 
                        @click="submitProfileUpdate" 
                        :disabled="!isProfileDirty || profileForm.processing"
                        :class="[
                            'px-6 py-2.5 inline-flex items-center justify-center gap-2 font-bold text-xs rounded-xl transition duration-150 shadow-sm border-0 cursor-pointer',
                            isProfileDirty && !profileForm.processing
                                ? 'bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900'
                                : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-600 opacity-50 cursor-not-allowed'
                        ]"
                    >
                        <Save class="h-4 w-4 shrink-0" />
                        <span>{{ profileForm.processing ? 'Menyimpan...' : 'Simpan Profil & Akun' }}</span>
                    </button>
                </div>
            </div>

            <!-- CONTAINER 2: HAK AKSES HALAMAN SISTEM (PERMISSIONS OVERRIDE) -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-4">
                <div class="p-6 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Hak Akses Halaman Sistem
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Sesuaikan menu & modul halaman yang dapat dibuka secara spesifik oleh pengguna ini.</p>
                        </div>

                        <button
                            type="button"
                            @click="resetToRoleDefault"
                            :class="[
                                'px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition cursor-pointer border',
                                useRoleDefault
                                    ? 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-transparent cursor-default'
                                    : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-white/10 dark:text-white border-emerald-200/50'
                            ]"
                        >
                            <RotateCcw class="h-3.5 w-3.5" />
                            <span>{{ useRoleDefault ? 'Menggunakan Default Peran' : 'Reset ke Default Peran' }}</span>
                        </button>
                    </div>

                    <!-- Mode Alert Badge -->
                    <div v-if="useRoleDefault" class="p-4 rounded-xl bg-slate-100/70 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 text-xs flex items-center gap-2.5">
                        <Shield class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <span>Saat ini pengguna mengikuti hak akses bawaan dari perannya. Centang opsi di bawah jika ingin mengkustomisasi secara khusus.</span>
                    </div>

                    <!-- Grouped Permission Checkboxes -->
                    <div class="space-y-6 pt-2">
                        <div v-for="group in allPermissionKeys" :key="group.group" class="space-y-2.5">
                            <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide flex items-center gap-1.5">
                                <KeyRound class="h-3.5 w-3.5 text-emerald-600 dark:text-white" />
                                <span>{{ group.group }}</span>
                            </h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5">
                                <div
                                    v-for="perm in group.permissions"
                                    :key="perm.key"
                                    @click="togglePermission(perm.key)"
                                    :class="[
                                        'p-3 rounded-xl border transition-all text-xs font-semibold flex items-center justify-between gap-2 cursor-pointer select-none',
                                        isPermissionChecked(perm.key)
                                            ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/40'
                                            : 'bg-slate-50/60 dark:bg-slate-950/20 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:border-slate-300'
                                    ]"
                                >
                                    <span class="truncate">{{ perm.label }}</span>
                                    <div :class="['h-4 w-4 rounded flex items-center justify-center shrink-0 border transition-all', isPermissionChecked(perm.key) ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900']">
                                        <Check v-if="isPermissionChecked(perm.key)" class="h-3 w-3 stroke-[3]" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Actions Card 2 -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between gap-3">
                    <span class="text-xs font-medium text-slate-400 dark:text-slate-500">
                        {{ isPermissionDirty ? 'Ada perubahan hak akses yang belum disimpan.' : 'Tidak ada perubahan pada hak akses.' }}
                    </span>
                    <div class="flex items-center gap-3">
                        <button 
                            type="button" 
                            @click="resetToRoleDefault"
                            :disabled="useRoleDefault"
                            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-xs rounded-xl transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Reset
                        </button>
                        <button 
                            type="button" 
                            @click="submitPermissionUpdate" 
                            :disabled="!isPermissionDirty || permissionForm.processing"
                            :class="[
                                'px-6 py-2.5 inline-flex items-center justify-center gap-2 font-bold text-xs rounded-xl transition duration-150 shadow-sm border-0 cursor-pointer',
                                isPermissionDirty && !permissionForm.processing
                                    ? 'bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900'
                                    : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-600 opacity-50 cursor-not-allowed'
                            ]"
                        >
                            <KeyRound class="h-4 w-4 shrink-0" />
                            <span>{{ permissionForm.processing ? 'Menyimpan...' : 'Simpan Hak Akses' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            </div>
        </div>

        <!-- Photo Zoom Modal -->
        <Teleport to="body">
            <div v-if="showPhotoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm" @click="showPhotoModal = false">
                <div class="relative max-w-sm w-full bg-white dark:bg-slate-900 p-4 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800" @click.stop>
                    <button type="button" @click="showPhotoModal = false" class="absolute top-3 right-3 p-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 rounded-full text-slate-500 transition">
                        <X class="h-4 w-4" />
                    </button>
                    <div class="text-center mb-3">
                        <h4 class="text-xs font-extrabold uppercase tracking-widest text-slate-400">Pasfoto Pengguna</h4>
                    </div>
                    <div class="w-full rounded-xl overflow-hidden border border-slate-100 dark:border-slate-800">
                        <img :src="targetUser.profile_photo_path" class="w-full h-auto object-contain" />
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
