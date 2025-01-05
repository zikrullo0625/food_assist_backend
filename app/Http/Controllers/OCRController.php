<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OCRController extends Controller
{
    public function recognizeText(Request $request)
    {
        // Получаем изображение из запроса
        $image = $request->file('image');

        // Путь к временной директории для сохранения файла
        $path = $image->storeAs('temp', 'image.png', 'public');

        // Путь к изображению
        $imagePath = storage_path('app/public/' . $path);

        // Создаем экземпляр TesseractOCR
        $ocr = new TesseractOCR($imagePath);

        // Указываем русский и английский языки
        $ocr->lang('rus');

        // Выполняем распознавание текста
        try {
            $text = $ocr->run();
            return response()->json(['text' => $text]);
        } catch (\Exception $e) {
            return response()->json(['text' => 'Ошибка при распознавании текста: ' . $e->getMessage()]);
        }
    }
}
