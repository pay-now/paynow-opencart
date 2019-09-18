<?php

class ModelExtensionPaymentPaynow extends Model
{
    public function createDatabaseTables() {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'paynow_payments` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `id_order` INT(10) UNSIGNED NOT NULL,
            `id_payment` varchar(30) NOT NULL,
            `status` varchar(64) NOT NULL,
            `created_at` datetime,
            `modified_at` datetime,
            UNIQUE (`id_payment`, `status`)
        )';
        $this->db->query($sql);
    }

    public function dropDatabaseTables()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "paynow_payments`;");
    }

    public function log($data)
    {
        $log = new Log('paynow.log');
        $log->write($data);
    }
}