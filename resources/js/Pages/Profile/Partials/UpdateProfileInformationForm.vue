<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/FormElements/InputError.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import Button from '@/Components/Ui/Button.vue';
import Alert from '@/Components/Ui/Alert.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const user = usePage().props.auth.user;
const swal = useSweetAlert();

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            swal.toast('Informasi profil berhasil diperbarui.', 'success');
        },
        onError: () => {
            swal.toast('Gagal memperbarui profil. Cek inputan Anda.', 'error');
        }
    });
};
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm h-full">
        <header class="mb-6">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                Informasi Akun
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Perbarui alamat email akun Anda. Nama pengguna dibatasi (Read-only).
            </p>
        </header>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <TextInput
                    label="Nama Lengkap"
                    v-model="form.name"
                    type="text"
                    disabled
                    class="opacity-75 cursor-not-allowed bg-gray-50 dark:bg-gray-800"
                />
                <p class="mt-1 text-xs text-gray-400">
                    *Hubungi Owner jika ingin mengubah nama akun.
                </p>
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <TextInput
                    label="Email Address"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="username"
                    :error="form.errors.email"
                />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <Alert variant="warning" class="mt-2">
                    <div class="flex flex-col gap-2">
                        <p class="text-sm">
                            Alamat email Anda belum diverifikasi.
                        </p>
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="text-sm font-bold underline hover:text-orange-800 focus:outline-none"
                            @success="swal.toast('Link verifikasi telah dikirim!', 'success')"
                        >
                            Klik di sini untuk mengirim ulang email verifikasi.
                        </Link>
                    </div>
                </Alert>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600 dark:text-green-400"
                >
                    Tautan verifikasi baru telah dikirim ke alamat email Anda.
                </div>
            </div>

            <div class="pt-2">
                <Button
                    type="submit"
                    variant="primary"
                    :processing="form.processing"
                    :disabled="form.processing"
                >
                    Simpan Perubahan
                </Button>
            </div>
        </form>
    </div>
</template>
