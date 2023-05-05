<?php

namespace PsmodCpf\Utils;

use Tools;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

trait PsmodCpfAdmin
{
    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookActionAdminControllerSetMedia()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }

        if (Tools::getValue('controller') == 'AdminCustomers'){
            $this->context->controller->addJS($this->_path.'views/js/jquery.mask.min.js');
            $this->context->controller->addJS($this->_path.'views/js/back.js');
        }
    }

    public function hookActionAdminCustomersFormModifier($params)
    {
        $extraInputs = &$params['fields'][0]['form']['input'];
        $extraInputs[] = [
            'type' => 'radio',
            'label' => 'Tipo de documento',
            'name' => 'tp_documento',
            'required' => true,
            'class' => 't',
            'values' => [
                [
                    'id' => 'documento_1',
                    'value' => 1,
                    'label' => 'CPF'
                ],
                [
                    'id' => 'documento_2',
                    'value' => 2,
                    'label' => 'CNPJ'
                ]
            ]
        ];

        $extraInputs[] = [
            'type' => 'text',
            'label' => 'Número',
            'name' => 'documento',
            'required' => true,
            'col' => '2'
        ];

        $extraInputs[] = [
            'type' => 'text',
            'label' => 'RG',
            'name' => 'rg_ie',
            'required' => false,
            'col' => '2'
        ];

        $id_customer = (int)$params['object']->id;
        $result = $this->searchCustomer($id_customer);

        $extraValues = &$params['fields_value'];
        $extraValues['tp_documento'] = (isset($result['tp_documento']) ? $result['tp_documento'] : 1);
        $extraValues['documento'] = (isset($result['documento']) ? $result['documento'] : null);
        $extraValues['rg_ie'] = (isset($result['rg_ie']) ? $result['rg_ie'] : null);
    }

    public function hookActionAdminCustomersControllerSaveBefore($params)
    {
        $objValidateDoc = new ValidateDocumento();
        $documento = Tools::getValue('documento');
        $id_customer = (int)Tools::getValue('id_customer');
        if (!empty($documento)) {
            if (!$objValidateDoc->validarDocumento($documento)) {
                $params['controller']->errors[] = $this->mensagemError;
            }

            if ($this->checkDuplicate($documento, $id_customer) !== false) {
                $params['controller']->errors[] = "O documento '{$documento}' já está cadastrado!";
            }

        } else {
            $params['controller']->errors[] = 'O campo número do documento é obrigatório';
        }
    }

    public function hookActionAdminCustomersControllerSaveAfter($params)
    {
        $id_customer = (int)Tools::getValue('id_customer');
        $result = $this->searchCustomer($id_customer);
        if ($result === false) {
            $this->insertDocumento($id_customer);
        } else {
            $this->updateDocumento($id_customer);
        }
    }

    public function fillFieldsAdmin(array $formData)
    {
        $result = $this->searchCustomer((int)$this->context->customer->id);
        if ($result) {
            $formData['tp_documento'] = $result['tp_documento'];
            $formData['documento'] = $result['documento'];
            $formData['rg_ie'] = $result['rg_ie'];
        }
        return $formData;
    }

    public function hookActionCustomerFormBuilderModifier($params)
    {
        /** @var FormBuilderInterface $formBuilder */
        $formBuilder = $params['form_builder'];
        if ($params['route'] === 'admin_customers_edit') {
            $formBuilder->add('tp_documento', ChoiceType::class, [
                'choices' => ['CPF' => '1','CNPJ' => '2'],
                'multiple' => false,
                'expanded' => true,
                'required' => false,
                'placeholder' => null,
                'label' => 'Tipo de documento',
                'disabled' => false
            ])
            ->add('documento', TextType::class, [
                'label' => 'Número',
                'required' => false,
                'disabled' => false,
            ])
            ->add('rg_ie', TextType::class, [
                'label' => 'RG',
                'disabled' => false
            ]);
    
            $formData = $params['data'];
            $formData['tp_documento'] = '1';

            if (!is_null($this->context->customer)) {
                $formData = $this->fillFieldsAdmin($params['data']);
            }

            $formBuilder->setData($formData);
        }
    }
}
