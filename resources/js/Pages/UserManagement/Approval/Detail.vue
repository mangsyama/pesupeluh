<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import {
    User,
    UserCheck,
    UserX,
    X,
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
    }
});

const { proxy } = getCurrentInstance();

const showPhotoModal = ref(false);

const form = useForm({
    role_id: props.targetUser.role_id || 11,
    supporting_unit_id: props.targetUser.supporting_unit_id || '',
    room_id: props.targetUser.room_id || '',
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

const submitApproval = () => {
    proxy.$swal({
        title: proxy.__('pages.user_management.alerts.are_you_sure'),
        text: proxy.__('pages.user_management.alerts.approve_warning').replace('{name}', props.targetUser.name),
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: proxy.__('pages.user_management.alerts.yes_approve'),
        cancelButtonText: proxy.__('pages.user_management.alerts.cancel')
    }).then((result) => {
        if (result.isConfirmed) {
            form.patch(route('users.approve', props.targetUser.uuid || props.targetUser.id));
        }
    });
};

const rejectUser = () => {
    proxy.$swal({
        title: proxy.__('pages.user_management.alerts.reject_confirm_title'),
        text: proxy.__('pages.user_management.alerts.reject_warning').replace('{name}', props.targetUser.name),
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: proxy.__('pages.user_management.alerts.yes_reject'),
        cancelButtonText: proxy.__('pages.user_management.alerts.cancel')
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('users.destroy', props.targetUser.uuid || props.targetUser.id));
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head :title="`Persetujuan Pendaftaran - ${targetUser.name}`" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">

            <!-- Premium Header Panel -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <UserCheck class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                Detail Persetujuan Pendaftar
                            </h2>
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase text-center leading-none bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300 border border-amber-200/50 dark:border-amber-500/20">
                                Menunggu Verifikasi
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                            Periksa kelengkapan data pendaftar sebelum menetapkan role akses serta penempatan unit/ruangan.
                        </p>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT CARD -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 space-y-8">

                    <!-- SECTION 1: PROFIL & DATA LENGKAP PENDAFTAR -->
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Data Profil Pendaftar
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Informasi akun dan data diri pendaftar yang perlu diverifikasi.</p>
                        </div>

                        <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:p-5">
                            <div class="flex flex-col sm:flex-row items-stretch gap-6">
                                <!-- Foto Pendaftar -->
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

                                <!-- Read-Only Fields Profil (Kolom-kolom Form) -->
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                                    <!-- Nama Lengkap -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Nama Lengkap
                                        </label>
                                        <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center uppercase">
                                            {{ targetUser.name || '-' }}
                                        </div>
                                    </div>

                                    <!-- NIP / Pegawai ID -->
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
                                        <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center break-all">
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

                    <!-- SECTION 2: FORM TETAPKAN PERAN DAN PENEMPATAN -->
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Tetapkan Peran Akses & Penempatan
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Atur hak akses peran serta unit/ruangan penempatan pendaftar.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Select Role -->
                            <div class="space-y-1.5 md:col-span-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Peran Akses Sistem <span class="text-red-400">*</span>
                                </label>
                                <SearchableSelect
                                    v-model="form.role_id"
                                    :options="roleOptions"
                                    :searchable="false"
                                    :absolute="false"
                                    value-key="id"
                                    label-key="name"
                                    placeholder="Pilih Peran Akses..."
                                />
                                <div v-if="form.errors.role_id" class="text-[10px] text-red-500 font-semibold mt-1">{{ form.errors.role_id }}</div>
                            </div>

                            <!-- Select Unit Penunjang -->
                            <div class="space-y-1.5 md:col-span-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Unit Penunjang
                                </label>
                                <SearchableSelect
                                    v-model="form.supporting_unit_id"
                                    :options="unitOptions"
                                    :searchable="true"
                                    :absolute="false"
                                    value-key="id"
                                    label-key="name"
                                    placeholder="Tanpa Unit Penunjang"
                                    search-placeholder="Cari unit penunjang..."
                                />
                                <div v-if="form.errors.supporting_unit_id" class="text-[10px] text-red-500 font-semibold mt-1">{{ form.errors.supporting_unit_id }}</div>
                            </div>

                            <!-- Select Ruangan -->
                            <div class="space-y-1.5 md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Penempatan Ruangan
                                </label>
                                <SearchableSelect
                                    v-model="form.room_id"
                                    :options="roomOptions"
                                    :searchable="true"
                                    :absolute="false"
                                    value-key="id"
                                    label-key="name"
                                    subtitle-key="location_floor"
                                    placeholder="Tanpa Ruangan"
                                    search-placeholder="Cari nama ruangan..."
                                />
                                <div v-if="form.errors.room_id" class="text-[10px] text-red-500 font-semibold mt-1">{{ form.errors.room_id }}</div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Actions -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-end gap-3">
                    <button 
                        type="button" 
                        @click="rejectUser"
                        class="w-1/2 sm:w-auto h-11 px-6 inline-flex items-center justify-center gap-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl transition duration-150 shadow-sm border-0"
                    >
                        <UserX class="h-4 w-4 shrink-0" />
                        <span class="sm:hidden">Tolak</span>
                        <span class="hidden sm:inline">Tolak Pendaftaran</span>
                    </button>
                    <button 
                        type="button" 
                        @click="submitApproval" 
                        :disabled="form.processing"
                        class="w-1/2 sm:w-auto h-11 px-6 inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 font-bold text-xs rounded-xl transition duration-150 shadow-sm border-0 disabled:opacity-50"
                    >
                        <UserCheck class="h-4 w-4 shrink-0" />
                        <span v-if="form.processing">{{ __('pages.user_management.alerts.saving') }}</span>
                        <template v-else>
                            <span class="sm:hidden">Setujui</span>
                            <span class="hidden sm:inline">Setujui & Verifikasi</span>
                        </template>
                    </button>
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
                        <h4 class="text-xs font-extrabold uppercase tracking-widest text-slate-400">Pasfoto Pendaftar</h4>
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
