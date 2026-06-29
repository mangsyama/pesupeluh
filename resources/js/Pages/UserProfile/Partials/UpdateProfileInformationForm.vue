<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, getCurrentInstance } from 'vue';
import { User } from '@lucide/vue';
import { compressImage } from '@/Utils/imageCompressor';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const photoPreview = ref(null);
const photoInput = ref(null);
const isPhotoDeleted = ref(false);

const form = useForm({
    name: user.name,
    email: user.email,
    username: user.username,
    nip: user.nip,
    phone_number: user.phone_number || '',
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
                confirmButtonColor: '#4f46e5',
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
            <h2 class="text-md font-bold text-slate-950 dark:text-white">
                {{ __('pages.profile.info_title') }}
            </h2>

            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                {{ __('pages.profile.info_desc') }}
            </p>
        </header>

        <form
            @submit.prevent="submit"
            class="mt-6 space-y-6"
        >
            <!-- Profile Photo Upload -->
            <div class="flex items-center gap-5 pb-6 border-b border-slate-100 dark:border-slate-800/80">
                <div class="relative h-20 w-20 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex-shrink-0 flex items-center justify-center">
                    <img 
                        v-if="!isPhotoDeleted && (photoPreview || user.profile_photo_path)" 
                        :src="photoPreview || user.profile_photo_path" 
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
                    <div class="flex items-center gap-2">
                        <SecondaryButton 
                            type="button" 
                            class="!px-3 !py-2 !text-xs"
                            @click="photoInput.click()"
                        >
                            {{ __('pages.profile.change_photo') || 'Pilih Foto' }}
                        </SecondaryButton>
                        <SecondaryButton 
                            v-if="!isPhotoDeleted && (photoPreview || user.profile_photo_path)"
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

            <!-- Fields Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- NIP -->
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

                <!-- Username -->
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

                <!-- Name -->
                <div>
                    <InputLabel for="name" :value="__('global.name')" />
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

                <!-- Email -->
                <div>
                    <InputLabel for="email" :value="__('global.email')" />
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

                <!-- Phone Number -->
                <div class="md:col-span-2">
                    <InputLabel for="phone_number" :value="__('pages.profile.phone') || 'No. Telepon'" />
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
            </div>

            <!-- Email verification notice -->
            <div v-if="mustVerifyEmail && user.email_verified_at === null">
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
                    class="mt-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                >
                    {{ __('pages.profile.verification_sent') }}
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex items-center justify-end gap-4">
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        {{ __('pages.profile.saved') }}
                    </p>
                </Transition>

                <PrimaryButton :disabled="form.processing">{{ __('pages.profile.btn_save') }}</PrimaryButton>
            </div>
        </form>
    </section>
</template>
