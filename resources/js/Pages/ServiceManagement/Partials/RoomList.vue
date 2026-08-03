<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Edit2, Trash2, X, ChevronLeft, ChevronRight } from '@lucide/vue';

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
                proxy.$toast(proxy.__('pages.service_management.rooms.toast_updated'), 'success');
            }
        });
    } else {
        roomForm.post(route('service-management.rooms.store'), {
            onSuccess: () => {
                showRoomModal.value = false;
                roomForm.reset();
                proxy.$toast(proxy.__('pages.service_management.rooms.toast_added'), 'success');
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
        <div class="overflow-x-auto bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm">
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
        <Teleport to="body">
            <div v-if="showRoomModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm">
                <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden transition-all duration-300">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 rounded-t-2xl">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ isEditingRoom ? __('pages.service_management.rooms.title_edit') : __('pages.service_management.rooms.title_add') }}
                        </h3>
                        <button type="button" @click="showRoomModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <form @submit.prevent="submitRoomForm" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.rooms.label_name') }} <span class="text-red-400">*</span></label>
                            <input 
                                v-model="roomForm.name"
                                type="text" 
                                required
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:border-emerald-500 dark:focus:border-white focus:ring-0 focus:outline-none transition duration-150"
                                :placeholder="__('pages.service_management.rooms.placeholder_name')"
                            />
                            <div v-if="roomForm.errors.name" class="text-xs text-red-500 mt-1">{{ roomForm.errors.name }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Gedung</label>
                            <input 
                                v-model="roomForm.building_name"
                                type="text" 
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:border-emerald-500 dark:focus:border-white focus:ring-0 focus:outline-none transition duration-150"
                                placeholder="Contoh: Gedung A / Gedung Utama"
                            />
                            <div v-if="roomForm.errors.building_name" class="text-xs text-red-500 mt-1">{{ roomForm.errors.building_name }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.rooms.label_floor') }}</label>
                            <input 
                                v-model="roomForm.location_floor"
                                type="text" 
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:border-emerald-500 dark:focus:border-white focus:ring-0 focus:outline-none transition duration-150"
                                :placeholder="__('pages.service_management.rooms.placeholder_floor')"
                            />
                            <div v-if="roomForm.errors.location_floor" class="text-xs text-red-500 mt-1">{{ roomForm.errors.location_floor }}</div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 mt-6">
                            <button type="button" @click="showRoomModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150">{{ __('global.cancel') }}</button>
                            <button type="submit" :disabled="roomForm.processing" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 font-bold text-sm rounded-xl transition duration-150 border-0 shadow-sm disabled:opacity-50">{{ __('pages.service_management.rooms.btn_save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
