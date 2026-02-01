<script setup lang="ts">
import PasswordInput from '@/Components/FormElements/PasswordInput.vue';
import Button from '@/Components/Ui/Button.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Konfirmasi Keamanan" />

    <AuthLayout
        title="Konfirmasi Password"
        description="Ini adalah area aman. Harap masukkan password Anda untuk melanjutkan."
    >
        <form @submit.prevent="submit" class="space-y-5">
            <PasswordInput
                v-model="form.password"
                label="Password Saat Ini"
                placeholder="Masukkan password Anda"
                required
                autofocus
                :error="form.errors.password"
            />

            <Button
                variant="primary"
                :processing="form.processing"
                className="w-full"
            >
                Konfirmasi
            </Button>
        </form>
    </AuthLayout>
</template>
