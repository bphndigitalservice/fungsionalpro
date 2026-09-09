<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @if ($url = $downloadUrl())
        <x-filament::icon-button
            icon="heroicon-m-arrow-top-right-on-square"
            :href="$url"
            target="_blank"
            :spa-mode="false"
            tag="a"
            label="Buka berkas"
        />
    @else
        <span class="text-sm text-gray-500">-</span>
    @endif
</x-dynamic-component>
