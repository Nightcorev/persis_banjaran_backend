<?php

namespace App\Http\Controllers;

use App\Models\AnggotaModel;
use App\Models\MusyawarahDetailModel;
use Illuminate\Http\Request;
use App\Models\BroadcastModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BroadcastController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10); // default 10
        $search = $request->input('search');

        $query = BroadcastModel::query();

        // Kalau ada search term, filter berdasarkan headline dan deskripsi
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('headline', 'ILIKE', '%' . $search . '%')
                    ->orWhere('deskripsi', 'ILIKE', '%' . $search . '%');
            });
        }

        $data = $query->orderBy('id_broadcast', 'desc')
            ->paginate($perPage);

        return response()->json([
            'message' => 'Data broadcast berhasil diambil',
            'data' => $data
        ]);
    }


    /**
     * Menyimpan data broadcast baru.
     */
    public function store(Request $request)
    {
        Log::debug('Data request masuk ke store():', $request->all());
        $konversiWaktu = $this->convertTimestamp($request->waktu_pengiriman);

        $broadcast = BroadcastModel::create([
            'headline' => $request->headline ?? null,
            'deskripsi' => $request->deskripsi ?? null,
            'tipe_broadcast' => $request->tipe_broadcast ?? null,
            'nama_file' => $request->nama_file ?? null,
            'status_pengiriman' => $request->status_pengiriman ?? null,
            'waktu_pengiriman' => $this->convertTimestamp($request->waktu_pengiriman),
            'tujuan' => $request->tujuan ?? null,
        ]);

        Log::debug('waktu dari frontend:' . $konversiWaktu);
        Log::debug('waktu dikonversi:', ['waktu_pengiriman' => $broadcast->waktu_pengiriman]);
        Log::debug('Current datetime: ' . now()->format('Y-m-d H:i:s'));

        // $this->sendInformation($broadcast);

        return response()->json([
            'message' => 'Broadcast berhasil dibuat',
            'data' => $broadcast
        ], 201);
    }

    public function sendInformation($broadcast)
    {

        $noTelpList = null;
        if ($broadcast->tujuan === 'test') {
            Log::debug('masuk ke tujuan test');
            $noTelpList = [
                '081281154008',
                '08996981377',
                // '085155072811'
            ];
        } else if ($broadcast->tujuan === 'PJ') {
            Log::debug('masuk ke tujuan PJ');

            // Ambil ID anggota yang menjadi PJ aktif
            $pjIds = MusyawarahDetailModel::where('aktif', true)
                ->where('jabatan', 'Ketua')
                ->pluck('id_anggota');

            // Ambil no telp anggota yang menjadi PJ
            $noTelpList = AnggotaModel::whereIn('id_anggota', $pjIds)
                ->pluck('no_telp');

            Log::debug('Nomor PJ: ' . json_encode($noTelpList));

        } else if ($broadcast->tujuan === 'anggota per PJ') {
            Log::debug('masuk ke tujuan anggota per PJ');

            // Ambil ID anggota yang jadi PJ aktif
            $pjIds = MusyawarahDetailModel::where('aktif', true)
                ->pluck('id_anggota');

            // Ambil no telp anggota yang bukan PJ
            $noTelpList = AnggotaModel::whereNotIn('id_anggota', $pjIds)
                ->pluck('no_telp');

            Log::debug('Nomor anggota non PJ: ' . json_encode($noTelpList));

        } else if ($broadcast->tujuan === 'semua') {
            Log::debug('masuk ke tujuan semua');

            // Ambil semua no telp anggota
            $noTelpList = AnggotaModel::pluck('no_telp');

            Log::debug('Nomor semua anggota: ' . json_encode($noTelpList));
        }
        try {

            $response = Http::post('http://localhost:3000/send_to_chatbot', [
                'no_wa' => $noTelpList,
                'pesan' => $broadcast->deskripsi,
                'status_pengiriman' => $broadcast->status_pengiriman,
                'waktu_pengiriman' => $broadcast->waktu_pengiriman,
                'nama_file' => $broadcast->nama_file
            ]);

            if ($response->successful()) {
                \Log::info('Pesan berhasil dikirim ke chatbot.');
            } else {
                \Log::error('Gagal mengirim pesan ke chatbot: ' . $response->body());
            }
        } catch (\Exception $e) {
            \Log::error('Exception saat mengirim ke chatbot: ' . $e->getMessage());
        }
    }

    public function uploadAttachment(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $request->namaFile;

            // Ganti spasi dengan underscore
            $filename = str_replace(' ', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $extension = $file->getClientOriginalExtension();
            $fullFilename = $filename . '.' . $extension;

            $directory = storage_path('app/public/uploads/broadcast');

            // Tambahkan angka jika file sudah ada
            $counter = 1;
            while (file_exists($directory . '/' . $fullFilename)) {
                $fullFilename = $filename . "($counter)." . $extension;
                $counter++;
            }

            $path = $file->storeAs('uploads/broadcast', $fullFilename, 'public');

            return response()->json([
                'success' => true,
                'filename' => $fullFilename,
                'path' => "/storage/$path",
                'url' => asset("storage/$path")
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Tidak ada file yang diupload'], 400);
    }


    public function convertTimestamp($timestamp)
    {
        try {
            // Convert format ISO8601 tanpa detik menjadi format lengkap
            $dateTime = Carbon::createFromFormat('Y-m-d\TH:i', $timestamp);
            return $dateTime->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Return null or handle error
            return null;
        }
    }


    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
