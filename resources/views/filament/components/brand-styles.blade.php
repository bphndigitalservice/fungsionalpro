<style>
    /* Equal square footprint for both institutional logos */
    .fi-brand-logos .fi-brand-logo {
        aspect-ratio: 1 / 1;
        width: auto;
        height: 100%;
        max-width: none;
    }

    /*
     * Login / simple auth header: logos on one row, app name underneath.
     * Filament sets an inline height on .fi-logo — override so the stack can grow.
     */
    .fi-simple-header .fi-logo {
        height: auto !important;
    }

    .fi-simple-header .fi-brand {
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        height: auto;
    }

    .fi-simple-header .fi-brand-logos {
        height: 4.5rem;
        gap: 0.75rem;
    }

    .fi-simple-header .fi-brand-name {
        font-size: 1.25rem;
        line-height: 1.2;
        text-align: center;
    }
</style>
