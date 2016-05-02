<?php
/**
 * Description of view
 *
 * @author Ederson Ferreira <ederson.dev@gmail.com>
 */
class ModulocpfViewModuleFrontController extends ModuleFrontController
{
    public $errors;
    
    public function initContent()
    {
        parent::initContent();
        
        $this->addJS(_MODULE_DIR_.$this->module->name.'/js/jquery.mask.min.js');
        
        // Redireciona para a tela de login caso não esteja logado
        if (!$this->context->customer->isLogged(true)){
            $back_url = $this->context->link->getModuleLink('modulocpf','view');
            $params = array('back' => $back_url);
            Tools::redirect($this->context->link->getPageLink('authentication', true, (int)$this->context->language->id, $params));
        }
        
        $id_customer = $this->context->customer->id;
        $db_prefix = _DB_PREFIX_;
        $sql = "SELECT * FROM `{$db_prefix}modulo_cpf` WHERE `ps_customer_id_customer` = {$id_customer}";
        $row = Db::getInstance()->getRow($sql);
        if ( $row ) {
            $this->context->smarty->assign('arrData', $row);
        }
        
        $arrDocTypes = array(
            array(
                'id' => '1',
                'name' => $this->module->l('Pessoa Jurídica')
            ),
            array(
                'id' => '2',
                'name' => $this->module->l('Pessoa física')
            )
        );
        $this->context->smarty->assign(array(
            'arrDocTypes' => $arrDocTypes,
            'urlValidateDoc' => $this->context->link->getModuleLink('modulocpf','validatedoc'),
            'id_customer' => $id_customer,
            'errors' => $this->errors
        ));
        
        $this->setTemplate('viewdoc.tpl');
    }
    
    public function postProcess()
    {
        if ( $_POST ) {
            $docType = Tools::getValue('doc_type');
            $nu_cpf_cnpj = Tools::getValue('cpf');
            $rg_ie = Tools::getValue('rg');
            if ( $docType === '1' ) {
                $nu_cpf_cnpj = Tools::getValue('cnpj');
                $rg_ie = Tools::getValue('nie');
            }
            $numberDoc = preg_replace("/[^0-9]/", "", $nu_cpf_cnpj);
            $id_customer = $this->context->customer->id;
            try {
                if ( strlen($numberDoc) > 11 ) {
                    $this->module->cnpjValidate($numberDoc);
                } else {
                    $this->module->cpfValidation($numberDoc);
                }
                
                Db::getInstance()->insert('modulo_cpf', array(
                    'nu_cpf_cnpj'               => pSQL($numberDoc),
                    'rg_ie'                     => pSQL($rg_ie),
                    'doc_type'                  => (int)$docType,
                    'ps_customer_id_customer'   => $id_customer
                ));
            } catch (Exception $exc) {
                $this->errors[] = Tools::displayError($exc->getMessage());
            }
        }
    }
}
