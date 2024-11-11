<x-filament-widgets::widget>
    <x-filament::section>
        @if($this->client)
            {{ $this->completionInfolist  }}
        @else
            <p>Please complete your profile.</p>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>
