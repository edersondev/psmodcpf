{if $arrData}
<div class="col-lg-12">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-archive"></i> CPF / CNPJ
        </div>
        <div class="panel-body">
            <dl>
                <dt>Tipo de pessoa</dt>
                <dd>{if $arrData.doc_type == 1}Pessoa Jurídica{else}Pessoa Física{/if}</dd>
            </dl>
            
            <dl>
                <dt>CPF / CNPJ</dt>
                <dd>{$arrData['nu_cpf_cnpj']}</dd>
            </dl>
            
            <dl>
                <dt>RG / IE</dt>
                <dd>{$arrData['rg_ie']}</dd>
            </dl>
            
        </div>
    </div>
</div>
{else}
    <div class="col-lg-12">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-archive"></i> CPF / CNPJ
            </div>
            <div class="panel-body">
                Não possui dados cadastrados, o cliente pode incluir o CPF / CNPJ ao fazer login na loja e ir em "Minha Conta"
            </div>
        </div>
    </div>
{/if}