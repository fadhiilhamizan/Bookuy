<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\Cart;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares data that every layout needs on a single, per-request basis.
 *
 * - $viewMode  : 'mobile' (iPhone mockup) or 'desktop' (full-width web).
 * - $cartCount : header cart badge. This replaces the old
 *                View::composer('*', CartComposer) which re-queried the cart
 *                on every individual view/partial render.
 */
class ShareLayoutData
{
    public function handle(Request $request, Closure $next): Response
    {
        // --- Dual-view preference ---
        $viewMode = session('view_mode', 'mobile');
        if (! in_array($viewMode, ['mobile', 'desktop'], true)) {
            $viewMode = 'mobile';
        }

        // --- Cart badge count ---
        $cartCount = 0;
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
            $cartCount = $cart ? $cart->items()->count() : 0;
        }

        View::share([
            'viewMode'  => $viewMode,
            'cartCount' => $cartCount,
        ]);

        return $next($request);
    }
}
