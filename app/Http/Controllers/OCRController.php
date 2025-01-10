<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OCRController extends Controller
{
    public function recognizeText(Request $request)
    {
        $image = $request->file('image');

        $path = $image->storeAs('temp', 'image.png', 'public');

        $imagePath = storage_path('app/public/' . $path);

        $ocr = new TesseractOCR($imagePath);

        $ocr->lang('eng');

        try {
            $text = $ocr->run();

            $words = preg_split('/\s+/', $text);

            $filteredWords = array_filter($words, function($word) {
                return preg_match('/^[a-zA-Zа-яА-ЯёЁ0-9]+$/u', $word);
            });

            $result = [];

            foreach ($filteredWords as $word) {
                $ingredient = Ingredient::where('name', $word)->first();

                if ($ingredient) {
                    $result[] = $ingredient;
                }
            }

            return response()->json(['text' => $result]);

        } catch (\Exception $e) {
            return response()->json(['text' => 'Ошибка при распознавании текста: ' . $e->getMessage()], 500);
        }
    }
}
