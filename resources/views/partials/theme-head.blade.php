{{--
    The theme the viewer chose for the journey planner and the student portal
    (config/themes.php), written into <head> before the page paints, so
    there is never a flash of another theme. Sets <html data-jp-theme>,
    <html data-appearance> (light, dark or auto) and <html data-glass>
    (tinted or clear), for the themes that use them; and, for a theme with
    backgrounds, <html data-wp> (which one), data-wp-tone (light or dark)
    and, for a photo, data-wp-photo with its picture in --g-photo.
    Pass $surface: "planner" or "auth".
--}}
@php
    $registry = config('themes.planner');
    $sheets = [];
    $themes = [];
    $wallpapers = [];
    $versioned = fn (string $path) => asset($path).'?v='.@filemtime(public_path($path));
    foreach ($registry['themes'] as $key => $theme) {
        $sheets[$key] = array_map(
            fn (string $path) => str_starts_with($path, 'https://') ? $path : asset($path).'?v='.@filemtime(public_path($path)),
            $theme[$surface],
        );
        $themes[] = ['key' => $key, 'label' => $theme['label'], 'appearance' => (bool) ($theme['appearance'] ?? false), 'material' => (bool) ($theme['material'] ?? false)];
        if (! empty($theme['wallpapers'])) {
            $wallpapers[$key] = [
                'default' => $theme['wallpaper'] ?? $theme['wallpapers'][0]['key'],
                'list' => array_map(fn (array $w) => [
                    'key' => $w['key'], 'label' => $w['label'], 'group' => $w['group'], 'tone' => $w['tone'],
                    'image' => isset($w['source']) ? $versioned('assets/glass/wallpapers/'.$w['key'].'.webp') : null,
                    'thumb' => isset($w['source']) ? $versioned('assets/glass/wallpapers/thumbs/'.$w['key'].'.webp') : null,
                ], $theme['wallpapers']),
            ];
        }
    }
@endphp
<script>
    (() => {
        const sheets = @json($sheets);
        const themes = @json($themes);
        const wallpapers = @json((object) $wallpapers);
        let theme = @json($registry['default']);
        let appearance = 'light';
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
        // The background behind the glass, for themes that offer a choice.
        const wp = wallpapers[theme];
        let wallpaper = null;
        if (wp) {
            wallpaper = wp.default;
            try {
                const savedWallpaper = localStorage.getItem('jpWallpaper');
                if (wp.list.some((w) => w.key === savedWallpaper)) wallpaper = savedWallpaper;
            } catch (error) { /* private window: the default stands */ }
            const chosen = wp.list.find((w) => w.key === wallpaper);
            root.dataset.wp = chosen.key;
            root.dataset.wpTone = chosen.tone;
            if (chosen.image) {
                root.dataset.wpPhoto = '';
                root.style.setProperty('--g-photo', 'url("' + chosen.image + '")');
            }
        }
        window.JP_THEMES = { current: theme, list: themes, wallpapers: wp ? { current: wallpaper, list: wp.list } : null };
        document.write(sheets[theme].map((href) => '<link rel="stylesheet" href="' + href + '">').join(''));
    })();
</script>
<noscript>@foreach ($sheets[$registry['default']] as $href)<link rel="stylesheet" href="{{ $href }}">@endforeach</noscript>
