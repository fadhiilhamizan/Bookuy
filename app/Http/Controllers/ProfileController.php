<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;

use App\Models\Notification;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'name' => 'required|string|max:255',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072',
            'gender' => 'nullable',
            'semester' => 'nullable',
            'description' => 'nullable',
        ]);

        $user->name = $request->name;
        $user->gender = $request->gender;
        $user->semester = $request->semester;
        $user->description = $request->description;

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::delete($user->profile_photo_path);
            }
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        $user->save();

        app(\App\Services\NotificationService::class)->send(
            $user->id,
            'Profile Updated!',
            'Informasi profil Anda berhasil disimpan.',
            'account',
            'icon-edit-pencil.png'
        );

        return redirect()->route('profile.index')->with('success', 'Profil berhasil diperbarui!');
    }

    // Method Sales History (Penjualan - Referensi)
    public function salesHistory(Request $request)
    {
        $user = Auth::user();
        $tab = $request->query('tab', 'ongoing');

        // Per-item view: the books this user has sold (seller_id on the line item).
        $query = OrderItem::with(['order', 'book'])
                      ->where('seller_id', $user->id);

        if ($tab == 'completed') {
            $query->whereHas('order', fn ($q) => $q->where('status', 'Delivered'));
        } else {
            $query->whereHas('order', fn ($q) => $q->whereIn('status', ['Packing', 'Picked', 'In Transit']));
        }

        $items = $query->latest()->get();

        return view('profile.sales_history', compact('items', 'tab'));
    }

    // == BARU: Method Purchase History (Pembelian) ==
    public function purchaseHistory(Request $request)
    {
        $user = Auth::user();
        $tab = $request->query('tab', 'ongoing'); // ongoing | completed

        // Per-item view: the books this user has bought (via their parent orders).
        $query = OrderItem::with(['order', 'book'])
                      ->whereHas('order', fn ($q) => $q->where('buyer_id', $user->id));

        if ($tab == 'completed') {
            $query->whereHas('order', fn ($q) => $q->where('status', 'Delivered'));
        } else {
            $query->whereHas('order', fn ($q) => $q->whereIn('status', ['Packing', 'Picked', 'In Transit']));
        }

        $items = $query->latest()->get();

        return view('profile.purchase_history', compact('items', 'tab'));
    }
}
