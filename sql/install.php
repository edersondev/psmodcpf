<?php
/**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

$sql = array();

$db_prefix = _DB_PREFIX_;
$db_engine = _MYSQL_ENGINE_;
$sql[] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$db_prefix}modulo_cpf` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`nu_cpf_cnpj` VARCHAR(20) NULL,
	`rg_ie` VARCHAR(45) NULL,
	`doc_type` TINYINT NULL,
	`{$db_prefix}customer_id_customer` INT(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `fk_{$db_prefix}modulo_cpf_{$db_prefix}customer_idx` (`{$db_prefix}customer_id_customer` ASC),
	CONSTRAINT `fk_{$db_prefix}modulo_cpf_{$db_prefix}customer`
		FOREIGN KEY (`{$db_prefix}customer_id_customer`)
		REFERENCES `{$db_prefix}customer` (`id_customer`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION)
ENGINE={$db_engine} DEFAULT CHARSET=utf8;
EOF;

foreach ($sql as $query) {
	if (Db::getInstance()->execute($query) == false) {
		return false;
	}
}
