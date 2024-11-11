<div>
    @if(!$this->record->educations)
        <div class="text-center text-gray-500">No education information available.</div>
    @else
        {{ $this->educationInfolist  }}
    @endif
</div>
