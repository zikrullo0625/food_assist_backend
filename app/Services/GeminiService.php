<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GeminiService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.google_genai.api_key');
        $this->model = 'gemini-2.0-flash'; // или другой model id
    }

    public function analyze(string $ocrText): array
    {
        $promptText = $this->buildHealthPrompt($ocrText);

        $response = $this->callGemini($promptText);
        return $this->parseHealthAnalysisResponse($response);
    }

    protected function buildHealthPrompt(string $ocrText): string
    {
        return <<<PROMPT
You are a nutrition expert.

Analyze the following OCR-extracted text from a product's label and determine how healthy this product is.

Text:
"""
$ocrText
"""

Instructions:
- Based on the text, estimate a health score between 0 (very unhealthy) to 100 (very healthy).
- List any health concerns such as high sugar, trans fats, artificial additives, allergens, etc.
- Determine the most probable product name based on the ingredients and label information.

Return a JSON object in the following format:

{
  "name": "Product Name",
  "healthScore": 0-100,
  "concerns": ["..."]
}
PROMPT;
    }

    protected function parseHealthAnalysisResponse(array $response): array
    {
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Удалим markdown блоки, если есть
        $text = preg_replace('/```json|```/', '', $text);

        // Найдём первую { и последнюю }, чтобы вырезать JSON
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            throw new \Exception("No valid JSON found in AI response: $text");
        }

        $jsonString = substr($text, $start, $end - $start + 1);
        $json = json_decode($jsonString, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($json['healthScore']) && isset($json['name'])) {
            return $json;
        }

        throw new \Exception("Unable to parse health analysis from AI response: $text");
    }


    protected function callGemini(string $prompt): array
    {
        $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$this->apiKey}", [
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
        ]);

        if ($response->failed()) {
            throw new \Exception('Gemini API call failed: ' . $response->body());
        }

        return $response->json();
    }

    protected function parseResponse(array $response): array
    {
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Попробуем распарсить JSON из текста
        if (preg_match('/\{.*"ingredients"\s*:\s*\[.*\]\s*\}/s', $text, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        throw new \Exception("Unable to parse ingredients from AI response: $text");
    }
}
