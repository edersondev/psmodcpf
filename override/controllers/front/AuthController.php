<?php

class AuthController extends AuthControllerCore
{
    protected function processSubmitAccount()
    {
        $docType = Tools::getValue('doc_type');
        $nu_cpf_cnpj = Tools::getValue('cpf');
        $rg_ie = Tools::getValue('rg');
        if ( $docType === '1' ) {
            $nu_cpf_cnpj = Tools::getValue('cnpj');
            $rg_ie = Tools::getValue('nie');
        }
        $numberDoc = preg_replace("/[^0-9]/", "", $nu_cpf_cnpj);
        
        $objModuloCpf = Module::getInstanceByName('modulocpf');
        
        try {
            if ( strlen($numberDoc) > 11 ) {
                $objModuloCpf->cnpjValidate($numberDoc);
            } else {
                $objModuloCpf->cpfValidation($numberDoc);
            }
        } catch (Exception $exc) {
            $this->errors[] = Tools::displayError($exc->getMessage());
        }
        
        parent::processSubmitAccount();
    }
}
