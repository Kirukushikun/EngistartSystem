@php
    $background = $background ?? 'var(--bg2)';
    $gridClass = $gridClass ?? 'grid-cols-1';
    $fields = $fields ?? [];
@endphp

<div class="apis-toolbar-shell" style="background: {{ $background }};">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div class="grid {{ $gridClass }} gap-3 flex-1 w-full">
            @foreach ($fields as $field)
                <div>
                    <label class="block text-[10px] text-apis-text2 mb-1 font-medium uppercase tracking-[0.07em]">{{ $field['label'] }}</label>

                    @if (($field['type'] ?? 'text') === 'select')
                        <select @foreach (($field['attributes'] ?? []) as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach class="{{ $field['class'] ?? 'apis-toolbar-control' }}">
                            @foreach (($field['options'] ?? []) as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="{{ $field['type'] ?? 'text' }}" @foreach (($field['attributes'] ?? []) as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach class="{{ $field['class'] ?? 'apis-toolbar-control' }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                    @endif
                </div>
            @endforeach
        </div>

        @if (!empty($trailingFields ?? []))
            <div class="flex items-end gap-3 lg:justify-end">
                @foreach ($trailingFields as $field)
                    <div>
                        <label class="block text-[10px] text-apis-text2 mb-1 font-medium uppercase tracking-[0.07em]">{{ $field['label'] }}</label>

                        @if (($field['type'] ?? 'text') === 'select')
                            <select @foreach (($field['attributes'] ?? []) as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach class="{{ $field['class'] ?? 'apis-toolbar-control' }}">
                                @foreach (($field['options'] ?? []) as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="{{ $field['type'] ?? 'text' }}" @foreach (($field['attributes'] ?? []) as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach class="{{ $field['class'] ?? 'apis-toolbar-control' }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
