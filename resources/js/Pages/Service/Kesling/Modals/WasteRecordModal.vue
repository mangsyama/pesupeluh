<script setup>
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Trash2 } from '@lucide/vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    rooms: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close']);

const form = useForm({
    unit_source: '',
    waste_type: 'Limbah Medis Infeksius (Kuning)',
    weight_kg: '',
});

const wasteTypes = [
    { label: 'Limbah Medis Infeksius (Kantung Kuning)', value: 'Limbah Medis Infeksius (Kuning)' },
    { label: 'Limbah Medis Benda Tajam (Safety Box)', value: 'Limbah Medis Tajam (Safety Box)' },
    { label: 'Limbah Kimia & Patologi (Kantung Cokelat/Ungu)', value: 'Limbah Kimia / Patologi' },
    { label: 'Limbah Domestik / Non-Medis (Kantung Hitam)', value: 'Limbah Domestik (Hitam)' },
];

const submit = () => {
    form.post(route('pansinar.waste.store'), {
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
                        <Trash2 class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black tracking-tight text-white leading-tight">
                            Input Timbangan Harian Limbah B3
                        </h3>
                        <p class="text-[11px] text-emerald-100/90 dark:text-emerald-200/80 font-medium leading-tight mt-0.5">
                            Penimbangan fisik limbah sebelum masuk gudang TPS B3
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="flex flex-col flex-1 min-h-0 justify-between">
                <div class="p-5 sm:p-6 overflow-y-auto space-y-4 max-h-[calc(100dvh-220px)] sm:max-h-[65vh] overscroll-contain touch-pan-y">
                    <!-- Unit Source Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Unit / Ruangan Asal Limbah <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            v-model="form.unit_source"
                            required
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="" disabled>Pilih Unit Penghasil Limbah...</option>
                            <option value="Ruang Operasi (OK)">Ruang Operasi (OK)</option>
                            <option value="IGD & Ruang Tindakan">IGD & Ruang Tindakan</option>
                            <option value="Laboratorium PK">Laboratorium PK</option>
                            <option value="Rawat Inap Melati">Rawat Inap Melati</option>
                            <option value="Rawat Inap Dahlia">Rawat Inap Dahlia</option>
                            <option value="Poliklinik Rawat Jalan">Poliklinik Rawat Jalan</option>
                            <option value="Ruang Bersalin (VK)">Ruang Bersalin (VK)</option>
                            <option value="Hemodialisa">Ruang Hemodialisa</option>
                        </select>
                    </div>

                    <!-- Waste Type -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Jenis Limbah Medis / Domestik <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            v-model="form.waste_type"
                            required
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        >
                            <option v-for="t in wasteTypes" :key="t.value" :value="t.value">
                                {{ t.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Weight Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Berat Timbangan Fisik (Kilogram) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                v-model="form.weight_kg"
                                type="number" 
                                step="0.1" 
                                min="0.1"
                                required
                                placeholder="0.0"
                                class="w-full pl-4 pr-12 py-2.5 text-base font-extrabold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">
                                KG
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Gunakan timbangan digital terkalibrasi di area TPS B3.</p>
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
                            <span v-else>{{ form.processing ? 'Menyimpan...' : 'Simpan Timbangan' }}</span>
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
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Timbangan' }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>
    </Modal>
</template>
