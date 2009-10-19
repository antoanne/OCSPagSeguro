{**
 * paymentForm.tpl
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for submitting a CC payment
 *
 *}
{assign var="pageTitle" value="plugins.paymethod.pagseguro"}
{include file="common/header.tpl"}

<script Language="JavaScript">
<!--

{include file="../plugins/paymethod/credit/templates/cpfjs.tpl"}

{literal}

function validateState(fld) {
   var error = "";
   error = "ERR";
   fld.value = fld.value.toUpperCase();
   if ( fld.value == "AC") error = "";
   if ( fld.value == "AM") error = "";
   if ( fld.value == "RR") error = "";
   if ( fld.value == "AP") error = "";
   if ( fld.value == "PA") error = "";
   if ( fld.value == "RO") error = "";
   if ( fld.value == "MT") error = "";
   if ( fld.value == "MA") error = "";
   if ( fld.value == "TO") error = "";
   if ( fld.value == "PI") error = "";
   if ( fld.value == "CE") error = "";
   if ( fld.value == "RN") error = "";
   if ( fld.value == "PB") error = "";
   if ( fld.value == "PE") error = "";
   if ( fld.value == "AL") error = "";
   if ( fld.value == "SE") error = "";
   if ( fld.value == "BA") error = "";
   if ( fld.value == "DF") error = "";
   if ( fld.value == "GO") error = "";
   if ( fld.value == "MS") error = "";
   if ( fld.value == "MG") error = "";
   if ( fld.value == "ES") error = "";
   if ( fld.value == "RJ") error = "";
   if ( fld.value == "SP") error = "";
   if ( fld.value == "PR") error = "";
   if ( fld.value == "SC") error = "";
   if ( fld.value == "RJ") error = "";
   
   if ( error != "" )
   {
     fld.style.background = 'Orange';
     error = "The field State must be a valid brasilian state.\n (O campo estado deve ser uma sigla representando um estado valido do Brasil..)";
   }
   else {
     fld.style.background = 'White';
   }
   return error;
}

function validateCEP(fld) {
   exp = /^[0-9]{8,8}$/ ;
   var error = "";
   if ( !fld.value.match(exp) )
   {
     fld.style.background = 'Orange';
     error = "The field ID only accept numbers and must have 8.\n (O campo CEP somente aceita números e necessita ter 8.)";
   }
   else {
     fld.style.background = 'White';
   }
   return error;
}

function validateCPF(fld) {
   exp = /^[0-9]{11,11}$/ ;
   var error = "";
   if ( !fld.value.match(exp) )
   {
     fld.style.background = 'Orange';
     error = "The field ID only accept numbers and must have 11.\n (O campo CPF somente aceita números e necessita ter 11.)";
   }
   else {
   	if ( isCpf(fld.value)) 
     		fld.style.background = 'White';
	else{
     		fld.style.background = 'Orange';
		error = "CPF invalido";
	}
   }
   return error;
}

function validateEmpty(fld) {
    var error = "";
    if (fld.value.length == 0) {
      fld.style.background = 'Yellow'; 
      error = "The required field has not been filled in.\n (O campo requerido não foi preenchido.)\n"
    } else {
      fld.style.background = 'White';
    }
      return error;  
}

-->
</script>
{/literal}

<table>
	<tr>
		<td>
		<!--
		<img src="{$baseUrl}/plugins/paymethod/credit/images/credit.gif" alt="CC" /></td>
		-->
		<font size="+2"><b>{translate key="plugins.paymethod.pagseguro.purchase.title"}</b></font>
		<td>{$CreditDescription}</td>
	</tr>
</table>

<p>{translate key="plugins.paymethod.pagseguro.warning"}</p>

<form action="{$CreditFormUrl}" method="post"  style="margin-bottom: 0px;" >
	{include file="common/formErrors.tpl"}
	{if $params.item_valor_1}
	<table class="data" width="100%">
		<tr>
			<td class="label" width="20%">{translate key="plugins.paymethod.pagseguro.purchase.amount"}</td>
			<td class="value" width="80%">{if ($params.moeda == 'BRL') }R$ {/if}<strong>{$params.valor|escape},00{if ($params.moeda == 'BRL') } ({$params.moeda|escape}){/if}</strong></td>
		</tr>
	</table>
	{/if}
	{if $params.item_name}
	<table class="data" width="100%">
		<tr>
			<td class="label" width="20%">{translate key="plugins.paymethod.pagseguro.purchase.description"}</td>
			<td class="value" width="80%"><strong>{$params.item_name|escape}</strong></td>
		</tr>
	</table>
	{/if}

	{foreach from=$params key="name" item="value"}
		<input type="hidden" name="{$name|escape}" value="{$value|escape}" >
	{/foreach}

	<p><input type="submit" name="submitBtn" value="{translate key="common.continue"}" class="button defaultButton" />
<input type="button" name="cancelBtn" value="{translate key="common.cancel"}" class="button" onclick="javascript:history.back()" /> </p>


</form>

{include file="common/footer.tpl"}
