<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $tab = $request->query('tab', 'penjual');

        // Satu baris per percakapan (pasangan user), membawa id pesan pertama & terakhir.
        // CASE dipakai (bukan LEAST/GREATEST) agar portabel: jalan di MySQL maupun SQLite.
        $low  = 'CASE WHEN sender_id < receiver_id THEN sender_id ELSE receiver_id END';
        $high = 'CASE WHEN sender_id < receiver_id THEN receiver_id ELSE sender_id END';

        $subquery = Message::select(DB::raw("($low) as user_1, ($high) as user_2, MAX(id) as last_msg_id, MIN(id) as first_msg_id"))
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->groupBy(DB::raw("($low), ($high)"));

        $threads = DB::table(DB::raw("({$subquery->toSql()}) as threads"))
            ->mergeBindings($subquery->getQuery())
            ->join('messages', 'threads.last_msg_id', '=', 'messages.id')
            ->orderBy('messages.created_at', 'desc')
            ->get();

        // --- Batch-load semua relasi sekali jalan (menghindari N+1 di dalam loop) ---
        $partnerIds = $threads
            ->map(fn ($t) => $t->user_1 == $userId ? $t->user_2 : $t->user_1)
            ->unique()->values();

        $partners = User::whereIn('id', $partnerIds)->get()->keyBy('id');

        // Pengirim pesan PERTAMA tiap thread -> menentukan inisiator.
        $firstSenders = Message::whereIn('id', $threads->pluck('first_msg_id'))
            ->pluck('sender_id', 'id');

        // Jumlah pesan belum dibaca per partner (1 query, dikelompokkan).
        $unreadByPartner = Message::where('receiver_id', $userId)
            ->where('is_read', false)
            ->whereIn('sender_id', $partnerIds)
            ->select('sender_id', DB::raw('COUNT(*) as c'))
            ->groupBy('sender_id')
            ->pluck('c', 'sender_id');

        $chats = [];

        foreach ($threads as $thread) {
            $partnerId = ($thread->user_1 == $userId) ? $thread->user_2 : $thread->user_1;
            $partner = $partners->get($partnerId);

            if (!$partner) continue;

            // Inisiator = pengirim pesan pertama di thread.
            // Saya inisiator      -> saya PEMBELI, lawan PENJUAL  -> tab "penjual".
            // Saya bukan inisiator -> saya PENJUAL, lawan PEMBELI -> tab "pembeli".
            $isInitiator = ($firstSenders->get($thread->first_msg_id) == $userId);

            if ($tab == 'penjual') {
                if (!$isInitiator) continue;
            } elseif ($tab == 'pembeli') {
                if ($isInitiator) continue;
            }

            $chats[] = (object) [
                'user' => $partner,
                'last_message' => $thread->message ?? 'Sent a photo',
                'time' => \Carbon\Carbon::parse($thread->created_at),
                'unread' => $unreadByPartner->get($partnerId, 0),
            ];
        }

        return view('chat.index', compact('chats', 'tab'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $partner = User::findOrFail($id);

        Message::where('sender_id', $id)
            ->where('receiver_id', $user->id)
            ->update(['is_read' => true]);

        $messages = Message::where(function($q) use ($user, $id) {
                $q->where('sender_id', $user->id)->where('receiver_id', $id);
            })
            ->orWhere(function($q) use ($user, $id) {
                $q->where('sender_id', $id)->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy(function($msg) {
                return $msg->created_at->format('Y-m-d');
            });

        return view('chat.show', compact('partner', 'messages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'image' => 'nullable|image|max:3072'
        ]);

        if (!$request->message && !$request->file('image')) return back();

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('chat-images', 'public');
        }

        Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'image_path' => $path
        ]);

        return back();
    }
}
