<?php

namespace App\Http\Controllers;

use App\Models\ResponBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResponBotController extends Controller
{
    public function index(Request $request)
    {
        $items = ResponBot::select('id_respon_bot as id', 'keyword', 'pesan', 'jawaban', 'function', 'tipe_respon')
            ->orderBy('keyword', 'asc')
            ->paginate($request->input('per_page', 15));

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pesan' => 'required|string|max:65535',
            'jawaban' => 'required|string|max:65535',
        ]);

        try {
            // Get the maximum keyword value and increment by 1
            $maxKeyword = ResponBot::max('keyword') ?? 0;
            $newKeyword = $maxKeyword + 1;

            ResponBot::create([
                'pesan' => $validated['pesan'],
                'jawaban' => $validated['jawaban'],
                'tipe_respon' => 'statis',
                'keyword' => $newKeyword,
            ]);

            return response()->json([
                'message' => 'Respon chatbot berhasil ditambahkan.',
            ], 201);
        } catch (\Exception $e) {
            Log::error("Error store respon bot: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menambahkan item chatbot.'], 500);
        }
    }

    public function show(ResponBot $responBot)
    {
        return response()->json([
            'data' => [
                'id' => $responBot->id_respon_bot,
                'keyword' => $responBot->keyword,
                'pesan' => $responBot->pesan,
                'jawaban' => $responBot->jawaban,
                'function' => $responBot->function,
                'tipe_respon' => $responBot->tipe_respon
            ]
        ]);
    }

    public function update(Request $request, ResponBot $responBot)
    {
        $validated = $request->validate([
            'pesan' => 'required|string|max:65535',
            'jawaban' => 'required|string|max:65535',
        ]);

        try {
            $responBot->update($validated);

            return response()->json([
                'message' => 'Respon chatbot berhasil diperbarui.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error update respon bot (ID: {$responBot->id_respon_bot}): " . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui item chatbot.'], 500);
        }
    }

    public function destroy(ResponBot $responBot)
    {
        try {
            $deletedKeyword = $responBot->keyword;
            $responBot->delete();

            // Update keywords after deletion
            ResponBot::where('keyword', '>', $deletedKeyword)
                ->decrement('keyword');

            return response()->json(['message' => 'Item chatbot berhasil dihapus.'], 200);
        } catch (\Exception $e) {
            Log::error("Error delete respon bot (ID: {$responBot->id_respon_bot}): " . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus item chatbot.'], 500);
        }
    }
}