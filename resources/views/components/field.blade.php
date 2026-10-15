@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'toggleable' => false])
<div class="field">
<label for="{{ $name }}">{{ $label }}</label>
@if($toggleable)
<div class="field-input-wrap">
@endif
<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if($type !== 'password' && $type !== 'file') value="{{ old($name, $value) }}" @endif {{ $attributes->class(['input', 'invalid' => $errors->has($name)]) }} @if($help || $errors->has($name)) aria-describedby="{{ $name }}-help" @endif @if($errors->has($name)) aria-invalid="true" @endif>
@if($toggleable)
<button class="password-toggle" type="button" data-password-toggle="{{ $name }}" aria-label="Show password" aria-controls="{{ $name }}" aria-pressed="false"><x-icon name="eye" /><x-icon name="eye-off" hidden /></button>
</div>
@endif
@if($errors->has($name))<small class="field-error" id="{{ $name }}-help">{{ $errors->first($name) }}</small>
@elseif($help)<small id="{{ $name }}-help">{{ $help }}</small>@endif
</div>
