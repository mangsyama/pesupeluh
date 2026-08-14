<script setup>
import { ref, computed, watch, getCurrentInstance, onMounted, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Edit2, Trash2, X, ChevronLeft, ChevronRight, MapPin } from '@lucide/vue';

const props = defineProps({
    rooms: {
        type: Array,
        default: () => []
    },
    searchQuery: {
        type: String,
        default: ''
    }
});

const { proxy } = getCurrentInstance();

const showRoomModal = ref(false);
const isEditingRoom = ref(false);

const handleEscapeKeyRoom = (e) => {
    if (e.key === 'Escape' && showRoomModal.value) {
        showRoomModal.value = false;
    }
};

const handlePopStateRoom = () => {
    if (showRoomModal.value) {
        showRoomModal.value = false;
    }
};

let pushHistoryFlagRoom = false;

watch(showRoomModal, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', handleEscapeKeyRoom);
        window.addEventListener('popstate', handlePopStateRoom);
        try {
            window.history.pushState({ modalOpen: true }, '');
            pushHistoryFlagRoom = true;
        } catch (e) {
            // ignore
        }
    } else {
        document.body.style.overflow = '';
        window.removeEventListener('keydown', handleEscapeKeyRoom);
        window.removeEventListener('popstate', handlePopStateRoom);

        if (pushHistoryFlagRoom && window.history.state && window.history.state.modalOpen) {
            pushHistoryFlagRoom = false;
            try {
                window.history.back();
            } catch (e) {
                // ignore
            }
        } else {
            pushHistoryFlagRoom = false;
        }
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleEscapeKeyRoom);
    window.removeEventListener('popstate', handlePopStateRoom);
    document.body.style.overflow = '';
});

const roomForm = useForm({
    id: null,
    name: '',
    building_name: '',
    location_floor: ''
});

const filteredRooms = computed(() => {
    const list = props.rooms || [];
    if (!props.searchQuery.trim()) return list;
    const query = props.searchQuery.toLowerCase();
    return list.filter(room => 
        room.name.toLowerCase().includes(query) || 
        (room.building_name && room.building_name.toLowerCase().includes(query)) ||
        (room.location_floor && room.location_floor.toLowerCase().includes(query))
    );
});

const currentPage = ref(1);
const itemsPerPage = ref(10);

const totalCount = computed(() => filteredRooms.value.length);
const lastPage = computed(() => Math.ceil(totalCount.value / itemsPerPage.value) || 1);
const fromCount = computed(() => totalCount.value === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1);
const toCount = computed(() => Math.min(currentPage.value * itemsPerPage.value, totalCount.value));

const paginatedRooms = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredRooms.value.slice(start, start + itemsPerPage.value);
});

const hasPrev = computed(() => currentPage.value > 1);
const hasNext = computed(() => currentPage.value < lastPage.value);

const goToPrev = () => { if (currentPage.value > 1) currentPage.value--; };
const goToNext = () => { if (currentPage.value < lastPage.value) currentPage.value++; };

watch(() => props.searchQuery, () => {
    currentPage.value = 1;
});

const openAddRoomModal = () => {
    isEditingRoom.value = false;
    roomForm.reset();
    roomForm.clearErrors();
    showRoomModal.value = true;
};

const openEditRoomModal = (room) => {
    isEditingRoom.value = true;
    roomForm.clearErrors();
    roomForm.id = room.id;
    roomForm.name = room.name;
    roomForm.building_name = room.building_name || '';
    roomForm.location_floor = room.location_floor || '';
    showRoomModal.value = true;
};

const submitRoomForm = () => {
    if (isEditingRoom.value) {
        roomForm.put(route('service-management.rooms.update', roomForm.id), {
            onSuccess: () => {
                showRoomModal.value = false;
            }
        });
    } else {
        roomForm.post(route('service-management.rooms.store'), {
            onSuccess: () => {
                showRoomModal.value = false;
                roomForm.reset();
            }
        });
    }
};

const deleteRoom = (room) => {
    proxy.$swal({
        title: proxy.__('pages.service_management.rooms.confirm_delete_title'),
        text: proxy.__('pages.service_management.rooms.confirm_delete_text').replace('{name}', room.name),
        icon: 'error',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: proxy.__('global.yes_delete'),
        cancelButtonText: proxy.__('global.cancel')
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('service-management.rooms.destroy', room.id), {
                onSuccess: () => {
                    proxy.$toast(proxy.__('pages.service_management.rooms.toast_deleted'), 'success');
                }
            });
        }
    });
};

defineExpose({
    openAddModal: openAddRoomModal
});
</script>

<template>
    <div class="space-y-4">
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <!-- Desktop Table View (>= md) -->
            <div class="hidden md:block overflow-x-auto rounded-b-2xl">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                            <th class="px-6 py-4">{{ __('pages.service_management.rooms.table_name') }}</th>
                            <th class="px-6 py-4">Gedung</th>
                            <th class="px-6 py-4">{{ __('pages.service_management.rooms.table_floor') }}</th>
                            <th class="px-6 py-4 text-right">{{ __('pages.service_management.rooms.table_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                        <tr v-if="filteredRooms.length === 0">
                            <td colspan="4" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('pages.service_management.rooms.empty_data') }}</td>
                        </tr>
                        <tr 
                            v-else
                            v-for="room in paginatedRooms" 
                            :key="room.id"
                            class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors duration-150"
                        >
                            <td class="px-6 py-4 font-semibold text-slate-955 dark:text-white">{{ room.name }}</td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ room.building_name || '-' }}</td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ room.location_floor || '-' }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button 
                                        @click="openEditRoomModal(room)" 
                                        class="p-2 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 transition duration-150"
                                        :title="__('Edit')"
                                    >
                                        <Edit2 class="h-3.5 w-3.5" />
                                    </button>
                                    <button 
                                        @click="deleteRoom(room)" 
                                        class="p-2 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 transition duration-150"
                                        :title="__('global.delete')"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View (< md) -->
            <div class="md:hidden p-4 space-y-3 bg-slate-50/30 dark:bg-slate-950/10">
                <div v-if="filteredRooms.length === 0" class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/60 p-10 text-center rounded-2xl text-slate-400 dark:text-slate-500 text-xs font-medium">
                    {{ __('pages.service_management.rooms.empty_data') }}
                </div>
                <div
                    v-else
                    v-for="room in paginatedRooms"
                    :key="'mobile-room-' + room.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm space-y-3 transition-all duration-150"
                >
                    <div>
                        <div class="font-extrabold text-sm text-slate-900 dark:text-white leading-snug">
                            {{ room.name }}
                        </div>
                    </div>

                    <div class="text-[11px] space-y-1.5 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-xl border border-slate-100 dark:border-slate-800/50">
                        <div class="flex justify-between items-center">
                            <span class="font-medium text-slate-400 dark:text-slate-500">Gedung:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ room.building_name || '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-medium text-slate-400 dark:text-slate-500">Lantai / Lokasi:</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ room.location_floor || '-' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1">
                        <button 
                            @click="openEditRoomModal(room)" 
                            class="flex-1 py-2 px-3 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 text-xs font-bold flex items-center justify-center gap-1.5 transition duration-150 cursor-pointer"
                        >
                            <Edit2 class="h-3.5 w-3.5" />
                            <span>Edit</span>
                        </button>
                        <button 
                            @click="deleteRoom(room)" 
                            class="flex-1 py-2 px-3 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 text-xs font-bold flex items-center justify-center gap-1.5 transition duration-150 cursor-pointer"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="lastPage > 1" class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2"></div>
                <div class="flex items-center gap-3">
                    <span class="text-[10px] sm:text-xs font-medium text-slate-500 dark:text-slate-400">
                        {{ fromCount }}–{{ toCount }} dari {{ totalCount }}
                    </span>
                    <div class="flex items-center gap-1">
                        <button
                            @click="goToPrev"
                            :disabled="!hasPrev"
                            class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                            aria-label="Halaman sebelumnya"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>
                        <button
                            @click="goToNext"
                            :disabled="!hasNext"
                            class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                            aria-label="Halaman berikutnya"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROOM MODAL -->
        <Modal :show="showRoomModal" @close="showRoomModal = false" max-width="md">
            <div class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900">
                <!-- Solid Emerald Sticky Header (No X button) -->
                <div class="bg-emerald-600 dark:bg-emerald-950/90 text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-10 shrink-0 border-b border-emerald-500/30 dark:border-emerald-800/50 shadow-sm">
                    <div class="flex items-center gap-3 pr-2">
                        <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                            <MapPin class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-white leading-tight">
                                {{ isEditingRoom ? __('pages.service_management.rooms.title_edit') : __('pages.service_management.rooms.title_add') }}
                            </h3>
                            <p class="text-xs text-emerald-100/90 dark:text-emerald-200/90 mt-0.5 font-medium">
                                {{ isEditingRoom ? 'Perbarui data lokasi ruangan' : 'Isi data lokasi ruangan baru' }}
                            </p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitRoomForm" class="flex flex-col flex-1 justify-between min-h-0">
                    <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">{{ __('pages.service_management.rooms.label_name') }} <span class="text-red-500">*</span></label>
                            <input 
                                v-model="roomForm.name"
                                type="text" 
                                required
                                class="w-full px-3.5 py-2.5 text-xs border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition duration-150"
                                :placeholder="__('pages.service_management.rooms.placeholder_name')"
                            />
                            <div v-if="roomForm.errors.name" class="text-[10px] text-red-500 font-semibold mt-1">{{ roomForm.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Gedung</label>
                            <input 
                                v-model="roomForm.building_name"
                                type="text" 
                                class="w-full px-3.5 py-2.5 text-xs border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition duration-150"
                                placeholder="Contoh: Gedung A / Gedung Utama"
                            />
                            <div v-if="roomForm.errors.building_name" class="text-[10px] text-red-500 font-semibold mt-1">{{ roomForm.errors.building_name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">{{ __('pages.service_management.rooms.label_floor') }}</label>
                            <input 
                                v-model="roomForm.location_floor"
                                type="text" 
                                class="w-full px-3.5 py-2.5 text-xs border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition duration-150"
                                :placeholder="__('pages.service_management.rooms.placeholder_floor')"
                            />
                            <div v-if="roomForm.errors.location_floor" class="text-[10px] text-red-500 font-semibold mt-1">{{ roomForm.errors.location_floor }}</div>
                        </div>
                    </div>

                    <!-- Sticky Action Footer -->
                    <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-end gap-3 sticky bottom-0 z-10 shrink-0">
                        <SecondaryButton type="button" @click="showRoomModal = false" class="h-11 px-5">{{ __('global.cancel') }}</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="roomForm.processing" class="h-11 px-6 !bg-emerald-600 hover:!bg-emerald-500 font-bold">
                            {{ roomForm.processing ? __('pages.service_management.rooms.btn_saving') || 'Menyimpan...' : __('pages.service_management.rooms.btn_save') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>
