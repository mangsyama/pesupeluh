<script setup>
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Ticket } from '@lucide/vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    rooms: {
        type: Array,
        default: () => []
    },
    categories: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close']);

const form = useForm({
    room_id: '',
    category_id: '',
    problem_description: '',
    urgency: 'SEDANG',
    reporter_name: '',
    reporter_phone: '',
});

const urgencyOptions = [
    { value: 'DARURAT', label: 'DARURAT (Panic)', desc: 'Kendala fatal / insiden darurat' },
    { value: 'TINGGI', label: 'Tinggi', desc: 'Air macet total, kloset meluap' },
    { value: 'SEDANG', label: 'Sedang', desc: 'Sabun habis, tisu habis, bau kurang sedap' },
    { value: 'RENDAH', label: 'Rendah', desc: 'Perbaikan minor non-krusial' },
];

const submit = () => {
    form.post(route('pansinar.tickets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        }
    });
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="lg">
        <div class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900 justify-between">
            <!-- Solid Emerald Sticky Header -->
            <div class="bg-emerald-600 dark:bg-emerald-950/90 text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-10 shrink-0 border-b border-emerald-500/30 dark:border-emerald-800/50 shadow-sm">
                <div class="flex items-center gap-3 pr-2">
                    <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                        <Ticket class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black tracking-tight text-white leading-tight">
                            Lapor Kendala Sanitasi & Toilet
                        </h3>
                        <p class="text-[11px] text-emerald-100/90 dark:text-emerald-200/80 font-medium leading-tight mt-0.5">
                            Sistem respon cepat fasilitas toilet & sanitasi lingkungan
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submit" class="flex flex-col flex-1 min-h-0 justify-between">
                <div class="p-5 sm:p-6 overflow-y-auto space-y-4 max-h-[calc(100dvh-220px)] sm:max-h-[65vh] overscroll-contain touch-pan-y">
                    <!-- Location Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Lokasi Toilet / Fasilitas <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            v-model="form.room_id"
                            required
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="" disabled>Pilih Ruangan / Toilet...</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                {{ room.name }} ({{ room.building_name || 'Gedung Utama' }} - {{ room.location_floor || 'Lantai 1' }})
                            </option>
                        </select>
                    </div>

                    <!-- Problem Category -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Kategori Kendala <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            v-model="form.category_id"
                            required
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="" disabled>Pilih Kategori Permasalahan...</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Urgency Radio Cards -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Tingkat Urgensi <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <div 
                                v-for="u in urgencyOptions" 
                                :key="u.value"
                                @click="form.urgency = u.value"
                                :class="[
                                    'p-2.5 rounded-xl border text-xs cursor-pointer transition-all',
                                    form.urgency === u.value
                                        ? (u.value === 'DARURAT' ? 'bg-rose-500/10 border-rose-500 text-rose-700 font-bold' : 'bg-emerald-50 dark:bg-white/10 border-emerald-500 dark:border-white text-emerald-800 dark:text-white font-bold')
                                        : 'bg-slate-50/50 dark:bg-slate-800/40 border-slate-200/80 dark:border-slate-700 text-slate-600 dark:text-slate-400'
                                ]"
                            >
                                <p class="font-bold text-[11px]">{{ u.label }}</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">{{ u.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Deskripsi Masalah Presisi <span class="text-rose-500">*</span>
                        </label>
                        <textarea 
                            v-model="form.problem_description"
                            rows="3"
                            required
                            placeholder="Contoh: Kloset nomor 2 mampet dan air wastafel tidak mengalir, sabun cuci tangan habis."
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        ></textarea>
                    </div>

                    <!-- Reporter (Optional) -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                Nama Pelapor (Opsional)
                            </label>
                            <input 
                                v-model="form.reporter_name"
                                type="text" 
                                placeholder="Pengunjung / Staf"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                No. WA Pelapor (Opsional)
                            </label>
                            <input 
                                v-model="form.reporter_phone"
                                type="text" 
                                placeholder="08123xxx"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Sticky Action Footer -->
                <div class="sticky bottom-0 z-30 shrink-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 p-4 pt-3.5 pb-8 sm:pb-5 sm:p-5 shadow-lg sm:shadow-none">
                    <!-- Mobile View: Stacked Full-Width Buttons identical to AduanMasyarakat -->
                    <div class="flex flex-col gap-2.5 w-full sm:hidden">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-70 shadow-sm"
                        >
                            <span v-if="form.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            <span v-else>{{ form.processing ? 'Mengirim...' : 'Kirim & Disposisi Tiket' }}</span>
                        </button>
                        <button
                            type="button"
                            @click="emit('close')"
                            :disabled="form.processing"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none disabled:opacity-50"
                        >
                            <span>Batal</span>
                        </button>
                    </div>

                    <!-- Desktop View: Aligned to right -->
                    <div class="hidden sm:flex items-center justify-end gap-3">
                        <SecondaryButton type="button" @click="emit('close')" class="h-11 px-5 cursor-pointer">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton 
                            type="submit" 
                            :disabled="form.processing" 
                            class="h-11 px-6 !bg-emerald-600 hover:!bg-emerald-500 font-bold cursor-pointer active:scale-95"
                        >
                            {{ form.processing ? 'Mengirim...' : 'Kirim & Disposisi Tiket' }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>
    </Modal>
</template>
