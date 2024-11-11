<div>
    @if(!$this->record->competentes)
        <div class="flex items-center justify-center h-64">
            <p class="text-gray-500">No competence information available.</p>
        </div>
    @else
        {{ $this->competenceInfolist }}
    @endif
</div>
