<div>
    @if(!$this->record->activities)
        <div class="flex items-center justify-center h-64">
            <p class="text-gray-500">No activity information available.</p>
        </div>
    @else
        {{ $this->activityInfolist }}
    @endif
</div>
