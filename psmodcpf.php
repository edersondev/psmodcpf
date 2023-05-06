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

use PsmodCpf\Utils\ValidateDocumento;
use PsmodCpf\Utils\PsmodCpfAdmin;
use PsmodCpf\Utils\PsmodCpfFront;

class Psmodcpf extends Module
{
    use PsmodCpfAdmin, PsmodCpfFront;

    protected $config_form = false;

    public $mensagemError = 'Número inválido. Verifique por favor!';

    public function __construct()
    {
        $this->name = 'psmodcpf';
        $this->tab = 'front_office_features';
        $this->version = '2.0.7';
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
    public function install()
    {
        Configuration::updateValue('PSMODCPF_LIVE_MODE', false);

        $this->createTable();

        return parent::install() &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->registerHook('validateCustomerFormFields') &&
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->registerHook('actionCustomerAccountUpdate') &&
            $this->registerHook('actionAdminCustomersFormModifier') &&
            $this->registerHook('actionAdminCustomersControllerSaveBefore') &&
            $this->registerHook('actionAdminCustomersControllerSaveAfter') &&
            $this->registerHook('additionalCustomerFormFields') &&
            $this->registerHook('actionCustomerFormBuilderModifier');
    }

    /**
     * @return void
     */
    public function createTable()
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

    public function uninstall()
    {
        Configuration::deleteByName('PSMODCPF_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->context->smarty->assign('module_dir', $this->_path);

        return $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
    }

    public function hookValidateCustomerFormFields($params)
    {
        foreach ($params['fields'] as $field) {
            if ($field->getName() == 'documento') {
                $objValidateDoc = new ValidateDocumento();
                if (!$objValidateDoc->validarDocumento($field->getValue())) {
                    $field->addError($this->mensagemError);
                }
                $id_customer = (is_null($this->context->customer->id) ? 0 : (int)$this->context->customer->id);
                if ($this->checkDuplicate($field->getValue(), $id_customer) !== false) {
                    $field->addError('O documento informado já está cadastrado!');
                }
            }
        }
    }

    public function insertDocumento($id_customer)
    {
        $dbDate = date('Y-m-d H:i:s');
        $arrData = [
            "documento" => $this->formatarDocumento(Tools::getValue('documento')),
            "rg_ie" => substr(Tools::getValue('rg_ie'), 0, 45),
            "tp_documento" => (int)Tools::getValue('tp_documento'),
            "id_customer" => $id_customer,
            "date_add" => $dbDate,
            "date_upd" => $dbDate
        ];
        Db::getInstance()->insert('modulo_cpf', $arrData);
    }

    public function updateDocumento($id_customer)
    {
        $arrData = [
            "documento" => $this->formatarDocumento(Tools::getValue('documento')),
            "rg_ie" => substr(Tools::getValue('rg_ie'), 0, 45),
            "tp_documento" => (int)Tools::getValue('tp_documento'),
            "date_upd" => date('Y-m-d H:i:s')
        ];
        Db::getInstance()->update('modulo_cpf', $arrData, 'id_customer = '.(int)$id_customer);
    }

    public function searchCustomer($id_customer)
    {
        if ($id_customer == 0) {
            return [];
        }
        $db_prefix = _DB_PREFIX_;
        $db = Db::getInstance();
        $sql = "SELECT * FROM `{$db_prefix}modulo_cpf` WHERE id_customer = {$id_customer}";
        return $db->getRow($sql);
    }

    public function validarDocumento($documento)
    {
        $objValidateDoc = new ValidateDocumento();
        if (!$objValidateDoc->validarDocumento($documento)) {
            throw new Exception($this->mensagemError);
        }
        $id_customer = (is_null($this->context->customer->id) ? null : $this->context->customer->id);
        if ($this->checkDuplicate($documento, $id_customer) !== false) {
            throw new Exception('O documento informado já está cadastrado!');
        }
    }

    public function checkDuplicate($documento, $id_customer = 0)
    {
        $db_prefix = _DB_PREFIX_;
        $db = Db::getInstance();
        $doc = $this->formatarDocumento($documento);
        $sql = "SELECT * FROM `{$db_prefix}modulo_cpf` WHERE `documento` = '{$doc}'";
        if ($id_customer > 0) {
            $sql .= " AND id_customer != {$id_customer}";
        }
        return $db->getRow($sql);
    }

    public function formatarDocumento($documento)
    {
        return preg_replace("/[\D]/", "", $documento);
    }
}
