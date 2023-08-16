<?php

namespace PsmodCpf\Utils;

use Tools;
use Symfony\Component\Form\FormBuilderInterface;
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
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }

        if (Tools::getValue('controller') == 'AdminCustomers') {
            $this->context->controller->addJS($this->_path . 'views/js/jquery.mask.min.js');
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
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

    public function hookActionBeforeCreateCustomerFormHandler($params)
    {
        $this->adminValidarDocumento($params['form_data']['documento']);
    }

    public function hookActionBeforeUpdateCustomerFormHandler($params)
    {
        $this->adminValidarDocumento($params['form_data']['documento'], (int)$params['id']);
    }

    public function adminValidarDocumento($documento, $id = null): void
    {
        $objValidateDoc = new ValidateDocumento();
        if (!empty($documento)) {
            if (!$objValidateDoc->validarDocumento($documento)) {
                $this->showErrorValidateForm($this->mensagemError);
            }

            if ($this->checkDuplicate($documento, $id) !== false) {
                $this->showErrorValidateForm("O documento '{$documento}' já está cadastrado!");
            }
        } else {
            $this->showErrorValidateForm('O campo número do documento é obrigatório');
        }
    }

    public function hookActionCustomerFormBuilderModifier($params)
    {
        /** @var FormBuilderInterface $formBuilder */
        $formBuilder = $params['form_builder'];

        $allowed_routes = ['admin_customers_edit', 'admin_customers_create'];

        if (in_array($params['route'], $allowed_routes)) {
            $formBuilder->add('tp_documento', ChoiceType::class, [
                'choices' => ['CPF' => '1', 'CNPJ' => '2'],
                'multiple' => false,
                'expanded' => true,
                'required' => false,
                'placeholder' => null,
                'label' => 'Tipo de documento',
                'disabled' => false
            ])
                ->add('documento', TextType::class, [
                    'label' => 'Número',
                    'disabled' => false,
                    'required' => false
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

    /**
     * Forma alternativa de lidar com mensagens de erro do Form
     */
    public function showErrorValidateForm($messagem): void
    {
        exit("
            <script>
                alert(\"{$messagem}\");
                history.back();
            </script>
        ");
    }

}
