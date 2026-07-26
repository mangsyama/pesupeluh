<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, getCurrentInstance } from 'vue';
import { User, Lock } from '@lucide/vue';
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
});

const pageAuthUser = usePage().props.auth.user;
const currentUser = computed(() => props.user || pageAuthUser);

const photoPreview = ref(null);
const photoInput = ref(null);
const isPhotoDeleted = ref(false);

const form = useForm({
    name: currentUser.value.name || '',
    nip: currentUser.value.nip || '',
    username: currentUser.value.username || '',
    email: currentUser.value.email || '',
    phone_number: currentUser.value.phone_number || '',
    profile_photo: null,
});

const handlePhotoChange = async (e) => {
    const file = e.target.files[0];
    if (file) {
        const { proxy } = getCurrentInstance();
        if (!file.type.startsWith('image/')) {
            proxy.$swal({
                title: 'File tidak didukung',
                text: 'Harap hanya mengunggah file gambar (JPEG, PNG, JPG, WEBP).',
                icon: 'warning',
                confirmButtonColor: '#059669',
            });
            e.target.value = ''; // clear input
            return;
        }
        try {
            // Compress the image before uploading (limit dimensions to 800x800, quality 0.85)
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
    form.transform((data) => ({
        ...data,
        _method: 'PATCH',
    })).post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.profile_photo = null;
        }
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-md font-bold text-slate-955 dark:text-white flex items-center gap-2.5">
                <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-white/10 flex items-center justify-center flex-shrink-0">
                    <User class="h-4 w-4 text-emerald-600 dark:text-white" />
                </div>
                <span>{{ __('pages.profile.info_title') }}</span>
            </h2>

            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                {{ __('pages.profile.info_desc') }}
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <!-- 1. Foto Profil (SELALU DI ATAS) -->
            <div class="flex items-center gap-5 pb-6 border-b border-slate-100 dark:border-slate-800">
                <div class="relative h-20 w-20 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex-shrink-0 flex items-center justify-center shadow-sm">
                    <img 
                        v-if="!isPhotoDeleted && (photoPreview || currentUser.profile_photo_path)" 
                        :src="photoPreview || currentUser.profile_photo_path" 
                        class="h-full w-full object-cover" 
                        alt="Profile Photo"
                    />
                    <User v-else class="h-10 w-10 text-slate-400" />
                </div>
                <div>
                    <InputLabel :value="__('pages.profile.photo') || 'Foto Profil'" />
                    <input 
                        type="file" 
                        ref="photoInput" 
                        class="hidden" 
                        accept="image/*"
                        @change="handlePhotoChange"
                    />
                    <div class="flex items-center gap-2 mt-1">
                        <SecondaryButton 
                            type="button" 
                            class="!px-3 !py-2 !text-xs"
                            @click="photoInput.click()"
                        >
                            {{ __('pages.profile.change_photo') || 'Pilih Foto' }}
                        </SecondaryButton>
                        <SecondaryButton 
                            v-if="!isPhotoDeleted && (photoPreview || currentUser.profile_photo_path)"
                            type="button" 
                            class="!px-3 !py-2 !text-xs !text-red-600 dark:!text-red-400 !border-red-200 dark:!border-red-950/40 hover:!bg-red-50 dark:hover:!bg-red-950/20"
                            @click="deletePhoto"
                        >
                            {{ __('pages.profile.delete_photo') || 'Hapus Foto' }}
                        </SecondaryButton>
                    </div>
                    <InputError class="mt-1" :message="form.errors.profile_photo" />
                </div>
            </div>

            <!-- 2. Grid Data Profil -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nama Lengkap (Editable) -->
                <div>
                    <InputLabel for="name" :value="__('global.name') || 'Nama Lengkap'" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                        autocomplete="name"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <!-- NIP (Editable) -->
                <div>
                    <InputLabel for="nip" :value="__('pages.profile.nip') || 'NIP'" />
                    <TextInput
                        id="nip"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.nip"
                        @input="form.nip = form.nip.replace(/\D/g, '')"
                        maxlength="18"
                        required
                        autocomplete="off"
                    />
                    <InputError class="mt-2" :message="form.errors.nip" />
                </div>

                <!-- Nama Pengguna / Username (Editable) -->
                <div>
                    <InputLabel for="username" :value="__('pages.profile.username') || 'Nama Pengguna'" />
                    <TextInput
                        id="username"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.username"
                        required
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.username" />
                </div>

                <!-- Email (Editable) -->
                <div>
                    <InputLabel for="email" :value="__('global.email') || 'Email'" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <!-- Nomor HP / WhatsApp (Editable) -->
                <div>
                    <InputLabel for="phone_number" :value="__('pages.profile.phone') || 'Nomor HP'" />
                    <TextInput
                        id="phone_number"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.phone_number"
                        @input="form.phone_number = form.phone_number.replace(/\D/g, '')"
                        maxlength="15"
                        autocomplete="tel"
                    />
                    <InputError class="mt-2" :message="form.errors.phone_number" />
                </div>

                <!-- Peran Spesifik (Role) - TERKUNCI -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <InputLabel for="role" value="Peran Spesifik (Role)" class="!mb-0" />
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 flex items-center gap-1">
                            <Lock class="h-3 w-3" />
                            Terkunci
                        </span>
                    </div>
                    <TextInput
                        id="role"
                        type="text"
                        class="block w-full opacity-70 cursor-not-allowed bg-slate-100/70 dark:bg-slate-900/60"
                        :model-value="currentUser.role?.name || 'User'"
                        disabled
                        readonly
                    />
                </div>

                <!-- Unit Penunjang - TERKUNCI -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <InputLabel for="supporting_unit" value="Unit Penunjang" class="!mb-0" />
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 flex items-center gap-1">
                            <Lock class="h-3 w-3" />
                            Terkunci
                        </span>
                    </div>
                    <TextInput
                        id="supporting_unit"
                        type="text"
                        class="block w-full opacity-70 cursor-not-allowed bg-slate-100/70 dark:bg-slate-900/60"
                        :model-value="currentUser.supporting_unit?.name || currentUser.supportingUnit?.name || '-'"
                        disabled
                        readonly
                    />
                </div>

                <!-- Ruangan - TERKUNCI -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <InputLabel for="room" value="Ruangan" class="!mb-0" />
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 flex items-center gap-1">
                            <Lock class="h-3 w-3" />
                            Terkunci
                        </span>
                    </div>
                    <TextInput
                        id="room"
                        type="text"
                        class="block w-full opacity-70 cursor-not-allowed bg-slate-100/70 dark:bg-slate-900/60"
                        :model-value="currentUser.room?.name || '-'"
                        disabled
                        readonly
                    />
                </div>
            </div>

            <!-- Email verification notice -->
            <div v-if="mustVerifyEmail && currentUser.email_verified_at === null">
                <p class="mt-2 text-xs text-slate-800 dark:text-slate-200">
                    {{ __('pages.profile.email_unverified') }}
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-xs text-slate-500 underline hover:text-slate-900 focus:outline-none dark:text-slate-400 dark:hover:text-slate-100"
                    >
                        {{ __('pages.profile.resend_verification') }}
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-xs font-semibold text-emerald-600 dark:text-white"
                >
                    {{ __('pages.profile.verification_sent') }}
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex items-center justify-end gap-4 pt-2">
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-xs font-semibold text-emerald-600 dark:text-white"
                    >
                        {{ __('pages.profile.saved') }}
                    </p>
                </Transition>

                <PrimaryButton :disabled="form.processing">{{ __('pages.profile.btn_save') }}</PrimaryButton>
            </div>
        </form>
    </section>
</template>
