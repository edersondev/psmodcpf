/**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

$(function(){
  var tipoDocumento = ( $('input[type=radio][name=tp_documento]:checked').val() ? $('input[type=radio][name=tp_documento]:checked').val() : 1 );
  setMaskInput(tipoDocumento);
  
  $('input[type=radio][name=tp_documento]').on('change', function(){
		var value = $(this).val()
				elementDoc = $('input[name="documento"]');

		elementDoc.val('');
		$('input[name="rg_ie"]').val('');
		setMaskInput(value);
		elementDoc.focus();
	});

});

function setMaskInput(tipoDocumento){
	var labelRgIe = $('input[name="rg_ie"]').closest('div.form-group').children('label'),
			mask,
			options;

	if(tipoDocumento == 1){
		labelRgIe.html('RG');
		mask = '000.000.000-00';
	} else {
		labelRgIe.html('Inscrição Estadual');
		mask = '00.000.000/0000-00';
	}

	options = {
		reverse: true,
		clearIfNotMatch: true
	}
	$('input[name="documento"]').mask(mask, options);
}