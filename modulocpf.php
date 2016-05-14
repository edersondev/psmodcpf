<?php

/**
 * Description of modulocpf
 *
 * @author Ederson Ferreira <ederson.dev@gmail.com>
 */

if (!defined('_PS_VERSION_')) { exit; }

class Modulocpf extends Module
{
    public function __construct()
    {
        $this->name = 'modulocpf';
        $this->tab = 'front_office_features';
        $this->version = '1.0';
        $this->author = 'Ederson Ferreira';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_); 
        $this->module_key = '11d9b64cbd7fbcb0355811e490ffcd04';
        
        parent::__construct();
        
        $this->displayName = 'Módulo CPF';
        $this->description = 'Adiciona o campo CPF / CNPJ no cadastro do cliente';
        
        $themeOverrides = Configuration::get('PS_DISABLE_OVERRIDES');
        if ( $themeOverrides === '1' ) {
            $this->warning = $this->l('Modo de reescrita de controller está desativada. ');
        }
    }
    
    public function install()
    {
        if ( !parent::install() || 
                !$this->createTableCpf() || 
                !$this->registerHook('createAccountForm') || 
                !$this->registerHook('displayCustomerAccount') || 
                !$this->registerHook('displayAdminCustomers') || 
                !$this->registerHook(array('header', 'footer', 'actionCustomerAccountAdd'))
            ){
            return false;
        }
        parent::processDeferedClearCache();
        return true;
    }
    
    public function uninstall()
    {
        if ( !parent::uninstall() || !parent::removeOverride('AuthController') ){
            return false;
        }
        parent::processDeferedClearCache();
        return true;
    }
    
    public function createTableCpf()
    {
        $db_prefix = _DB_PREFIX_;
        $sql = <<<EOF
            CREATE TABLE IF NOT EXISTS `{$db_prefix}modulo_cpf` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `nu_cpf_cnpj` VARCHAR(20) NULL,
                `rg_ie` VARCHAR(45) NULL,
                `doc_type` TINYINT NULL,
                `ps_customer_id_customer` INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `fk_ps_modulo_cpf_ps_customer_idx` (`ps_customer_id_customer` ASC),
                CONSTRAINT `fk_ps_modulo_cpf_ps_customer`
                  FOREIGN KEY (`ps_customer_id_customer`)
                  REFERENCES `{$db_prefix}customer` (`id_customer`)
                  ON DELETE CASCADE
                  ON UPDATE NO ACTION)
            ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOF;
        
        try {
            Db::getInstance()->execute($sql);
            return true;
        } catch (Exception $exc) {
            return false;
        }
    }
    
    public function hookcreateAccountForm()
    {
        $this->context->controller->addJS($this->_path . 'js/jquery.mask.min.js');
        
        $arrDocTypes = array(
            array(
                'id' => '1',
                'name' => $this->l('Pessoa Jurídica')
            ),
            array(
                'id' => '2',
                'name' => $this->l('Pessoa física')
            )
        );
        
        $this->smarty->assign(array(
            'arrDocTypes' => $arrDocTypes,
            'urlValidateDoc' => $this->context->link->getModuleLink('modulocpf','validatedoc')
        ));
        
        return $this->display(__FILE__, 'blockcpf.tpl');
    }
    
    
    /**
    * Hook executado após a inclusão do cliente
    *
    * @param $params
    *
    * @return bool
    */
    public function hookActionCustomerAccountAdd($params)
    {
        $postData = $params['_POST'];
       
        $docType = $postData['doc_type'];
        $nu_cpf_cnpj = $postData['cpf'];
        $rg_ie = $postData['rg'];
        if ( $docType === '1' ) {
            $nu_cpf_cnpj = $postData['cnpj'];
            $rg_ie = $postData['nie'];
        }
        $numberDoc = preg_replace("/[^0-9]/", "", $nu_cpf_cnpj);
        
        $idCustomer = $params['newCustomer']->id;
        
        try {
            if ( !empty($nu_cpf_cnpj) ) {
                Db::getInstance()->insert('modulo_cpf', array(
                    'nu_cpf_cnpj'               => pSQL($numberDoc),
                    'rg_ie'                     => pSQL($rg_ie),
                    'doc_type'                  => (int)$docType,
                    'ps_customer_id_customer'   => $idCustomer
                ));
            }
            return true;
        } catch (Exception $exc) {
            return false;
        }
    }
    
    public function hookDisplayAdminCustomers($params)
    {
        $id_customer = $params['id_customer'];
        $db_prefix = _DB_PREFIX_;
        $sql = "SELECT * FROM `{$db_prefix}modulo_cpf` WHERE `ps_customer_id_customer` = {$id_customer}";
        $row = Db::getInstance()->getRow($sql);
        if ( $row ) {
            $this->smarty->assign('arrData', $row);
        }
        return $this->display(__FILE__, 'adminblockcpf.tpl');
    }
    
    public function hookDisplayCustomerAccount()
    {
        $linkView = $this->context->link->getModuleLink('modulocpf','view');
        $this->smarty->assign('linkView', $linkView);
        return $this->display(__FILE__, 'frontblockcpf.tpl');
    }
    
    public function getContent()
    {
        $themeOverrides = Configuration::get('PS_DISABLE_OVERRIDES');
        $this->smarty->assign('themeOverrides', $themeOverrides);
        return $this->display(__file__, 'aviso.tpl');
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
        
        if($this->checkDuplicate($cnpj) !== false) {
            throw new Exception('Este CNPJ já está cadastrado!');
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

        if($this->checkDuplicate($cpf) !== false) {
            throw new Exception('Este CPF já está cadastrado!');
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
    
    public function checkDuplicate($value)
    {
        $db_prefix = _DB_PREFIX_;
        $db = Db::getInstance();
        $result = $db->getRow("SELECT * FROM `{$db_prefix}modulo_cpf` WHERE `nu_cpf_cnpj` = '{$value}'");
        return $result;
    }
}
