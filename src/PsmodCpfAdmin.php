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
    public function hookActionAdminControllerSetMedia(): void
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }

        if (Tools::getValue('controller') == 'AdminCustomers'){
            $this->context->controller->addJS($this->_path.'views/js/jquery.mask.min.js');
            $this->context->controller->addJS($this->_path.'views/js/back.js');
        }
    }

    public function hookActionBeforeCreateCustomerFormHandler($params): void
    {
        $this->validarDocumento($params['form_data']['documento']);
    }

    public function hookActionAfterCreateCustomerFormHandler($params): void
    {
        $id_customer = (int)$params['id'];
        $this->insertDocumento($id_customer, $params['form_data']);
    }

    public function hookActionBeforeUpdateCustomerFormHandler($params): void
    {
        $id_customer = (int)$params['id'];
        $this->validarDocumento($params['form_data']['documento'], $id_customer);
    }

    public function validarDocumento($documento, $id = null): void
    {
        $objValidateDoc = new ValidateDocumento();
        if (!empty($documento)) {
            if (!$objValidateDoc->validarDocumento($documento)) {
                $this->showErrorValidateForm($this->mensagemError);
            }

            if (!is_null($id) && $this->checkDuplicate($documento, $id) !== false) {
                $this->showErrorValidateForm("O documento '{$documento}' já está cadastrado!");
            }
        } else {
            $this->showErrorValidateForm('O campo número do documento é obrigatório');
        }
    }

    public function hookActionAfterUpdateCustomerFormHandler($params): void
    {
        $id_customer = (int)$params['id'];

        $result = $this->searchCustomer($id_customer);
        if ($result === false) {
            $this->insertDocumento($id_customer, $params['form_data']);
        } else {
            $this->updateDocumento($id_customer, $params['form_data']);
        }
    }

    public function fillFieldsAdmin(): array
    {
        $id_customer = (int)Tools::getValue('id_customer');
        $result = $this->searchCustomer($id_customer);
        if ($result) {
            return [
                'tp_documento'  => $result['tp_documento'],
                'documento'     => $result['documento'],
                'rg_ie'         => $result['rg_ie']
            ];
        }
        return [];
    }

    public function hookActionCustomerFormBuilderModifier($params): void
    {
        /** @var FormBuilderInterface $formBuilder */
        $formBuilder = $params['form_builder'];

        $allowed_routes = ['admin_customers_edit', 'admin_customers_create'];

        if (in_array($params['route'], $allowed_routes)) {
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
                'required' => false,
                'disabled' => false
            ]);

            $formData = array_merge($params['data'], $this->fillFieldsAdmin());

            $formBuilder->setData($formData);
        }
    }

    /**
     * Forma alternativa de lidar com mensagens de erro do Form
     * módulo não tem acesso a classe PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Handler
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
