<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/FormElements/InputError.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import Button from '@/Components/Ui/Button.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);
const swal = useSweetAlert();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            swal.toast('Password berhasil diubah!', 'success');
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
            swal.toast('Gagal mengubah password. Periksa input Anda.', 'error');
        },
    });
};
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm h-full">
        <header class="mb-6">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                Ganti Password
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gunakan password yang panjang dan acak agar akun tetap aman.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-5">
            <div>
                <TextInput
                    label="Password Saat Ini"
                    v-model="form.current_password"
                    type="password"
                    ref="currentPasswordInput"
                    autocomplete="current-password"
                    :error="form.errors.current_password"
                />
            </div>

            <div>
                <TextInput
                    label="Password Baru"
                    v-model="form.password"
                    type="password"
                    ref="passwordInput"
                    autocomplete="new-password"
                    :error="form.errors.password"
                />
            </div>

            <div>
                <TextInput
                    label="Konfirmasi Password"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    :error="form.errors.password_confirmation"
                />
            </div>

            <div class="pt-2">
                <Button
                    type="submit"
                    variant="primary"
                    :processing="form.processing"
                    :disabled="form.processing"
                >
                    Update Password
                </Button>
            </div>
        </form>
    </div>
</template>
