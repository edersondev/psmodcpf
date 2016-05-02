<?php

/**
 * Description of validatedoc
 *
 * @author Ederson Ferreira <ederson.dev@gmail.com>
 */
class ModulocpfValidatedocModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $cpfCnpj = filter_input(INPUT_GET, 'cpf_cnpj');
        $inputCpfCnpj = preg_replace("/[^0-9]/", "", $cpfCnpj);
        
        $doctype = ( strlen($inputCpfCnpj) > 11 ? 'cnpj' : 'cpf' );
        $arrRetorno = array(
            'status' => false,
            'doctype' => $doctype
        );
        
        try {
            if ( strlen($inputCpfCnpj) > 11 ) {
                $this->module->cnpjValidate($inputCpfCnpj);
            } else {
                $this->module->cpfValidation($inputCpfCnpj);
            }
            $arrRetorno['status'] = true;
        } catch (Exception $exc) {
            $arrRetorno['error'] = $exc->getMessage();
        }
        
        echo Tools::jsonEncode($arrRetorno);
        exit;
    }
}
