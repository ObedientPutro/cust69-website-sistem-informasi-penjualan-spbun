import Swal from 'sweetalert2';

export function useSweetAlert() {
    // Definisi Style Tailwind untuk SweetAlert (Light & Dark Mode Support)
    const commonCustomClass = {
        popup: '!rounded-xl !border !border-gray-200 !bg-white !shadow-xl dark:!border-gray-700 dark:!bg-gray-800 dark:!text-white',
        title: '!text-lg !font-bold !text-gray-800 dark:!text-white',
        htmlContainer: '!text-sm !text-gray-600 dark:!text-gray-300',
        confirmButton: '!inline-flex !items-center !justify-center !rounded-lg !bg-brand-600 !px-5 !py-2.5 !text-center !text-sm !font-medium !text-white hover:!bg-brand-700 focus:!ring-4 focus:!ring-brand-300 dark:focus:!ring-brand-900 !mx-1',
        cancelButton: '!inline-flex !items-center !justify-center !rounded-lg !border !border-gray-200 !bg-white !px-5 !py-2.5 !text-center !text-sm !font-medium !text-gray-900 hover:!bg-gray-100 hover:!text-blue-700 focus:!z-10 focus:!ring-4 focus:!ring-gray-100 dark:!border-gray-600 dark:!bg-gray-800 dark:!text-gray-400 dark:hover:!bg-gray-700 dark:hover:!text-white dark:focus:!ring-gray-700 !mx-1',
    };

    // 1. Toast Notification (Muncul di pojok kanan atas, hilang sendiri)
    const toast = (
        title: string,
        icon: 'success' | 'error' | 'warning' | 'info' = 'success',
    ) => {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            customClass: {
                popup: '!flex !items-center !p-4 !w-full !max-w-xs !text-gray-500 !bg-white !rounded-lg !shadow dark:!text-gray-400 dark:!bg-gray-800 !border !border-gray-100 dark:!border-gray-700',
                title: '!text-sm !font-semibold !text-gray-800 dark:!text-white !ml-2',
                timerProgressBar: '!bg-brand-500',
            },
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            },
        });

        Toast.fire({
            icon: icon,
            title: title,
        });
    };

    // 2. Confirm Dialog (Untuk Delete data, dll)
    const confirm = (
        title: string,
        text: string,
        confirmText: string = 'Ya, hapus',
    ) => {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Batal',
            buttonsStyling: false,
            customClass: commonCustomClass,
        });
    };

    // 3. Alert Biasa
    const alert = (
        title: string,
        text: string,
        icon: 'success' | 'error' = 'success',
    ) => {
        return Swal.fire({
            title: title,
            text: text,
            icon: icon,
            confirmButtonText: 'OK',
            buttonsStyling: false,
            customClass: commonCustomClass,
        });
    };

    return { toast, confirm, alert };
}
