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

class Psmodcpf extends Module
{
	protected $config_form = false;

	public function __construct()
	{
		$this->name = 'psmodcpf';
		$this->tab = 'front_office_features';
		$this->version = '2.0.0';
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
			$this->registerHook('header') &&
			$this->registerHook('backOfficeHeader') &&
			$this->registerHook('validateCustomerFormFields') &&
			$this->registerHook('displayAdminCustomers') &&
			$this->registerHook('displayCustomerAccount') &&
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
		/**
		 * If values have been submitted in the form, process.
		 */
		if (((bool)Tools::isSubmit('submitPsmodcpfModule')) == true) {
			$this->postProcess();
		}

		$this->context->smarty->assign('module_dir', $this->_path);

		$output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

		return $output.$this->renderForm();
	}

	/**
	 * Create the form that will be displayed in the configuration of your module.
	 */
	protected function renderForm()
	{
		$helper = new HelperForm();

		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$helper->module = $this;
		$helper->default_form_language = $this->context->language->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitPsmodcpfModule';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
			.'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');

		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id,
		);

		return $helper->generateForm(array($this->getConfigForm()));
	}

	/**
	 * Create the structure of your form.
	 */
	protected function getConfigForm()
	{
		return array(
			'form' => array(
				'legend' => array(
				'title' => $this->l('Settings'),
				'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('Live mode'),
						'name' => 'PSMODCPF_LIVE_MODE',
						'is_bool' => true,
						'desc' => $this->l('Use this module in live mode'),
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => true,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => false,
								'label' => $this->l('Disabled')
							)
						),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'prefix' => '<i class="icon icon-envelope"></i>',
						'desc' => $this->l('Enter a valid email address'),
						'name' => 'PSMODCPF_ACCOUNT_EMAIL',
						'label' => $this->l('Email'),
					),
					array(
						'type' => 'password',
						'name' => 'PSMODCPF_ACCOUNT_PASSWORD',
						'label' => $this->l('Password'),
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				),
			),
		);
	}

	/**
	 * Set values for the inputs.
	 */
	protected function getConfigFormValues()
	{
		return array(
			'PSMODCPF_LIVE_MODE' => Configuration::get('PSMODCPF_LIVE_MODE', true),
			'PSMODCPF_ACCOUNT_EMAIL' => Configuration::get('PSMODCPF_ACCOUNT_EMAIL', 'contact@prestashop.com'),
			'PSMODCPF_ACCOUNT_PASSWORD' => Configuration::get('PSMODCPF_ACCOUNT_PASSWORD', null),
		);
	}

	/**
	 * Save form data.
	 */
	protected function postProcess()
	{
		$form_values = $this->getConfigFormValues();
		foreach (array_keys($form_values) as $key) {
			Configuration::updateValue($key, Tools::getValue($key));
		}
	}

	/**
	* Add the CSS & JavaScript files you want to be loaded in the BO.
	*/
	public function hookBackOfficeHeader()
	{
		if (Tools::getValue('module_name') == $this->name) {
			$this->context->controller->addJS($this->_path.'views/js/back.js');
			$this->context->controller->addCSS($this->_path.'views/css/back.css');
		}
	}

	/**
	 * Add the CSS & JavaScript files you want to be added on the FO.
	 */
	public function hookHeader()
	{
		$this->context->controller->addJS($this->_path.'/views/js/jquery.mask.min.js');
		$this->context->controller->addJS($this->_path.'/views/js/front.js');
		$this->context->controller->addCSS($this->_path.'/views/css/front.css');
	}

	public function hookValidateCustomerFormFields()
	{
		/* Place your code here. */
	}

	public function hookDisplayAdminCustomers()
	{
		/* Place your code here. */
	}

	public function hookDisplayCustomerAccount()
	{
		/* Place your code here. */
	}

	public function hookAdditionalCustomerFormFields()
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
			//->setErrors(['seii'=>'sdd'])
			;

		$format['url_ajax_validatedoc'] = (new FormField)
			->setName('url_ajax_validatedoc')
			->setType('hidden')
			->setValue($this->context->link->getModuleLink($this->name,'validatedoc'));

		return $format;
	}

	public function cnpjValidate($str)
	{
		$nulos = array("12345678909123","111111111111111","22222222222222","333333333333333",
			"44444444444444","55555555555555", "666666666666666","77777777777777",
			"88888888888888", "99999999999999","00000000000000");
		
		/* Retira todos os caracteres que nao sejam 0-9 */
		$cnpj = preg_replace("/[^0-9]/", "", $str);
		
		if (strlen($cnpj) <> 14) {
			throw new Exception('O CNPJ deve conter 14 dígitos!');
		}
		
		if (!is_numeric($cnpj)) {
			throw new Exception('Apenas números são aceitos!');
		}
		
		if (in_array($cnpj, $nulos)) {
			throw new Exception('CNPJ nulo. Verifique por favor!');
		}
		if (strlen($cnpj) > 14){
			$cnpj = substr($cnpj, 1);
		}
		$sum1 = 0;
		$sum2 = 0;
		$sum3 = 0;
		$calc1 = 5;
		$calc2 = 6;
		for ($i=0; $i <= 12; $i++) {
			$calc1 = $calc1 < 2 ? 9 : $calc1;
			$calc2 = $calc2 < 2 ? 9 : $calc2;
			if ($i <= 11) {
				$sum1 += $cnpj[$i] * $calc1;
			}
			
			$sum2 += $cnpj[$i] * $calc2;
			$sum3 += $cnpj[$i];
			$calc1--;
			$calc2--;
		}
		$sum1 %= 11;
		$sum2 %= 11;
		$result = ($sum3 && $cnpj[12] == ($sum1 < 2 ? 0 : 11 - $sum1) && $cnpj[13] == ($sum2 < 2 ? 0 : 11 - $sum2)) ? true : false;
		
		if(!$result) {
			throw new Exception('CNPJ inválido. Verifique por favor!');
		}
	}

	public function cpfValidation($item)
	{
		$nulos = array("12345678909","11111111111","22222222222","33333333333",
			"44444444444","55555555555","66666666666", "77777777777",
			"88888888888", "99999999999", "00000000000");
		
		/* Retira todos os caracteres que nao sejam 0-9 */
		$cpf = preg_replace("/[^0-9]/", "", $item);
		if (strlen($cpf) <> 11) {
			throw new Exception('O CPF deve conter 11 dígitos!');
		}
		if (!is_numeric($cpf)) {
			throw new Exception('Apenas números são aceitos!');
		}
		/* Retorna falso se o cpf for nulo*/
		if (in_array($cpf, $nulos)) {
			throw new Exception('CPF inválido!');
		}
		/*Calcula o penúltimo dígito verificador*/
		$acum = 0;
		for ($i = 0; $i < 9; $i++) {
			$acum += $cpf[$i] * (10 - $i);
		}
		$x = $acum % 11;
		$acum = ($x > 1) ? (11 - $x) : 0;
		/* Retorna falso se o digito calculado eh diferente do passado na string */
		if ($acum != $cpf[9]) {
			throw new Exception('CPF inválido. Verifique por favor!');
		}
		/*Calcula o último dígito verificador*/
		$acum = 0;
		for ($i = 0; $i < 10; $i++) {
			$acum += $cpf[$i] * (11 - $i);
		}
		$x = $acum % 11;
		$acum = ($x > 1) ? (11 - $x) : 0;
		/* Retorna falso se o digito calculado eh diferente do passado na string */
		if ($acum != $cpf[10]) {
			throw new Exception('CPF inválido. Verifique por favor!');
		}
	}

	public function validarDocumento($documento)
	{
		$doc = preg_replace("/[^0-9]/", "", $documento);
		if(strlen($doc) > 11){
			$this->cnpjValidate($documento);
		} else {
			$this->cpfValidation($documento);
		}
		$this->checkDuplicate($doc);
	}

	public function checkDuplicate($value)
	{
		$db_prefix = _DB_PREFIX_;
		$db = Db::getInstance();
		$result = $db->getRow("SELECT * FROM `{$db_prefix}modulo_cpf` WHERE `nu_cpf_cnpj` = '{$value}'");
		if($result !== false){
			throw new Exception('O documento informado já está cadastrado!');
		}
	}
}
