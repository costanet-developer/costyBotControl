<script>
function cambiarEstado(btn, estado) {
    const labels = { 'EN_REVISION': 'Tomar en Revisión', 'APROBADO': 'Aprobar', 'RECHAZADO': 'Rechazar' };
    const label = labels[estado] || estado;
    Swal.fire({
        title: '¿' + label + '?',
        text: 'Se cambiará el estado del comprobante.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#E60012',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, ' + label.toLowerCase(),
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            const form = btn.closest('form');
            form.querySelector('input[name="estado"]').value = estado;
            form.submit();
        }
    });
}

function cambiarEstadoConObs(btn, estado) {
    Swal.fire({
        title: '¿Rechazar comprobante?',
        text: 'Indica el motivo del rechazo:',
        icon: 'warning',
        input: 'text',
        inputPlaceholder: 'Motivo (opcional)',
        showCancelButton: true,
        confirmButtonColor: '#E60012',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, rechazar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            const form = btn.closest('form');
            form.querySelector('input[name="estado"]').value = estado;
            form.querySelector('input[name="observacion"]').value = result.value || '';
            form.submit();
        }
    });
}

function verImagen(src) {
    const lb = document.getElementById('lightbox');
    document.getElementById('lightbox-img').src = src;
    lb.classList.remove('hidden');
}

function cerrarImagen() {
    const lb = document.getElementById('lightbox');
    lb.classList.add('hidden');
    document.getElementById('lightbox-img').src = '';
}

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-ajax')) return;
    e.preventDefault();

    fetch(form.action, {
        method: form.method,
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then((r) => (r.ok ? r.json() : r.json().then((j) => Promise.reject(j))))
        .then((json) => {
            if (json.ok && window.cargarDetalle && window.sesionActual) {
                window.cargarDetalle(window.sesionActual);
            } else {
                window.location.reload();
            }
        })
        .catch((err) => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: (err && err.error) || 'No se pudo actualizar el estado del comprobante.',
            });
        });
});
</script>
