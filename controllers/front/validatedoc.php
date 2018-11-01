<?php

class PsmodcpfValidatedocModuleFrontController extends ModuleFrontController
{
  public function postProcess()
  {
    header('Content-Type: application/json');
    $documento = filter_input(INPUT_POST, 'documento');
    $arrRetorno = ['status' => false];
    if(is_null($documento)){
      echo Tools::jsonEncode($arrRetorno);
      exit;
    }
    try {
      $this->module->validarDocumento($documento);
      $arrRetorno['status'] = true;
    }catch(Exception $e){
      http_response_code(422);
      $arrRetorno['error'] = $e->getMessage();
    }
    echo Tools::jsonEncode($arrRetorno);
    exit;
  }
}