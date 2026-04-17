<div class="p-6 overflow-y-auto h-full">
    @include('partials.apis.alert', ['type' => 'warn', 'message' => 'Settings values are governed by the Change Management Control workflow. To change a value, DH Gen Services or ED Manager must submit a Settings Change Request, which requires VP Gen Services approval before IT Admin can implement it.'])

    <div class="max-w-[560px] space-y-5 mt-4">
        <div class="rounded-[12px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg)">
            <div class="px-[18px] py-[12px]" style="border-bottom: 0.5px solid var(--border); background: var(--bg2)">
                <span class="text-[13px] font-medium text-apis-text">Current System Values</span>
            </div>
            @foreach ($this->settings as $index => $setting)
                <div class="px-[18px] py-[12px] flex justify-between items-center gap-3" style="border-top: {{ $index > 0 ? '0.5px solid var(--border)' : 'none' }};">
                    <div>
                        <p class="text-[12px] font-medium m-0 mb-0.5 text-apis-text">{{ $setting['label'] }}</p>
                        <p class="text-[11px] m-0 text-apis-text2">Key: {{ $setting['key'] }}</p>
                    </div>
                    <span class="font-mono text-[13px] font-medium whitespace-nowrap" style="color: var(--blue)">{{ $setting['value'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="rounded-[12px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg)">
            <div class="px-[18px] py-[12px]" style="border-bottom: 0.5px solid var(--border); background: var(--bg2)">
                <span class="text-[13px] font-medium text-apis-text">System Information</span>
            </div>
            @foreach ($this->systemInformation as $index => $item)
                <div class="px-[18px] py-[12px] flex justify-between items-center gap-3" style="border-top: {{ $index > 0 ? '0.5px solid var(--border)' : 'none' }};">
                    <span class="text-[12px] font-medium text-apis-text">{{ $item['label'] }}</span>
                    <span class="text-[12px] text-apis-text2 text-right">{{ $item['value'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
