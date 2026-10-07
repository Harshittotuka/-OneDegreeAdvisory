{{--
    The lens of Liquid Glass: what is behind the glass bends a little, like
    looking through gel, rather than only blurring. An SVG displacement
    filter, used by the Glass theme on its navigation layer (sidebar, tab
    bar, top-bar controls, menus) in browsers that can filter a backdrop
    (html.lg-lens, set by partials.theme-head). Inert everywhere else.
--}}
<svg width="0" height="0" style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true" focusable="false">
    <filter id="lg-lens" x="0" y="0" width="100%" height="100%" color-interpolation-filters="sRGB">
        <feTurbulence type="fractalNoise" baseFrequency="0.008 0.011" numOctaves="2" seed="7" result="noise"/>
        <feGaussianBlur in="noise" stdDeviation="2" result="map"/>
        <feDisplacementMap in="SourceGraphic" in2="map" scale="38" xChannelSelector="R" yChannelSelector="G"/>
    </filter>
</svg>
