const form = document.getElementById('patient-form');

if (form) {
    const allergies = document.getElementById('alergias-container');
    document.getElementById('add-allergy').addEventListener('click', () => {
        if (allergies.children.length < 20) {
            allergies.append(document.getElementById('allergy-template').content.cloneNode(true));
        }
    });
    allergies.addEventListener('click', (event) => {
        if (event.target.closest('.remove-allergy')) event.target.closest('.allergy-row').remove();
    });

    const input = document.getElementById('url');
    const preview = document.getElementById('preview');
    const status = document.getElementById('photo-status');
    const container = document.getElementById('camera-container');
    const video = document.getElementById('video');
    const capture = document.getElementById('capture-btn');
    const open = document.getElementById('open-camera-btn');
    let stream = null;
    let previewUrl = null;
    let requestId = 0;

    function showPreview(file) {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
    }

    function stopCamera() {
        requestId++;
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
        video.srcObject = null;
        container.hidden = true;
        capture.disabled = true;
        open.disabled = false;
    }

    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;
        stopCamera();
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 4 * 1024 * 1024) {
            status.textContent = 'Seleccione una imagen JPG, PNG o WebP de hasta 4 MiB.';
            input.value = '';
            return;
        }
        showPreview(file);
        status.textContent = 'Fotografía seleccionada. Se guardará al enviar el formulario.';
    });

    open.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia || !window.isSecureContext) {
            status.textContent = 'La cámara requiere HTTPS o localhost y un navegador compatible. Puede subir un archivo.';
            return;
        }
        const currentRequest = ++requestId;
        open.disabled = true;
        container.hidden = false;
        status.textContent = 'Esperando permiso para la cámara…';
        try {
            const acquired = await navigator.mediaDevices.getUserMedia({ video: { width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false });
            if (currentRequest !== requestId) {
                acquired.getTracks().forEach((track) => track.stop());
                return;
            }
            stream = acquired;
            video.srcObject = stream;
            await video.play();
            capture.disabled = false;
            status.textContent = 'Cámara lista. Capture la fotografía o cierre la cámara.';
        } catch {
            stopCamera();
            status.textContent = 'No se pudo abrir la cámara. Revise los permisos o suba una fotografía.';
        }
    });

    capture.addEventListener('click', () => {
        if (!video.videoWidth || !video.videoHeight) {
            status.textContent = 'Espere a que la cámara esté lista.';
            return;
        }
        capture.disabled = true;
        const canvas = document.getElementById('canvas');
        const ratio = Math.min(1, 1600 / Math.max(video.videoWidth, video.videoHeight));
        canvas.width = Math.round(video.videoWidth * ratio);
        canvas.height = Math.round(video.videoHeight * ratio);
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        stopCamera();
        canvas.toBlob((blob) => {
            if (!blob) {
                status.textContent = 'No se pudo capturar la fotografía. Inténtelo de nuevo.';
                return;
            }
            const file = new File([blob], 'captura.jpg', { type: 'image/jpeg' });
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            showPreview(file);
            status.textContent = 'Fotografía capturada. Se guardará al enviar el formulario.';
        }, 'image/jpeg', 0.85);
    });

    document.getElementById('close-camera-btn').addEventListener('click', stopCamera);
    form.addEventListener('submit', stopCamera);
    window.addEventListener('pagehide', () => {
        stopCamera();
        if (previewUrl) URL.revokeObjectURL(previewUrl);
    });
}
