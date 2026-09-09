@php
    $appName = config('app.name');
@endphp

<div class="fi-brand flex h-full items-center gap-2.5" role="group" aria-label="{{ $appName }}">
    <div class="fi-brand-logos flex h-full shrink-0 items-center gap-2">
        <img
            src="{{ asset('images/logo-pengayoman.png') }}"
            alt="Logo Pengayoman"
            class="fi-brand-logo h-full w-auto shrink-0 object-contain"
            width="44"
            height="44"
        />
        <img
            src="{{ asset('images/logo-bphn.png') }}"
            alt="Logo BPHN"
            class="fi-brand-logo h-full w-auto shrink-0 object-contain"
            width="44"
            height="44"
        />
    </div>

    <span class="fi-brand-name truncate text-base font-bold leading-none tracking-tight text-gray-950 dark:text-white">
        {{ $appName }}
    </span>
</div>
