<?php

namespace PsmodCpf\Utils;

use Tools;

trait PsmodCpfFront
{
    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookActionFrontControllerSetMedia()
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

    public function fillFieldsFront($format)
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
}
