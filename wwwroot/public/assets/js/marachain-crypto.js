/**
 * MARAChain WebCrypto Client v1.4.0
 *
 * Client-side encryption using the Web Crypto API.
 * The Data Encryption Key (DEK) NEVER leaves the browser.
 * All encryption is performed with AES-256-GCM.
 *
 * @package MARAChain\Assets\JS
 * @author  Aythami
 * @since   1.4.0
 */
const MARACrypto = {
    /**
     * Generate a random AES-256-GCM key (DEK).
     *
     * @returns {Promise<CryptoKey>} A CryptoKey object usable for encryption.
     *
     * @since 1.4.0
     */
    async generateDEK() {
        return await crypto.subtle.generateKey(
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt']
        );
    },

    /**
     * Export a CryptoKey as raw bytes encoded in hexadecimal.
     *
     * @param {CryptoKey} key The AES key to export.
     * @returns {Promise<string>} Hex-encoded key string (64 hex characters).
     *
     * @since 1.4.0
     */
    async exportKey(key) {
        const raw = await crypto.subtle.exportKey('raw', key);
        return Array.from(new Uint8Array(raw))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
    },

    /**
     * Encrypt plaintext with AES-256-GCM.
     *
     * Generates a random 12-byte IV for each encryption operation.
     * Accepts either a string or an ArrayBuffer as input.
     *
     * @param {CryptoKey} key           The AES-256-GCM key (DEK).
     * @param {string|ArrayBuffer} plaintext The data to encrypt.
     * @returns {Promise<{ciphertext: ArrayBuffer, iv: Uint8Array}>}
     *
     * @since 1.4.0
     */
    async encrypt(key, plaintext) {
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const data = typeof plaintext === 'string'
            ? new TextEncoder().encode(plaintext)
            : plaintext;

        const ciphertext = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv },
            key,
            data
        );

        return { ciphertext, iv };
    },

    /**
     * Compute SHA-256 hash of the provided data.
     *
     * Accepts either a string or an ArrayBuffer.
     * Returns the hash as a lowercase hexadecimal string (64 characters).
     *
     * @param {ArrayBuffer|string} data The data to hash.
     * @returns {Promise<string>} Hex-encoded SHA-256 hash.
     *
     * @since 1.4.0
     */
    async sha256(data) {
        const buffer = typeof data === 'string'
            ? new TextEncoder().encode(data)
            : data;

        const hash = await crypto.subtle.digest('SHA-256', buffer);
        return Array.from(new Uint8Array(hash))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
    },

    /**
     * Read a File object as an ArrayBuffer.
     *
     * Wraps FileReader in a Promise for async/await usage.
     *
     * @param {File} file The file to read.
     * @returns {Promise<ArrayBuffer>}
     *
     * @since 1.4.0
     */
    readFile(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = () => reject(reader.error);
            reader.readAsArrayBuffer(file);
        });
    },

    /**
     * Full document encryption flow.
     *
     * 1. Read the file as ArrayBuffer
     * 2. Compute SHA-256 hash of the file
     * 3. Generate a new AES-256-GCM DEK
     * 4. Encrypt the file data with the DEK
     * 5. Export the DEK as hex for server-side key wrapping
     *
     * @param {File} file The PDF document to encrypt.
     * @returns {Promise<{fileHash: string, ciphertext: ArrayBuffer, dekHex: string, iv: string}>}
     *
     * @since 1.4.0
     */
    async encryptDocument(file) {
        const fileData = await this.readFile(file);
        const fileHash = await this.sha256(fileData);
        const dek = await this.generateDEK();
        const { ciphertext, iv } = await this.encrypt(dek, fileData);
        const dekHex = await this.exportKey(dek);

        return {
            fileHash,
            ciphertext,
            dekHex,
            iv: Array.from(iv)
                .map(b => b.toString(16).padStart(2, '0'))
                .join(''),
        };
    }
};

// Export for module environments (e.g. tests via Node.js)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MARACrypto;
}
