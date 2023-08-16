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

if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

use PsmodCpf\Utils\PsmodCpfAdmin;
use PsmodCpf\Utils\PsmodCpfFront;

class Psmodcpf extends Module
{
    use PsmodCpfAdmin, PsmodCpfFront;

    protected $config_form = false;

    public $mensagemError = 'Número inválido. Verifique por favor!';

    protected $_listOfHooks = [
        'actionFrontControllerSetMedia',
        'actionAdminControllerSetMedia',
        'validateCustomerFormFields',
        'actionCustomerAccountAdd',
        'actionCustomerAccountUpdate',
        'actionBeforeUpdateCustomerFormHandler',
        'actionAfterUpdateCustomerFormHandler',
        'additionalCustomerFormFields',
        'actionCustomerFormBuilderModifier',
        'actionBeforeCreateCustomerFormHandler',
        'actionAfterCreateCustomerFormHandler'
    ];

    public function __construct()
    {
        $this->name = 'psmodcpf';
        $this->tab = 'front_office_features';
        $this->version = '2.1.1';
        $this->author = 'Ederson Ferreira da Silva';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Módulo CPF');
        $this->description = $this->l('Adiciona o campo CPF / CNPJ no cadastro do cliente');

        $this->confirmUninstall = $this->l('Tem certeza de que deseja desinstalar o módulo CPF?');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install(): bool
    {
        Configuration::updateValue('PSMODCPF_LIVE_MODE', false);

        $this->createTable();
        
        return parent::install() && $this->registerHooks();
    }

    public function registerHooks(): bool
    {
        $validHook = true;

        foreach ($this->_listOfHooks as $hook) {
            if (!$this->registerHook($hook)) {
                $validHook = false;
            }
        }

        return $validHook;
    }

    /**
     * @return void
     */
    public function createTable(): void
    {
        $db_prefix = _DB_PREFIX_;
        $db_engine = _MYSQL_ENGINE_;
        $query = <<<SQL
        CREATE TABLE IF NOT EXISTS `{$db_prefix}modulo_cpf` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `documento` VARCHAR(20) NULL,
            `rg_ie` VARCHAR(45) NULL,
            `tp_documento` TINYINT NULL,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            `id_customer` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `{$db_prefix}modulo_cpf_UN` (`documento`),
        INDEX `fk_{$db_prefix}modulo_cpf_{$db_prefix}customer_idx` (`id_customer` ASC),
        CONSTRAINT `fk_{$db_prefix}modulo_cpf_{$db_prefix}customer`
            FOREIGN KEY (`id_customer`)
            REFERENCES `{$db_prefix}customer` (`id_customer`)
            ON DELETE CASCADE
            ON UPDATE NO ACTION)
        ENGINE={$db_engine} DEFAULT CHARSET=utf8;
SQL;

        Db::getInstance()->execute($query);
    }

    public function uninstall(): bool
    {
        Configuration::deleteByName('PSMODCPF_LIVE_MODE');

        foreach ($this->_listOfHooks as $hook) {
            $this->unregisterHook($hook);
        }

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent(): string
    {
        $this->context->smarty->assign('module_dir', $this->_path);

        return $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
    }

    public function insertDocumento($id_customer, $form_data): void
    {
        $arrData = $this->getDataToDb($form_data, $id_customer, true);
        Db::getInstance()->insert('modulo_cpf', $arrData);
    }

    public function updateDocumento($id_customer, $form_data): void
    {
        $arrData = $this->getDataToDb($form_data, $id_customer);
        Db::getInstance()->update('modulo_cpf', $arrData, 'id_customer = '.(int)$id_customer);
    }

    public function getDataToDb($form_data, $id_customer, $new_register = false): array
    {
        $dbDate = date('Y-m-d H:i:s');
        $arrData = [
            "documento" => $this->formatarDocumento($form_data['documento']),
            "rg_ie" => substr($form_data['rg_ie'], 0, 45),
            "tp_documento" => (int)$form_data['tp_documento'],
            "date_upd" => $dbDate
        ];

        if ($new_register) {
            $arrData['id_customer'] = $id_customer;
            $arrData['date_add'] = $dbDate;
        }

        return $arrData;
    }

    public function searchCustomer($id_customer): array|bool|object|null
    {
        if ($id_customer == 0) {
            return [];
        }
        $db_prefix = _DB_PREFIX_;
        $db = Db::getInstance();
        $sql = "SELECT * FROM `{$db_prefix}modulo_cpf` WHERE id_customer = {$id_customer}";
        return $db->getRow($sql);
    }

    public function checkDuplicate($documento, $id_customer = null): array|bool|object|null
    {
        $db_prefix = _DB_PREFIX_;
        $db = Db::getInstance();
        $doc = $this->formatarDocumento($documento);
        $sql = "SELECT * FROM `{$db_prefix}modulo_cpf` WHERE `documento` = '{$doc}'";
        if (!is_null($id_customer)) {
            $sql .= " AND id_customer != {$id_customer}";
        }
        return $db->getRow($sql);
    }

    public function formatarDocumento($documento): string
    {
        return preg_replace("/[\D]/", "", $documento);
    }
}
