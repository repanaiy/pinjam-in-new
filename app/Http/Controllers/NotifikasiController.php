<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifikasi', [
            'notifications' => $request->user()->notifications()->paginate(15),
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /** Tandai satu notifikasi dibaca (hanya milik sendiri). */
    public function read(Request $request, string $id): RedirectResponse
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('toast', ['type' => 'success', 'title' => 'Selesai', 'message' => 'Semua notifikasi ditandai dibaca.']);
    }
}
