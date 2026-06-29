<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-md font-bold text-slate-950 dark:text-white">
                {{ __('pages.profile.delete_title') }}
            </h2>

            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                {{ __('pages.profile.delete_desc') }}
            </p>
        </header>

        <div class="flex justify-end">
            <DangerButton @click="confirmUserDeletion">{{ __('pages.profile.delete_title') }}</DangerButton>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2
                    class="text-md font-bold text-slate-950 dark:text-white"
                >
                    {{ __('pages.profile.delete_confirm_title') }}
                </h2>

                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    {{ __('pages.profile.delete_confirm_desc') }}
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        :value="__('global.password')"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        :placeholder="__('global.password')"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        {{ __('global.cancel') }}
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        {{ __('pages.profile.delete_title') }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
