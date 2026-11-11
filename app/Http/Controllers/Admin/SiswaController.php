<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportSiswaRequest;
use App\Http\Requests\Admin\SiswaRequest;
use App\Models\Loan;
use App\Models\User;
use App\Services\AuditService;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiswaController extends Controller
{
    public function __construct(private AuditService $audit) {}

    private function find(string|int $id): User
    {
        return User::query()->siswa()->findOrFail($id);
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $kelas = (string) $request->query('kelas', '');
        $status = in_array($request->query('status'), ['aktif', 'nonaktif'], true) ? $request->query('status') : null;

        $students = User::query()->siswa()
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('nis', 'like', "%{$q}%")->orWhere('username', 'like', "%{$q}%")))
            ->when($kelas !== '', fn ($b) => $b->where('kelas', $kelas))
            ->when($status, fn ($b) => $b->where('is_active', $status === 'aktif'))
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('admin.siswa.index', [
            'students' => $students,
            'kelasList' => User::query()->siswa()->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas'),
            'q' => $q, 'kelas' => $kelas, 'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.siswa.form', ['student' => new User(['is_active' => true])]);
    }

    public function store(SiswaRequest $request): RedirectResponse
    {
        $student = User::create(array_merge($request->safe()->except(['password']), [
            'password' => $request->validated('password'),
            'role' => 'siswa',
        ]));

        $this->audit->log(null, $request->user(), 'student_created', "Siswa {$student->name} ({$student->nis}) ditambahkan", ['user_id' => $student->id]);

        return redirect()->route('admin.siswa.index')->with('toast', ['type' => 'success', 'title' => 'Siswa ditambahkan', 'message' => "{$student->name} dapat masuk dengan username {$student->username}."]);
    }

    public function edit(string $id): View
    {
        return view('admin.siswa.form', ['student' => $this->find($id)]);
    }

    public function update(SiswaRequest $request, string $id): RedirectResponse
    {
        $student = $this->find($id);
        $data = $request->safe()->except(['password']);
        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $student->update($data);
        $this->audit->log(null, $request->user(), 'student_updated', "Siswa {$student->name} diperbarui", ['user_id' => $student->id]);

        return redirect()->route('admin.siswa.index')->with('toast', ['type' => 'success', 'title' => 'Siswa diperbarui', 'message' => 'Perubahan tersimpan.']);
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $student = $this->find($id);

        if (Loan::query()->where('user_id', $student->id)->reserving()->exists()) {
            return back()->with('toast', ['type' => 'error', 'title' => 'Tidak bisa dihapus', 'message' => 'Siswa masih memiliki pengajuan atau peminjaman aktif.']);
        }

        $student->delete(); // soft delete: riwayat transaksi tetap utuh
        $this->audit->log(null, $request->user(), 'student_deleted', "Siswa {$student->name} dihapus (soft delete)", ['user_id' => $student->id]);

        return redirect()->route('admin.siswa.index')->with('toast', ['type' => 'success', 'title' => 'Siswa dihapus', 'message' => 'Akun dihapus; riwayat transaksi tetap tersimpan.']);
    }

    public function toggle(Request $request, string $id): RedirectResponse
    {
        $student = $this->find($id);
        $student->update(['is_active' => ! $student->is_active]);

        $this->audit->log(null, $request->user(), $student->is_active ? 'student_activated' : 'student_deactivated', "Akun {$student->name} ".($student->is_active ? 'diaktifkan' : 'dinonaktifkan'), ['user_id' => $student->id]);

        return back()->with('toast', ['type' => 'success', 'title' => $student->is_active ? 'Akun diaktifkan' : 'Akun dinonaktifkan', 'message' => $student->name]);
    }

    /** Password sementara ditampilkan sekali saja (flash). */
    public function resetPassword(Request $request, string $id): RedirectResponse
    {
        $student = $this->find($id);
        $temp = Str::random(10);
        $student->update(['password' => $temp]);

        $this->audit->log(null, $request->user(), 'student_password_reset', "Password {$student->name} direset", ['user_id' => $student->id]);

        return back()->with('temp_password', ['name' => $student->name, 'username' => $student->username, 'password' => $temp]);
    }

    // ------------------------------------------------------------------ import

    public function importForm(): View
    {
        return view('admin.siswa.import', ['result' => null]);
    }

    public function import(ImportSiswaRequest $request, StudentImportService $importer): View|RedirectResponse
    {
        try {
            $result = $importer->import($request->file('file'), $request->user());
        } catch (BusinessRuleException $e) {
            return back()->with('toast', ['type' => 'error', 'title' => 'Import gagal', 'message' => $e->getMessage()]);
        }

        // Dirender langsung (bukan redirect) agar kata sandi acak hanya ada di respons ini.
        return view('admin.siswa.import', ['result' => $result]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            echo "nama,nis,kelas,no_hp\nRaka Pratama,20240182,XII IPA 2,081234567101\nNadia Putri,20240175,XII IPA 1,081234567102\n";
        }, 'template-import-siswa.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
