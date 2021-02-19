<?php

class ControllerExtensionPaymentPaynow extends Controller
{
    private $version = "1.0.4";

    private $error = array();

    private $apiClient;

    public function install()
    {
        $this->load->model('extension/payment/paynow');
        $this->load->model('setting/setting');

        $default_settings = array(
            'payment_paynow_sandbox_enabled' => 0,
            'payment_paynow_sort_order' => 1,
            'payment_paynow_geo_zone' => 0
        );
        $this->model_setting_setting->editSetting('payment_paynow', $default_settings);
        $this->model_extension_payment_paynow->createDatabaseTables();
    }

    public function uninstall()
    {
        $this->load->model('extension/payment/paynow');
        $this->load->model('setting/setting');

        $this->model_setting_setting->deleteSetting('payment_paynow');
        $this->model_extension_payment_paynow->dropDatabaseTables();
    }

    public function index()
    {
        $this->load->language("extension/payment/paynow");
        $this->document->setTitle($this->language->get("heading_title"));
        $this->load->model("setting/setting");

        if (($this->request->server["REQUEST_METHOD"] == "POST") && $this->validate()) {
            $this->sendShopUrlsConfiguration();
            $this->model_setting_setting->editSetting("payment_paynow", $this->request->post);
            $this->session->data["success"] = $this->language->get("text_success");
            $this->response->redirect($this->url->link("marketplace/extension", "user_token=" . $this->session->data["user_token"] . "&type=payment", true));
        }

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data["error_sandbox_api_key"] = isset($this->error["sandbox_api_key"]) ? $this->error["sandbox_api_key"] : "";
        $data["error_sandbox_signature_key"] = isset($this->error["sandbox_signature_key"]) ? $this->error["sandbox_signature_key"] : "";
        $data["error_production_api_key"] = isset($this->error["production_api_key"]) ? $this->error["production_api_key"] : "";
        $data["error_production_signature_key"] = isset($this->error["production_signature_key"]) ? $this->error["production_signature_key"] : "";

        $data["payment_paynow_status"] = $this->getConfigValue("payment_paynow_status");
        $data["payment_paynow_sort_order"] = $this->getConfigValue("payment_paynow_sort_order");
        $data["payment_paynow_geo_zone"] = $this->getConfigValue("payment_paynow_geo_zone");

        $data["payment_paynow_sandbox_enabled"] = $this->getConfigValue("payment_paynow_sandbox_enabled");
        $data["payment_paynow_sandbox_api_key"] = $this->getConfigValue("payment_paynow_sandbox_api_key");
        $data["payment_paynow_sandbox_signature_key"] = $this->getConfigValue("payment_paynow_sandbox_signature_key");
        $data["payment_paynow_production_api_key"] = $this->getConfigValue("payment_paynow_production_api_key");
        $data["payment_paynow_production_signature_key"] = $this->getConfigValue("payment_paynow_production_signature_key");

        $data["breadcrumbs"] = [];
        $data["breadcrumbs"][] = [
            "text" => $this->language->get("text_home"),
            "href" => $this->url->link("common/dashboard", "user_token=" . $this->session->data["user_token"], true)
        ];

        $data["breadcrumbs"][] = [
            "text" => $this->language->get("text_extension"),
            "href" => $this->url->link("marketplace/extension", "user_token=" . $this->session->data["user_token"] . "&type=payment", true)
        ];

        $data["breadcrumbs"][] = [
            "text" => $this->language->get("heading_title"),
            "href" => $this->url->link("extension/payment/paynow", "user_token=" . $this->session->data["user_token"], true)
        ];

        $data["action"] = $this->url->link("extension/payment/paynow", "user_token=" . $this->session->data["user_token"], true);
        $data["cancel"] = $this->url->link("marketplace/extension", "user_token=" . $this->session->data["user_token"] . "&type=payment", true);

        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");

        $this->response->setOutput($this->load->view("extension/payment/paynow", $data));
    }

    private function initApiClient()
    {
        require_once(DIR_SYSTEM . 'library/vendor/paynow/autoload.php');

        $this->load->model("setting/setting");
        $isSandboxEnabled = $this->getConfigValue('payment_paynow_sandbox_enabled');
        $apiKey = $isSandboxEnabled ? $this->getConfigValue('payment_paynow_sandbox_api_key') : $this->getConfigValue('payment_paynow_production_api_key');
        $signatureKey = $isSandboxEnabled ? $this->getConfigValue('payment_paynow_sandbox_signature_key') : $this->getConfigValue('payment_paynow_production_signature_key');

        $this->apiClient = new \Paynow\Client(
            $apiKey,
            $signatureKey,
            $isSandboxEnabled ? \Paynow\Environment::SANDBOX : \Paynow\Environment::PRODUCTION,
            'OpenCart-' . VERSION . '/Plugin-' . $this->version
        );
    }

    private function sendShopUrlsConfiguration()
    {
        $this->load->model('extension/payment/paynow');
        try {
            $this->initApiClient();
            $shopConfiguration = new \Paynow\Service\ShopConfiguration($this->apiClient);
            $shopConfiguration->changeUrls(
                $this->buildBaseUrlWithRoute('checkout/success'),
                $this->buildBaseUrlWithRoute('extension/payment/paynow/notifications')
            );
        } catch (Paynow\Exception\PaynowException $exception) {
            $this->model_extension_payment_paynow->log($exception->getMessage() . ": " . json_encode($exception->getErrors()));
        }
    }

    private function getConfigValue($name)
    {
        return isset($this->request->post[$name]) ? $this->request->post[$name] : $this->config->get($name);
    }

    protected function validate()
    {
        if (!$this->user->hasPermission("modify", "extension/payment/paynow")) {
            $this->error["warning"] = $this->language->get("error_permission");
        }

        if ((int)$this->request->post["payment_paynow_sandbox_enabled"]) {
            if (!$this->request->post["payment_paynow_sandbox_api_key"]) {
                $this->error["sandbox_api_key"] = $this->language->get("error_sandbox_key");
            }
            if (!$this->request->post["payment_paynow_sandbox_signature_key"]) {
                $this->error["sandbox_signature_key"] = $this->language->get("error_sandbox_key");
            }
        } else {
            if (!$this->request->post["payment_paynow_production_api_key"]) {
                $this->error["production_api_key"] = $this->language->get("error_prod_key");
            }
            if (!$this->request->post["payment_paynow_production_signature_key"]) {
                $this->error["production_signature_key"] = $this->language->get("error_prod_key");
            }
        }

        return !$this->error;
    }

    private function buildBaseUrlWithRoute($route)
    {
        $context_url = parse_url($this->url->link($route, '', true));
        $base_url = new Url($context_url['scheme'] . '://' . $context_url['host'] . '/');
        return $base_url->link($route);
    }
}