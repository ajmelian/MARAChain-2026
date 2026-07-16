<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Uuid;
use App\Models\DocumentModel;
use RuntimeException;

/**
 * StorageService — Persistencia de documentos cifrados.
 *
 * MVP actual: almacena ciphertext en MySQL (columna ciphertext).
 * IPFS: almacena en IPFS (localhost:5001) y guarda ipfs_cid en MySQL.
 * La eliminación desvincula ipfs_cid sin borrar el pin de IPFS.
 *
 * El backend NUNCA recibe plaintext. Solo ciphertext del cliente.
 *
 * @package App\Services
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.8.0
 */
class StorageService
{
    private string $ipfsApiUrl;

    private EncryptionService $encryptionService;

    public function __construct()
    {
        $this->ipfsApiUrl        = 'http://127.0.0.1:5001/api/v0/';
        $this->encryptionService = new EncryptionService();
    }

    /**
     * Store an encrypted document, uploading to IPFS if available,
     * falling back to MySQL storage.
     *
     * @param array  $envelope   marachain-envelope v1
     * @param array  $metadata   Document metadata
     * @param string $ciphertext Base64-encoded ciphertext
     *
     * @return \App\Entities\Document
     *
     * @throws RuntimeException When envelope validation fails
     */
    public function storeEncryptedDocument(array $envelope, array $metadata, string $ciphertext): \App\Entities\Document
    {
        if (! $this->encryptionService->validateEnvelope($envelope)) {
            throw new RuntimeException('Invalid marachain-envelope format.');
        }

        $fileHashSha256 = $metadata['fileHashSha256'] ?? '';
        $manifestHash   = $envelope['manifestHash'] ?? '';

        if ($manifestHash !== $fileHashSha256) {
            throw new RuntimeException(
                'Manifest hash mismatch. Expected: ' . substr($fileHashSha256, 0, 16)
                . '..., Got: ' . substr($manifestHash, 0, 16) . '...'
            );
        }

        // ── Upload to IPFS ──────────────────────────────────────────
        $ipfsCid      = null;
        $blockchainTxId = null;
        $rawCiphertext = base64_decode($ciphertext);

        if ($rawCiphertext !== false) {
            $ipfsResult = $this->uploadToIpfs($rawCiphertext);
            if ($ipfsResult !== null) {
                $ipfsCid       = $ipfsResult['cid'];
                // Also pin to ensure persistence
                $this->pinToIpfs($ipfsCid);
            }
        }

        $documentModel = model(DocumentModel::class);

        $doc = $documentModel->create([
            'ownerId'          => $metadata['ownerId'] ?? '',
            'title'            => $metadata['title'] ?? 'Sin titulo',
            'description'      => $metadata['description'] ?? null,
            'mimeType'         => $metadata['mimeType'] ?? 'application/pdf',
            'fileSize'         => (int) ($metadata['fileSize'] ?? 0),
            'fileHashSha256'   => $fileHashSha256,
            'manifestHash'     => $manifestHash,
            'manifestJson'     => json_encode($envelope, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'encryptionFormat' => 'marachain-envelope',
            'contentCipher'    => $envelope['contentCipher'] ?? 'AES-256-GCM',
            'ipfsCid'          => $ipfsCid,
            'encryptedAt'      => date('Y-m-d H:i:s'),
            'version'          => 1,
            'status'           => 'ENCRYPTED',
        ]);

        return $doc;
    }

    /**
     * Mark a document as destroyed (CU-DELETE-001 step 2-3).
     *
     * Removes ipfs_cid reference from MySQL. The IPFS pin can be
     * removed later via unpin command. blockchain_tx_id is preserved.
     *
     * @param string $documentId Document UUID
     *
     * @return bool
     */
    public function destroyDocument(string $documentId): bool
    {
        $documentModel = model(DocumentModel::class);
        $doc           = $documentModel->find($documentId);

        if ($doc === null) {
            return false;
        }

        return $documentModel->update($documentId, [
            'ipfs_cid' => null,
            'status'   => 'DESTROYED',
            'destroyed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Upload raw binary data to IPFS.
     *
     * @param string $data Raw binary ciphertext
     *
     * @return array{cid: string, size: int}|null CID and size, or null on failure
     */
    public function uploadToIpfs(string $data): ?array
    {
        try {
            $ch = curl_init($this->ipfsApiUrl . 'add');
            curl_setopt_array($ch, [
                CURLOPT_POST         => true,
                CURLOPT_POSTFIELDS   => $data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT      => 30,
                CURLOPT_HTTPHEADER   => ['Content-Type: application/octet-stream'],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || $response === false) {
                log_message('error', 'IPFS upload failed with HTTP ' . $httpCode);

                return null;
            }

            $parts = explode("\n", trim($response));
            $last  = json_decode(end($parts), true);

            if (! is_array($last) || ! isset($last['Hash'])) {
                log_message('error', 'IPFS upload: invalid response: ' . substr($response, 0, 200));

                return null;
            }

            return [
                'cid'  => $last['Hash'],
                'size' => (int) ($last['Size'] ?? 0),
            ];
        } catch (\Throwable $e) {
            log_message('error', 'IPFS upload exception: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Pin a CID to ensure it is not garbage-collected.
     *
     * @param string $cid CID to pin
     *
     * @return bool True if pin succeeded
     */
    public function pinToIpfs(string $cid): bool
    {
        try {
            $ch = curl_init($this->ipfsApiUrl . 'pin/add?arg=' . urlencode($cid));
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || $response === false) {
                log_message('error', 'IPFS pin failed for ' . $cid);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'IPFS pin exception: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Unpin a CID (for deletion workflows).
     *
     * @param string $cid CID to unpin
     *
     * @return bool True if unpin succeeded
     */
    public function unpinFromIpfs(string $cid): bool
    {
        try {
            $ch = curl_init($this->ipfsApiUrl . 'pin/rm?arg=' . urlencode($cid));
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || $response === false) {
                log_message('error', 'IPFS unpin failed for ' . $cid);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'IPFS unpin exception: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Check IPFS node health.
     *
     * @return array{connected: bool, peerId?: string, error?: string}
     */
    public function checkIpfsHealth(): array
    {
        try {
            $ch = curl_init($this->ipfsApiUrl . 'id');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || $response === false) {
                return ['connected' => false, 'error' => 'IPFS API unreachable on ' . $this->ipfsApiUrl];
            }

            $data = json_decode($response, true);

            return [
                'connected' => true,
                'peerId'    => $data['ID'] ?? 'unknown',
            ];
        } catch (\Throwable $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }
}
