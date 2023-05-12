<?php

namespace PsmodCpf\Utils;

use Tools;
use FormField;

trait PsmodCpfFront
{
    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookActionFrontControllerSetMedia(): void
    {
        if (
            Tools::getValue('controller') == 'order' ||
            Tools::getValue('controller') == 'identity' ||
            Tools::getValue('controller') == 'registration' ||
            (Tools::getValue('controller') == 'authentication' && Tools::getValue('create_account') == '1')
        ) {
            $this->context->controller->registerJavascript(
                'module-psmodcpf-jquerymask',
                'modules/'.$this->name.'/views/js/jquery.mask.min.js',
                ['priority' => 210]
            );
            $this->context->controller->registerJavascript(
                'module-psmodcpf-front',
                'modules/'.$this->name.'/views/js/front.js',
                ['priority' => 211]
            );
        }
    }

    public function fillFieldsFront($format): array
    {
        $result = $this->searchCustomer((int)$this->context->customer->id);
        if ($result) {
            $format['tp_documento']->setValue($result['tp_documento']);
            $format['documento']->setValue($result['documento']);
            $format['rg_ie']->setValue($result['rg_ie']);
            $format['add_documento']->setValue('false');
        }
        return $format;
    }

    public function hookActionCustomerAccountAdd($params): void
    {
        $form_data = [
            'documento' => Tools::getValue('documento'),
            'rg_ie' => Tools::getValue('rg_ie'),
            'tp_documento' => Tools::getValue('tp_documento')
        ];
        $this->insertDocumento($params['newCustomer']->id, $form_data);
    }

    public function hookActionCustomerAccountUpdate($params): void
    {
        $form_data = [
            'documento' => Tools::getValue('documento'),
            'rg_ie' => Tools::getValue('rg_ie'),
            'tp_documento' => Tools::getValue('tp_documento')
        ];

        $result = $this->searchCustomer((int)$params['customer']->id);
        if ($result === false) {
            $this->insertDocumento($params['customer']->id, $form_data);
        }
    }

    public function hookAdditionalCustomerFormFields(): array
    {
        $format = [];
        $tipoDocumento = (new FormField)
            ->setName('tp_documento')
            ->setType('radio-buttons')
            ->setLabel('Tipo de documento')
            ->addAvailableValue(1, 'CPF')
            ->addAvailableValue(2, 'CNPJ')
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
            ->setMaxLength(45);

        $format['add_documento'] = (new FormField)
            ->setName('add_documento')
            ->setType('hidden')
            ->setValue('true');

        if (!is_null($this->context->customer->id)) {
            return $this->fillFieldsFront($format);
        }
        return $format;
    }

    public function hookValidateCustomerFormFields($params): void
    {
        foreach ($params['fields'] as $field) {
            if ($field->getName() == 'documento') {
                $objValidateDoc = new ValidateDocumento();
                if (!$objValidateDoc->validarDocumento($field->getValue())) {
                    $field->addError($this->mensagemError);
                }
                $id_customer = (is_null($this->context->customer->id) ? 0 : (int)$this->context->customer->id);
                if ($this->checkDuplicate($field->getValue(), $id_customer) !== false) {
                    $field->addError('O documento informado já está cadastrado!');
                }
            }
        }
    }

    public function validarDocumentoAjax($documento)
    {
        $objValidateDoc = new ValidateDocumento();
        if (!$objValidateDoc->validarDocumento($documento)) {
            throw new \Exception($this->mensagemError);
        }
        $id_customer = (is_null($this->context->customer->id) ? null : $this->context->customer->id);
        if ($this->checkDuplicate($documento, $id_customer) !== false) {
            throw new \Exception('O documento informado já está cadastrado!');
        }
    }

}
