<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Uuid;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Webhook;

/**
 * PaymentService — Integracion con Stripe.
 *
 * Gestiona pagos unicos (pay-per-document) y suscripciones
 * (planes Basic, Pro, Enterprise) via Stripe Checkout y webhooks.
 *
 * @package App\Services
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.9.0
 */
class PaymentService
{
    private string $webhookSecret;

    public function __construct()
    {
        $secretKey = env('stripe.secretKey');
        if (empty($secretKey)) {
            throw new RuntimeException('Stripe secret key not configured.');
        }

        Stripe::setApiKey($secretKey);
        Stripe::setApiVersion('2025-02-24.acacia');

        $this->webhookSecret = env('stripe.webhookSecret') ?? '';
    }

    private function savePayment(array $data): void
    {
        $db = db_connect();
        $db->table('payments')->insert($data);
    }

    private function updatePaymentStatus(string $sessionId, string $status): void
    {
        $db = db_connect();
        $db->table('payments')
            ->where('stripe_session_id', $sessionId)
            ->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    private function updateSubscriptionStatus(string $subscriptionId, string $status): void
    {
        $db = db_connect();
        $db->table('payments')
            ->where('stripe_subscription_id', $subscriptionId)
            ->update(['subscription_status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Create a Stripe Checkout session for a one-time document payment.
     *
     * @param string $userId      Custom user UUID
     * @param string $transferId  Transfer UUID
     * @param int    $amountCents Amount in cents (e.g., 500 = 5.00 EUR)
     * @param string $currency    ISO 4217 currency code
     *
     * @return array{sessionId: string, url: string}
     */
    public function createDocumentPayment(
        string $userId,
        string $transferId,
        int $amountCents = 500,
        string $currency = 'eur'
    ): array {
        $baseUrl    = base_url();
        $successUrl = rtrim($baseUrl, '/') . '/inbox?payment=success';
        $cancelUrl  = rtrim($baseUrl, '/') . '/inbox?payment=cancelled';

        try {
            $session = Session::create([
                'mode'          => 'payment',
                'payment_method_types' => ['card'],
                'line_items'    => [[
                    'price_data' => [
                        'currency'     => $currency,
                        'product_data' => [
                            'name'        => 'Envio de documento - MARAChain',
                            'description' => 'Pago unico por envio de documento',
                        ],
                        'unit_amount'  => $amountCents,
                    ],
                    'quantity'   => 1,
                ]],
                'metadata'       => [
                    'user_id'    => $userId,
                    'transfer_id' => $transferId,
                    'type'       => 'document_send',
                ],
                'success_url'    => $successUrl,
                'cancel_url'     => $cancelUrl,
            ]);

            $this->savePayment([
                'id'                 => Uuid::v4(),
                'user_id'            => $userId,
                'stripe_session_id'  => $session->id,
                'amount'             => $amountCents,
                'currency'           => $currency,
                'status'             => 'pending',
                'payment_type'       => 'one_time',
                'transfer_id'        => $transferId,
            ]);

            return [
                'sessionId' => $session->id,
                'url'       => $session->url,
            ];
        } catch (ApiErrorException $e) {
            throw new RuntimeException('Stripe error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a Stripe Checkout session for subscription.
     *
     * @param string $userId    Custom user UUID
     * @param string $priceId   Stripe Price ID (from Stripe Dashboard)
     *
     * @return array{sessionId: string, url: string}
     */
    public function createSubscription(string $userId, string $priceId): array
    {
        $baseUrl    = base_url();
        $successUrl = rtrim($baseUrl, '/') . '/profile?subscription=active';
        $cancelUrl  = rtrim($baseUrl, '/') . '/profile?subscription=pending';

        try {
            $session = Session::create([
                'mode'          => 'subscription',
                'payment_method_types' => ['card'],
                'line_items'    => [[
                    'price'    => $priceId,
                    'quantity' => 1,
                ]],
                'metadata'       => [
                    'user_id' => $userId,
                    'type'    => 'subscription',
                ],
                'success_url'    => $successUrl,
                'cancel_url'     => $cancelUrl,
            ]);

            $this->savePayment([
                'id'                 => Uuid::v4(),
                'user_id'            => $userId,
                'stripe_session_id'  => $session->id,
                'status'             => 'pending',
                'payment_type'       => 'subscription',
                'subscription_plan'  => $priceId,
            ]);

            return [
                'sessionId' => $session->id,
                'url'       => $session->url,
            ];
        } catch (ApiErrorException $e) {
            throw new RuntimeException('Stripe error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Handle Stripe webhook event.
     *
     * @param string $payload         Raw request body
     * @param string $signatureHeader Stripe signature header
     *
     * @return array{type: string, handled: bool}
     */
    public function handleWebhook(string $payload, string $signatureHeader): array
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signatureHeader,
                $this->webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            throw new RuntimeException('Invalid webhook payload.', 0, $e);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            throw new RuntimeException('Invalid webhook signature.', 0, $e);
        }

        $session      = $event->data->object;
        $sessionId    = $session->id ?? $session->invoice ?? null;
        $handled      = true;

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->updatePaymentStatus($sessionId, 'completed');
                $this->completeTransferAfterPayment($session->metadata ?? []);
                break;

            case 'checkout.session.expired':
                $this->updatePaymentStatus($sessionId, 'expired');
                break;

            case 'invoice.paid':
                $subscriptionId = $session->subscription ?? '';
                $this->updateSubscriptionStatus($subscriptionId, 'active');
                break;

            case 'customer.subscription.deleted':
                $this->updateSubscriptionStatus($session->id ?? '', 'cancelled');
                break;

            default:
                $handled = false;
        }

        return ['type' => $event->type, 'handled' => $handled];
    }

    private function completeTransferAfterPayment(array $metadata): void
    {
        $transferId = $metadata['transfer_id'] ?? null;
        if ($transferId === null) {
            return;
        }

        $transferModel = model(\App\Models\DocumentTransferModel::class);
        $transfer      = $transferModel->freshEntity($transferId);

        if ($transfer !== null) {
            $transferModel->transitionStatus($transfer, 'READY');
        }
    }
}
