<?php

class PsmodcpfValidatedocModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        header('Content-Type: application/json');
        $documento = filter_input(INPUT_POST, 'documento');
        $arrRetorno = ['status' => false];
        if (is_null($documento)) {
            echo json_encode($arrRetorno);
            exit;
        }
        try {
            $this->module->validarDocumentoAjax($documento);
            $arrRetorno['status'] = true;
        } catch (Exception $e) {
            http_response_code(422);
            $arrRetorno['error'] = $e->getMessage();
        }
        echo json_encode($arrRetorno);
        exit;
    }
}
