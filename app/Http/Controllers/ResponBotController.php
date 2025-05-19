<?php

namespace App\Http\Controllers;

use App\Models\ResponBot;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;

class ResponBotController extends Controller
{
    /**
     * Menampilkan daftar resource.
     * GET /chatbot
     */
    public function index(Request $request)
    {
        // Ambil data dengan pagination, urutkan berdasarkan ID (atau created_at jika ada)
        // Penting: Pilih juga primary key 'id_respon_bot' dan alias sebagai 'id' untuk frontend
        $items = ResponBot::select('id_respon_bot as id', 'pesan', 'jawaban') // Pilih kolom yg perlu + alias PK
            ->orderBy('id_respon_bot', 'asc') // Urutkan berdasarkan PK
            ->paginate($request->input('per_page', 15));

        return response()->json($items);
    }

    /**
     * Menyimpan resource baru.
     * POST /chatbot
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pesan' => 'required|string|max:65535',
            'jawaban' => 'required|string|max:65535',
            // 'pesan' => 'required|string|max:65535|unique:t_respon_bot,pesan', // Uncomment jika perlu unique
        ]);

        try {
            $item = ResponBot::create($validated);
            // Kembalikan data baru termasuk ID yg di-alias
            $newItem = ResponBot::select('id_respon_bot as id', 'pesan', 'jawaban')
                ->find($item->id_respon_bot); // Ambil ulang dg alias
            return response()->json(['message' => 'Item chatbot berhasil ditambahkan.', 'data' => $newItem], 201);
        } catch (\Exception $e) {
            Log::error("Error store respon bot: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menambahkan item chatbot.'], 500);
        }
    }

    /**
     * Menampilkan resource spesifik. (Opsional)
     * GET /chatbot/{responBot} -> Gunakan nama model sbg parameter type-hinted
     */
    public function show(ResponBot $responBot) // Gunakan Route Model Binding
    {
        // $responBot sudah otomatis di-fetch berdasarkan id_respon_bot di URL
        // Perlu alias ID jika frontend mengharapkan 'id'
        $responBot->id = $responBot->id_respon_bot;
        return response()->json(['data' => $responBot]);
    }

    /**
     * Memperbarui resource spesifik.
     * PUT /chatbot/{responBot} -> Gunakan nama model sbg parameter type-hinted
     */
    public function update(Request $request, ResponBot $responBot) // Gunakan Route Model Binding
    {
        // $responBot sudah otomatis di-fetch
        // Otorisasi
        // if (!Gate::allows('update-chatbot-item', $responBot)) { abort(403); }

        $validated = $request->validate([
            'pesan' => 'required|string|max:65535',
            'jawaban' => 'required|string|max:65535',
            // Validasi unique jika perlu, abaikan ID saat ini
            // 'pesan' => ['required', 'string', 'max:65535', Rule::unique('t_respon_bot')->ignore($responBot->id_respon_bot, 'id_respon_bot')],
        ]);

        try {
            $responBot->update($validated);
            // Kembalikan data yg diupdate termasuk ID yg di-alias
            $updatedItem = ResponBot::select('id_respon_bot as id', 'pesan', 'jawaban')
                ->find($responBot->id_respon_bot);
            return response()->json(['message' => 'Item chatbot berhasil diperbarui.', 'data' => $updatedItem]);
        } catch (\Exception $e) {
            Log::error("Error update respon bot (ID: {$responBot->id_respon_bot}): " . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui item chatbot.'], 500);
        }
    }

    /**
     * Menghapus resource spesifik.
     * DELETE /chatbot/{responBot} -> Gunakan nama model sbg parameter type-hinted
     */
    public function destroy(ResponBot $responBot) // Gunakan Route Model Binding
    {
        // $responBot sudah otomatis di-fetch
        // Otorisasi
        // if (!Gate::allows('delete-chatbot-item', $responBot)) { abort(403); }

        try {
            $responBot->delete();
            return response()->json(['message' => 'Item chatbot berhasil dihapus.'], 200);
        } catch (\Exception $e) {
            Log::error("Error delete respon bot (ID: {$responBot->id_respon_bot}): " . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus item chatbot.'], 500);
        }
    }
}
