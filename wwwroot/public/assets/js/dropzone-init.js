/**
 * MARAChain Dropzone Initialization v1.4.0
 *
 * Vanilla JS drag-and-drop file upload area.
 * PDF-only, no auto-upload, drag-and-drop + click-to-select.
 * Uses MARACrypto for client-side file hashing and encryption.
 *
 * @package MARAChain\Assets\JS
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.4.0
 */
document.addEventListener('DOMContentLoaded', () => {
    const dropzoneEl = document.getElementById('dropzone-area');
    if (!dropzoneEl) return;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzoneEl.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzoneEl.addEventListener(eventName, () => {
            dropzoneEl.classList.add('border-primary', 'bg-light');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzoneEl.addEventListener(eventName, () => {
            dropzoneEl.classList.remove('border-primary', 'bg-light');
        });
    });

    dropzoneEl.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) handleFileSelection(files[0]);
    });

    const fileInput = document.getElementById('document-file');
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleFileSelection(e.target.files[0]);
        });
    }

    dropzoneEl.addEventListener('click', () => {
        if (fileInput) fileInput.click();
    });

    // ── Form submit handler: encrypt before upload ──
    const form = document.getElementById('transfer-form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!window._selectedFile) {
                showFileError('Selecciona un documento PDF antes de enviar.');
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            const statusEl = document.getElementById('encryption-status');

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="zmdi zmdi-spinner zmdi-hc-spin"></i> Cifrando documento...';
            }

            try {
                const result = await MARACrypto.encryptDocument(window._selectedFile);

                if (statusEl) {
                    statusEl.innerHTML = '<div class="alert alert-success mt-2 mb-0">'
                        + '<i class="zmdi zmdi-check-circle"></i> '
                        + '<strong>Documento cifrado.</strong> SHA-256: '
                        + escapeHtml(result.fileHash.substring(0, 16)) + '&hellip;</div>';
                }

                // Build marachain-envelope v1
                const envelope = {
                    format: 'marachain-envelope',
                    version: 1,
                    contentCipher: 'AES-256-GCM',
                    manifestHash: result.fileHash,
                    recipients: [],
                    iv: result.iv,
                    dek: result.dekHex
                };

                const metadata = {
                    title: form.querySelector('#document-title')?.value || 'Sin titulo',
                    description: form.querySelector('#document-description')?.value || '',
                    mimeType: 'application/pdf',
                    fileSize: window._selectedFile.size,
                    fileHashSha256: result.fileHash,
                    ownerId: form.querySelector('#owner-id')?.value || ''
                };

                // Submit via FormData
                const formData = new FormData();
                formData.append('envelope', JSON.stringify(envelope));
                formData.append('metadata', JSON.stringify(metadata));
                formData.append('file', new Blob([result.ciphertext], { type: 'application/octet-stream' }),
                    window._selectedFile.name + '.enc');

                const csrfToken = form.querySelector('input[name="csrf_token_name"]')?.value || '';
                if (csrfToken) formData.append('csrf_token_name', csrfToken);

                const response = await fetch('/documents/upload', {
                    method: 'POST',
                    body: formData,
                    headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}
                });

                const data = await response.json();

                if (response.ok) {
                    const docId = data.data?.documentId || '';
                    document.getElementById('encrypted-doc-id').value = docId;
                    document.getElementById('encrypted-hash').value = result.fileHash;

                    if (statusEl) {
                        statusEl.innerHTML = '<div class="alert alert-success mt-2 mb-0">'
                            + '<i class="zmdi zmdi-check-circle"></i> '
                            + '<strong>Documento subido y cifrado correctamente.</strong></div>';
                    }

                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="zmdi zmdi-send"></i> Enviar transferencia';
                    }
                } else {
                    throw new Error(data.messages?.envelope || data.message || 'Error al subir el documento.');
                }
            } catch (err) {
                console.error('MARAChain upload error:', err);
                if (statusEl) {
                    statusEl.innerHTML = '<div class="alert alert-danger mt-2 mb-0">'
                        + '<i class="zmdi zmdi-alert-triangle"></i> '
                        + '<strong>Error:</strong> ' + escapeHtml(err.message) + '</div>';
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="zmdi zmdi-send"></i> Reintentar envio';
                }
            }
        });
    }
});

function handleFileSelection(file) {
    const info = document.getElementById('file-info');
    const status = document.getElementById('encryption-status');

    if (file.type !== 'application/pdf') {
        showFileError('Solo se permiten archivos PDF.');
        window._selectedFile = null;
        window._fileHash = null;
        return;
    }

    document.getElementById('file-error')?.style &&
        (document.getElementById('file-error').style.display = 'none');

    if (info) {
        const sizeKB = (file.size / 1024).toFixed(0);
        info.innerHTML = '<div class="d-flex align-items-center">'
            + '<i class="zmdi zmdi-file-text text-primary mr-2"></i>'
            + '<div><strong>' + escapeHtml(file.name) + '</strong><br>'
            + '<small class="text-muted">' + sizeKB + ' KB — PDF</small></div></div>';
        info.classList.remove('d-none');
    }

    if (status) {
        status.innerHTML = '<div class="alert alert-info mt-2 mb-0">'
            + '<i class="zmdi zmdi-shield-security"></i> '
            + '<strong>El documento se cifrara antes del envio.</strong>'
            + '<br><small>El contenido nunca sale del navegador sin cifrar.</small></div>';
        status.classList.remove('d-none');
    }

    window._selectedFile = file;

    if (typeof MARACrypto !== 'undefined') {
        MARACrypto.sha256(file).then(hash => {
            if (info) {
                info.innerHTML += '<br><small class="text-muted">SHA-256: '
                    + escapeHtml(hash.substring(0, 16)) + '&hellip;</small>';
            }
            window._fileHash = hash;
        }).catch(err => console.error('MARAChain hash error:', err));
    }
}

function showFileError(message) {
    const info = document.getElementById('file-info');
    if (info) {
        info.innerHTML = '<div class="text-danger"><i class="zmdi zmdi-alert-triangle"></i> '
            + escapeHtml(message) + '</div>';
        info.classList.remove('d-none');
    }
    window._selectedFile = null;
    window._fileHash = null;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}
