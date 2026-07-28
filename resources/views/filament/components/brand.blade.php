<div class="flex h-full items-center gap-2.5" role="group" aria-label="{{ config('app.name') }}">
    <img
        src="{{ asset('images/logo-pengayoman.png') }}"
        alt="Logo Pengayoman"
        class="h-full w-auto shrink-0 rounded-sm object-contain"
        width="40"
        height="40"
    />
    <img
        src="{{ asset('images/logo-bphn.jpg') }}"
        alt="Logo BPHN"
        class="h-full w-auto shrink-0 rounded-sm object-contain"
        width="40"
        height="40"
    />
    <span class="truncate text-base font-bold leading-none tracking-tight text-gray-950 dark:text-white">
        {{ config('app.name') }}
    </span>
</div>
