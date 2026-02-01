<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import FileDropzone from '@/Components/FormElements/FileDropzone.vue';
import ToggleSwitch from '@/Components/FormElements/ToggleSwitch.vue';
import Button from '@/Components/Ui/Button.vue';
import Alert from '@/Components/Ui/Alert.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';

const props = defineProps<{
    settings: any;
}>();

const swal = useSweetAlert();

// --- 1. FORM IDENTITAS & KOP (Ada File Upload) ---
// PENTING: Kita tambahkan _method: 'PUT' agar Laravel membacanya sebagai PUT
// meskipun nanti kita kirim pakai .post() (karena keterbatasan upload file)
const identityForm = useForm({
    _method: 'PUT',
    site_name: props.settings?.site_name || '',
    address: props.settings?.address || '',
    phone: props.settings?.phone || '',
    public_email: props.settings?.public_email || '',
    logo_left: null as File | null,
    logo_right: null as File | null,
});
// Previews
const logoLeftPreview = ref(props.settings?.logo_left_url);
const logoRightPreview = ref(props.settings?.logo_right_url);

const submitIdentity = () => {
    // Tetap gunakan .post() agar file binary terkirim,
    // tapi Laravel akan menganggapnya PUT karena ada field _method
    identityForm.post(route('settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            swal.toast('Identitas Website Berhasil Disimpan!', 'success');
            identityForm.logo_left = null;
            identityForm.logo_right = null;
        },
        onError: () => swal.toast('Gagal menyimpan identitas.', 'error'),
    });
};

// --- 2. FORM EMAIL (Tanpa File) ---
const emailForm = useForm({
    notification_email: props.settings?.notification_email || '',
});

const submitEmail = () => {
    // Disini kita bisa pakai .put() murni karena tidak ada file
    emailForm.put(route('settings.update'), {
        preserveScroll: true,
        onSuccess: () => swal.toast('Email Notifikasi Disimpan.', 'success'),
        onError: () => swal.toast('Email tidak valid.', 'error'),
    });
};

// --- 3. LOGIC SWITCHES ---

// A. Toggle Email Notification
const toggleEmail = (value: boolean) => {
    const currentSavedEmail = props.settings?.notification_email;

    if (value && !currentSavedEmail) {
        swal.toast('Simpan alamat email terlebih dahulu sebelum mengaktifkan notifikasi.', 'warning');
        return;
    }

    // Gunakan router.put() langsung
    router.put(route('settings.update'), {
        enable_email_notification: value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            const status = value ? 'Diaktifkan' : 'Dimatikan';
            swal.toast(`Notifikasi Email ${status}`, 'success');
        },
        onError: () => swal.toast('Gagal mengubah status notifikasi.', 'error')
    });
};

// B. Toggle Web Notification (Dengan Izin Browser)
const toggleWeb = async (value: boolean) => {
    if (!value) {
        updateWebStatus(false);
        return;
    }

    if (!("Notification" in window)) {
        swal.toast("Browser Anda tidak mendukung notifikasi web.", "error");
        return;
    }

    if (Notification.permission == "granted") {
        updateWebStatus(true);
    } else if (Notification.permission != "denied") {
        const permission = await Notification.requestPermission();
        if (permission == "granted") {
            updateWebStatus(true);
            swal.toast("Izin browser diberikan!", "success");
        } else {
            swal.toast("Izin notifikasi ditolak oleh browser.", "warning");
        }
    } else {
        swal.alert(
            "Izin Ditolak Browser",
            "Anda telah memblokir notifikasi. Silakan reset izin pada ikon gembok di URL bar browser Anda.",
            "warning"
        );
    }
};

// Helper update status web ke backend
const updateWebStatus = (status: boolean) => {
    // Gunakan router.put() langsung
    router.put(route('settings.update'), {
        enable_web_notification: status,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            const msg = status ? 'Diaktifkan' : 'Dimatikan';
            swal.toast(`Notifikasi Web ${msg}`, 'success');
        }
    });
};
</script>

<template>
    <Head title="Website Settings" />
    <AdminLayout>

        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Pengaturan Website</h2>
            <p class="text-sm text-gray-500">Konfigurasi identitas SPBU (KOP Surat) dan preferensi notifikasi.</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

            <form @submit.prevent="submitIdentity" class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm h-fit">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Identitas & KOP Laporan
                </h3>

                <div class="space-y-5">
                    <TextInput
                        v-model="identityForm.site_name"
                        label="Nama SPBU / Instansi"
                        placeholder="Contoh: SPBU-N KOPERASI MINA..."
                        required
                        :error="identityForm.errors.site_name"
                    />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <TextInput
                            v-model="identityForm.phone"
                            label="Nomor Telepon (KOP)"
                            placeholder="021-xxxxxx"
                            :error="identityForm.errors.phone"
                        />
                        <TextInput
                            v-model="identityForm.public_email"
                            label="Email Publik (KOP)"
                            placeholder="contact@spbu.com"
                            :error="identityForm.errors.public_email"
                        />
                    </div>

                    <TextArea
                        v-model="identityForm.address"
                        label="Alamat Lengkap (Untuk KOP)"
                        placeholder="Jalan..."
                        required
                        :error="identityForm.errors.address"
                    />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Logo Kiri</span>
                            <div v-if="logoLeftPreview && !identityForm.logo_left" class="mb-3 p-2 border rounded bg-gray-50 dark:bg-gray-800 flex justify-center">
                                <img :src="logoLeftPreview" class="h-16 object-contain" alt="Logo Kiri">
                            </div>
                            <FileDropzone
                                v-model="identityForm.logo_left"
                                label="Upload (Ganti)"
                                accept="image/*"
                                is-mini
                            />
                        </div>

                        <div>
                            <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Logo Kanan</span>
                            <div v-if="logoRightPreview && !identityForm.logo_right" class="mb-3 p-2 border rounded bg-gray-50 dark:bg-gray-800 flex justify-center">
                                <img :src="logoRightPreview" class="h-16 object-contain" alt="Logo Kanan">
                            </div>
                            <FileDropzone
                                v-model="identityForm.logo_right"
                                label="Upload (Ganti)"
                                accept="image/*"
                                is-mini
                            />
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t dark:border-gray-800">
                        <Button
                            type="submit"
                            variant="primary"
                            :processing="identityForm.processing"
                        >
                            Simpan Identitas
                        </Button>
                    </div>
                </div>
            </form>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm h-fit">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    Pusat Notifikasi
                </h3>

                <Alert variant="info"
                       title="Catatan!"
                       message="Notifikasi stok menipis dan laporan harian akan dikirimkan sesuai konfigurasi di bawah ini."
                       class="mb-5"/>

                <div class="space-y-6">
                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h4 class="font-bold text-gray-800 dark:text-white">Notifikasi Email</h4>
                                <p class="text-xs text-gray-500">Teruskan notifikasi ke email.</p>
                            </div>
                            <ToggleSwitch
                                :model-value="Boolean(settings?.enable_email_notification)"
                                @update:model-value="toggleEmail"
                                label=""
                            />
                        </div>

                        <form @submit.prevent="submitEmail" class="flex gap-3 items-end">
                            <div class="flex-1">
                                <TextInput
                                    v-model="emailForm.notification_email"
                                    type="email"
                                    label="Alamat Email Penerima"
                                    placeholder="owner@example.com"
                                    :error="emailForm.errors.notification_email"
                                />
                            </div>
                            <Button
                                type="submit"
                                variant="outline"
                                size="md"
                                :processing="emailForm.processing"
                                class="mb-[2px]"
                            >
                                Simpan Email
                            </Button>
                        </form>
                    </div>

                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-gray-800 dark:text-white">Notifikasi Browser</h4>
                                <p class="text-xs text-gray-500">Pop-up notifikasi saat membuka website.</p>
                            </div>
                            <ToggleSwitch
                                :model-value="Boolean(settings?.enable_web_notification)"
                                @update:model-value="toggleWeb"
                                label=""
                            />
                        </div>
                        <p class="text-xs text-gray-400 mt-2 italic">
                            *Memerlukan izin browser. Izin akan diminta saat tombol diaktifkan.
                        </p>
                    </div>
                </div>
            </div>

        </div>

    </AdminLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
