<?php

require_once 'vendor/autoload.php';

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

use PsmodCpf\Utils\ValidateDocumento;
use PsmodCpf\Utils\PsmodCpfAdmin;

class Psmodcpf extends Module
{
    use PsmodCpfAdmin;

    protected $config_form = false;

    public $mensagemError = 'Número inválido. Verifique por favor!';

    protected $_listOfHooks = [
        'actionFrontControllerSetMedia',
        'actionAdminControllerSetMedia',
        'validateCustomerFormFields',
        'actionCustomerAccountAdd',
        'actionCustomerAccountUpdate',
        'actionAdminCustomersFormModifier',
        'actionBeforeCreateCustomerFormHandler',
        'actionAfterCreateCustomerFormHandler',
        'actionBeforeUpdateCustomerFormHandler',
        'actionAfterUpdateCustomerFormHandler',
        'additionalCustomerFormFields',
        'actionCustomerFormBuilderModifier'
    ];

    public function __construct()
    {
        $this->name = 'psmodcpf';
        $this->tab = 'front_office_features';
        $this->version = '2.0.6';
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

        include(dirname(__FILE__) . '/sql/install.php');

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

    public function uninstall()
    {
        Configuration::deleteByName('PSMODCPF_LIVE_MODE');

        include(dirname(__FILE__) . '/sql/uninstall.php');

        foreach ($this->_listOfHooks as $hook) {
            $this->unregisterHook($hook);
        }

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookActionFrontControllerSetMedia()
    {
        if (Tools::getValue('controller') == 'order' || Tools::getValue('controller') == 'identity' || (Tools::getValue('controller') == 'authentication' && Tools::getValue('create_account') == '1')) {
            $this->context->controller->registerJavascript(
                'module-psmodcpf-jquerymask',
                'modules/' . $this->name . '/views/js/jquery.mask.min.js',
                ['priority' => 210]
            );
            $this->context->controller->registerJavascript(
                'module-psmodcpf-front',
                'modules/' . $this->name . '/views/js/front.js',
                ['priority' => 211]
            );
        }
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

    public function hookActionCustomerAccountAdd($params)
    {
        $form_data = [
            'documento' => Tools::getValue('documento'),
            'rg_ie' => Tools::getValue('rg_ie'),
            'tp_documento' => Tools::getValue('tp_documento')
        ];
        $this->insertDocumento($params['newCustomer']->id, $form_data);
    }

    public function hookActionCustomerAccountUpdate($params)
    {
        $form_data = [
            'documento' => Tools::getValue('documento'),
            'rg_ie' => Tools::getValue('rg_ie'),
            'tp_documento' => Tools::getValue('tp_documento')
        ];

        $result = $this->searchCustomer((int)$params['customer']->id);
        if ($result === false) {
            $this->insertDocumento($params['customer']->id, $form_data);
        }
    }

    public function hookActionAfterCreateCustomerFormHandler($params)
    {
        $id_customer = (int)$params['id'];
        $this->insertDocumento($id_customer, $params['form_data']);
    }

    public function hookActionAfterUpdateCustomerFormHandler($params)
    {
        $id_customer = (int)Tools::getValue('id_customer');
        $result = $this->searchCustomer($id_customer);
        if ($result === false) {
            $this->insertDocumento($id_customer, $params['form_data']);
        } else {
            $this->updateDocumento($id_customer, $params['form_data']);
        }
    }

    public function hookAdditionalCustomerFormFields($params)
    {
        $format = [];
        $tipoDocumento = (new FormField)
            ->setName('tp_documento')
            ->setType('radio-buttons')
            ->setLabel('Tipo de documento')
            ->addAvailableValue(1, 'CPF')
            ->addAvailableValue(2, 'CNPJ')
            ->setValue(1);
        $format[$tipoDocumento->getName()] = $tipoDocumento;

        $format['documento'] = (new FormField)
            ->setName('documento')
            ->setType('text')
            ->setLabel('Número')
            ->setRequired(true);

        $format['rg_ie'] = (new FormField)
            ->setName('rg_ie')
            ->setType('text')
            ->setLabel('RG')
            ->setMaxLength(45);

        $format['add_documento'] = (new FormField)
            ->setName('add_documento')
            ->setType('hidden')
            ->setValue('true');

        if (!is_null($this->context->customer->id)) {
            return $this->fillFieldsFront($format);
        }
        return $format;
    }

    private function insertDocumento($id_customer, $form_data)
    {
        $arrData = $this->getDataToDb($form_data, $id_customer, true);
        Db::getInstance()->insert('modulo_cpf', $arrData);
    }

    private function updateDocumento($id_customer, $form_data)
    {
        $arrData = $this->getDataToDb($form_data, $id_customer);
        Db::getInstance()->update('modulo_cpf', $arrData, 'id_customer = ' . (int)$id_customer);
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

    public function formatarDocumento($documento): string
    {
        return preg_replace("/[\D]/", "", $documento);
    }

    public function fillFieldsFront($format)
    {
        $result = $this->searchCustomer((int)$this->context->customer->id);
        if ($result) {
            $format['tp_documento']->setValue($result['tp_documento']);
            $format['documento']->setValue($result['documento']);
            $format['rg_ie']->setValue($result['rg_ie']);
            $format['add_documento']->setValue('false');
        }
        return $format;
    }

    private function searchCustomer($id_customer)
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

    public function checkDuplicate($documento, $id_customer = null)
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

    /**
     * Forma alternativa de lidar com mensagens de erro do Form
     */
    public function showErrorValidateForm($messagem): void
    {
        exit("
            <script>
                alert(\"{$messagem}\");
                history.back();
            </script>
        ");
    }
}
