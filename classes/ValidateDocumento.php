<?php

namespace PrestaShop\Module\Psmodcpf;

class ValidateDocumento
{

  public function validarDocumento($documento)
  {
    $nu_documento = preg_replace("/[^0-9]/", "", $documento);
    if(strlen($nu_documento) > 11){
      return $this->cnpjValidate($nu_documento);
    }
    return $this->cpfValidation($nu_documento);
  }

  private function cnpjValidate($cnpj)
	{
		$nulos = array("12345678909123","111111111111111","22222222222222","333333333333333",
			"44444444444444","55555555555555", "666666666666666","77777777777777",
			"88888888888888", "99999999999999","00000000000000");
		
		if (strlen($cnpj) <> 14) {
			return false;
		}
		
		if (!is_numeric($cnpj)) {
			return false;
		}
		
		if (in_array($cnpj, $nulos)) {
			return false;
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
		
		return $result;
  }
  
  private function cpfValidation($cpf)
	{
		$nulos = array("12345678909","11111111111","22222222222","33333333333",
			"44444444444","55555555555","66666666666", "77777777777",
			"88888888888", "99999999999", "00000000000");
		
		if (strlen($cpf) <> 11) {
			return false;
		}
		if (!is_numeric($cpf)) {
			return false;
		}
		/* Retorna falso se o cpf for nulo*/
		if (in_array($cpf, $nulos)) {
			return false;
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
			return false;
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
			return false;
		}
		return true;
	}
}