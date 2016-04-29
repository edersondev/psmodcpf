<div class="account_creation" style="padding-bottom: 20px;">

    <h3 class="page-subheading">{l s='CPF / CNPJ' mod='modulocpf'}</h3>
        
        <div class="clearfix">
            <label>Tipo de pessoa:</label>
            <br>
            
            {foreach from=$arrDocTypes key=k item=doctype}
                <div class="radio-inline">
                    <label for="doctype-{$k}" class="top">
                        <div class="radio">
                            <span>
                                <input type="radio" name="doc_type" id="doctype-{$k}" value="{$doctype['id']}" {if isset($smarty.post.doc_type) && $smarty.post.doc_type == $doctype['id']}checked="checked"{/if} />
                            </span>
                        </div>
                        {$doctype['name']}
                    </label>
                </div>
            {/foreach}

        </div>
              

        <div id="field_cpf" style="display: none;">
            
            <div id="validate-cpf" class="required form-group">
                    <label for="cpf">{l s='CPF:' mod='modulocpf'} <sup>*</sup></label>
                    <input type="text" class="form-control" id="cpf" name="cpf" />
                    <p class="required" id="erro_cpf" style="display:none;"></p>
            </div>
            
            <div class="form-group">
                <label for="rg">{l s='RG:' mod='modulocpf'}</label>
                <input type="text" class="form-control" name="rg" id="rg" value="{if isset($smarty.post.rg_ie)}{$smarty.post.rg_ie}{/if}" />
            </div>
        </div>

        <div id="field_cnpj" style="display: none;">
            <div id="validate-cnpj" class="required form-group">
                <label for="cnpj">{l s='CNPJ:' mod='modulocpf'} <sup>*</sup></label>
                <input type="text" class="form-control" name="cnpj" id="cnpj" value="{if isset($smarty.post.document)}{$smarty.post.document}{/if}" />
                <p class="required" id="erro_cnpj" style="display:none;"></p>
            </div>

            <div class="form-group">
                <label for="nie">{l s='IE:' mod='modulocpf'}</label>
                <input type="text" class="form-control" name="nie" id="nie" value="{if isset($smarty.post.rg_ie)}{$smarty.post.rg_ie}{/if}" />
            </div>
        </div>
</div>

<input type="hidden" name="validatedoc" id="validatedoc" value="{$urlValidateDoc}" />

<script type="text/javascript">
{literal}
    
$(function(){
    
    var docType = $('input[name=doc_type]:checked').val();
    if ( !docType ) {
        $('#doctype-1').prop( "checked", true );
    }
    var docTypeSelected = $('input[name=doc_type]:checked').val();
    if (docTypeSelected === '2'){
        $('#field_cnpj').hide(function (){
            $('#field_cpf').show();
        });
    } else {
        $('#field_cpf').hide(function (){
            $('#field_cnpj').show();
        });
    }
    
    $('input[name=doc_type]').click(function (){
        clearFields();
        var value = $(this).val();
        if (value === '2'){
            $('#field_cnpj').hide(function (){
                $('#field_cpf').show('slow');
            });
        } else {
            $('#field_cpf').hide(function (){
                $('#field_cnpj').show('slow');
            });
        }
    });
    
    var options = {
        clearIfNotMatch: true,
        onComplete: function(cpf_cnpj) {
            validateDoc(cpf_cnpj);
        }
    };
        
    // Ação para o campo Cpf
    $('#cpf').mask('999.999.999-99', options);
    $('#cnpj').mask('99.999.999/9999-99', options);
    
    // form-error ou form-ok
});

function validateDoc(cpf_cnpj) {
    $('#erro_cpf').hide();
    $('#erro_cnpj').hide();
    
    $.ajax({
        type: "GET",
        url: $('#validatedoc').val(),
        data: {cpf_cnpj: cpf_cnpj},
        dataType: "json",
        success: function (json){
            if ( json.status === true ){
                $('#validate-' + json.doctype).attr('class','required form-group form-ok');
                $('#submitAccount:disabled').removeAttr('disabled');
            }else{
                $('#erro_' + json.doctype).empty();
                $('#erro_' + json.doctype).append(json.error);
                $('#erro_' + json.doctype).show('slow');
                
                $('#validate-' + json.doctype).attr('class','required form-group form-error');
                $('#submitAccount').attr('disabled','disabled');
            }
        }
    });
}

function clearFields() {
    $('#erro_cpf').hide();
    $('#erro_cnpj').hide();
    
    $('#validate-cpf').removeClass('form-ok');
    $('#validate-cpf').removeClass('form-error');
    $('#validate-cnpj').removeClass('form-ok');
    $('#validate-cnpj').removeClass('form-error');
    
    $('#cpf').val('');
    $('#rg').val('');
    $('#cnpj').val('');
    $('#nie').val('');
}

{/literal}
</script>