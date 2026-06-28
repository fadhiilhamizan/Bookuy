<!DOCTYPE html>
<html lang="id">
<!--
// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B
-->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookuy</title>

    <!-- Google Fonts (Poppins) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Compiled CSS + JS (Tailwind, app shell, dual-view) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Slot untuk CSS tambahan per halaman -->
    @stack('styles')
</head>

<body data-view-mode="{{ $viewMode ?? 'mobile' }}" class="@yield('body-class')">

    <!-- App shell: renders as an iPhone mockup (mobile) or a full-width canvas (desktop) -->
    <div class="app-shell">
        <div class="app-screen">
            @if(($viewMode ?? 'mobile') === 'mobile')
                <div class="iphone-notch"></div>
            @endif

            <!-- Scrollable content area -->
            <div class="app-content">
                @yield('content')
            </div>

            <!-- Navigation (filled by pages that extend layouts.app-main) -->
            @stack('navbar')
        </div>
    </div>

    <!-- Dual-view toggle -->
    @php($isDesktop = ($viewMode ?? 'mobile') === 'desktop')
    <a href="{{ route('viewmode.toggle') }}" class="view-toggle"
       title="{{ $isDesktop ? 'Switch to mobile mockup' : 'Switch to desktop view' }}"
       aria-label="{{ $isDesktop ? 'Switch to mobile mockup' : 'Switch to desktop view' }}">
        @if($isDesktop)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
            <span>Mobile view</span>
        @else
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            <span>Desktop view</span>
        @endif
    </a>

    <!-- Slot untuk JS tambahan per halaman -->
    @stack('scripts')
</body>
</html>
