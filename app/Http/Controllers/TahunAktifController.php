<?php

namespace App\Http\Controllers;

use App\Models\TahunAktif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate; // Jika pakai Gate
use Illuminate\Support\Facades\Log;

class TahunAktifController extends Controller
{
    /**
     * Menampilkan daftar tahun aktif.
     * GET /tahun-aktif
     */
    public function index()
    {
        // Otorisasi? Mungkin semua boleh lihat
        $tahunList = TahunAktif::orderBy('tahun', 'desc')->get();
        return response()->json($tahunList);
    }

    /**
     * Menyimpan tahun baru.
     * POST /tahun-aktif
     */
    public function store(Request $request)
    {
        // Otorisasi (Hanya Admin/Bendahara)
        // if (!Gate::allows('manage-tahun-aktif')) { abort(403); }

        $validated = $request->validate([
            'tahun' => 'required|integer|digits:4|unique:t_tahun_aktif,tahun',
            // Anda bisa menambahkan validasi bulan awal/akhir jika perlu
        ]);

        try {
            // Default status biasanya 'Tidak Aktif' saat baru dibuat
            $tahun = TahunAktif::create([
                'tahun' => $validated['tahun'],
                'bulan_awal' => 1, // Default
                'bulan_akhir' => 12, // Default
                'status' => 'Tidak Aktif',
            ]);
            return response()->json(['message' => 'Tahun berhasil ditambahkan.', 'data' => $tahun], 201);
        } catch (\Exception $e) {
            Log::error("Error store tahun aktif: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menambahkan tahun.'], 500);
        }
    }


    /**
     * Memperbarui status tahun aktif.
     * PUT /tahun-aktif/{id}
     */
    public function update(Request $request, string $id)
    {
        // Otorisasi (Hanya Admin/Bendahara)
        // if (!Gate::allows('manage-tahun-aktif')) { abort(403); }

        $validated = $request->validate([
            'status' => 'required|in:Aktif,Tidak Aktif',
        ]);

        try {
            $tahunToUpdate = TahunAktif::findOrFail($id);
            $newStatus = $validated['status'];

            DB::beginTransaction();

            // Jika mengaktifkan tahun ini, nonaktifkan tahun lain yang sedang aktif
            if ($newStatus === 'Aktif') {
                TahunAktif::where('status', 'Aktif')
                    ->where('id', '!=', $id) // Kecuali diri sendiri
                    ->update(['status' => 'Tidak Aktif']);
            }

            $tahunToUpdate->update(['status' => $newStatus]);

            DB::commit();
            return response()->json(['message' => 'Status tahun berhasil diperbarui.', 'data' => $tahunToUpdate->refresh()]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Data tahun tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error update status tahun (ID: {$id}): " . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui status tahun.'], 500);
        }
    }

    /**
     * Menghapus tahun. (Hati-hati jika ada relasi!)
     * DELETE /tahun-aktif/{id}
     */
    public function destroy(string $id)
    {
        // Otorisasi (Hanya Admin/Bendahara)
        // if (!Gate::allows('manage-tahun-aktif')) { abort(403); }

        // Pertimbangkan validasi: jangan hapus jika ada data iuran terkait?
        // if (IuranLog::where('tahun', TahunAktif::find($id)?->tahun)->exists()) {
        //     return response()->json(['message' => 'Tidak dapat menghapus tahun karena masih ada data iuran terkait.'], 409); // Conflict
        // }

        try {
            $tahun = TahunAktif::findOrFail($id);
            // Jangan hapus tahun yg sedang aktif?
            if ($tahun->status === 'Aktif') {
                return response()->json(['message' => 'Tidak dapat menghapus tahun yang sedang aktif.'], 400);
            }
            $tahun->delete();
            return response()->json(['message' => 'Tahun berhasil dihapus.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Data tahun tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error("Error delete tahun (ID: {$id}): " . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus tahun.'], 500);
        }
    }
}
