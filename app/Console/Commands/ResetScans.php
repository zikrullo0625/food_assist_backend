<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetScans extends Command
{
    protected $signature = 'users:reset-scans';
    protected $description = 'Сбросить количество сканов до 5 для всех пользователей';

    public function handle()
    {
        User::query()->update(['scans' => 5]);
        $this->info('Scans reset to 5 for all users.');
    }
}
