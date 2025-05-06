<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class StatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get products or use empty collection
        $products = $this->products ?? collect();

        // Filter products from the last 7 days
        $filtered = $products->filter(function ($product) {
            return Carbon::parse($product->created_at)->gt(now()->subDays(7));
        });

        // Debug the filtered products
        Log::info('Filtered products count: ' . $filtered->count());

        // Setup Russian weekday abbreviations
        $weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        $dailyStats = array_fill_keys($weekDays, ['useful' => 0, 'harmful' => 0]);

        // Map full Russian day names to abbreviations
        $dayMapping = [
            'понедельник' => 'Пн',
            'вторник' => 'Вт',
            'среда' => 'Ср',
            'четверг' => 'Чт',
            'пятница' => 'Пт',
            'суббота' => 'Сб',
            'воскресенье' => 'Вс',
        ];

        $totalUseful = 0;
        $totalHarmful = 0;

        // Process each product
        foreach ($filtered as $product) {
            // Parse creation date
            $carbonDate = Carbon::parse($product->created_at);

            // Get day of week in English (fallback if Russian locale fails)
            $dayOfWeek = strtolower($carbonDate->format('l'));

            // Map English day to Russian abbreviation
            $dayAbbr = null;

            // Try to get the Russian day name first
            try {
                $carbonDate->locale('ru');
                $fullDayName = strtolower($carbonDate->dayName);
                $dayAbbr = $dayMapping[$fullDayName] ?? null;
            } catch (\Exception $e) {
                Log::error('Error setting Russian locale: ' . $e->getMessage());
            }

            // If Russian mapping failed, map from English
            if ($dayAbbr === null) {
                $englishToRussian = [
                    'monday' => 'Пн',
                    'tuesday' => 'Вт',
                    'wednesday' => 'Ср',
                    'thursday' => 'Чт',
                    'friday' => 'Пт',
                    'saturday' => 'Сб',
                    'sunday' => 'Вс',
                ];
                $dayAbbr = $englishToRussian[$dayOfWeek] ?? null;
            }

            // Skip if we couldn't determine the day
            if (!$dayAbbr || !array_key_exists($dayAbbr, $dailyStats)) {
                Log::warning('Could not map day: ' . $dayOfWeek . ' for date: ' . $product->created_at);
                continue;
            }

            // Check if product is useful (health score >= 60)
            $isUseful = isset($product->health_score) && $product->health_score >= 60;

            // Update statistics
            if ($isUseful) {
                $dailyStats[$dayAbbr]['useful']++;
                $totalUseful++;
            } else {
                $dailyStats[$dayAbbr]['harmful']++;
                $totalHarmful++;
            }

            // Log the classification for debugging
            Log::info("Product ID {$product->id} with score {$product->health_score} classified as " .
                ($isUseful ? 'useful' : 'harmful') . " on day {$dayAbbr}");
        }

        $totalChecked = $totalUseful + $totalHarmful;
        $usefulPercent = $totalChecked > 0 ? round(($totalUseful / $totalChecked) * 100) : 0;
        $harmfulPercent = $totalChecked > 0 ? 100 - $usefulPercent : 0;

        // Log the final stats for debugging
        Log::info("Stats summary: Total {$totalChecked}, Useful {$totalUseful}, Harmful {$totalHarmful}");

        return [
            'last_7_days' => [
                'by_day' => $dailyStats,
                'summary' => [
                    'useful_percent' => $usefulPercent,
                    'harmful_percent' => $harmfulPercent,
                ],
            ],
            'global_summary' => [
                'total_checked' => $totalChecked,
                'useful_count' => $totalUseful,
                'harmful_count' => $totalHarmful,
            ],
        ];
    }
}
