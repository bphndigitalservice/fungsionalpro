import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Clusters/Reference/**/*.php',
        './resources/views/filament/clusters/reference/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/kenepa/banner/resources/**/*.php',
    ],
}
