<script setup lang="ts">
import Alert from '@/Components/Ui/Alert.vue';
import Button from '@/Components/Ui/Button.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ status?: string }>();
const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Verifikasi Email" />

    <AuthLayout
        title="Verifikasi Email"
        description="Terima kasih telah mendaftar! Mohon verifikasi alamat email Anda dengan mengklik tautan yang telah kami kirimkan."
    >
        <div v-if="verificationLinkSent" class="mb-6">
            <Alert
                variant="success"
                title="Tautan Terkirim"
                message="Tautan verifikasi baru telah dikirim ke alamat email Anda."
            />
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <Button
                variant="primary"
                :processing="form.processing"
                className="w-full"
            >
                Kirim Ulang Email Verifikasi
            </Button>

            <div class="flex justify-center">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm text-gray-500 hover:text-red-600 font-medium underline decoration-gray-300 underline-offset-2 transition"
                >
                    Log Out Akun
                </Link>
            </div>
        </form>
    </AuthLayout>
</template>
