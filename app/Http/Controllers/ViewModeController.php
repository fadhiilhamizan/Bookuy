<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Toggles the layout between the mobile mockup and the full-width desktop view.
 * The preference is stored in the session so it persists across requests.
 */
class ViewModeController extends Controller
{
    public function toggle(Request $request)
    {
        $current = session('view_mode', 'mobile');
        $next = $current === 'desktop' ? 'mobile' : 'desktop';

        session(['view_mode' => $next]);

        return redirect()->back(fallback: '/');
    }
}
