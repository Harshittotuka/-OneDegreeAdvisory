{{--
    The animated scene beside the student sign-in forms: a turning line-globe,
    flight paths drawing out from India to four destinations with a plane on
    the main route, floating destination chips, and the journey's four steps
    lighting up in turn. Decoration only; everything here is aria-hidden
    except the copy. Takes $eyebrow, $lead, $gold, $tail and $text.
--}}
<aside class="sp-visual" data-sp-visual>
    <div class="sp-sky" aria-hidden="true">
        <i style="--x:8%;--y:14%;--d:0s"></i><i style="--x:22%;--y:7%;--d:1.2s"></i><i style="--x:41%;--y:12%;--d:2.1s"></i>
        <i style="--x:63%;--y:6%;--d:.6s"></i><i style="--x:84%;--y:16%;--d:1.8s"></i><i style="--x:92%;--y:44%;--d:2.6s"></i>
        <i style="--x:5%;--y:52%;--d:1.4s"></i><i style="--x:14%;--y:83%;--d:.3s"></i><i style="--x:78%;--y:88%;--d:2.3s"></i>
        <i style="--x:55%;--y:93%;--d:1s"></i><i style="--x:33%;--y:64%;--d:2.9s"></i><i style="--x:96%;--y:72%;--d:.9s"></i>
    </div>

    <div class="sp-stage" aria-hidden="true">
    <div class="sp-scene" data-sp-parallax>
        <svg class="sp-globe" viewBox="26 35 468 450" fill="none">
            <defs>
                <radialGradient id="spGlobeFill" cx="38%" cy="32%" r="75%">
                    <stop offset="0%" stop-color="#3a27c4" stop-opacity=".55"/>
                    <stop offset="60%" stop-color="#1a0088" stop-opacity=".25"/>
                    <stop offset="100%" stop-color="#100258" stop-opacity="0"/>
                </radialGradient>
                <linearGradient id="spRoute" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stop-color="#ff5e32"/>
                    <stop offset="100%" stop-color="#f7da82"/>
                </linearGradient>
                <clipPath id="spGlobeClip"><circle cx="260" cy="260" r="170"/></clipPath>
            </defs>

            <circle class="sp-halo" cx="260" cy="260" r="214"/>
            <circle cx="260" cy="260" r="170" fill="url(#spGlobeFill)"/>
            <circle cx="260" cy="260" r="170" class="sp-rim"/>

            <g clip-path="url(#spGlobeClip)" class="sp-grid">
                {{-- Parallels --}}
                <ellipse cx="260" cy="260" rx="170" ry="34"/>
                <ellipse cx="260" cy="190" rx="155" ry="28"/>
                <ellipse cx="260" cy="330" rx="155" ry="28"/>
                <ellipse cx="260" cy="128" rx="112" ry="20"/>
                <ellipse cx="260" cy="392" rx="112" ry="20"/>
                {{-- Meridians, turning: each sweeps its width from full to nothing --}}
                <ellipse cx="260" cy="260" ry="170" rx="170"><animate attributeName="rx" values="170;0;170" dur="14s" repeatCount="indefinite"/></ellipse>
                <ellipse cx="260" cy="260" ry="170" rx="113"><animate attributeName="rx" values="113;0;170;113" keyTimes="0;.22;.72;1" dur="14s" repeatCount="indefinite"/></ellipse>
                <ellipse cx="260" cy="260" ry="170" rx="57"><animate attributeName="rx" values="57;0;170;57" keyTimes="0;.11;.61;1" dur="14s" repeatCount="indefinite"/></ellipse>
                <line x1="260" y1="90" x2="260" y2="430"/>
            </g>

            {{-- Orbit with a travelling satellite --}}
            <g class="sp-orbit">
                <ellipse cx="260" cy="260" rx="236" ry="74" transform="rotate(-18 260 260)"/>
                <circle class="sp-sat" r="5">
                    <animateMotion dur="11s" repeatCount="indefinite" path="M 36 333 A 236 74 -18 1 1 484 187 A 236 74 -18 1 1 36 333"/>
                </circle>
            </g>

            {{-- Routes: India to the UK, Canada, Germany and Australia --}}
            <g class="sp-routes">
                <path id="spR1" class="sp-route-base" d="M322 296 Q252 112 196 176"/>
                <path class="sp-route r1" pathLength="1" d="M322 296 Q252 112 196 176"/>
                <path class="sp-route-base" d="M322 296 Q190 150 128 214"/>
                <path class="sp-route r2" pathLength="1" d="M322 296 Q190 150 128 214"/>
                <path class="sp-route-base" d="M322 296 Q282 150 246 180"/>
                <path class="sp-route r3" pathLength="1" d="M322 296 Q282 150 246 180"/>
                <path class="sp-route-base" d="M322 296 Q420 300 404 382"/>
                <path class="sp-route r4" pathLength="1" d="M322 296 Q420 300 404 382"/>
            </g>

            {{-- Pins --}}
            <g class="sp-pins">
                <g class="sp-pin home" transform="translate(322 296)"><circle class="pulse" r="6"/><circle class="dot" r="6"/></g>
                <g class="sp-pin" transform="translate(196 176)" style="--pd:.4s"><circle class="pulse" r="4"/><circle class="dot" r="4"/></g>
                <g class="sp-pin" transform="translate(128 214)" style="--pd:1.1s"><circle class="pulse" r="4"/><circle class="dot" r="4"/></g>
                <g class="sp-pin" transform="translate(246 180)" style="--pd:1.8s"><circle class="pulse" r="4"/><circle class="dot" r="4"/></g>
                <g class="sp-pin" transform="translate(404 382)" style="--pd:2.5s"><circle class="pulse" r="4"/><circle class="dot" r="4"/></g>
            </g>

            {{-- The plane, flying the main route --}}
            <g class="sp-plane">
                <path transform="scale(1.15)" d="M11 0C11-1.3 10-2 8.8-2H3.2L-2.8-9.5H-5.4L-2-2H-7.2L-9.2-4.8H-11L-9.6 0-11 4.8H-9.2L-7.2 2H-2L-5.4 9.5H-2.8L3.2 2H8.8C10 2 11 1.3 11 0Z"/>
                <animateMotion dur="6s" repeatCount="indefinite" rotate="auto" keyPoints="0;1;1" keyTimes="0;.8;1" calcMode="linear"><mpath href="#spR1"/></animateMotion>
                <animate attributeName="opacity" values="0;1;1;0;0" keyTimes="0;.08;.72;.8;1" dur="6s" repeatCount="indefinite"/>
            </g>
        </svg>

        <span class="sp-chip c1"><b></b>United Kingdom</span>
        <span class="sp-chip c2"><b></b>Canada</span>
        <span class="sp-chip c3"><b></b>Germany</span>
        <span class="sp-chip c4"><b></b>Australia</span>
        <span class="sp-chip home"><b></b>India · you are here</span>
    </div>
    </div>

    <div class="sp-visual-copy">
        <span class="sp-eyebrow light">{{ $eyebrow }}</span>
        <h2>{{ $lead }} <em class="sp-gold">{{ $gold }}</em> {{ $tail }}</h2>
        <p>{{ $text }}</p>

        <ol class="sp-steps" aria-hidden="true">
            <li style="--i:0"><span>1</span>Shortlist</li>
            <li style="--i:1"><span>2</span>Apply</li>
            <li style="--i:2"><span>3</span>Visa</li>
            <li style="--i:3"><span>4</span>Fly out</li>
        </ol>
    </div>
</aside>
