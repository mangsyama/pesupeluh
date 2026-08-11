<script setup>
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, getCurrentInstance } from 'vue';
import { User, Save, Camera, Trash2 } from '@lucide/vue';
import { compressImage } from '@/Utils/imageCompressor';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    user: {
        type: Object,
        default: null,
    },
    supportingUnits: {
        type: Array,
        default: () => [],
    },
    rooms: {
        type: Array,
        default: () => [],
    },
});

const { proxy } = getCurrentInstance();
const pageAuthUser = usePage().props.auth.user;
const currentUser = computed(() => props.user || pageAuthUser);

const photoPreview = ref(null);
const photoInput = ref(null);
const isPhotoDeleted = ref(false);

const initialProfileState = ref({
    name: currentUser.value.name || '',
    nip: currentUser.value.nip || '',
    username: currentUser.value.username || '',
    email: currentUser.value.email || '',
    phone_number: currentUser.value.phone_number || '',
    supporting_unit_id: currentUser.value.supporting_unit_id || currentUser.value.supporting_unit?.id || currentUser.value.supportingUnit?.id || '',
    room_id: currentUser.value.room_id || currentUser.value.room?.id || '',
});

const form = useForm({
    name: currentUser.value.name || '',
    nip: currentUser.value.nip || '',
    username: currentUser.value.username || '',
    email: currentUser.value.email || '',
    phone_number: currentUser.value.phone_number || '',
    supporting_unit_id: currentUser.value.supporting_unit_id || currentUser.value.supporting_unit?.id || currentUser.value.supportingUnit?.id || '',
    room_id: currentUser.value.room_id || currentUser.value.room?.id || '',
    profile_photo: null,
});

const isProfileDirty = computed(() => {
    const init = initialProfileState.value;
    return (
        form.name !== init.name ||
        form.nip !== init.nip ||
        form.username !== init.username ||
        form.email !== init.email ||
        form.phone_number !== init.phone_number ||
        (form.supporting_unit_id || '') !== (init.supporting_unit_id || '') ||
        (form.room_id || '') !== (init.room_id || '') ||
        form.profile_photo !== null
    );
});

const unitOptions = computed(() => {
    return (props.supportingUnits || []).map(u => ({
        id: u.id,
        name: u.name,
    }));
});

const roomOptions = computed(() => {
    return (props.rooms || []).map(r => {
        const b = r.building_name ? (/^gedung/i.test(r.building_name.trim()) ? r.building_name.trim() : `Gedung ${r.building_name.trim()}`) : null;
        const f = r.location_floor ? (/^lantai/i.test(r.location_floor.trim()) || /^lt\./i.test(r.location_floor.trim()) ? r.location_floor.trim() : `Lantai ${r.location_floor.trim()}`) : null;
        const details = [b, f].filter(Boolean).join(' - ');
        return {
            id: r.id,
            name: r.name,
            location_floor: details,
        };
    });
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    } catch (e) {
        return dateStr;
    }
};

const handlePhotoChange = async (e) => {
    const file = e.target.files[0];
    if (file) {
        if (!file.type.startsWith('image/')) {
            proxy.$swal({
                title: 'File tidak didukung',
                text: 'Harap hanya mengunggah file gambar (JPEG, PNG, JPG, WEBP).',
                icon: 'warning',
                confirmButtonColor: '#059669',
            });
            e.target.value = '';
            return;
        }
        try {
            const compressedBase64 = await compressImage(file, 800, 800, 0.85);
            form.profile_photo = compressedBase64;
            photoPreview.value = compressedBase64;
            isPhotoDeleted.value = false;
        } catch (error) {
            console.error("Compression error:", error);
            form.profile_photo = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                photoPreview.value = e.target.result;
            };
            reader.readAsDataURL(file);
            isPhotoDeleted.value = false;
        }
    }
};

const deletePhoto = () => {
    form.profile_photo = 'delete';
    photoPreview.value = null;
    isPhotoDeleted.value = true;
};

const submit = () => {
    if (!isProfileDirty.value) return;

    proxy.$swal({
        title: 'Simpan Perubahan Profil?',
        text: 'Informasi data profil Anda akan diperbarui.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            form.transform((data) => ({
                ...data,
                _method: 'PATCH',
            })).post(route('profile.update'), {
                preserveScroll: true,
                onSuccess: () => {
                    form.profile_photo = null;
                    initialProfileState.value = {
                        name: form.name,
                        nip: form.nip,
                        username: form.username,
                        email: form.email,
                        phone_number: form.phone_number,
                        supporting_unit_id: form.supporting_unit_id,
                        room_id: form.room_id,
                    };
                    proxy.$toast('Data profil berhasil diperbarui.', 'success');
                }
            });
        }
    });
};
</script>

<template>
    <section>
        <form @submit.prevent="submit">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                        Data Profil & Akun Pengguna
                    </h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Perbarui informasi identitas pribadi, penempatan unit/ruangan, dan pasfoto akun Anda.</p>
                </div>

                <div class="pt-1">
                    <div class="flex flex-col sm:flex-row items-stretch gap-6">
                        <!-- Foto Profil Card -->
                        <div class="flex flex-col items-center justify-between shrink-0 w-full sm:w-52 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-sm">
                            <div class="w-full flex-1 min-h-[160px] rounded-lg overflow-hidden bg-slate-50 dark:bg-slate-950/50 flex items-center justify-center relative group">
                                <img 
                                    v-if="!isPhotoDeleted && (photoPreview || currentUser.profile_photo_path)" 
                                    :src="photoPreview || currentUser.profile_photo_path" 
                                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" 
                                    alt="Profile Photo"
                                />
                                <User v-else class="h-14 w-14 text-slate-400" />
                            </div>

                            <input 
                                type="file" 
                                ref="photoInput" 
                                class="hidden" 
                                accept="image/*"
                                @change="handlePhotoChange"
                            />
                            <div class="flex items-center gap-1.5 w-full mt-3">
                                <button
                                    type="button"
                                    @click="photoInput.click()"
                                    class="flex-1 h-8 px-2 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 font-bold text-[11px] rounded-lg border border-emerald-200/60 dark:border-emerald-800/40 flex items-center justify-center gap-1 transition"
                                >
                                    <Camera class="h-3.5 w-3.5" />
                                    <span>Pilih Foto</span>
                                </button>
                                <button
                                    v-if="!isPhotoDeleted && (photoPreview || currentUser.profile_photo_path)"
                                    type="button"
                                    @click="deletePhoto"
                                    class="h-8 w-8 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 font-bold text-[11px] rounded-lg border border-rose-200/60 dark:border-rose-800/40 flex items-center justify-center shrink-0 transition"
                                    title="Hapus Foto"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                            <InputError class="mt-1" :message="form.errors.profile_photo" />

                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 mt-2 text-center truncate w-full">Terdaftar: {{ formatDate(currentUser.created_at) }}</span>
                        </div>

                        <!-- Form Inputs Profil (Grid 2 Kolom) -->
                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                            <!-- Nama Lengkap -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Nama Lengkap <span class="text-red-400">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    placeholder="Masukkan Nama Lengkap..."
                                />
                                <InputError class="mt-1" :message="form.errors.name" />
                            </div>

                            <!-- NIP -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    NIP / Pegawai ID <span class="text-red-400">*</span>
                                </label>
                                <input
                                    v-model="form.nip"
                                    type="text"
                                    required
                                    @input="form.nip = form.nip.replace(/\D/g, '')"
                                    maxlength="18"
                                    class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    placeholder="Masukkan NIP..."
                                />
                                <InputError class="mt-1" :message="form.errors.nip" />
                            </div>

                            <!-- Username -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Username <span class="text-red-400">*</span>
                                </label>
                                <input
                                    v-model="form.username"
                                    type="text"
                                    required
                                    class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    placeholder="Masukkan Username..."
                                />
                                <InputError class="mt-1" :message="form.errors.username" />
                            </div>

                            <!-- Email -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Email <span class="text-red-400">*</span>
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    placeholder="Masukkan Email..."
                                />
                                <InputError class="mt-1" :message="form.errors.email" />
                            </div>

                            <!-- Nomor Telepon / WhatsApp -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Nomor Telepon / WhatsApp
                                </label>
                                <input
                                    v-model="form.phone_number"
                                    type="text"
                                    @input="form.phone_number = form.phone_number.replace(/\D/g, '')"
                                    maxlength="15"
                                    class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    placeholder="08xxxxxxxxxx"
                                />
                                <InputError class="mt-1" :message="form.errors.phone_number" />
                            </div>



                            <!-- Penempatan Ruangan (EDITABLE BY USER) -->
                            <div class="space-y-1.5">
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
                                <InputError class="mt-1" :message="form.errors.room_id" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email verification notice -->
                <div v-if="mustVerifyEmail && currentUser.email_verified_at === null" class="p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/50 rounded-xl text-xs">
                    <p class="text-amber-800 dark:text-amber-300">
                        {{ __('pages.profile.email_unverified') }}
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="rounded-md font-bold text-amber-900 dark:text-amber-200 underline hover:text-amber-700 ml-1"
                        >
                            {{ __('pages.profile.resend_verification') }}
                        </Link>
                    </p>

                    <div
                        v-show="status === 'verification-link-sent'"
                        class="mt-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        {{ __('pages.profile.verification_sent') }}
                    </div>
                </div>
            </div>

            <!-- FOOTER ACTION CONTAINER (EXACTLY MATCHING USER MANAGEMENT EDIT) -->
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                <span class="text-xs font-medium text-slate-400 dark:text-slate-500">
                    {{ isProfileDirty ? 'Ada perubahan data profil yang belum disimpan.' : 'Tidak ada perubahan pada data profil.' }}
                </span>
                <button
                    type="submit"
                    :disabled="!isProfileDirty || form.processing"
                    :class="[
                        'px-6 py-2.5 inline-flex items-center justify-center gap-2 font-bold text-xs rounded-xl transition duration-150 shadow-sm border-0 cursor-pointer w-full sm:w-auto',
                        isProfileDirty && !form.processing
                            ? 'bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900'
                            : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-600 opacity-50 cursor-not-allowed'
                    ]"
                >
                    <Save class="h-4 w-4 shrink-0" />
                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Profil & Akun' }}</span>
                </button>
            </div>
        </form>
    </section>
</template>

