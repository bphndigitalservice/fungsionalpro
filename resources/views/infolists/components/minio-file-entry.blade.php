<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <x-filament::icon-button
        icon="heroicon-m-arrow-top-right-on-square"
        href="{{ $downloadUrl()  }}"
        target="_blank"
        tag="a"
        label="Lihat"
    />
</x-dynamic-component>
