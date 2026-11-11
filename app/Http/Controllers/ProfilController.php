<?php

namespace App\Http\Controllers;

use App\Http\Requests\Siswa\UpdatePasswordRequest;
use App\Http\Requests\Siswa\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profil', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'phone']);

        if ($request->hasFile('avatar')) {
            // Avatar memakai disk public (satu-satunya selain foto katalog).
            $path = $request->file('avatar')->store('avatars', 'public');
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $data['avatar_path'] = $path;
        }

        $user->update($data);

        return back()->with('toast', ['type' => 'success', 'title' => 'Profil disimpan', 'message' => 'Data profil berhasil diperbarui.']);
    }

    public function password(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->with('toast', ['type' => 'success', 'title' => 'Password diganti', 'message' => 'Gunakan password baru saat masuk berikutnya.']);
    }
}
