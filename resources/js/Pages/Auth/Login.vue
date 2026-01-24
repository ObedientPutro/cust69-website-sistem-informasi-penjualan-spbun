<script setup lang="ts">
import CommonGridShape from '@/Components/Common/CommonGridShape.vue';
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import PasswordInput from '@/Components/FormElements/PasswordInput.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import Alert from '@/Components/Ui/Alert.vue';
import Button from '@/Components/Ui/Button.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    form.clearErrors();
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Log in" />

    <div
        class="flex min-h-screen w-full rounded-2xl bg-white text-gray-700 dark:bg-gray-900 dark:text-gray-200"
    >
        <div
            class="relative flex w-full flex-col justify-center lg:flex-row dark:bg-gray-900"
        >
            <div class="flex w-full flex-1 flex-col lg:w-1/2">
                <div
                    class="mx-auto mt-10 flex w-full max-w-md flex-1 flex-col justify-center px-6 sm:px-0 lg:mt-0"
                >
                    <div>
                        <div class="mb-5 sm:mb-8">
                            <h1
                                class="text-title-sm sm:text-title-md mb-2 font-semibold text-gray-800 dark:text-white/90"
                            >
                                Sign In
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Masuk untuk mengelola Data SPBU-N
                            </p>
                        </div>

                        <div v-if="status" class="mb-6">
                            <Alert
                                variant="success"
                                title="Berhasil"
                                :message="status"
                            />
                        </div>

                        <div v-if="form.errors.email" class="mb-6">
                            <Alert
                                variant="error"
                                title="Gagal Masuk"
                                :message="form.errors.email"
                            />
                        </div>

                        <form @submit.prevent="submit">
                            <div class="space-y-5">
                                <TextInput
                                    v-model="form.email"
                                    type="email"
                                    label="Email"
                                    placeholder="info@gmail.com"
                                    required
                                    autofocus
                                >
                                </TextInput>

                                <PasswordInput
                                    v-model="form.password"
                                    label="Password"
                                    placeholder="Masukkan password anda"
                                    :error="form.errors.password"
                                    required
                                />

                                <div class="flex items-center justify-between">
                                    <Checkbox
                                        v-model:checked="form.remember"
                                        label="Ingat Saya"
                                    />

                                    <Link
                                        v-if="canResetPassword"
                                        :href="route('password.request')"
                                        class="text-brand-500 hover:text-brand-600 dark:text-brand-400 text-sm"
                                    >
                                        Lupa password?
                                    </Link>
                                </div>

                                <Button
                                    variant="primary"
                                    :processing="form.processing"
                                    className="w-full"
                                >
                                    Sign In
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div
                class="bg-brand-950 relative hidden h-full w-full items-center lg:grid lg:w-1/2 dark:bg-white/5"
            >
                <div class="z-1 flex items-center justify-center">
                    <CommonGridShape />
                    <div
                        class="relative z-10 flex max-w-xs flex-col items-center"
                    >
                        <Link :href="route('dashboard')" class="mb-4 block">
                            <img
                                width="{231}"
                                height="{48}"
                                src="/images/logo/auth-logo.svg"
                                alt="Logo"
                                class="h-12 w-auto"
                            />
                        </Link>
                        <p class="text-center text-gray-400 dark:text-white/60">
                            Sistem Manajemen Stok & Penjualan Bahan Bakar Minyak
                            untuk Nelayan
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
