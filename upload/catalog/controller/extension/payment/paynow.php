<?php

class ControllerExtensionPaymentPaynow extends Controller
{
    const PAYNOW_PAYMENT_STATUS_NEW = "NEW";
    const PAYNOW_PAYMENT_STATUS_PENDING = "PENDING";
    const PAYNOW_PAYMENT_STATUS_REJECTED = "REJECTED";
    const PAYNOW_PAYMENT_STATUS_CONFIRMED = "CONFIRMED";
    const PAYNOW_PAYMENT_STATUS_ERROR = "ERROR";

    const ORDER_STATUS_PENDING = 1;
    const ORDER_STATUS_REJECTED = 7;
    const ORDER_STATUS_CONFIRMED = 2;
    const ORDER_STATUS_ERROR = 10;

    private $version = "1.0.5";
    private $apiClient = null;

    private $apiKey;
    private $signatureKey;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->initApiClient();
    }

    private function initApiClient()
    {
        require_once(DIR_SYSTEM . "library/vendor/paynow/autoload.php");

        $this->load->model("setting/setting");
        $isSandboxEnabled = (int)$this->config->get("payment_paynow_sandbox_enabled");
        $this->apiKey = $isSandboxEnabled ? $this->config->get("payment_paynow_sandbox_api_key") : $this->config->get("payment_paynow_production_api_key");
        $this->signatureKey = $isSandboxEnabled ? $this->config->get("payment_paynow_sandbox_signature_key") : $this->config->get("payment_paynow_production_signature_key");

        $this->apiClient = new \Paynow\Client(
            $this->apiKey,
            $this->signatureKey,
            $isSandboxEnabled ? \Paynow\Environment::SANDBOX : \Paynow\Environment::PRODUCTION,
            "OpenCart-" . VERSION . "/Plugin-" . $this->version
        );
    }

    public function index()
    {
        return $this->load->view("extension/payment/paynow", []);
    }

    public function pay()
    {
        $json = [];
        if ($this->session->data["payment_method"]["code"] == "paynow") {
            $this->language->load("extension/payment/paynow");
            $this->load->model("checkout/order");
            $this->load->model("extension/payment/paynow");

            try {
                $orderInfo = $this->model_checkout_order->getOrder($this->session->data["order_id"]);
                $paymentData = $this->sendPaymentData($orderInfo);

                // store payment data
                $this->model_extension_payment_paynow->storePaymentState(
                    $paymentData->paymentId,
                    $paymentData->status,
                    $orderInfo["order_id"]
                );
                $this->model_checkout_order->addOrderHistory($orderInfo["order_id"], self::ORDER_STATUS_PENDING, "Paynow: " . $paymentData->paymentId);
                $json = [
                    "redirect" => $paymentData->redirectUrl,
                ];
            } catch (\Paynow\Exception\PaynowException $exception) {
                $this->model_extension_payment_paynow->log($exception->getMessage() . ": " . json_encode($exception->getErrors()));
                $json = [
                    "error" => $this->language->get("payment_paynow_text_error")
                ];
            }
        }

        $this->response->addHeader("Content-Type: application/json");
        $this->response->setOutput(json_encode($json));
    }

    private function sendPaymentData($orderInfo)
    {
        $payment_data = [
            "amount" => $this->toAmount($orderInfo["total"], $orderInfo["currency_code"]),
            "currency" => $orderInfo["currency_code"],
            "externalId" => $orderInfo["order_id"],
            "description" => $this->language->get("payment_paynow_text_order") . $orderInfo["order_id"],
            "buyer" => [
                "email" => $orderInfo["email"],
                'firstName' => $orderInfo['firstname'],
                'lastName' => $orderInfo['lastname']
            ]
        ];
        $idempotencyKey = uniqid($orderInfo['order_id'], "_");
        $payment = new \Paynow\Service\Payment($this->apiClient);
        return $payment->authorize($payment_data, $idempotencyKey);
    }

    public function notifications()
    {
        $this->load->model("extension/payment/paynow");
        $this->load->model("checkout/order");

        $payload = trim(file_get_contents("php://input"));
        $headers = $this->getRequestHeaders();
        $notificationData = json_decode($payload, true);

        try {
            new \Paynow\Notification($this->signatureKey, $payload, $headers);
            $payment = $this->model_extension_payment_paynow->getLastPaymentStatus($notificationData["paymentId"]);
            if (!$payment) {
                $this->model_extension_payment_paynow->log("Order for payment not exists - " . $notificationData["paymentId"]);
                header('HTTP/1.1 400 Bad Request', true, 400);
                exit;
            }
            $this->updateOrderState($payment, $notificationData);
        } catch (\Exception $exception) {
            $this->model_extension_payment_paynow->log($exception->getMessage() . " - " . $notificationData["paymentId"]);
            header('HTTP/1.1 400 Bad Request', true, 400);
            exit;
        }

        header("HTTP/1.1 202 Accepted");
        exit;
    }

    /**
     * @deprecated
     */
    public function notification() {
        $this->notifications();
    }

    private function updateOrderState($payment, $notificationData)
    {
        $orderInfo = $this->model_checkout_order->getOrder($payment["id_order"]);
        if ($orderInfo) {
            if (!$this->isCorrectStatus($payment['status'], $notificationData['status'])) {
                throw new Exception('Order status transition is incorrect ' . $payment['status'] . ' - ' . $notificationData['status'] . ' for order ' . $orderInfo['order_id']);
            }
            switch ($notificationData['status']) {
                case self::PAYNOW_PAYMENT_STATUS_PENDING:
                    break;
                case self::PAYNOW_PAYMENT_STATUS_REJECTED:
                    $this->model_checkout_order->addOrderHistory($orderInfo['order_id'], self::ORDER_STATUS_REJECTED, "Paynow: " . $payment["id_payment"] . "- " . $notificationData['status']);
                    break;
                case self::PAYNOW_PAYMENT_STATUS_CONFIRMED:
                    $this->model_checkout_order->addOrderHistory($orderInfo['order_id'], self::ORDER_STATUS_CONFIRMED, "Paynow: " . $payment["id_payment"] . "- " . $notificationData['status']);
                    break;
                case self::PAYNOW_PAYMENT_STATUS_ERROR:
                    $this->model_checkout_order->addOrderHistory($orderInfo['order_id'], self::ORDER_STATUS_ERROR, "Paynow: " . $payment["id_payment"] . "- " . $notificationData['status']);
                    break;
            }

            $this->model_extension_payment_paynow->storePaymentState(
                $notificationData['paymentId'],
                $notificationData['status'],
                $orderInfo["order_id"],
                (new DateTime($notificationData['modifiedAt']))->format('Y-m-d H:i:s')
            );
        }
    }

    private function isCorrectStatus($previousStatus, $nextStatus)
    {
        $paymentStatusFlow = [
            self::PAYNOW_PAYMENT_STATUS_NEW => [
                self::PAYNOW_PAYMENT_STATUS_NEW,
                self::PAYNOW_PAYMENT_STATUS_PENDING,
                self::PAYNOW_PAYMENT_STATUS_ERROR,
                self::PAYNOW_PAYMENT_STATUS_CONFIRMED,
                self::PAYNOW_PAYMENT_STATUS_REJECTED
            ],
            self::PAYNOW_PAYMENT_STATUS_PENDING => [
                self::PAYNOW_PAYMENT_STATUS_CONFIRMED,
                self::PAYNOW_PAYMENT_STATUS_REJECTED
            ],
            self::PAYNOW_PAYMENT_STATUS_REJECTED => [self::PAYNOW_PAYMENT_STATUS_CONFIRMED],
            self::PAYNOW_PAYMENT_STATUS_CONFIRMED => [],
            self::PAYNOW_PAYMENT_STATUS_ERROR => [
                self::PAYNOW_PAYMENT_STATUS_CONFIRMED,
                self::PAYNOW_PAYMENT_STATUS_REJECTED
            ]
        ];
        $previousStatusExists = isset($paymentStatusFlow[$previousStatus]);
        $isChangePossible = in_array($nextStatus, $paymentStatusFlow[$previousStatus]);
        return $previousStatusExists && $isChangePossible;
    }

    private function getRequestHeaders()
    {
        if (!function_exists("apache_request_headers")) {
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) == "HTTP_") {
                    $subject = ucwords(str_replace("_", " ", strtolower(substr($key, 5))));
                    $headers[str_replace(" ", "-", $subject)] = $value;
                }
            }
            return $headers;
        }
        return apache_request_headers();
    }

    private function toAmount($value, $currencyCode)
    {
        return $this->currency->format(number_format($value * 100, 0, '', ''), $currencyCode, "", false);
    }
}