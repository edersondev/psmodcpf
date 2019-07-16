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

include(dirname(__FILE__).'/classes/ValidateDocumento.php');

use PrestaShop\Module\Psmodcpf\ValidateDocumento;

class Psmodcpf extends Module
{
	protected $config_form = false;

	public $mensagemError = 'Número inválido. Verifique por favor!';

	public function __construct()
	{
		$this->name = 'psmodcpf';
		$this->tab = 'front_office_features';
		$this->version = '2.0.3';
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

		include(dirname(__FILE__).'/sql/install.php');

		return parent::install() &&
			$this->registerHook('actionFrontControllerSetMedia') &&
			$this->registerHook('actionAdminControllerSetMedia') &&
			$this->registerHook('validateCustomerFormFields') &&
			$this->registerHook('actionCustomerAccountAdd') &&
			$this->registerHook('actionCustomerAccountUpdate') &&
			$this->registerHook('actionAdminCustomersFormModifier') &&
			$this->registerHook('actionAdminCustomersControllerSaveBefore') &&
			$this->registerHook('actionAdminCustomersControllerSaveAfter') &&
			$this->registerHook('additionalCustomerFormFields');
	}

	public function uninstall()
	{
		Configuration::deleteByName('PSMODCPF_LIVE_MODE');

		include(dirname(__FILE__).'/sql/uninstall.php');

		return parent::uninstall();
	}

	/**
	 * Load the configuration form
	 */
	public function getContent()
	{
		$this->context->smarty->assign('module_dir', $this->_path);

		$output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

		return $output;
	}

	/**
	* Add the CSS & JavaScript files you want to be loaded in the BO.
	*/
	public function hookActionAdminControllerSetMedia()
	{
		
		if (Tools::getValue('module_name') == $this->name) {
			$this->context->controller->addCSS($this->_path.'views/css/back.css');
		}

		if (Tools::getValue('controller') == 'AdminCustomers'){
			$this->context->controller->addJS($this->_path.'views/js/jquery.mask.min.js');
			$this->context->controller->addJS($this->_path.'views/js/back.js');
		}
	}

	/**
	 * Add the CSS & JavaScript files you want to be added on the FO.
	 */
	public function hookActionFrontControllerSetMedia()
	{
		if (Tools::getValue('controller') == 'order' || Tools::getValue('controller') == 'identity' || (Tools::getValue('controller') == 'authentication' && Tools::getValue('create_account') == '1')){
			$this->context->controller->registerJavascript(
					'module-psmodendereco-jquerymask',
					'modules/'.$this->name.'/views/js/jquery.mask.min.js',
					['priority' => 210]
			);
			$this->context->controller->registerJavascript(
					'module-psmodendereco-front',
					'modules/'.$this->name.'/views/js/front.js',
					['priority' => 211]
			);
		}
	}

	public function hookValidateCustomerFormFields($params)
	{
		foreach($params['fields'] as $field){
			if($field->getName() == 'documento'){
				$objValidateDoc = new ValidateDocumento();
				if(!$objValidateDoc->validarDocumento($field->getValue())){
					$field->addError($this->mensagemError);
				}
				$id_customer = ( is_null($this->context->customer->id) ? null : $this->context->customer->id );
				if($this->checkDuplicate($field->getValue(), $id_customer) !== false){
					$field->addError('O documento informado já está cadastrado!');
				}
			}
		}
	}

	public function hookActionCustomerAccountAdd($params)
	{
		$this->insertDocumento($params['newCustomer']->id);
	}

	public function hookActionCustomerAccountUpdate($params)
	{
		$result = $this->searchCustomer($params['customer']->id);
		if($result === false){
			$this->insertDocumento($params['customer']->id);
		}
	}

	public function hookActionAdminCustomersFormModifier($params)
	{
		$extraInputs = &$params['fields'][0]['form']['input'];
		$extraInputs[] = [
			'type' => 'radio',
			'label' => 'Tipo de documento',
			'name' => 'tp_documento',
			'required' => true,
			'class' => 't',
			'values' => [
				[
					'id' => 'documento_1',
					'value' => 1,
					'label' => 'CPF'
				],
				[
					'id' => 'documento_2',
					'value' => 2,
					'label' => 'CNPJ'
				]
			]
		];

		$extraInputs[] = [
			'type' => 'text',
			'label' => 'Número',
			'name' => 'documento',
			'required' => true,
			'col' => '2'
		];

		$extraInputs[] = [
			'type' => 'text',
			'label' => 'RG',
			'name' => 'rg_ie',
			'required' => false,
			'col' => '2'
		];

		$id_customer = $params['object']->id;
		$result = $this->searchCustomer($id_customer);

		$extraValues = &$params['fields_value'];
		$extraValues['tp_documento'] = ( isset($result['tp_documento']) ? $result['tp_documento'] : 1 );
		$extraValues['documento'] = ( isset($result['documento']) ? $result['documento'] : null );
		$extraValues['rg_ie'] = ( isset($result['rg_ie']) ? $result['rg_ie'] : null );
	}

	public function hookActionAdminCustomersControllerSaveBefore($params)
	{
		$objValidateDoc = new ValidateDocumento();
		$documento = Tools::getValue('documento');
		$id_customer = Tools::getValue('id_customer');

		if(!$objValidateDoc->validarDocumento($documento)){
			$params['controller']->errors[] = $this->mensagemError;
		}
		if($this->checkDuplicate($documento,$id_customer) !== false){
			$params['controller']->errors[] = "O documento '{$documento}' já está cadastrado!";
		}
	}

	public function hookActionAdminCustomersControllerSaveAfter($params)
	{
		$id_customer = Tools::getValue('id_customer');
		$result = $this->searchCustomer($id_customer);
		if($result === false){
			$this->insertDocumento($id_customer);
		} else {
			$this->updateDocumento($id_customer);
		}
	}

	public function hookAdditionalCustomerFormFields($params)
	{
		$format = [];
		$tipoDocumento = (new FormField)
			->setName('tp_documento')
			->setType('radio-buttons')
			->setLabel('Tipo de documento')
			->addAvailableValue(1,'CPF')
			->addAvailableValue(2,'CNPJ')
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

		$format['url_ajax_validatedoc'] = (new FormField)
			->setName('url_ajax_validatedoc')
			->setType('hidden')
			->setValue($this->context->link->getModuleLink($this->name,'validatedoc'));

		$format['add_documento'] = (new FormField)
			->setName('add_documento')
			->setType('hidden')
			->setValue('true');

		if(!is_null($this->context->customer->id)){
			return $this->fillFields($format);
		}
		return $format;
	}

	private function insertDocumento($id_customer)
	{
		$arrData = [
			"documento" => preg_replace("/[^0-9]/", "", Tools::getValue('documento')),
			"rg_ie" => substr(Tools::getValue('rg_ie'), 0, 45),
			"tp_documento" => (int)Tools::getValue('tp_documento'),
			"id_customer" => $id_customer,
			"date_add" => date('Y-m-d H:i:s'),
			"date_upd" => date('Y-m-d H:i:s')
		];
		Db::getInstance()->insert('modulo_cpf',$arrData);
	}

	private function updateDocumento($id_customer)
	{
		$arrData = [
			"documento" => preg_replace("/[^0-9]/", "", Tools::getValue('documento')),
			"rg_ie" => substr(Tools::getValue('rg_ie'), 0, 45),
			"tp_documento" => (int)Tools::getValue('tp_documento'),
			"date_upd" => date('Y-m-d H:i:s')
		];
		Db::getInstance()->update('modulo_cpf', $arrData, 'id_customer = '.(int)$id_customer );
	}

	public function fillFields($format)
	{
		$result = $this->searchCustomer($this->context->customer->id);
		if($result){
			$format['tp_documento']->setValue($result['tp_documento']);
			$format['documento']->setValue($result['documento']);
			$format['rg_ie']->setValue($result['rg_ie']);
			$format['add_documento']->setValue('false');
		}
		return $format;
	}

	private function searchCustomer($id_customer)
	{
		$db_prefix = _DB_PREFIX_;
		$db = Db::getInstance();
		$sql = "SELECT * FROM `{$db_prefix}modulo_cpf` WHERE id_customer = {$id_customer}";
		return $db->getRow($sql);
	}

	public function validarDocumento($documento)
	{
		$objValidateDoc = new ValidateDocumento();
		if(!$objValidateDoc->validarDocumento($documento)){
			throw new Exception($this->mensagemError);
		}
		$id_customer = ( is_null($this->context->customer->id) ? null : $this->context->customer->id );
		if($this->checkDuplicate($documento,$id_customer) !== false){
			throw new Exception('O documento informado já está cadastrado!');
		}
	}

	public function checkDuplicate($documento, $id_customer = null)
	{
		$db_prefix = _DB_PREFIX_;
		$db = Db::getInstance();
		$doc = preg_replace("/[^0-9]/", "", $documento);
		$sql = "SELECT * FROM `{$db_prefix}modulo_cpf` WHERE `documento` = '{$doc}'";
		if(!is_null($id_customer)){
			$sql .= " AND id_customer != {$id_customer}";
		}
		$result = $db->getRow($sql);
		return $result;
	}
}
