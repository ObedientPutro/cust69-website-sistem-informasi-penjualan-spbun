import Swal from 'sweetalert2';

export function useSweetAlert() {

    // 1. Toast Notification (Muncul di pojok kanan atas, hilang sendiri)
    const toast = (title: string, icon: 'success' | 'error' | 'warning' | 'info' = 'success') => {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: icon,
            title: title
        });
    };

    // 2. Confirm Dialog (Untuk Delete data, dll)
    const confirm = (title: string, text: string, confirmText: string = 'Yes, delete it!') => {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#465fff', // Warna Brand Anda
            cancelButtonColor: '#d33',
            confirmButtonText: confirmText,
            customClass: {
                popup: 'dark:bg-gray-800 dark:text-white' // Support Dark Mode
            }
        });
    };

    // 3. Alert Biasa
    const alert = (title: string, text: string, icon: 'success' | 'error' = 'success') => {
        return Swal.fire({
            title: title,
            text: text,
            icon: icon,
            confirmButtonColor: '#465fff',
            customClass: {
                popup: 'dark:bg-gray-800 dark:text-white'
            }
        });
    };

    return { toast, confirm, alert };
}
