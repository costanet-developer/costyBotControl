import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.Swal = Swal;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    if (sessionStorage.getItem('welcome_shown') !== 'true') {
        const userName = document.body.dataset.userName;
        if (userName) {
            Swal.fire({
                icon: 'success',
                title: '¡Bienvenido, ' + userName + '!',
                text: 'Has iniciado sesión correctamente.',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            sessionStorage.setItem('welcome_shown', 'true');
        }
    }
});
