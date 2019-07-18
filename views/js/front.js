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
	var tipoDocumento = ( $('input[type=radio][name=tp_documento]:checked').val() ? $('input[type=radio][name=tp_documento]:checked').val() : 1 ),
			add_documento = $('input[name=add_documento]').val();
	setMaskInput(tipoDocumento);

	$('input[type=radio][name=tp_documento]').on('change', function(){
		var value = $(this).val()
				elementDoc = $('input[name="documento"]');

		elementDoc.val('');
		resetStatusField();
		$('input[name="rg_ie"]').val('');
		setMaskInput(value);
		elementDoc.focus();
	});

	if(add_documento == 'false'){
		$('input[name="documento"]').attr('readonly','readonly');
		$('input[name="rg_ie"]').attr('readonly','readonly');
		$('input[type=radio][name=tp_documento]:not(:checked)').attr('disabled', true);
	}

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
		onComplete: function(documento) {
			var element = $('input[name="documento"]');
					elementFormGroup = element.closest('div.form-group')
					elementDiv = element.parent('div');

			resetStatusField();

			$.ajax({
				url: `${window.location.origin}/index.php?fc=module&module=psmodcpf&controller=validatedoc`,
				type: 'post',
				dataType: 'json',
				data:{
					documento: documento
				},
				beforeSend: function(jqXHR,settings){
					element.attr('readonly','readonly');
					elementFormGroup.find('div.form-control-comment').html('Aguarde...');
				},
				complete: function(jqXHR,textStatus){
					element.removeAttr('readonly');
					elementFormGroup.find('div.form-control-comment').empty();
				},
				success: function(){
					elementFormGroup.addClass('has-success');
				},
				error: function(jqXHR,textStatus,errorThrown){
					var message = `
						<div class="help-block">
							<ul>
								<li class="alert alert-danger">${jqXHR.responseJSON.error}</li>
							</ul>
						</div>
					`;
					elementFormGroup.addClass('has-error');
					elementDiv.append(message);
				},
			});
		},
		reverse: true,
		clearIfNotMatch: true
	}
	$('input[name="documento"]').mask(mask, options);
}

function resetStatusField()
{
	var elementFormGroup = $('input[name="documento"]').closest('div.form-group');
	elementFormGroup.removeClass('has-error');
	elementFormGroup.removeClass('has-success');
	elementFormGroup.find('div.help-block').remove();
}