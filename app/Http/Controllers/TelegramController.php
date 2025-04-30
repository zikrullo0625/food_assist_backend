<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class IngredientOCRController extends Controller
{
    protected string $botToken = '7789033027:AAH3jJPoTGXqb_0xQH7GYSnElt_avdqFCQU';
    protected string $ocrApiKey = 'K88393584388957';

    public function webhook(Request $request, GeminiService $gemini)
    {
        $data = $request->all();
        $chatId = $data['message']['chat']['id'] ?? null;

        try {
            if (!$chatId) {
                return response()->json(['error' => 'Invalid Telegram payload'], 400);
            }

            if (isset($data['message']['text']) && $data['message']['text'] === '/start') {
                $this->sendMessage("Добро пожаловать! Отправьте фото состава продукта, и я скажу, насколько он полезен.", $chatId);
                return response()->json(['status' => 'ok']);
            }

            if (!isset($data['message']['photo'])) {
                $this->sendMessage("Пожалуйста, отправьте фото состава продукта.", $chatId);
                return response()->json(['status' => 'no_photo']);
            }

            // Получение фото
            $photo = end($data['message']['photo']);
            $fileId = $photo['file_id'];
            $fileInfo = Http::get("https://api.telegram.org/bot{$this->botToken}/getFile", [
                'file_id' => $fileId
            ])->json();

            $filePath = $fileInfo['result']['file_path'];
            $fileUrl = "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";

            // Скачиваем и сохраняем локально
            $response = Http::get($fileUrl);
            if (!$response->successful()) {
                $this->sendMessage("Ошибка при загрузке файла.", $chatId);
                return response()->json(['status' => 'download_failed']);
            }

            $filename = basename($filePath);
            $localPath = storage_path("app/public/telegram/{$filename}");
            Storage::disk('public')->put("telegram/{$filename}", $response->body());

            // OCR
            $ocrResponse = Http::attach('file', file_get_contents($localPath), $filename)
                ->post('https://api.ocr.space/parse/image', [
                    'apikey' => $this->ocrApiKey,
                    'language' => 'eng',
                    'OCREngine' => 2,
                ]);

            $ocrData = $ocrResponse->json();
            $text = $ocrData['ParsedResults'][0]['ParsedText'] ?? '';

            if (empty(trim($text))) {
                $this->sendMessage("Не удалось распознать текст. Попробуйте другое фото.", $chatId);
                return response()->json(['status' => 'ocr_failed']);
            }

            // Отправляем текст в Gemini
            $analysis = $gemini->analyze($text);

            $responseText = "🧠 Оценка здоровья продукта: {$analysis['healthScore']}/100\n";
            if (!empty($analysis['concerns'])) {
                $responseText .= "\n⚠️ Возможные проблемы:\n";
                foreach ($analysis['concerns'] as $concern) {
                    $responseText .= "- $concern\n";
                }
            }

            $this->sendMessage($responseText, $chatId);
            return response()->json(['status' => 'ok']);

        } catch (\Throwable $e) {
            $this->sendMessage("Произошла ошибка: " . $e->getMessage(), $chatId);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function sendMessage(string $text, int $chatId): void
    {
        Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }
}
