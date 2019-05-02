{$element_id = uniqid()}
<div class="cerb-portal-form-prompt cerb-portal-form-prompt-captcha" id="{$element_id}">
	<label>{$label}</label>
	
	<p>
		<img src="data:image/png;base64,{base64_encode($image_bytes)}">
		<input name="prompts[{$var}]" type="text" value="{$dict->get($var)}" placeholder="(enter the text from the image above)" autocomplete="off" spellcheck="false">
	</p>
</div>