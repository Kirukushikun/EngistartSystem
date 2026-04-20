<div>
    @if ($isOpen)
        @php
            $tones = [
                'info' => [
                    'accent' => 'var(--blue)',
                    'background' => 'var(--blue-bg)',
                    'border' => 'var(--blue-bd)',
                ],
                'warn' => [
                    'accent' => 'var(--amber)',
                    'background' => 'var(--amber-bg)',
                    'border' => 'var(--amber-bd)',
                ],
                'danger' => [
                    'accent' => 'var(--red)',
                    'background' => 'var(--red-bg)',
                    'border' => 'var(--red-bd)',
                ],
            ];

            $toneStyle = $tones[$tone] ?? $tones['info'];
        @endphp

        <div class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <button type="button" wire:click="close" class="absolute inset-0 bg-black/40"></button>

            <div class="relative w-full max-w-lg rounded-[14px] bg-apis-bg shadow-xl" style="border: 0.5px solid var(--border2)">
                <div class="p-[18px_20px] border-b" style="border-color: var(--border)">
                    <div class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.08em]" style="background: {{ $toneStyle['background'] }}; color: {{ $toneStyle['accent'] }}; border: 0.5px solid {{ $toneStyle['border'] }};">
                        Confirmation
                    </div>
                    <h3 class="mt-3 text-[16px] font-semibold text-apis-text">{{ $title }}</h3>
                    <p class="mt-1 text-[13px] leading-[1.6] text-apis-text2">{{ $message }}</p>
                </div>

                @if ($summary !== [])
                    <div class="p-[18px_20px] border-b" style="border-color: var(--border)">
                        <div class="rounded-[12px] p-[12px_14px]" style="background: var(--bg2); border: 0.5px solid var(--border)">
                            <div class="space-y-2.5">
                                @foreach ($summary as $item)
                                    <div class="flex items-start justify-between gap-4 text-[12px]">
                                        <span class="text-apis-text2">{{ $item['label'] ?? 'Detail' }}</span>
                                        <span class="text-right text-apis-text font-medium">{{ $item['value'] ?? '—' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if ($fields !== [])
                    <div class="p-[18px_20px] space-y-4">
                        @foreach ($fields as $field)
                            @php
                                $name = (string) ($field['name'] ?? '');
                                $label = (string) ($field['label'] ?? 'Field');
                                $type = (string) ($field['type'] ?? 'text');
                                $placeholder = (string) ($field['placeholder'] ?? '');
                            @endphp

                            <div>
                                <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">{{ $label }}</label>
                                @if ($type === 'textarea')
                                    <textarea wire:model.live="values.{{ $name }}" class="apis-remarks-control" placeholder="{{ $placeholder }}"></textarea>
                                @else
                                    <input wire:model.live="values.{{ $name }}" type="{{ $type }}" class="apis-toolbar-control w-full" placeholder="{{ $placeholder }}">
                                @endif
                                @error('values.' . $name)
                                    <p class="mt-2 text-[11px]" style="color: var(--red)">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-end gap-2 p-[16px_20px] border-t" style="border-color: var(--border)">
                    <button type="button" wire:click="close" class="apis-card-button">{{ $cancelText }}</button>
                    <button type="button" wire:click="confirm" class="apis-card-button font-medium" style="background: {{ $toneStyle['background'] }}; color: {{ $toneStyle['accent'] }}; border: 0.5px solid {{ $toneStyle['border'] }};">{{ $confirmText }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
