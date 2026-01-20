<?php
/**
 * 2026 Dialog
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Axel Paillaud <contact@axelweb.fr>
 * @copyright 2026 Dialog
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade script for version 1.0.2
 * Creates askdialog_export_state table for resumable exports (handles timeout/interruptions)
 *
 * @param AskDialog $module
 *
 * @return bool
 */
function upgrade_module_1_0_2($module)
{
    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'askdialog_export_state` (
        `id_export_state` int(11) NOT NULL AUTO_INCREMENT,
        `id_shop` int(11) NOT NULL,
        `export_type` varchar(50) NOT NULL,
        `status` enum(\'in_progress\',\'completed\',\'failed\') NOT NULL DEFAULT \'in_progress\',
        `total_products` int(11) NOT NULL,
        `products_exported` int(11) NOT NULL DEFAULT 0,
        `batch_size` int(11) NOT NULL,
        `tmp_file_path` varchar(255) DEFAULT NULL,
        `id_lang` int(11) NOT NULL,
        `country_code` varchar(10) NOT NULL,
        `started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_export_state`),
        UNIQUE KEY `idx_shop_type` (`id_shop`, `export_type`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

    return Db::getInstance()->execute($sql);
}
