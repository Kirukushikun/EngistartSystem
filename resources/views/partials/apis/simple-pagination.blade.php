<div class="apis-pagination-shell flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <p class="text-[12px] text-apis-text2 m-0">{{ $summary }}</p>

    <div class="flex items-center gap-2">
        <button type="button"
                wire:click="previousPage"
                @disabled($page <= 1)
                class="apis-card-button"
                style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text); opacity: {{ $page <= 1 ? '0.5' : '1' }};">
            Previous
        </button>
        <span class="text-[12px] text-apis-text2 px-1">Page {{ $page }} of {{ $totalPages }}</span>
        <button type="button"
                wire:click="nextPage"
                @disabled($page >= $totalPages)
                class="apis-card-button"
                style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text); opacity: {{ $page >= $totalPages ? '0.5' : '1' }};">
            Next
        </button>
    </div>
</div>
