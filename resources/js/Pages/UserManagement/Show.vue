<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    User,
    UserCheck,
    UserX,
    X,
    Edit2,
    Shield,
    KeyRound,
    Check
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
    allPermissionKeys: {
        type: Array,
        default: () => []
    }
});

const { proxy } = getCurrentInstance();

const formatRoomDetails = (room) => {
    if (!room) return '';
    const b = room.building_name ? (/^gedung/i.test(room.building_name.trim()) ? room.building_name.trim() : `Gedung ${room.building_name.trim()}`) : null;
    const f = room.location_floor ? (/^lantai/i.test(room.location_floor.trim()) || /^lt\./i.test(room.location_floor.trim()) ? room.location_floor.trim() : `Lantai ${room.location_floor.trim()}`) : null;
    return [b, f].filter(Boolean).join(' - ');
};

const showPhotoModal = ref(false);

const useRoleDefault = computed(() => {
    return props.targetUser.page_permissions === null || props.targetUser.page_permissions === undefined;
});

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

const activePermissions = computed(() => {
    if (useRoleDefault.value) {
        return getRoleDefaultPermissions(props.targetUser.role_id);
    }
    return Array.isArray(props.targetUser.page_permissions) ? props.targetUser.page_permissions : [];
});

const isPermissionChecked = (key) => {
    return activePermissions.value.includes(key);
};

const roleNameFormatted = computed(() => {
    if (props.targetUser.role?.name) {
        return proxy.__('roles.' + props.targetUser.role.name);
    }
    return 'Staf / Pelapor';
});

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
    <Head :title="`Detail Pengguna - ${targetUser.name}`" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">

                <!-- Header Panel (SAME LAYOUT & SIZE AS INDEX) -->
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <User class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Detail Data Pengguna
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Ringkasan profil, peran akses, dan hak akses halaman pengguna.
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Edit Data Pengguna (Standar Halaman Index) -->
                    <Link
                        :href="route('users.edit', targetUser.uuid || targetUser.id)"
                        class="w-full sm:w-auto h-10 inline-flex items-center justify-center gap-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 font-bold text-sm rounded-xl transition duration-150 whitespace-nowrap shadow-sm border-0 cursor-pointer"
                    >
                        <Edit2 class="h-4 w-4" />
                        <span>Edit Data Pengguna</span>
                    </Link>
                </div>

                <!-- CONTAINER 1: DATA PROFIL, AKUN, PERAN & STATUS PENGGUNA -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-4">
                <div class="p-6 space-y-8">

                    <!-- SEKSI 1.1: PROFIL & INFORMASI AKUN -->
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Data Profil & Akun Pengguna
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Informasi identitas pribadi dan login pengguna.</p>
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

                                <!-- Read-Only Fields Profil (Polos tanpa Icon di Label) -->
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                                    <!-- Nama Lengkap -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Nama Lengkap
                                        </label>
                                        <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                            {{ targetUser.name || '-' }}
                                        </div>
                                    </div>

                                    <!-- NIP -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            NIP / Pegawai ID
                                        </label>
                                        <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                            {{ targetUser.nip || '-' }}
                                        </div>
                                    </div>

                                    <!-- Username -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Username
                                        </label>
                                        <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                            {{ targetUser.username || '-' }}
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Email
                                        </label>
                                        <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                            {{ targetUser.email || '-' }}
                                        </div>
                                    </div>

                                    <!-- Nomor HP -->
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Nomor Telepon / WhatsApp
                                        </label>
                                        <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                            {{ targetUser.phone_number || '-' }}
                                        </div>
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
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Peran hak akses serta penempatan unit/ruangan kerja pengguna.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Select Role -->
                            <div class="space-y-1.5 md:col-span-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Peran Akses Sistem
                                </label>
                                <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-bold flex items-center">
                                    {{ roleNameFormatted }}
                                </div>
                            </div>

                            <!-- Select Unit Penunjang -->
                            <div class="space-y-1.5 md:col-span-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Unit Penunjang
                                </label>
                                <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                    {{ targetUser.supporting_unit?.name || 'Tanpa Unit Penunjang' }}
                                </div>
                            </div>

                            <!-- Select Ruangan -->
                            <div class="space-y-1.5 md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Penempatan Ruangan
                                </label>
                                <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                    {{ targetUser.room ? targetUser.room.name + (formatRoomDetails(targetUser.room) ? ' (' + formatRoomDetails(targetUser.room) + ')' : '') : 'Tanpa Ruangan' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 1.3: STATUS KEAKTIFAN AKUN -->
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Status Keaktifan Akun
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Status kelayakan akses login pengguna.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                :class="[
                                    'p-4 rounded-xl border flex items-start gap-3',
                                    targetUser.is_active
                                        ? 'border-emerald-600 bg-emerald-500/10 text-emerald-900 dark:text-emerald-300 font-extrabold'
                                        : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20 text-slate-600 dark:text-slate-400'
                                ]"
                            >
                                <UserCheck class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" />
                                <div class="space-y-0.5">
                                    <div class="text-xs font-bold uppercase tracking-wide">Aktif (Verified)</div>
                                    <div class="text-[11px] font-normal leading-relaxed opacity-80">Pengguna dapat login dan mengakses fitur sistem sesuai perannya.</div>
                                </div>
                            </div>

                            <div
                                :class="[
                                    'p-4 rounded-xl border flex items-start gap-3',
                                    !targetUser.is_active
                                        ? 'border-amber-600 bg-amber-500/10 text-amber-900 dark:text-amber-300 font-extrabold'
                                        : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20 text-slate-600 dark:text-slate-400'
                                ]"
                            >
                                <UserX class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
                                <div class="space-y-0.5">
                                    <div class="text-xs font-bold uppercase tracking-wide">Nonaktif / Suspended</div>
                                    <div class="text-[11px] font-normal leading-relaxed opacity-80">Akses login ditutup sementara oleh Administrator.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- CONTAINER 2: HAK AKSES HALAMAN SISTEM (READ-ONLY) -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-4">
                <div class="p-6 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Hak Akses Halaman Sistem
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Daftar menu & modul halaman yang aktif untuk pengguna ini.</p>
                        </div>
                        <span 
                            :class="[
                                'px-3.5 py-1.5 rounded-full text-[11px] font-extrabold border uppercase tracking-wider self-start sm:self-auto',
                                useRoleDefault 
                                    ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700' 
                                    : 'bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border-emerald-200/50'
                            ]"
                        >
                            {{ useRoleDefault ? 'Default Peran' : 'Kustomisasi Khusus' }}
                        </span>
                    </div>

                    <!-- Mode Alert Badge -->
                    <div v-if="useRoleDefault" class="p-4 rounded-xl bg-slate-100/70 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 text-xs flex items-center gap-2.5">
                        <Shield class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <span>Pengguna mengikuti hak akses bawaan dari perannya.</span>
                    </div>

                    <!-- Grouped Read-Only Permission Checkboxes -->
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
                                    :class="[
                                        'p-3 rounded-xl border transition-all text-xs font-semibold flex items-center justify-between gap-2 opacity-90 select-none',
                                        isPermissionChecked(perm.key)
                                            ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/40'
                                            : 'bg-slate-50/60 dark:bg-slate-950/20 text-slate-400 dark:text-slate-600 border-slate-200 dark:border-slate-800/60'
                                    ]"
                                >
                                    <span class="truncate">{{ perm.label }}</span>
                                    <div :class="['h-4 w-4 rounded flex items-center justify-center shrink-0 border', isPermissionChecked(perm.key) ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900']">
                                        <Check v-if="isPermissionChecked(perm.key)" class="h-3 w-3 stroke-[3]" />
                                    </div>
                                </div>
                            </div>
                        </div>
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
