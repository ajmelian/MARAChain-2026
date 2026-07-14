<?php

declare(strict_types=1);

namespace App\Services;

/**
 * External DLT anchor contract.
 *
 * Provides an abstraction over distributed ledger technology (DLT)
 * or blockchain anchoring. The internal ledger always exists; this
 * interface enables anchoring checkpoints to an external chain for
 * long-term integrity verification.
 *
 * The external blockchain receives roots and checkpoints only —
 * never document-level data.
 *
 * @package App\Services
 * @since   1.3.0
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 */
interface LedgerAnchorInterface
{
    /**
     * Anchor a checkpoint to the external ledger.
     *
     * Submits a checkpoint (merkle root, timestamp, metadata) to the
     * external DLT and returns a receipt that can be used for later
     * verification.
     *
     * @param  array $checkpoint Checkpoint data containing at minimum:
     *                           - merkleRoot: string
     *                           - timestamp:  int (Unix epoch)
     *                           - version:    int
     * @return array             Anchor receipt containing:
     *                           - anchorId:   string
     *                           - txHash:     string
     *                           - blockHeight:int
     *                           - timestamp:  int
     *
     * @throws \RuntimeException If anchoring fails due to network or consensus error.
     *
     * @since 1.3.0
     * @author Aythami Melián Perdomo <ajmelper@gmail.com>
     */
    public function anchor(array $checkpoint): array;

    /**
     * Verify an anchor receipt against the external ledger.
     *
     * Checks that a previously anchored checkpoint is still present
     * and unmodified on the external DLT.
     *
     * @param  array $receipt Anchor receipt returned by anchor().
     * @return array          Verification result containing:
     *                        - verified:   bool
     *                        - timestamp:  int
     *                        - confirmations: int
     *
     * @since 1.3.0
     * @author Aythami Melián Perdomo <ajmelper@gmail.com>
     */
    public function verify(array $receipt): array;

    /**
     * Get the current status of an anchor on the external ledger.
     *
     * @param  string $anchorId The anchor identifier returned by anchor().
     * @return string           Current status string
     *                          (e.g. "confirmed", "pending", "failed", "unknown").
     *
     * @since 1.3.0
     * @author Aythami Melián Perdomo <ajmelper@gmail.com>
     */
    public function status(string $anchorId): string;
}
