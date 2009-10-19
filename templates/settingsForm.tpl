{**
 * settingsForm.tpl
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for PayPal settings.
 *
 *}
	<tr>
		<td colspan="2"><h4>{translate key="plugins.paymethod.pagseguro.settings"}</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{fieldLabel name="crediturl" required="true" key="plugins.paymethod.pagseguro.settings.crediturl"}</td>
		<td class="value" width="80%">
			<input type="text" class="textField" name="crediturl" id="crediturl" size="50" value="{$crediturl|escape}" /><br/>
			{translate key="plugins.paymethod.pagseguro.settings.crediturl.description"}<br/>
			&nbsp;
		</td>
	</tr>
	{if !$isCurlInstalled}
		<tr>
			<td colspan="2">
				<span class="instruct">{translate key="plugins.paymethod.pagseguro.settings.curlNotInstalled"}</span>
			</td>
		</tr>
	{/if}
