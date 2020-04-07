<?php

class ModelExtensionPaymentPaynow extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/paynow');
        $status = (int)$this->config->get('payment_paynow_status') === 1 ? true : false;
        $method = [];

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_paynow_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($total < 1) {
            $status = false;
        } elseif (!$this->config->get('payment_paynow_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        if ($status) {
            $method = [
                'code' => 'paynow',
                'title' => $this->language->get('payment_paynow_text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('payment_paynow_sort_order')
            ];

            return $method;
        }

        return $method;
    }

    public function storePaymentState($id_payment, $status, $id_order, $modified_at = null)
    {
        $modified_at = !$modified_at ? 'NOW()' : '"' . $modified_at . '"';
        $query = 'INSERT INTO ' . DB_PREFIX . 'paynow_payments (id_order, id_payment, status, created_at, modified_at) 
            VALUES (' . (int)$id_order . ', "' . $this->db->escape($id_payment) . '", "' . $this->db->escape($status) . '", NOW(), ' . $modified_at . ') 
            ON DUPLICATE KEY UPDATE modified_at=' . $modified_at;

        $this->db->query($query);
    }

    public function getLastPaymentStatus($id_payment)
    {
        $query = 'SELECT id_order, status, id_payment FROM  ' . DB_PREFIX . 'paynow_payments WHERE id_payment="' . $this->db->escape($id_payment) . '" ORDER BY created_at DESC';
        $result = $this->db->query($query)->row;

        if ($result) {
            return $result;
        }

        return null;
    }

    public function log($data)
    {
        $log = new Log('paynow.log');
        $log->write($data);
    }
}