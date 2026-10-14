@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'step' => null,
    'min' => null,
    'placeholder' => null,
    'rows' => 4,
])

<div class="space-y-1.5">
    <label for="{{ $name }}" class="block text-sm font-medium text-[#294751]">
        {{ $label }} @if($required)<span class="text-[#bd5146]">*</span>@endif
    </label>
    @if($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @required($required) @if($errors->has($name)) aria-invalid="true" @endif placeholder="{{ $placeholder }}" class="field-control block w-full rounded-md px-3 py-2.5 text-sm">{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required) @if($step) step="{{ $step }}" @endif @if($min !== null) min="{{ $min }}" @endif @if($placeholder) placeholder="{{ $placeholder }}" @endif @if($errors->has($name)) aria-invalid="true" @endif class="field-control block w-full rounded-md px-3 py-2.5 text-sm">
    @endif
    @error($name)<p class="text-xs text-[#b43f35]">{{ $message }}</p>@enderror
</div>