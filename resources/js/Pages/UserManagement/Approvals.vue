<script setup>
import { ref, getCurrentInstance, computed } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ShieldAlert, UserCheck, Trash2, Search, X, Calendar, Phone, Mail, User, Eye } from '@lucide/vue';

const props = defineProps({
    users: {
        type: Array,
        required: true
    },
    roles: {
        type: Array,
        required: true
    },
    rooms: {
        type: Array,
        required: true
    },
    supportingUnits: {
        type: Array,
        required: true
    }
});

const { proxy } = getCurrentInstance();

const searchQuery = ref('');
const showApprovalModal = ref(false);
const showDetailModal = ref(false);
const activePhotoUrl = ref(null);
const showPhotoModal = ref(false);

const selectedUser = ref(null);
const detailUser = ref(null);

const openDetailModal = (user) => {
    detailUser.value = user;
    showDetailModal.value = true;
};

const form = useForm({
    role_id: 8, // Default to REPORTER
    room_id: '',
    supporting_unit_id: '',
});

const filteredUsers = computed(() => {
    if (!searchQuery.value.trim()) return props.users;
    const query = searchQuery.value.toLowerCase();
    return props.users.filter(u => 
        u.name.toLowerCase().includes(query) || 
        u.email.toLowerCase().includes(query) ||
        (u.nip && u.nip.toLowerCase().includes(query))
    );
});

const viewPhoto = (user) => {
    activePhotoUrl.value = user.profile_photo_path;
    showPhotoModal.value = true;
};

const openApprovalModal = (user) => {
    selectedUser.value = user;
    form.reset();
    form.clearErrors();
    form.role_id = user.role_id || 8;
    form.room_id = user.room_id || '';
    form.supporting_unit_id = user.supporting_unit_id || '';
    showApprovalModal.value = true;
};

const submitApproval = () => {
    proxy.$swal({
        title: proxy.__('pages.user_management.alerts.are_you_sure'),
        text: proxy.__('pages.user_management.alerts.approve_warning').replace('{name}', selectedUser.value.name),
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        confirmButtonText: proxy.__('pages.user_management.alerts.yes_approve'),
        cancelButtonText: proxy.__('pages.user_management.alerts.cancel')
    }).then((result) => {
        if (result.isConfirmed) {
            form.patch(route('users.approve', selectedUser.value.id), {
                onSuccess: () => {
                    showApprovalModal.value = false;
                    proxy.$toast(proxy.__('pages.user_management.alerts.approve_success'), 'success');
                }
            });
        }
    });
};

const rejectUser = (user) => {
    proxy.$swal({
        title: proxy.__('pages.user_management.alerts.reject_confirm_title'),
        text: proxy.__('pages.user_management.alerts.reject_warning').replace('{name}', user.name),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: proxy.__('pages.user_management.alerts.yes_reject'),
        cancelButtonText: proxy.__('pages.user_management.alerts.cancel')
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('users.destroy', user.id), {
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
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head :title="__('pages.user_management.approval_title')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                <!-- Premium Header Panel -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="space-y-1">
                        <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight flex items-center gap-2">
                            {{ __('pages.user_management.approval_title') }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                            {{ __('pages.user_management.approval_desc') }}
                        </p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-shrink-0">
                        <!-- Search Box -->
                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                :placeholder="__('pages.user_management.search_all_placeholder')" 
                                class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150 shadow-none"
                            />
                        </div>
                    </div>
                </div>

                <!-- List / Table -->
                <div class="overflow-x-auto bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                <th class="px-6 py-4">{{ __('pages.user_management.table.name_email') }}</th>
                                <th class="px-6 py-4">{{ __('pages.user_management.table.nip') }}</th>
                                <th class="px-6 py-4">{{ __('pages.user_management.table.phone') }}</th>
                                <th class="px-6 py-4">{{ __('pages.user_management.table.reg_time') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('pages.user_management.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
                                    {{ __('pages.user_management.table.empty_approvals') }}
                                </td>
                            </tr>
                            <tr 
                                v-else
                                v-for="user in filteredUsers" 
                                :key="user.id"
                                class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors duration-150"
                            >
                                <!-- Nama / Email -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-300">
                                            <img 
                                                v-if="user.profile_photo_path" 
                                                :src="user.profile_photo_path" 
                                                class="h-full w-full object-cover cursor-zoom-in" 
                                                @click="viewPhoto(user)" 
                                                :title="__('pages.user_management.table.zoom_photo')" 
                                            />
                                            <User v-else class="h-4.5 w-4.5 text-slate-500" />
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-950 dark:text-white">
                                                {{ user.name }}
                                            </div>
                                            <div class="text-xs text-slate-400 dark:text-slate-500">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- NIP -->
                                <td class="px-6 py-4 text-slate-650 dark:text-slate-400 text-xs">
                                    {{ user.nip || '-' }}
                                </td>
                                
                                <!-- Nomor HP -->
                                <td class="px-6 py-4 text-slate-650 dark:text-slate-400 text-xs">
                                    {{ user.phone_number || '-' }}
                                </td>

                                <!-- Registered Time -->
                                <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                        {{ formatDate(user.created_at) }}
                                    </div>
                                </td>
                                
                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Tombol Lihat Detail -->
                                        <button
                                            @click="openDetailModal(user)"
                                            class="h-9 px-3 inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-semibold text-xs rounded-xl transition duration-150 whitespace-nowrap"
                                            title="Lihat Detail"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                            Detail
                                        </button>
                                        <!-- Tombol Setujui -->
                                        <button 
                                            @click="openApprovalModal(user)"
                                            class="h-9 px-3 inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs rounded-xl transition duration-150 whitespace-nowrap"
                                            :title="__('pages.user_management.alerts.approve_action')"
                                        >
                                            <UserCheck class="h-3.5 w-3.5" />
                                            {{ __('pages.user_management.alerts.approve_action') }}
                                        </button>
                                        <!-- Tombol Tolak -->
                                        <button 
                                            @click="rejectUser(user)"
                                            class="h-9 px-3 inline-flex items-center gap-1.5 bg-slate-100 hover:bg-red-50 hover:text-red-600 dark:bg-slate-800 dark:hover:bg-red-950/40 dark:hover:text-red-400 text-slate-600 dark:text-slate-300 font-semibold text-xs rounded-xl transition duration-150"
                                            :title="__('pages.user_management.alerts.reject_action')"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                            {{ __('pages.user_management.alerts.reject_action') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            <!-- Approval Modal (Detail user + Set role & placements) -->
            <Teleport to="body">
                <div v-if="showApprovalModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm">
                    <div class="w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden transition-all duration-300">

                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-900/50">
                            <h3 class="text-base font-bold text-slate-950 dark:text-white flex items-center gap-2">
                                <UserCheck class="h-5 w-5 text-indigo-500" />
                                {{ __('pages.user_management.alerts.approve_confirm_title') }}
                            </h3>
                            <button type="button" @click="showApprovalModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="p-6 space-y-5 max-h-[80vh] overflow-y-auto">

                            <!-- ═══ SECTION 1: Detail Data Pendaftar ═══ -->
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3">Data Pendaftar</div>
                                <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 rounded-xl p-4">
                                    <div class="flex items-start gap-4">
                                        <!-- Foto Pasfoto -->
                                        <div
                                            class="h-20 w-16 rounded-xl overflow-hidden bg-slate-200 dark:bg-slate-700 flex items-center justify-center flex-shrink-0 border border-slate-200 dark:border-slate-700 cursor-pointer"
                                            @click="selectedUser?.profile_photo_path && viewPhoto(selectedUser)"
                                            :title="selectedUser?.profile_photo_path ? __('pages.user_management.table.zoom_photo') : ''"
                                        >
                                            <img
                                                v-if="selectedUser?.profile_photo_path"
                                                :src="selectedUser.profile_photo_path"
                                                class="h-full w-full object-cover"
                                            />
                                            <User v-else class="h-7 w-7 text-slate-400" />
                                        </div>

                                        <!-- Info Grid -->
                                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2.5 text-sm">
                                            <div>
                                                <div class="text-xs text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">Nama Lengkap</div>
                                                <div class="font-bold text-slate-900 dark:text-white">{{ selectedUser?.name ?? '-' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">{{ __('pages.user_management.table.nip') }}</div>
                                                <div class="font-mono text-slate-800 dark:text-slate-200">{{ selectedUser?.nip ?? '-' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">Email</div>
                                                <div class="text-slate-700 dark:text-slate-300 break-all">{{ selectedUser?.email ?? '-' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">{{ __('pages.user_management.table.phone') }}</div>
                                                <div class="text-slate-700 dark:text-slate-300">{{ selectedUser?.phone_number ?? '-' }}</div>
                                            </div>
                                            <div class="sm:col-span-2">
                                                <div class="text-xs text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">{{ __('pages.user_management.table.reg_time') }}</div>
                                                <div class="text-slate-600 dark:text-slate-400 text-xs">{{ formatDate(selectedUser?.created_at) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══ SECTION 2: Form Penugasan ═══ -->
                            <form @submit.prevent="submitApproval" class="space-y-4">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Tetapkan Akses & Penempatan</div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Role -->
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.specific_role') }} <span class="text-red-400">*</span></label>
                                        <select
                                            v-model="form.role_id"
                                            required
                                            class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                        >
                                            <option v-for="role in roles" :key="role.id" :value="role.id">
                                                {{ role.name }}
                                            </option>
                                        </select>
                                        <div v-if="form.errors.role_id" class="text-xs text-red-500 mt-1">{{ form.errors.role_id }}</div>
                                    </div>

                                    <!-- Unit Penunjang -->
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.supporting_unit') }}</label>
                                        <select
                                            v-model="form.supporting_unit_id"
                                            class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                        >
                                            <option value="">{{ __('pages.user_management.form.no_unit') }}</option>
                                            <option v-for="unit in supportingUnits" :key="unit.id" :value="unit.id">
                                                {{ unit.name }}
                                            </option>
                                        </select>
                                        <div v-if="form.errors.supporting_unit_id" class="text-xs text-red-500 mt-1">{{ form.errors.supporting_unit_id }}</div>
                                    </div>

                                    <!-- Ruangan -->
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.room_placeholder') }}</label>
                                        <select
                                            v-model="form.room_id"
                                            class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                        >
                                            <option value="">{{ __('pages.user_management.form.no_room') }}</option>
                                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                                {{ room.name }}
                                            </option>
                                        </select>
                                        <div v-if="form.errors.room_id" class="text-xs text-red-500 mt-1">{{ form.errors.room_id }}</div>
                                    </div>
                                </div>

                                <!-- Modal Actions -->
                                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 mt-2">
                                    <button
                                        type="button"
                                        @click="showApprovalModal = false"
                                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150"
                                    >
                                        {{ __('pages.user_management.alerts.cancel') }}
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm rounded-xl transition duration-150 shadow-sm disabled:opacity-50 flex items-center gap-2"
                                    >
                                        <UserCheck class="h-4 w-4" />
                                        {{ form.processing ? __('pages.user_management.alerts.saving') : __('pages.user_management.alerts.approve_btn') }}
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- DETAIL USER MODAL (Read-only) -->
            <Teleport to="body">
                <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" @click.self="showDetailModal = false">
                    <div class="w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-900/50">
                            <h3 class="text-base font-bold text-slate-950 dark:text-white flex items-center gap-2">
                                <Eye class="h-5 w-5 text-slate-400" />
                                Detail Data Pendaftar
                            </h3>
                            <button type="button" @click="showDetailModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6 space-y-5">
                            <!-- Foto + Info utama -->
                            <div class="flex items-start gap-5">
                                <!-- Foto -->
                                <div
                                    class="h-24 w-[72px] rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center flex-shrink-0 cursor-pointer"
                                    @click="detailUser?.profile_photo_path && viewPhoto(detailUser)"
                                    :title="detailUser?.profile_photo_path ? __('pages.user_management.table.zoom_photo') : 'Tidak ada foto'"
                                >
                                    <img v-if="detailUser?.profile_photo_path" :src="detailUser.profile_photo_path" class="h-full w-full object-cover" />
                                    <User v-else class="h-8 w-8 text-slate-400" />
                                </div>

                                <!-- Nama + Badge role -->
                                <div class="flex-1">
                                    <div class="text-lg font-extrabold text-slate-900 dark:text-white leading-tight">{{ detailUser?.name ?? '-' }}</div>
                                    <div class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ detailUser?.email ?? '-' }}</div>
                                    <div class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                                        {{ detailUser?.role?.name ?? 'Belum ditentukan' }}
                                    </div>
                                </div>
                            </div>

                            <!-- Grid Info Lengkap -->
                            <div class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 rounded-xl p-4">
                                <div>
                                    <div class="text-xs text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">NIP</div>
                                    <div class="font-mono text-sm text-slate-800 dark:text-slate-200">{{ detailUser?.nip ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">No. HP</div>
                                    <div class="text-sm text-slate-800 dark:text-slate-200">{{ detailUser?.phone_number ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">Ruangan</div>
                                    <div class="text-sm text-slate-800 dark:text-slate-200">{{ detailUser?.room?.name ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">Unit Penunjang</div>
                                    <div class="text-sm text-slate-800 dark:text-slate-200">{{ detailUser?.supporting_unit?.name ?? '-' }}</div>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-xs text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">Tanggal Daftar</div>
                                    <div class="text-sm text-slate-700 dark:text-slate-300">{{ formatDate(detailUser?.created_at) }}</div>
                                </div>
                            </div>

                            <!-- Footer actions -->
                            <div class="flex justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="showDetailModal = false"
                                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150"
                                >
                                    Tutup
                                </button>
                                <button
                                    type="button"
                                    @click="() => { showDetailModal = false; openApprovalModal(detailUser); }"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm rounded-xl transition duration-150 flex items-center gap-2"
                                >
                                    <UserCheck class="h-4 w-4" />
                                    Lanjut ke Persetujuan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>


            <Teleport to="body">
                <div v-if="showPhotoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm" @click="showPhotoModal = false">
                    <div class="relative max-w-sm w-full bg-white dark:bg-slate-900 p-3 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800" @click.stop>
                        <button type="button" @click="showPhotoModal = false" class="absolute top-2 right-2 p-1.5 bg-slate-100 dark:bg-slate-850 hover:bg-slate-200 rounded-full text-slate-500 transition">
                            <X class="h-4.5 w-4.5" />
                        </button>
                        <div class="text-center mb-3">
                            <h4 class="text-xs font-extrabold uppercase tracking-widest text-slate-400">{{ __('pages.user_management.table.physical_verification') }}</h4>
                        </div>
                        <div class="w-full aspect-[3/4] rounded-xl overflow-hidden border border-slate-100 dark:border-slate-800">
                            <img :src="activePhotoUrl" class="w-full h-full object-cover" />
                        </div>
                    </div>
                </div>
            </Teleport>
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