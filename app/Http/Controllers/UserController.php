<?php

namespace App\Http\Controllers;

use App\Http\Resources\HistoryResource;
use App\Http\Resources\StatsResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function getScans(Request $request)
    {
        $user = User::find(Auth::id());
        $user->scans += $request->get('scans');
        $user->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function stats(Request $request)
    {
        $user = User::with('products')->find(Auth::id());
        return response()->json(new StatsResource($user));
    }

    public function history(Request $request)
    {
        $user = User::with('products')->find(Auth::id());
        return response()->json(HistoryResource::collection($user->products));
    }
}
