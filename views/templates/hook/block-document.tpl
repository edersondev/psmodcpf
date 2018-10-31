<div class="form-group row">
	<label class="col-md-3 form-control-label">
		Tipo de documento
	</label>

	<div class="col-md-6 form-control-valign">
		<label class="radio-inline">
			<span class="custom-radio">
				<input name="tp_documento" type="radio" value="1" checked="checked">
				<span></span>
			</span>
			CPF
		</label>

		<label class="radio-inline">
			<span class="custom-radio">
				<input name="tp_documento" type="radio" value="2">
				<span></span>
			</span>
			CNPJ
		</label>

	</div>
	<div class="col-md-3 form-control-comment"></div>
</div>

<div class="form-group row ">
	<label class="col-md-3 form-control-label required">
		Número
	</label>
    <div class="col-md-6">        
			<input class="form-control cpf_cnpj" name="documento" type="text" required>
    </div>
    <div class="col-md-3 form-control-comment"></div>
</div>