<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    protected string $ocrApiKey = 'K88393584388957';

    public function analyze(Request $request, GeminiService $gemini): \Illuminate\Http\JsonResponse
    {
        $user = User::find(Auth::id());

        if ($user->scans < 1) {
            return response()->json([
                'success' => false,
                'scans' => false,
                'message' => 'У вас закончились сканы'
            ]);
        }

        $file = $request->file('image');

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'Файл не предоставлен'
            ], 400);
        }

        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $localPath = $file->storeAs('mobile', $filename);

        $imageUrl = url("img/{$filename}");
        Log::info($imageUrl);

        if (!Storage::exists("mobile/{$filename}")) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сохранении изображения'
            ], 500);
        }

        $fileContents = Storage::get("mobile/{$filename}");

        $ocrResponse = Http::attach('file', $fileContents, $filename)
            ->post('https://api.ocr.space/parse/image', [
                'apikey' => $this->ocrApiKey,
                'language' => 'eng',
                'OCREngine' => 2,
            ]);

        if (!$ocrResponse || !$ocrResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка распознавания текста'
            ], 500);
        }

        $ocrData = $ocrResponse->json();
        $text = $ocrData['ParsedResults'][0]['ParsedText'] ?? '';

        try {
            $analysis = $gemini->analyze($text);

            $user->scans -= 1;
            $user->save();
            $user->products()->create([
                'name' => $analysis['name'],
                'health_score' => $analysis['healthScore'],
                'concerns' => $analysis['concerns'],
                'image' => $imageUrl,
            ]);
            return response()->json([
                'success' => true,
                'result' => array_merge($analysis, [
                    'productImage' => $imageUrl
                ])
            ]);
        } catch (\Throwable $e) {
            Log::error('Gemini analysis error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при анализе текста'
            ]);
        }
    }

    public function getScans(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = User::find(Auth::id());
        $scansToAdd = $request->input('scans', 0);

        if ($scansToAdd > 0) {
            $user->scans += $scansToAdd;
            $user->save();

            return response()->json([
                'success' => true,
                'current_scans' => $user->scans
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Неверное количество сканов'
        ]);
    }
}
