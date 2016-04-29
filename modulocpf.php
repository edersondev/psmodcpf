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
}
