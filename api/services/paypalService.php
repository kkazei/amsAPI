<?php
require 'vendor/autoload.php';

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use Dotenv\Dotenv;


class PayPalService {
    private $apiContext;

    public function __construct() {
        // Hardcoded PayPal credentials
        $clientId = 'ARD6z3nbLTy8I8WaedSIAUOsiz1z6W2WTSv9kDhZ_9j9e_N-ILAKQiEq_lxXAcCBrDW0yKtG3iJ2ZNcF';
        $secret = 'EFD93MtH6p-k4-7qLJmg5yJdXVvUfkl0kE_TrwBdwQtQlRwhw_EYkfQjcWQFUxdyJjMcmdKTCCk4zobY';
        $mode = 'sandbox'; // or 'live'

        $this->apiContext = new ApiContext(
            new OAuthTokenCredential($clientId, $secret)
        );
        $this->apiContext->setConfig([
            'mode' => $mode
        ]);
    }

    public function createPayment($amount, $currency, $returnUrl, $cancelUrl) {
        $payer = new \PayPal\Api\Payer();
        $payer->setPaymentMethod('paypal');

        $amountObj = new \PayPal\Api\Amount();
        $amountObj->setTotal($amount);
        $amountObj->setCurrency($currency);

        $transaction = new \PayPal\Api\Transaction();
        $transaction->setAmount($amountObj);
        $transaction->setDescription('Payment description');

        $redirectUrls = new \PayPal\Api\RedirectUrls();
        $redirectUrls->setReturnUrl($returnUrl);
        $redirectUrls->setCancelUrl($cancelUrl);

        $payment = new \PayPal\Api\Payment();
        $payment->setIntent('sale');
        $payment->setPayer($payer);
        $payment->setTransactions([$transaction]);
        $payment->setRedirectUrls($redirectUrls);

        try {
            $payment->create($this->apiContext);
            return $payment;
        } catch (Exception $ex) {
            throw new Exception('Unable to create payment: ' . $ex->getMessage());
        }
    }

    public function executePayment($paymentId, $payerId) {
        $payment = \PayPal\Api\Payment::get($paymentId, $this->apiContext);

        $execution = new \PayPal\Api\PaymentExecution();
        $execution->setPayerId($payerId);

        try {
            $result = $payment->execute($execution, $this->apiContext);
            return $result;
        } catch (Exception $ex) {
            throw new Exception('Unable to execute payment: ' . $ex->getMessage());
        }
    }
}
?>