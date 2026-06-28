@extends('layouts.app')
<!--
// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B
-->
<!--
  Layout for the 5 primary tab pages (Home, Chat, Sell, Notifications, Profile).
  Adds the navigation chrome on top of layouts.app. Navigation renders as a
  bottom tab bar on mobile and a sticky top bar on desktop (same links).
  All nav styling now lives in resources/css/app.css.
-->

{{-- Reserve space for the nav + let the shell know this page has navigation. --}}
@section('body-class', 'has-nav')

@section('content')
    @yield('main-content')
@endsection

@push('navbar')
    @auth
    @php($currentRoute = request()->route()?->getName())
    @if(($viewMode ?? 'mobile') === 'desktop')
        {{-- ===== Desktop: sticky top bar ===== --}}
        <nav class="desktop-nav">
            <a href="{{ route('home') }}" class="desktop-nav__brand">
                <img src="{{ asset('images/logo-color-full.png') }}" alt="Bookuy" style="height:26px;width:auto;">
            </a>
            <div class="desktop-nav__links">
                <a href="{{ route('home') }}" class="desktop-nav__link {{ $currentRoute == 'home' ? 'active' : '' }}">
                    <img src="{{ asset('images/nav-home.png') }}" alt=""><span>Home</span>
                </a>
                <a href="{{ route('chat.index') }}" class="desktop-nav__link {{ $currentRoute == 'chat.index' ? 'active' : '' }}">
                    <img src="{{ asset('images/nav-chat.png') }}" alt=""><span>Chat</span>
                </a>
                <a href="{{ route('product.create') }}" class="desktop-nav__link {{ $currentRoute == 'product.create' ? 'active' : '' }}">
                    <img src="{{ asset('images/nav-create.png') }}" alt=""><span>Sell</span>
                </a>
                <a href="{{ route('notification.index') }}" class="desktop-nav__link {{ $currentRoute == 'notification.index' ? 'active' : '' }}">
                    <img src="{{ asset('images/nav-notification.png') }}" alt=""><span>Notifications</span>
                </a>
                <a href="{{ route('profile.index') }}" class="desktop-nav__link {{ $currentRoute == 'profile.index' ? 'active' : '' }}">
                    <img src="{{ asset('images/nav-profile.png') }}" alt=""><span>Profile</span>
                </a>
            </div>
        </nav>
    @else
        {{-- ===== Mobile: floating bottom tab bar ===== --}}
        <nav class="nav-bar">
            <img src="{{ asset('images/nav-bar-bg.png') }}" alt="Nav Background" class="nav-bar-bg-img">
            <div class="nav-items">
                <a href="{{ route('home') }}" class="nav-item {{ $currentRoute == 'home' ? 'active' : '' }}">
                    <img src="{{ asset('images/nav-home.png') }}" alt="Home">
                </a>
                <a href="{{ route('chat.index') }}" class="nav-item {{ $currentRoute == 'chat.index' ? 'active' : '' }}">
                    <img src="{{ asset('images/nav-chat.png') }}" alt="Chat">
                </a>
                <a href="{{ route('product.create') }}" class="nav-item {{ $currentRoute == 'product.create' ? 'active' : '' }}">
                    <img src="{{ asset('images/nav-create.png') }}" alt="Create">
                </a>
                <a href="{{ route('notification.index') }}" class="nav-item {{ $currentRoute == 'notification.index' ? 'active' : '' }}">
                    <img src="{{ asset('images/nav-notification.png') }}" alt="Notification">
                </a>
                <a href="{{ route('profile.index') }}" class="nav-item {{ $currentRoute == 'profile.index' ? 'active' : '' }}">
                    <img src="{{ asset('images/nav-profile.png') }}" alt="Profile">
                </a>
            </div>
        </nav>
    @endif
    @endauth
@endpush
