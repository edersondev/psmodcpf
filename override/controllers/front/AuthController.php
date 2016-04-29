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
        
        try {
            if ( strlen($numberDoc) > 11 ) {
                $this->cnpjValidate($numberDoc);
            } else {
                $this->cpfValidation($numberDoc);
            }
        } catch (Exception $exc) {
            $this->errors[] = Tools::displayError($exc->getMessage());
        }
        
        parent::processSubmitAccount();
    }
    
    private function cnpjValidate($str)
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
    
    private function cpfValidation($item)
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
    
    private function checkDuplicate($value)
    {
        $db_prefix = _DB_PREFIX_;
        $db = Db::getInstance();
        $result = $db->getRow("SELECT * FROM `{$db_prefix}modulo_cpf` WHERE `nu_cpf_cnpj` = '{$value}'");
        return $result;
    }
}
