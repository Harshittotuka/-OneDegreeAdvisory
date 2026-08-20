@extends('career-library.layout')

@php
    use App\Support\CareerLibraryIcons as Icons;

    // Live-editor instrumentation. When the admin live editor renders this page
    // ($live = true) every editable node gains data-ed* hooks that the editor
    // chrome (admin.career-library._editor_chrome) decorates and serializes.
    // With $live false/absent the public markup is exactly what it always was.
    $live = ! empty($live);
    $ed = fn (string $k): string => $live ? ' data-ed="'.$k.'"' : '';
    $rep = fn (string $k): string => $live ? ' data-ed-rep="'.$k.'"' : '';
    $it = $live ? ' data-ed-item' : '';
    $mirror = fn (string $k): string => $live ? ' data-ed-mirror="'.$k.'"' : '';

    $seo = $data['seo'] ?? [];
    $stats = $data['stats'];
    $salary = $stats['salary'];
    $currency = $salary['currency'] !== '' ? $salary['currency'] : 'INR';
    $year = $settings['report_year'];

    // The source backend sometimes stores a mojibake '?' before ₹ amounts; the
    // original page strips it at render time, so we do the same.
    $entrySalary = str_replace('?', '', $salary['entry']);
    $seniorSalary = str_replace('?', '', $salary['senior']);

    $demandLevel = $stats['demandLevel'] !== '' ? $stats['demandLevel'] : 'High';
    $outlookColor = 'bg-emerald-500';
    $outlookTextColor = 'text-emerald-600';
    $outlookWidth = '90%';
    if ($demandLevel === 'Medium') {
        $outlookColor = 'bg-amber-500';
        $outlookTextColor = 'text-amber-600';
        $outlookWidth = '60%';
    } elseif ($demandLevel === 'Low') {
        $outlookColor = 'bg-rose-500';
        $outlookTextColor = 'text-rose-600';
        $outlookWidth = '30%';
    }

    $industryChipColors = [
        'bg-blue-50 text-blue-700 border-blue-200',
        'bg-emerald-50 text-emerald-700 border-emerald-200',
        'bg-purple-50 text-purple-700 border-purple-200',
        'bg-orange-50 text-orange-700 border-orange-200',
        'bg-pink-50 text-pink-700 border-pink-200',
        'bg-cyan-50 text-cyan-700 border-cyan-200',
    ];

    $accordionGroups = [
        ['key' => 'conventionalOptions', 'title' => 'Conventional Career Options', 'icon' => 'briefcase', 'options' => $data['conventionalOptions'], 'color' => 'bg-indigo-500', 'iconColor' => 'text-indigo-700'],
        ['key' => 'newAgeOptions', 'title' => 'New Age Career Options', 'icon' => 'zap', 'options' => $data['newAgeOptions'], 'color' => 'bg-amber-500', 'iconColor' => 'text-amber-600'],
        ['key' => 'aiRelatedOptions', 'title' => 'AI Related Career Options', 'icon' => 'cpu', 'options' => $data['aiRelatedOptions'], 'color' => 'bg-cyan-500', 'iconColor' => 'text-cyan-600'],
    ];
@endphp

@section('title', $seo['title'] !== '' ? $seo['title'] : $careerName.' Career — Trending Career')
@section('meta_description', $seo['description'] !== '' ? $seo['description'] : 'Explore the '.$careerName.' career path: salary, eligibility, pathways and outlook.')
@section('meta_keywords', ! empty($seo['keywords']) ? implode(',', $seo['keywords']) : 'career, guidance, roadmap, jobs, profession, education')

@section('app')
<div class="max-w-5xl mx-auto px-4 mb-4 pt-8 rpdf">
    <button onclick="window.location.href='{{ route('career-library.index') }}'" class="text-sm text-indigo-600 font-medium hover:underline flex items-center gap-1">
    ← Search another career
    </button>
</div>
<div class="w-full max-w-7xl mx-auto pb-24 animate-fade-in-up">

    <!-- HERO HEADER -->
    <header class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-b-[3rem] text-white overflow-hidden shadow-2xl mb-16 mx-4 md:mx-0">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 right-0 w-96 h-96 bg-rose-500 rounded-full blur-3xl -mr-20 -mt-20"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-blue-500 rounded-full blur-3xl -ml-20 -mb-20"></div>
    </div>

    <div class="relative px-6 py-12 md:px-12 md:py-16 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">

        <!-- Left: Title & Intro -->
        <div class="lg:col-span-7 space-y-6">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 backdrop-blur-md border border-white/20 rounded-full text-xs font-bold uppercase tracking-wider text-indigo-200">
                {!! Icons::svg('compass') !!} Career Blueprint
            </div>
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold leading-tight font-sans"{!! $ed('title') !!}>
                {{ $data['title'] }}
            </h1>
            <p class="text-lg md:text-xl text-indigo-50 leading-relaxed max-w-2xl border-l-4 border-rose-500 pl-6"{!! $ed('introduction') !!}>
                {{ $data['introduction'] }}
            </p>
        </div>

        <!-- Right: Market Snapshot (COMPACT DESIGN) -->
        <div class="lg:col-span-5 relative w-full">
            <div class="bg-white/95 backdrop-blur-xl rounded-2xl p-5 shadow-xl border border-white/40 w-full animate-shake-card" @if($live) data-cl-stats-card @endif>
                <h3 class="text-lg font-bold text-slate-900 mb-4 flex flex-col gap-1 border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-indigo-600">{!! Icons::svg('barChart') !!}</span>
                        Market Snapshot
                        @if ($live)
                            <button type="button" class="cl-stats-gear" data-cl-stats-open contenteditable="false" title="Median salary &amp; demand level">⚙</button>
                        @endif
                    </div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide ml-7">
                        @if ($live)
                            <span data-ed-mirror="title">{{ $data['title'] }}</span> | {{ $year }} | <span{!! $ed('salary_currency') !!}>{{ $currency }}</span>
                        @else
                            {{ $data['title'] }} | {{ $year }} | {{ $currency }}
                        @endif
                    </span>
                </h3>

                <!-- IMPRESSIVE SALARY GRAPH (Responsive Dumbbell Plot) - COMPACT -->
                <div class="mb-4 w-full bg-slate-50/50 rounded-2xl border border-slate-100 p-3 md:p-4">

                    <!-- Top Graphic Area -->
                    <div class="relative h-14 w-full flex items-center justify-between mb-2">

                        <!-- Connecting Line Background -->
                        <div class="absolute left-3 right-4 top-1/2 -translate-y-1/2 h-2 bg-slate-200 rounded-full overflow-hidden z-0">
                            <div class="h-full w-full bg-gradient-to-r from-indigo-300 via-purple-500 to-indigo-600 animate-pulse opacity-80"></div>
                        </div>

                        <!-- Center Badge (Floating) -->
                        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-20">
                            <div class="bg-indigo-600 text-white text-[9px] font-bold px-2 py-0.5 rounded-full border-2 border-white shadow-sm whitespace-nowrap flex items-center gap-1 transform -translate-y-5 md:-translate-y-6 animate-bounce">
                                High Growth 🚀
                            </div>
                        </div>

                        <!-- Entry Dot -->
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-5 h-5 md:w-6 md:h-6 bg-indigo-100 rounded-full border-[3px] border-white shadow-md box-content">
                                <div class="w-full h-full rounded-full bg-indigo-400 opacity-60"></div>
                            </div>
                        </div>

                        <!-- Senior Dot -->
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-7 h-7 md:w-8 md:h-8 bg-indigo-600 rounded-full border-[3px] border-white shadow-lg box-content relative">
                                <div class="absolute inset-0 bg-white rounded-full opacity-20 animate-ping"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Text Area -->
                    <div class="flex justify-between items-start gap-2">
                        <!-- Entry Text -->
                        <div class="flex-1 text-left min-w-0 pr-1">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-0.5">Entry Level</span>
                            <div class="bg-white rounded-lg border border-slate-200 p-1.5 shadow-sm inline-block max-w-full">
                                <span class="font-bold text-slate-700 text-xs md:text-sm break-words leading-tight block"{!! $ed('salary_entry') !!}>
                                    {{ $entrySalary }}
                                </span>
                            </div>
                        </div>

                        <!-- Spacer -->
                        <div class="w-2 shrink-0"></div>

                        <!-- Senior Text -->
                        <div class="flex-1 text-right min-w-0 pl-1">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-0.5">Senior Level</span>
                            <div class="bg-indigo-50 rounded-lg border border-indigo-100 p-1.5 shadow-sm inline-block max-w-full text-right">
                                <span class="font-bold text-indigo-700 text-xs md:text-sm break-words leading-tight block"{!! $ed('salary_senior') !!}>
                                    {{ $seniorSalary }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-3 pt-2 border-t border-slate-200/60">
                        <span class="text-[9px] text-slate-400 font-medium bg-white px-2 py-0.5 rounded-full border border-slate-100 inline-block">
                            *Estimated Annual Packages (<span{!! $mirror('salary_currency') !!}>{{ $currency }}</span>)
                        </span>
                    </div>

                </div>

                <!-- Grid Stats -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-indigo-50 rounded-xl p-3 border border-indigo-100 flex flex-col justify-center min-w-0">
                        <span class="text-[10px] font-bold text-indigo-400 uppercase">Growth</span>
                        <div class="flex items-center gap-1.5 mt-0.5 min-w-0">
                            <span class="text-emerald-500 flex-shrink-0 scale-75">{!! Icons::svg('trendingUp') !!}</span>
                            <span class="text-lg md:text-xl font-bold text-slate-900 truncate"{!! $ed('jobGrowth') !!}>{{ $stats['jobGrowth'] }}</span>
                        </div>
                    </div>
                    <div class="bg-rose-50 rounded-xl p-3 border border-rose-100 flex flex-col justify-center min-w-0" @if($live) data-cl-demand-open role="button" tabindex="0" title="Click to change demand level" @endif>
                        <span class="text-[10px] font-bold text-rose-400 uppercase">Demand</span>
                        <div class="flex items-center gap-1.5 mt-0.5 min-w-0">
                            <span class="text-rose-500 flex-shrink-0 scale-75">{!! Icons::svg('users') !!}</span>
                            <span class="text-lg md:text-xl font-bold text-slate-900 truncate" @if($live) data-cl-demand @endif>{{ $demandLevel }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-500 uppercase block mb-2 tracking-wide">Top Industries</span>
                    <div class="flex flex-wrap gap-1.5"{!! $rep('topIndustries') !!}>
                        @foreach ($stats['topIndustries'] as $i => $industry)
                            @if ($live)
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold border shadow-sm {{ $industryChipColors[$i % count($industryChipColors)] }}" data-ed-item data-cl-chip><span data-ed="text">{{ $industry }}</span></span>
                            @else
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold border shadow-sm {{ $industryChipColors[$i % count($industryChipColors)] }}">{{ $industry }}</span>
                            @endif
                        @endforeach
                        @if ($live)
                            <button type="button" class="cl-add-chip" data-cl-add="topIndustries" contenteditable="false" title="Add industry">+</button>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
    </header>

    <!-- TWO COLUMN LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 px-4 md:px-8">

    <!-- LEFT COLUMN: Main Content -->
    <div class="lg:col-span-8 space-y-12">

        <!-- Who Should Pursue -->
        <section class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 left-0 w-2 h-full bg-indigo-500"></div>
        <h3 class="flex items-center gap-3 text-2xl font-bold text-slate-900 mb-6">
            <span class="text-indigo-600">{!! Icons::svg('target') !!}</span>
            Who Should Pursue This?
        </h3>
        <ul class="grid grid-cols-1 gap-4"{!! $rep('whoShouldPursue') !!}>
            @foreach ($data['whoShouldPursue'] as $point)
            <li class="flex items-start gap-3"{!! $it !!}>
                <div class="mt-1.5 flex-shrink-0 w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center">
                <div class="w-2 h-2 rounded-full bg-indigo-600"></div>
                </div>
                <span class="text-slate-700 text-lg leading-relaxed"{!! $ed('text') !!}>{{ $point }}</span>
            </li>
            @endforeach
            @if ($live)
                <li class="cl-add-row" contenteditable="false"><button type="button" class="cl-add-btn" data-cl-add="whoShouldPursue">+ Add point</button></li>
            @endif
        </ul>
        </section>

        <!-- Work Nature -->
        <section>
        <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center gap-3 border-b border-slate-100 pb-4">
            <span class="text-emerald-600">{!! Icons::svg('briefcase') !!}</span>
            Work Nature & Reality
        </h2>
        <div class="space-y-6">
            <p class="text-slate-700 text-lg leading-relaxed"{!! $ed('workNature_description') !!}>
            {{ $data['workNature']['description'] }}
            </p>

            <div class="bg-slate-50 rounded-xl p-6">
            <h4 class="font-bold text-slate-900 mb-4 text-sm uppercase tracking-wide flex items-center gap-2">
                <span class="text-emerald-500">{!! Icons::svg('checkCircle') !!}</span>
                Work Activities
            </h4>
            <ul class="space-y-3"{!! $rep('workNature_examples') !!}>
                @foreach ($data['workNature']['examples'] as $example)
                <li class="flex items-start gap-3 text-slate-700"{!! $it !!}>
                    <span class="mt-2 w-1.5 h-1.5 bg-emerald-400 rounded-full flex-shrink-0"></span>
                    <span class="text-lg leading-relaxed"{!! $ed('text') !!}>{{ $example }}</span>
                </li>
                @endforeach
                @if ($live)
                    <li class="cl-add-row" contenteditable="false"><button type="button" class="cl-add-btn" data-cl-add="workNature_examples">+ Add activity</button></li>
                @endif
            </ul>
            </div>
        </div>
        </section>

        <!-- Eligibility -->
        <section class="bg-indigo-50 rounded-2xl p-8 border border-indigo-100">
        <h3 class="flex items-center gap-3 text-2xl font-bold text-slate-900 mb-6">
            <span class="text-indigo-600">{!! Icons::svg('gradHat') !!}</span>
            Eligibility & Requirements
        </h3>
        <ul class="grid grid-cols-1 gap-4"{!! $rep('eligibility') !!}>
            @foreach ($data['eligibility'] as $point)
            <li class="flex items-start gap-3"{!! $it !!}>
                <div class="mt-1.5 flex-shrink-0 w-5 h-5 rounded-full bg-indigo-200 flex items-center justify-center">
                <span class="text-indigo-700 text-xs font-bold">✓</span>
                </div>
                <span class="text-slate-700 text-lg leading-relaxed"{!! $ed('text') !!}>{{ $point }}</span>
            </li>
            @endforeach
            @if ($live)
                <li class="cl-add-row" contenteditable="false"><button type="button" class="cl-add-btn" data-cl-add="eligibility">+ Add requirement</button></li>
            @endif
        </ul>
        </section>

        <!-- Career Navigators -->
        <section>
        <h2 class="text-2xl font-bold text-slate-900 mb-8 flex items-center gap-3 border-b border-slate-100 pb-4">
            <span class="text-rose-600">{!! Icons::svg('mapPin') !!}</span>
            Career Navigators
        </h2>

        <div class="space-y-10"{!! $rep('pathways') !!}>
            @foreach ($data['pathways'] as $idx => $path)
                <div class="relative"{!! $it !!}>
                <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-3">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-900 text-white text-sm font-sans font-bold" @if($live) data-cl-pathnum contenteditable="false" @endif>
                    {{ $idx + 1 }}
                    </span>
                    @if ($live)<span data-ed="name">{{ $path['name'] }}</span>@else{{ $path['name'] }}@endif
                </h3>

                <div class="ml-4 pl-4 border-l-2 border-slate-200 space-y-4"{!! $rep('steps') !!}>
                    @foreach ($path['steps'] as $step)
                    <div class="relative pl-6"{!! $it !!}>
                        <div class="absolute left-[-21px] top-1 w-3 h-3 bg-white border-2 border-slate-300 rounded-full"></div>
                        <div class="flex items-start gap-2">
                            <span class="text-slate-400 flex-shrink-0 mt-0.5">{!! Icons::svg('chevronRight') !!}</span>
                            <div>
                            <h4 class="font-bold text-slate-900 text-lg"{!! $ed('title') !!}>{{ $step['title'] }}</h4>
                            <p class="text-slate-600 text-lg leading-relaxed mt-1"{!! $ed('description') !!}>{{ $step['description'] }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @if ($live)
                        <div class="cl-add-row" contenteditable="false"><button type="button" class="cl-add-btn" data-cl-add="steps">+ Add step</button></div>
                    @endif
                </div>
                </div>
            @endforeach
            @if ($live)
                <div class="cl-add-row" contenteditable="false"><button type="button" class="cl-add-btn" data-cl-add="pathways">+ Add route</button></div>
            @endif
        </div>
        </section>

        <!-- Detailed Career Options Sections (Accordions) -->
        <section class="pt-8">
        <h2 class="text-3xl font-bold text-slate-900 mb-8">Explore Opportunities</h2>

        @foreach ($accordionGroups as $group)
            @if (! empty($group['options']) || $live)
            <div class="mb-8 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-opacity-20 {{ $group['color'] }} text-white">
                    <span class="{{ $group['iconColor'] }}">{!! Icons::svg($group['icon']) !!}</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">{{ $group['title'] }}</h3>
                </div>
                <div class="divide-y divide-slate-100"{!! $rep($group['key']) !!}>
                    @foreach ($group['options'] as $opt)
                    <div class="group"{!! $it !!}>
                        @if ($live)
                            {{-- Live editor: a plain div, not a <button> — browsers block
                                 contenteditable inside a button, so the title wouldn't be
                                 editable. The accordion is force-open via CSS while editing. --}}
                            <div class="w-full text-left px-6 py-4 flex items-center justify-between">
                                <span class="font-bold text-slate-700 text-lg" data-ed="title">
                                    {{ $opt['title'] }}
                                </span>
                            </div>
                        @else
                            <button
                                onclick="toggleAccordion(this)"
                                class="w-full text-left px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors focus:outline-none"
                            >
                                <span class="font-bold text-slate-700 text-lg group-hover:text-indigo-600 transition-colors">
                                    {{ $opt['title'] }}
                                </span>
                                <span class="chevron-icon text-slate-400 transition-transform duration-300">
                                    {!! Icons::svg('chevronDown') !!}
                                </span>
                            </button>
                        @endif
                        <div class="accordion-content bg-slate-50/50">
                            <div class="px-6 pb-6 pt-2">
                                <p class="text-slate-600 leading-relaxed mb-4 text-lg"{!! $ed('description') !!}>
                                {{ $opt['description'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @if ($live)
                        <div class="cl-add-row" contenteditable="false"><button type="button" class="cl-add-btn" data-cl-add="{{ $group['key'] }}">+ Add option</button></div>
                    @endif
                </div>
            </div>
            @endif
        @endforeach
        </section>

    </div>

    <!-- RIGHT COLUMN: Sidebar (Sticky) -->
    <div class="lg:col-span-4 space-y-8">
        <div class="sticky top-8 space-y-8">

            <!-- Market Outlook -->
            <div class="bg-gradient-to-br from-white to-slate-50 rounded-xl border border-slate-200 shadow-sm p-6">
            <h4 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                <span class="text-indigo-500">{!! Icons::svg('building') !!}</span>
                Market Outlook
            </h4>
            <p class="text-slate-600 text-lg leading-relaxed mb-4"{!! $ed('futureOutlook') !!}>
                {{ $stats['futureOutlook'] }}
            </p>
            <div class="w-full bg-slate-200 rounded-full h-2">
                <div class="{{ $outlookColor }} h-2 rounded-full" style="width: {{ $outlookWidth }}" @if($live) data-cl-outlook-bar @endif></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-slate-500">
                <span>Demand Level</span>
                <span class="font-bold {{ $outlookTextColor }}" @if($live) data-cl-outlook-label @endif>{{ $demandLevel }}</span>
            </div>
            </div>

            <!-- Video Recommendations -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden rpdf">
            <div class="bg-rose-50 px-6 py-4 border-b border-rose-100">
                <h3 class="font-bold text-lg text-rose-900 flex items-center gap-2">
                <span class="text-rose-600">{!! Icons::svg('youtube') !!}</span>
                Recommended Watch
                </h3>
            </div>
            <div class="p-4 space-y-4"{!! $rep('videoRecommendations') !!}>
                @foreach ($data['videoRecommendations'] as $index => $video)
                    @php
                        $ytQuery = trim($video['title'] !== '' ? $video['title'].' '.$video['channelName'] : $careerName.' career');
                        $videoHref = $video['url'] !== '' ? $video['url'] : 'https://www.youtube.com/results?search_query='.urlencode($ytQuery);
                    @endphp
                    @if ($live)
                        <div class="group block" data-ed-item data-cl-video>
                            <script type="application/json" class="le-extra">@json(['url' => $video['url']])</script>
                            <div class="relative rounded-lg overflow-hidden bg-slate-100 aspect-video mb-2 flex items-center justify-center border border-slate-200 group-hover:border-rose-300 transition-colors"
                                 data-ed-img="thumbnail" data-ed-bg="1" data-ed-imgval="{{ $video['thumbnail'] }}"
                                 @if ($video['thumbnail'] !== '') style="background-image: url('{{ $video['thumbnail'] }}'); background-size: cover; background-position: center;" @endif>
                                <span class="{{ $video['thumbnail'] !== '' ? 'text-white/90 drop-shadow' : 'text-slate-400' }} group-hover:text-rose-600 transition-colors">{!! Icons::svg('playCircle') !!}</span>
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm leading-tight group-hover:text-rose-600 transition-colors" data-ed="title">
                                {{ $video['title'] !== '' ? $video['title'] : ($index + 1 == 1 ? 'Career in '.$careerName : 'Career Opportunities in '.$careerName) }}
                            </h4>
                            <span class="text-xs text-slate-500 block mt-1" data-ed="channelName">{{ $video['channelName'] }}</span>
                            <span class="text-xs text-slate-400 block mt-1 italic" data-ed="description">{{ $video['description'] }}</span>
                        </div>
                    @else
                        <a
                        href="{{ $videoHref }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group block"
                        >
                        <div class="relative rounded-lg overflow-hidden bg-slate-100 aspect-video mb-2 flex items-center justify-center border border-slate-200 group-hover:border-rose-300 transition-colors">
                            @if ($video['thumbnail'] !== '')
                                <img src="{{ $video['thumbnail'] }}" alt="{{ $video['title'] }}" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
                                <span class="relative text-white/90 drop-shadow group-hover:text-rose-500 transition-colors">{!! Icons::svg('playCircle') !!}</span>
                            @else
                                <span class="text-slate-400 group-hover:text-rose-600 transition-colors">{!! Icons::svg('playCircle') !!}</span>
                            @endif
                        </div>
                        <h4 class="font-bold text-slate-900 text-sm leading-tight group-hover:text-rose-600 transition-colors">
                            {{ $video['title'] !== '' ? $video['title'] : ($index + 1 == 1 ? 'Career in '.$careerName : 'Career Opportunities in '.$careerName) }}
                        </h4>
                        @if ($video['channelName'] !== '')
                            <span class="text-xs text-slate-500 block mt-1">{{ $video['channelName'] }}</span>
                        @endif
                        </a>
                    @endif
                @endforeach
                @if ($live)
                    <div class="cl-add-row" contenteditable="false"><button type="button" class="cl-add-btn" data-cl-add="videoRecommendations">+ Add video</button></div>
                @endif
            </div>
            </div>

        </div>
    </div>

    </div>

    <!-- NEXT STEP SECTION START -->
    <div class="mt-20 border-t border-slate-200 pt-12 px-4 md:px-8 rpdf next-steps">
    <h2 class="text-3xl font-bold text-slate-900 mb-10 text-center">Take the Next Step</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- Card 1: 15-Year Plan -->
        <a target="_blank" href="{{ $settings['next_steps_url'] }}">
        <div class="group relative bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 cursor-pointer overflow-hidden ">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-indigo-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 mb-4 group-hover:scale-110 transition-transform">
                {!! Icons::svg('compass') !!}
            </div>
            <h3 class="font-bold text-slate-800 text-lg leading-tight mb-2 group-hover:text-blue-600 transition-colors">
                15-Year Career Plan
            </h3>
            <p class="text-slate-500 text-sm">
                Get a detailed 15-Year Plan for <strong{!! $mirror('title') !!}>{{ $data['title'] }}</strong> {{ $year }}.
            </p>
        </div>
        </a>

        <!-- Card 2: Salary Prediction -->
        <a target="_blank" href="{{ $settings['next_steps_url'] }}">
        <div class="group relative bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 cursor-pointer overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 to-teal-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
            <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 mb-4 group-hover:scale-110 transition-transform">
                {!! Icons::svg('barChart') !!}
            </div>
            <h3 class="font-bold text-slate-800 text-lg leading-tight mb-2 group-hover:text-emerald-600 transition-colors">
                Salary Prediction {{ $year }}
            </h3>
            <p class="text-slate-500 text-sm">
                Salary prediction for <strong{!! $mirror('title') !!}>{{ $data['title'] }}</strong> {{ $year }}.
            </p>
        </div>
        </a>

        <!-- Card 3: Top Skills -->
        <a target="_blank" href="{{ $settings['next_steps_url'] }}">
        <div class="group relative bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 cursor-pointer overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-amber-500 to-orange-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600 mb-4 group-hover:scale-110 transition-transform">
                {!! Icons::svg('zap') !!}
            </div>
            <h3 class="font-bold text-slate-800 text-lg leading-tight mb-2 group-hover:text-amber-600 transition-colors">
                Top Skills in Demand
            </h3>
            <p class="text-slate-500 text-sm">
                Top Skills in Demand for <strong{!! $mirror('title') !!}>{{ $data['title'] }}</strong> in {{ $year }}.
            </p>
        </div>
        </a>

        <!-- Card 4: Assessment & Counselling -->
        <a target="_blank" href="{{ $settings['next_steps_url'] }}">
        <div class="group relative bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 cursor-pointer overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-rose-500 to-pink-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
            <div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center text-rose-600 mb-4 group-hover:scale-110 transition-transform">
                {!! Icons::svg('users') !!}
            </div>
            <h3 class="font-bold text-slate-800 text-lg leading-tight mb-2 group-hover:text-rose-600 transition-colors">
                Assessment & Counselling
            </h3>
            <p class="text-slate-500 text-sm">
                Get Career Assessment and Career Counselling.
            </p>
        </div>
        </a>

    </div>
    </div>
    <!-- NEXT STEP SECTION END -->

    <!-- AI DISCLAIMER -->
    <p class="mt-10 pt-6 border-t border-slate-200 max-w-3xl mx-auto text-center text-sm italic text-slate-500 leading-relaxed">
        {{ \App\Support\AiDisclaimer::TEXT }}
    </p>

</div>

@if ($live)
{{-- Blank-item templates the editor clones when "+ Add …" is clicked. Each
     matches the live-mode markup of one repeater item exactly. --}}
<template data-cl-tpl="topIndustries"><span class="px-2.5 py-1 rounded-lg text-[10px] font-bold border shadow-sm bg-blue-50 text-blue-700 border-blue-200" data-ed-item data-cl-chip><span data-ed="text"></span></span></template>
<template data-cl-tpl="whoShouldPursue"><li class="flex items-start gap-3" data-ed-item>
    <div class="mt-1.5 flex-shrink-0 w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center"><div class="w-2 h-2 rounded-full bg-indigo-600"></div></div>
    <span class="text-slate-700 text-lg leading-relaxed" data-ed="text"></span>
</li></template>
<template data-cl-tpl="workNature_examples"><li class="flex items-start gap-3 text-slate-700" data-ed-item>
    <span class="mt-2 w-1.5 h-1.5 bg-emerald-400 rounded-full flex-shrink-0"></span>
    <span class="text-lg leading-relaxed" data-ed="text"></span>
</li></template>
<template data-cl-tpl="eligibility"><li class="flex items-start gap-3" data-ed-item>
    <div class="mt-1.5 flex-shrink-0 w-5 h-5 rounded-full bg-indigo-200 flex items-center justify-center"><span class="text-indigo-700 text-xs font-bold">✓</span></div>
    <span class="text-slate-700 text-lg leading-relaxed" data-ed="text"></span>
</li></template>
<template data-cl-tpl="steps"><div class="relative pl-6" data-ed-item>
    <div class="absolute left-[-21px] top-1 w-3 h-3 bg-white border-2 border-slate-300 rounded-full"></div>
    <div class="flex items-start gap-2">
        <span class="text-slate-400 flex-shrink-0 mt-0.5">{!! Icons::svg('chevronRight') !!}</span>
        <div>
        <h4 class="font-bold text-slate-900 text-lg" data-ed="title"></h4>
        <p class="text-slate-600 text-lg leading-relaxed mt-1" data-ed="description"></p>
        </div>
    </div>
</div></template>
<template data-cl-tpl="pathways"><div class="relative" data-ed-item>
    <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-3">
        <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-900 text-white text-sm font-sans font-bold" data-cl-pathnum contenteditable="false">•</span>
        <span data-ed="name"></span>
    </h3>
    <div class="ml-4 pl-4 border-l-2 border-slate-200 space-y-4" data-ed-rep="steps">
        <div class="cl-add-row" contenteditable="false"><button type="button" class="cl-add-btn" data-cl-add="steps">+ Add step</button></div>
    </div>
</div></template>
<template data-cl-tpl="option"><div class="group" data-ed-item>
    <div class="w-full text-left px-6 py-4 flex items-center justify-between">
        <span class="font-bold text-slate-700 text-lg" data-ed="title"></span>
    </div>
    <div class="accordion-content bg-slate-50/50">
        <div class="px-6 pb-6 pt-2">
            <p class="text-slate-600 leading-relaxed mb-4 text-lg" data-ed="description"></p>
        </div>
    </div>
</div></template>
<template data-cl-tpl="videoRecommendations"><div class="group block" data-ed-item data-cl-video>
    <script type="application/json" class="le-extra">{"url":""}</script>
    <div class="relative rounded-lg overflow-hidden bg-slate-100 aspect-video mb-2 flex items-center justify-center border border-slate-200 group-hover:border-rose-300 transition-colors" data-ed-img="thumbnail" data-ed-bg="1" data-ed-imgval="">
        <span class="text-slate-400 group-hover:text-rose-600 transition-colors">{!! Icons::svg('playCircle') !!}</span>
    </div>
    <h4 class="font-bold text-slate-900 text-sm leading-tight group-hover:text-rose-600 transition-colors" data-ed="title"></h4>
    <span class="text-xs text-slate-500 block mt-1" data-ed="channelName"></span>
    <span class="text-xs text-slate-400 block mt-1 italic" data-ed="description"></span>
</div></template>
@endif
@endsection

@unless ($live)
@section('overlays')
{{-- Lead-capture gate. The career report is hidden behind this until the
     visitor submits their contact details (recorded server-side). Once done
     the front-end sets a session flag so it isn't shown again this visit. It is
     NOT rendered in the admin live editor. --}}
{{-- Self-contained styles: the page's compiled Tailwind (no build step) omits
     several utilities this modal would need (arbitrary z-index, /opacity
     backdrops, focus:ring, hover states), so the gate is styled with its own
     dedicated CSS below rather than depending on those classes existing. --}}
<style>
    #cl-lead-gate {
        position: fixed; inset: 0; z-index: 100000;
        display: none; align-items: center; justify-content: center;
        padding: 16px;
        background: rgba(15, 23, 42, 0.62);
        -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
    }
    #cl-lead-gate.is-open { display: flex; }
    .cl-gate-card {
        width: 100%; max-width: 440px; background: #fff;
        border-radius: 20px; overflow: hidden;
        box-shadow: 0 30px 70px rgba(2, 6, 23, .45);
        animation: fadeInUp .35s ease-out both;
    }
    .cl-gate-head {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff; padding: 22px 24px;
    }
    .cl-gate-head h2 { margin: 0; font-size: 1.35rem; font-weight: 800; line-height: 1.2; color: #fff; }
    .cl-gate-head p { margin: 6px 0 0; font-size: .9rem; color: #e0e7ff; line-height: 1.5; }
    .cl-gate-body { padding: 24px; }
    .cl-gate-field { margin-bottom: 16px; }
    .cl-gate-field label { display: block; font-size: .85rem; font-weight: 600; color: #334155; margin-bottom: 6px; }
    .cl-gate-field input {
        width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0;
        border-radius: 12px; outline: none; color: #1e293b; font-size: 1rem;
        transition: border-color .15s, box-shadow .15s; background: #fff;
    }
    .cl-gate-field input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(199, 210, 254, .7); }
    #cl-lead-error { color: #dc2626; font-size: .85rem; margin: 0 0 12px; display: none; }
    #cl-lead-error.is-shown { display: block; }
    #cl-lead-submit {
        width: 100%; background: #4f46e5; color: #fff; border: 0;
        font-weight: 700; font-size: 1rem; padding: 13px; border-radius: 12px;
        cursor: pointer; transition: background .15s, opacity .15s;
    }
    #cl-lead-submit:hover { background: #4338ca; }
    #cl-lead-submit:disabled { opacity: .6; cursor: not-allowed; }
    .cl-gate-fineprint { font-size: .72rem; color: #94a3b8; text-align: center; line-height: 1.5; margin: 14px 0 0; }
</style>
<div id="cl-lead-gate" aria-modal="true" role="dialog" aria-labelledby="cl-lead-title">
    <div class="cl-gate-card">
        <div class="cl-gate-head">
            <h2 id="cl-lead-title">Get the full career report</h2>
            <p>Share your details and our team will help you explore <strong>{{ $data['title'] }}</strong>.</p>
        </div>
        <form id="cl-lead-form" class="cl-gate-body" novalidate>
            <div class="cl-gate-field">
                <label for="cl-lead-name">Full name</label>
                <input type="text" id="cl-lead-name" name="name" required autocomplete="name" placeholder="Your name">
            </div>
            <div class="cl-gate-field">
                <label for="cl-lead-email">Email</label>
                <input type="email" id="cl-lead-email" name="email" required autocomplete="email" placeholder="you@domain.com">
            </div>
            <div class="cl-gate-field">
                <label for="cl-lead-phone">Phone</label>
                <input type="tel" id="cl-lead-phone" name="phone" required autocomplete="tel" placeholder="+91 90000 00000">
            </div>
            <p id="cl-lead-error"></p>
            <button type="submit" id="cl-lead-submit">
                <span class="cl-lead-submit-label">View career report</span>
            </button>
            <p class="cl-gate-fineprint">By continuing you agree to be contacted about your career interests.</p>
        </form>
    </div>
</div>
@endsection
@endunless

@section('scripts')
@php
    // Blade's @json splits its argument on commas (they become the json_encode
    // flag/depth params), so this payload is a precomputed variable.
    // Lead-gate context — which career the visitor is opening.
    $jsLeadContext = ['career' => $data['title'], 'country' => $countryName, 'language' => $language];
@endphp
<script type="module">

    @unless ($live)
    // --- LEAD GATE ---
    // The career report renders and stays readable for a short preview window;
    // after it elapses the contact form pops up (blurring the report behind it).
    // The report is NEVER unlocked — on submit we record the lead server-side,
    // then send the visitor back to the main Trending Career page where a
    // thank-you confirmation is shown.
    (function () {
        // How long the visitor gets to read before the gate appears. Set in the
        // CMS (Career Library → Page settings), stored in seconds.
        const GATE_DELAY_MS = {{ (int) ($settings['lead_gate_delay'] ?? 18) * 1000 }};
        // How long (ms) the reading window is remembered on this device, per
        // page. Refreshing then resumes the countdown instead of restarting it,
        // and once it's elapsed the page re-opens locked until this expires.
        const LOCK_MS = {{ (int) ($settings['lead_gate_lock_minutes'] ?? 30) }} * 60000;
        const LOCK_KEY = 'cl_gate_lock:' + location.pathname;
        const gate = document.getElementById('cl-lead-gate');
        const form = document.getElementById('cl-lead-form');
        if (!gate || !form) return;

        const LEAD_URL = @json(route('career-library.lead'));
        const INDEX_URL = @json(route('career-library.index'));
        const CSRF = @json(csrf_token());
        // Same copy the server returns for a malformed or placeholder address.
        const EMAIL_HELP = @json(config('site.forms.email_help'));
        const CONTEXT = @json($jsLeadContext);

        const submitBtn = document.getElementById('cl-lead-submit');
        const errorEl = document.getElementById('cl-lead-error');

        function openGate() {
            gate.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            const first = document.getElementById('cl-lead-name');
            if (first) setTimeout(() => first.focus(), 50);
        }

        function showError(msg) {
            if (!errorEl) return;
            errorEl.textContent = msg;
            errorEl.classList.add('is-shown');
        }

        // Persist when the preview started (per page) so a refresh resumes the
        // countdown rather than granting a fresh one. When the remembered start
        // is older than the reading window the page opens locked immediately;
        // after LOCK_MS the record expires and a fresh preview is allowed.
        function readStart() {
            if (LOCK_MS <= 0) return null; // locking disabled → always fresh
            try {
                const start = parseInt(localStorage.getItem(LOCK_KEY) || '', 10);
                if (!start || (Date.now() - start) > LOCK_MS) return null; // none / expired
                return start;
            } catch (e) { return null; }
        }

        let start = readStart();
        if (start === null) {
            start = Date.now();
            try { if (LOCK_MS > 0) localStorage.setItem(LOCK_KEY, String(start)); } catch (e) {}
        }

        const remaining = Math.max(0, GATE_DELAY_MS - (Date.now() - start));
        if (remaining <= 0) {
            openGate();
        } else {
            // Let the visitor read the report first; the gate appears after the
            // remaining preview window, then blurs the background until they submit.
            setTimeout(openGate, remaining);
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            errorEl && errorEl.classList.remove('is-shown');

            const name = form.name.value.trim();
            const email = form.email.value.trim();
            const phone = form.phone.value.trim();

            if (!name || !email || !phone) {
                showError('Please fill in your name, email and phone.');
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError(EMAIL_HELP);
                return;
            }
            // Placeholder addresses bounce, so they are refused server-side too.
            if (/example/i.test(email)) {
                showError(EMAIL_HELP);
                return;
            }

            submitBtn.disabled = true;
            const label = submitBtn.querySelector('.cl-lead-submit-label');
            const prev = label ? label.textContent : '';
            if (label) label.textContent = 'Submitting…';

            try {
                const res = await fetch(LEAD_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ name, email, phone, ...CONTEXT }),
                });

                if (!res.ok) {
                    // Surface the server's validation message (e.g. a rejected
                    // placeholder email) rather than a generic failure.
                    let data = {};
                    try { data = await res.json(); } catch (e) {}
                    const invalid = data.errors ? Object.values(data.errors).flat()[0] : '';
                    submitBtn.disabled = false;
                    if (label) label.textContent = prev;
                    showError(invalid || data.message || 'Something went wrong. Please try again.');
                    return;
                }

                // Never unlock the report. Flag the thank-you and send the
                // visitor back to the main Trending Career page, where the
                // confirmation popup is shown.
                try { sessionStorage.setItem('cl_show_thanks', '1'); } catch (e) {}
                window.location.href = INDEX_URL;
            } catch (err) {
                submitBtn.disabled = false;
                if (label) label.textContent = prev;
                showError('Something went wrong. Please try again.');
            }
        });
    })();
    @endunless

    window.toggleAccordion = (btn) => {
        const content = btn.nextElementSibling;
        const icon = btn.querySelector('.chevron-icon');
        const isOpen = content.classList.contains('open');

        if (isOpen) {
        content.classList.remove('open');
        icon.classList.remove('rotate-180');
        } else {
        content.classList.add('open');
        icon.classList.add('rotate-180');
        }
    };

    if(window.self !== window.top){
        document.querySelectorAll('nav, .stripe-site-header').forEach(function(nav) {
            nav.style.display = 'none';
        });
    }
</script>
@endsection
