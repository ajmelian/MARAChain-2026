/**
 * MARAChain Dropzone Initialization v1.4.0
 *
 * Vanilla JS drag-and-drop file upload area.
 * PDF-only, no auto-upload, drag-and-drop + click-to-select.
 * Uses MARACrypto for client-side file hashing.
 *
 * @package MARAChain\Assets\JS
 * @author  Aythami
 * @since   1.4.0
 */
document.addEventListener('DOMContentLoaded', () => {
    const dropzoneEl = document.getElementById('dropzone-area');

    if (!dropzoneEl) {
        return;
    }

    // ── Prevent default browser drag behaviours ──
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzoneEl.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    // ── Visual feedback on drag hover ──
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

    // ── Handle file drop ──
    dropzoneEl.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelection(files[0]);
        }
    });

    // ── Handle file input change ──
    const fileInput = document.getElementById('document-file');
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelection(e.target.files[0]);
            }
        });
    }

    // ── Click on dropzone opens file dialog ──
    dropzoneEl.addEventListener('click', () => {
        if (fileInput) {
            fileInput.click();
        }
    });
});

/**
 * Handle a file selected by the user (via drag-drop or file input).
 *
 * Validates the file is a PDF, displays file information,
 * shows encryption status, and computes SHA-256 hash asynchronously.
 *
 * @param {File} file The selected PDF file.
 *
 * @since 1.4.0
 */
function handleFileSelection(file) {
    const info = document.getElementById('file-info');
    const status = document.getElementById('encryption-status');
    const fileUploadedInput = document.getElementById('file-uploaded');
    const fileErrorEl = document.getElementById('file-error');

    // ── Validate PDF type ──
    if (file.type !== 'application/pdf') {
        showFileError('Solo se permiten archivos PDF.');
        if (fileUploadedInput) {
            fileUploadedInput.value = '0';
        }
        window._selectedFile = null;
        window._fileHash = null;
        return;
    }

    // Clear any previous error
    if (fileErrorEl) {
        fileErrorEl.style.display = 'none';
    }

    // Mark file as uploaded
    if (fileUploadedInput) {
        fileUploadedInput.value = '1';
    }

    // ── Display file information ──
    if (info) {
        const sizeKB = (file.size / 1024).toFixed(0);
        info.innerHTML = [
            '<div class="d-flex align-items-center">',
            '  <i class="zmdi zmdi-file-text text-primary mr-2"></i>',
            '  <div>',
            '    <strong>' + escapeHtml(file.name) + '</strong><br>',
            '    <small class="text-muted">' + sizeKB + ' KB — PDF</small>',
            '  </div>',
            '</div>'
        ].join('');
        info.classList.remove('d-none');
    }

    // ── Show encryption indicator ──
    if (status) {
        status.innerHTML = [
            '<div class="alert alert-info mt-2 mb-0">',
            '  <i class="zmdi zmdi-shield-security"></i>',
            '  <strong>El documento se cifrará antes del envío.</strong>',
            '  <br><small>El contenido nunca sale del navegador sin cifrar.</small>',
            '</div>'
        ].join('');
        status.classList.remove('d-none');
    }

    // ── Store file reference for submission ──
    window._selectedFile = file;

    // ── Compute SHA-256 hash asynchronously ──
    if (typeof MARACrypto !== 'undefined') {
        MARACrypto.sha256(file).then(hash => {
            if (info) {
                info.innerHTML += [
                    '<br><small class="text-muted">SHA-256: ' +
                    escapeHtml(hash.substring(0, 16)) +
                    '…</small>'
                ].join('');
            }
            window._fileHash = hash;
        }).catch(err => {
            console.error('MARAChain: Error computing file hash:', err);
        });
    }
}

/**
 * Display a file selection error message.
 *
 * @param {string} message The error message to show.
 *
 * @since 1.4.0
 */
function showFileError(message) {
    const info = document.getElementById('file-info');
    const fileUploadedInput = document.getElementById('file-uploaded');

    if (fileUploadedInput) {
        fileUploadedInput.value = '0';
    }

    if (info) {
        info.innerHTML = '<div class="text-danger"><i class="zmdi zmdi-alert-triangle"></i> ' +
            escapeHtml(message) + '</div>';
        info.classList.remove('d-none');
    }

    window._selectedFile = null;
    window._fileHash = null;
}

/**
 * Escape HTML entities to prevent XSS in dynamic content.
 *
 * @param {string} str The string to escape.
 * @returns {string} Escaped string safe for innerHTML.
 *
 * @since 1.4.0
 */
function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}
