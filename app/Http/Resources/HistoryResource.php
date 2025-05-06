<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class HistoryResource extends JsonResource
{
    /**
     * Преобразуем ресурс в массив.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Убедимся, что у продукта есть поле 'image', если оно есть
        $imageUrl = $this->image ?? '/path/to/default/image.jpg'; // укажи путь по умолчанию

        return [
            'image' => $imageUrl, // предполагаем, что в модели 'image' или 'image_url'
            'name' => $this->name,
            'scanned_at' => Carbon::parse($this->created_at)->format('d F, H:i'), // форматируем дату
            'health_status' => $this->health_score >= 60 ? 'Полезный продукт' : 'Вредный продукт', // проверка на здоровье
        ];
    }
}

