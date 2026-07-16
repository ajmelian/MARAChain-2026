<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\PaymentService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * PaymentsController — Stripe checkout and webhook endpoints.
 *
 * POST /payments/checkout    — Create Stripe Checkout session
 * POST /stripe/webhook       — Stripe webhook handler (no auth)
 * GET  /payments/success     — Payment success redirect
 * GET  /payments/cancel      — Payment cancel redirect
 *
 * @package App\Controllers
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.9.0
 */
class PaymentsController extends BaseController
{
    use ResponseTrait;

    /**
     * POST /payments/checkout — Create a Stripe Checkout session.
     *
     * @return ResponseInterface JSON with session URL
     */
    public function checkout(): ResponseInterface
    {
        $input = $this->request->getJSON(true);

        if ($input === null) {
            return $this->failValidationErrors('Invalid JSON body.');
        }

        $shieldUser = auth()->user();
        if ($shieldUser === null) {
            return $this->failUnauthorized('Authentication required.');
        }

        $userModel = model(\App\Models\UserModel::class);
        $customUser = $userModel->findByShieldUserId($shieldUser->id ?? 0);

        if ($customUser === null) {
            return $this->failNotFound('User profile not found.');
        }

        $transferId = $input['transferId'] ?? '';
        $priceId    = $input['priceId'] ?? '';
        $type       = $input['type'] ?? 'one_time';

        try {
            $service = new PaymentService();

            if ($type === 'subscription') {
                if (empty($priceId)) {
                    return $this->failValidationErrors('priceId is required for subscriptions.');
                }
                $result = $service->createSubscription($customUser->id, $priceId);
            } else {
                if (empty($transferId)) {
                    return $this->failValidationErrors('transferId is required for one-time payments.');
                }
                $amount  = (int) ($input['amount'] ?? 500);
                $currency = $input['currency'] ?? 'eur';
                $result = $service->createDocumentPayment($customUser->id, $transferId, $amount, $currency);
            }

            return $this->respond([
                'status'     => 'success',
                'sessionId'  => $result['sessionId'],
                'url'        => $result['url'],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Stripe checkout error: ' . $e->getMessage());

            return $this->fail($e->getMessage(), 400);
        }
    }

    /**
     * POST /stripe/webhook — Stripe webhook handler.
     *
     * No authentication required. Stripe signs the request.
     *
     * @return ResponseInterface
     */
    public function webhook(): ResponseInterface
    {
        $payload         = $this->request->getBody();
        $signatureHeader = $this->request->header('Stripe-Signature');

        if (empty($payload) || $signatureHeader === null) {
            return $this->respond(['status' => 'error', 'message' => 'Missing payload or signature.'], 400);
        }

        try {
            $service  = new PaymentService();
            $result   = $service->handleWebhook($payload, $signatureHeader->getValue());

            if ($result['handled']) {
                log_message('info', "Stripe webhook handled: {$result['type']}");
            }

            return $this->respond(['status' => 'ok', 'event' => $result['type']]);
        } catch (\Throwable $e) {
            log_message('error', "Stripe webhook error: {$e->getMessage()}");

            return $this->respond(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
