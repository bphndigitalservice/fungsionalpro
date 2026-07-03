<div>
    @if($this->record->competences && $this->record->competences->isNotEmpty())
        {{ $this->competenceInfolist }}
    @else
        <div class="flex items-center justify-center h-64">
            <p class="text-gray-500">No competence information available.</p>
        </div>
    @endif
</div>
