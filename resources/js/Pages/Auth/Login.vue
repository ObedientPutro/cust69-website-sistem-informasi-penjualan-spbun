<script setup lang="ts">
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import PasswordInput from '@/Components/FormElements/PasswordInput.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import Alert from '@/Components/Ui/Alert.vue';
import Button from '@/Components/Ui/Button.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.clearErrors();
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login" />

    <AuthLayout
        title="Selamat Datang Kembali"
        description="Masuk ke akun Anda untuk mulai mengelola data SPBU-N."
    >
        <div v-if="status" class="mb-6">
            <Alert variant="success" title="Berhasil" :message="status" />
        </div>

        <div v-if="form.errors.email" class="mb-6">
            <Alert variant="error" title="Gagal Masuk" :message="form.errors.email" />
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <TextInput
                v-model="form.email"
                type="email"
                label="Email"
                placeholder="info@spbun.com"
                required
                autofocus
            />

            <PasswordInput
                v-model="form.password"
                label="Password"
                placeholder="Masukkan password"
                :error="form.errors.password"
                required
            />

            <div class="flex items-center justify-between">
                <Checkbox v-model:checked="form.remember" label="Ingat Saya" />

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-brand-500 hover:text-brand-600 font-medium"
                >
                    Lupa Password?
                </Link>
            </div>

            <Button
                variant="primary"
                :processing="form.processing"
                className="w-full mt-2"
            >
                Masuk Sekarang
            </Button>
        </form>
    </AuthLayout>
</template>
