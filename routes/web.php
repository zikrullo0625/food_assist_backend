<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/create-storage-link', function () {
    $target = storage_path('app'); // Ссылка на storage/app
    $link = public_path('storage'); // Путь к public/storage

    // Проверка существования ссылки
    if (file_exists($link)) {
        if (is_link($link)) {
            $realPath = readlink($link);
            if ($realPath === $target) {
                return 'Symbolic link already exists and points to: ' . $realPath;
            } else {
                return 'Symbolic link exists but points to wrong target: ' . $realPath;
            }
        } else {
            return 'Storage exists but is not a symbolic link! Please remove the folder public/storage and try again.';
        }
    }

    // Проверка целевой папки
    if (!is_dir($target)) {
        return 'Target directory does not exist: ' . $target;
    }

    // Проверка прав на запись
    if (!is_writable(dirname($link))) {
        return 'Public directory is not writable: ' . dirname($link);
    }

    // Создание ссылки
    try {
        symlink($target, $link);
        return 'Symbolic link created successfully!';
    } catch (\Exception $e) {
        return 'Error creating symbolic link: ' . $e->getMessage();
    }
});

// Тестовый маршрут для проверки структуры
Route::get('/test-storage', function () {
    $basePath = storage_path('app');
    $folders = ['public', 'private', 'mobile'];
    $result = [];

    foreach ($folders as $folder) {
        $path = $basePath . '/' . $folder;
        $result[] = "Folder $folder exists: " . (is_dir($path) ? 'Yes' : 'No');
    }

    return implode('<br>', $result);
});
Route::get('/migrate', function () {
    Artisan::call('migrate:refresh', ['--force' => true]);
    return response('Миграции применены', 200);
});
Route::get('/stlink', function () {
    Artisan::call('storage:link');
    return response('Storage linked!', 200);
});
Route::get('/img/{path}', function ($path) {

    // Указываем путь к файлу в mobile
    $fullPath = 'mobile/' . $path;

    // Проверяем, существует ли файл в storage
    if (!Storage::disk('local')->exists($fullPath)) {
        abort(404, 'Файл не найден');
    }

    // Получаем файл
    $file = Storage::disk('local')->get($fullPath);
    $type = Storage::disk('local')->mimeType($fullPath);

    // Отправляем файл с правильным Content-Type
    return response($file)->header('Content-Type', $type);
})->where('path', '.*');

