<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="Pengaturan Akun" />

    <AuthenticatedLayout>
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                Pengaturan Akun
            </h2>
            <p class="text-sm text-gray-500">
                Kelola informasi login dan keamanan akun Anda.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2 items-start">

            <div class="space-y-6">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                />
            </div>

            <div class="space-y-6">
                <UpdatePasswordForm />
            </div>

        </div>
    </AuthenticatedLayout>
</template>
