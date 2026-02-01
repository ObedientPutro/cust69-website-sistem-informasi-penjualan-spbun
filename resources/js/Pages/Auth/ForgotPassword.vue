<script setup lang="ts">
import TextInput from '@/Components/FormElements/TextInput.vue';
import Alert from '@/Components/Ui/Alert.vue';
import Button from '@/Components/Ui/Button.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{ status?: string }>();

const form = useForm({ email: '' });

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Lupa Password" />

    <AuthLayout
        title="Lupa Password?"
        description="Jangan khawatir. Masukkan email Anda dan kami akan mengirimkan tautan untuk mereset password Anda."
    >
        <div v-if="status" class="mb-6">
            <Alert variant="success" title="Email Terkirim" :message="status" />
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <TextInput
                v-model="form.email"
                type="email"
                label="Email Terdaftar"
                placeholder="contoh@email.com"
                required
                autofocus
                :error="form.errors.email"
            />

            <Button
                variant="primary"
                :processing="form.processing"
                className="w-full"
            >
                Kirim Tautan Reset
            </Button>

            <div class="text-center pt-2">
                <Link :href="route('login')" class="text-sm text-gray-500 hover:text-brand-600 font-medium flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Login
                </Link>
            </div>
        </form>
    </AuthLayout>
</template>
