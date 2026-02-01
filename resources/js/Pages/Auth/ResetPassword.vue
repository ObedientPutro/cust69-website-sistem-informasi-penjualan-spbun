<script setup lang="ts">
import PasswordInput from '@/Components/FormElements/PasswordInput.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import Button from '@/Components/Ui/Button.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ email: string; token: string }>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password" />

    <AuthLayout
        title="Buat Password Baru"
        description="Password baru Anda harus berbeda dari password yang digunakan sebelumnya."
    >
        <form @submit.prevent="submit" class="space-y-5">
            <TextInput
                v-model="form.email"
                type="email"
                label="Email"
                :readonly="true"
                class="opacity-75 bg-gray-50"
                :error="form.errors.email"
            />

            <PasswordInput
                v-model="form.password"
                label="Password Baru"
                placeholder="Minimal 8 karakter"
                required
                autofocus
                :error="form.errors.password"
            />

            <PasswordInput
                v-model="form.password_confirmation"
                label="Konfirmasi Password"
                placeholder="Ulangi password baru"
                required
                :error="form.errors.password_confirmation"
            />

            <Button
                variant="primary"
                :processing="form.processing"
                className="w-full"
            >
                Reset Password
            </Button>
        </form>
    </AuthLayout>
</template>
