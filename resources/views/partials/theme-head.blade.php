{{--
    The theme the viewer chose for the journey planner and the student portal
    (config/themes.php), written into <head> before the page paints, so
    there is never a flash of another theme. Sets <html data-jp-theme>,
    <html data-appearance> (light, dark or auto) and <html data-glass>
    (tinted or clear), for the themes that use them.
    Pass $surface: "planner" or "auth".
--}}
@php
    $registry = config('themes.planner');
    $sheets = [];
    $themes = [];
    foreach ($registry['themes'] as $key => $theme) {
        $sheets[$key] = array_map(
            fn (string $path) => str_starts_with($path, 'https://') ? $path : asset($path).'?v='.@filemtime(public_path($path)),
            $theme[$surface],
        );
        $themes[] = ['key' => $key, 'label' => $theme['label'], 'appearance' => (bool) ($theme['appearance'] ?? false), 'material' => (bool) ($theme['material'] ?? false)];
    }
@endphp
<script>
    (() => {
        const sheets = @json($sheets);
        const themes = @json($themes);
        let theme = @json($registry['default']);
        let appearance = 'auto';
        let glass = 'tinted';
        try {
            const savedTheme = localStorage.getItem('jpTheme');
            if (savedTheme && sheets[savedTheme]) theme = savedTheme;
            const savedAppearance = localStorage.getItem('jpAppearance');
            if (['light', 'dark', 'auto'].includes(savedAppearance)) appearance = savedAppearance;
            const savedGlass = localStorage.getItem('jpGlass');
            if (['tinted', 'clear'].includes(savedGlass)) glass = savedGlass;
        } catch (error) { /* private window: the default stands */ }
        const root = document.documentElement;
        root.dataset.jpTheme = theme;
        root.dataset.appearance = appearance;
        root.dataset.glass = glass;
        // The glass lens bends the backdrop through an SVG filter, which only
        // Chromium draws; elsewhere the glass is the same, without the bend.
        const brands = (navigator.userAgentData && navigator.userAgentData.brands) || [];
        if (brands.some((b) => /Chromium/.test(b.brand))) root.classList.add('lg-lens');
        window.JP_THEMES = { current: theme, list: themes };
        document.write(sheets[theme].map((href) => '<link rel="stylesheet" href="' + href + '">').join(''));
    })();
</script>
<noscript>@foreach ($sheets[$registry['default']] as $href)<link rel="stylesheet" href="{{ $href }}">@endforeach</noscript>
