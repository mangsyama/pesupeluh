<script setup>
import { ref, computed, getCurrentInstance, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    User,
    Check,
    ChevronDown,
    UserCheck,
    UserX,
    X,
    Search
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
    role_id: props.targetUser.role_id || 8,
    supporting_unit_id: props.targetUser.supporting_unit_id || '',
    room_id: props.targetUser.room_id || '',
});

// Dropdown States
const isRoleDropdownOpen = ref(false);
const isUnitDropdownOpen = ref(false);
const isRoomDropdownOpen = ref(false);
const roomSearchQuery = ref('');

const roleDropdownRef = ref(null);
const unitDropdownRef = ref(null);
const roomDropdownRef = ref(null);

const handleClickOutside = (event) => {
    if (isRoleDropdownOpen.value && roleDropdownRef.value && !roleDropdownRef.value.contains(event.target)) {
        isRoleDropdownOpen.value = false;
    }
    if (isUnitDropdownOpen.value && unitDropdownRef.value && !unitDropdownRef.value.contains(event.target)) {
        isUnitDropdownOpen.value = false;
    }
    if (isRoomDropdownOpen.value && roomDropdownRef.value && !roomDropdownRef.value.contains(event.target)) {
        isRoomDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Computed Labels
const selectedRoleLabel = computed(() => {
    if (!form.role_id) return '';
    const r = props.roles.find(item => item.id === form.role_id);
    return r ? proxy.__('roles.' + r.name) : '';
});

const selectedUnitLabel = computed(() => {
    if (!form.supporting_unit_id) return '';
    const u = props.supportingUnits.find(item => item.id === form.supporting_unit_id);
    return u ? u.name : '';
});

const selectedRoomLabel = computed(() => {
    if (!form.room_id) return '';
    const rm = props.rooms.find(item => item.id === form.room_id);
    return rm ? rm.name + (rm.location_floor ? ' (' + rm.location_floor + ')' : '') : '';
});

const filteredRooms = computed(() => {
    const list = props.rooms || [];
    const q = roomSearchQuery.value.trim().toLowerCase();
    if (!q) return list;
    return list.filter(rm =>
        (rm.name && rm.name.toLowerCase().includes(q)) ||
        (rm.location_floor && rm.location_floor.toLowerCase().includes(q))
    );
});

// Toggle Handlers
const toggleRoleDropdown = (event) => {
    event?.stopPropagation();
    isRoleDropdownOpen.value = !isRoleDropdownOpen.value;
    isUnitDropdownOpen.value = false;
    isRoomDropdownOpen.value = false;
};

const toggleUnitDropdown = (event) => {
    event?.stopPropagation();
    isUnitDropdownOpen.value = !isUnitDropdownOpen.value;
    isRoleDropdownOpen.value = false;
    isRoomDropdownOpen.value = false;
};

const toggleRoomDropdown = (event) => {
    event?.stopPropagation();
    isRoomDropdownOpen.value = !isRoomDropdownOpen.value;
    isRoleDropdownOpen.value = false;
    isUnitDropdownOpen.value = false;
    if (isRoomDropdownOpen.value) {
        roomSearchQuery.value = '';
    }
};

const selectRole = (id) => { form.role_id = id; isRoleDropdownOpen.value = false; };
const selectUnit = (id) => { form.supporting_unit_id = id; isUnitDropdownOpen.value = false; };
const selectRoom = (id) => { form.room_id = id; isRoomDropdownOpen.value = false; };

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
            form.patch(route('users.approve', props.targetUser.id), {
                onSuccess: () => {
                    proxy.$toast(proxy.__('pages.user_management.alerts.approve_success'), 'success');
                }
            });
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
            router.delete(route('users.destroy', props.targetUser.id), {
                onSuccess: () => {
                    proxy.$toast(proxy.__('pages.user_management.alerts.reject_success'), 'success');
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
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                        <UserCheck class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                Detail Persetujuan Pendaftar
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
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
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-5 sm:gap-6">
                                <!-- Foto Pendaftar -->
                                <div class="flex flex-col items-center shrink-0">
                                    <div
                                        class="h-36 w-36 sm:h-[142px] sm:w-[142px] aspect-square rounded-xl overflow-hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-center cursor-pointer relative group"
                                        @click="targetUser.profile_photo_path && (showPhotoModal = true)"
                                    >
                                        <img v-if="targetUser.profile_photo_path" :src="targetUser.profile_photo_path" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                        <User v-else class="h-12 w-12 text-slate-400" />
                                        <div v-if="targetUser.profile_photo_path" class="absolute inset-0 bg-slate-950/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-bold uppercase tracking-wider">Perbesar</div>
                                    </div>
                                </div>

                                <!-- Grid Informasi -->
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5 text-sm">
                                    <div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">Nama Lengkap</div>
                                        <div class="text-sm font-bold text-slate-800 dark:text-white uppercase leading-tight">{{ targetUser.name || '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">NIP</div>
                                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200 leading-tight">{{ targetUser.nip || '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">Username</div>
                                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200 leading-tight">{{ targetUser.username || '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">Email</div>
                                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200 leading-tight break-all">{{ targetUser.email || '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">Nomor HP</div>
                                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200 leading-tight">{{ targetUser.phone_number || '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">Tanggal Registrasi</div>
                                        <div class="text-sm font-medium text-slate-700 dark:text-slate-300 leading-tight">{{ formatDate(targetUser.created_at) }}</div>
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

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Select Role -->
                            <div class="space-y-1.5 relative" ref="roleDropdownRef">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Peran Akses Sistem <span class="text-red-400">*</span>
                                </label>
                                <button type="button" @click="toggleRoleDropdown"
                                    class="w-full h-11 px-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150"
                                >
                                    <span v-if="selectedRoleLabel" class="font-semibold text-slate-800 dark:text-slate-100 truncate">{{ selectedRoleLabel }}</span>
                                    <span v-else class="text-slate-400 dark:text-slate-500">Pilih Peran Akses...</span>
                                    <ChevronDown :class="['h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0 ml-2', isRoleDropdownOpen ? 'rotate-180 text-emerald-500' : '']" />
                                </button>
                                <div v-if="isRoleDropdownOpen" class="relative z-10 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-2 space-y-1 shadow-sm">
                                    <button v-for="r in roles" :key="r.id" type="button" @click.stop="selectRole(r.id)"
                                        class="w-full text-left px-3 py-2.5 rounded-lg text-sm transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30"
                                        :class="form.role_id === r.id ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-700 dark:text-slate-300 font-medium'"
                                    >
                                        <span class="truncate">{{ __('roles.' + r.name) }}</span>
                                        <Check v-if="form.role_id === r.id" class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0 ml-2" />
                                    </button>
                                </div>
                                <div v-if="form.errors.role_id" class="text-[10px] text-red-500 font-semibold mt-1">{{ form.errors.role_id }}</div>
                            </div>

                            <!-- Select Unit Penunjang -->
                            <div class="space-y-1.5 relative" ref="unitDropdownRef">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Unit Penunjang
                                </label>
                                <button type="button" @click="toggleUnitDropdown"
                                    class="w-full h-11 px-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150"
                                >
                                    <span v-if="selectedUnitLabel" class="text-slate-800 dark:text-slate-100 truncate font-medium">{{ selectedUnitLabel }}</span>
                                    <span v-else class="text-slate-400 dark:text-slate-500">Tanpa Unit Penunjang</span>
                                    <ChevronDown :class="['h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0 ml-2', isUnitDropdownOpen ? 'rotate-180 text-emerald-500' : '']" />
                                </button>
                                <div v-if="isUnitDropdownOpen" class="relative z-10 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-2 space-y-1 shadow-sm">
                                    <button type="button" @click.stop="selectUnit('')"
                                        class="w-full text-left px-3 py-2.5 rounded-lg text-sm transition-colors flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/60"
                                        :class="!form.supporting_unit_id ? 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                                    >
                                        <span class="truncate">Tanpa Unit Penunjang</span>
                                        <Check v-if="!form.supporting_unit_id" class="h-4 w-4 text-slate-500 shrink-0 ml-2" />
                                    </button>
                                    <button v-for="u in supportingUnits" :key="u.id" type="button" @click.stop="selectUnit(u.id)"
                                        class="w-full text-left px-3 py-2.5 rounded-lg text-sm transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30"
                                        :class="form.supporting_unit_id === u.id ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-700 dark:text-slate-300 font-medium'"
                                    >
                                        <span class="truncate">{{ u.name }}</span>
                                        <Check v-if="form.supporting_unit_id === u.id" class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0 ml-2" />
                                    </button>
                                </div>
                                <div v-if="form.errors.supporting_unit_id" class="text-[10px] text-red-500 font-semibold mt-1">{{ form.errors.supporting_unit_id }}</div>
                            </div>

                            <!-- Select Ruangan -->
                            <div class="space-y-1.5 relative" ref="roomDropdownRef">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Penempatan Ruangan
                                </label>
                                <button type="button" @click="toggleRoomDropdown"
                                    class="w-full h-11 px-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150"
                                >
                                    <span v-if="selectedRoomLabel" class="text-slate-800 dark:text-slate-100 truncate font-medium">{{ selectedRoomLabel }}</span>
                                    <span v-else class="text-slate-400 dark:text-slate-500">Tanpa Ruangan</span>
                                    <ChevronDown :class="['h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0 ml-2', isRoomDropdownOpen ? 'rotate-180 text-emerald-500' : '']" />
                                </button>
                                <div v-if="isRoomDropdownOpen" class="relative z-10 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-2 space-y-2 shadow-sm">
                                    <div class="relative">
                                        <Search class="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input v-model="roomSearchQuery" type="text" placeholder="Cari nama ruangan..."
                                            class="w-full h-9 pl-9 pr-3 text-sm bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                            @click.stop
                                        />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                                        <button type="button" @click.stop="selectRoom('')"
                                            class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/60"
                                            :class="!form.room_id ? 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                                        >
                                            <span class="truncate">Tanpa Ruangan</span>
                                            <Check v-if="!form.room_id" class="h-4 w-4 text-slate-500 shrink-0 ml-2" />
                                        </button>
                                        <div v-if="filteredRooms.length === 0" class="p-3 text-center text-sm text-slate-400 italic">Ruangan tidak ditemukan</div>
                                        <button v-else v-for="rm in filteredRooms" :key="rm.id" type="button" @click.stop="selectRoom(rm.id)"
                                            class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30"
                                            :class="form.room_id === rm.id ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-700 dark:text-slate-300 font-medium'"
                                        >
                                            <div>
                                                <span class="truncate font-semibold">{{ rm.name }}</span>
                                                <span v-if="rm.location_floor" class="text-xs text-slate-400 ml-1.5 font-normal">({{ rm.location_floor }})</span>
                                            </div>
                                            <Check v-if="form.room_id === rm.id" class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0 ml-2" />
                                        </button>
                                    </div>
                                </div>
                                <div v-if="form.errors.room_id" class="text-[10px] text-red-500 font-semibold mt-1">{{ form.errors.room_id }}</div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Actions -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-end gap-3">
                    <button type="button" @click="rejectUser"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-5 py-2.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-400 font-semibold text-xs rounded-xl border border-rose-200/60 dark:border-rose-900/50 transition duration-150"
                    >
                        <UserX class="h-4 w-4" />
                        <span>Tolak Pendaftaran</span>
                    </button>
                    <button type="button" @click="submitApproval" :disabled="form.processing"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs rounded-xl transition duration-150 shadow-sm disabled:opacity-50"
                    >
                        <UserCheck class="h-4 w-4" />
                        <span>{{ form.processing ? __('pages.user_management.alerts.saving') : 'Setujui & Verifikasi' }}</span>
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
