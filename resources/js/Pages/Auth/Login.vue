<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import CommonGridShape from '@/Components/Common/CommonGridShape.vue';
import TextInput from "@/Components/FormElements/TextInput.vue";
import PasswordInput from "@/Components/FormElements/PasswordInput.vue";
import Checkbox from "@/Components/FormElements/Checkbox.vue";
import Button from "@/Components/Ui/Button.vue";
import Alert from "@/Components/Ui/Alert.vue";

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

    <div class="flex min-h-screen w-full rounded-2xl bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200">
        <div class="relative flex w-full flex-col justify-center lg:flex-row dark:bg-gray-900">

            <div class="flex w-full flex-col flex-1 lg:w-1/2">

                <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto px-6 sm:px-0 mt-10 lg:mt-0">
                    <div>
                        <div class="mb-5 sm:mb-8">
                            <h1 class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
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
                                        class="text-sm text-brand-500 hover:text-brand-600 dark:text-brand-400"
                                    >
                                        Lupa password?
                                    </Link>
                                </div>

                                <Button variant="primary" :processing="form.processing" className="w-full">
                                    Sign In
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="lg:w-1/2 w-full h-full bg-brand-950 dark:bg-white/5 lg:grid items-center hidden relative">
                <div class="items-center justify-center flex z-1">
                    <CommonGridShape />
                    <div class="flex flex-col items-center max-w-xs relative z-10">
                        <Link :href="route('dashboard')" class="block mb-4">
                            <img width="{231}" height="{48}" src="/images/logo/auth-logo.svg" alt="Logo" class="h-12 w-auto" />
                        </Link>
                        <p class="text-gray-400 dark:text-white/60 text-center">
                            Sistem Manajemen Stok & Penjualan Bahan Bakar Minyak untuk Nelayan
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
