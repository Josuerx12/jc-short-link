<?php

namespace App\Http\Controllers;

use App\Models\Links;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LinksController extends Controller
{
    public function findAll(Request $request)
    {
        $user = $request->user();
        $expiredAtParam = $request->query('expiredAt');

        if ($expiredAtParam) {
            try {
                $expiredAt = Carbon::parse($expiredAtParam);
            } catch (Exception $e) {
                return response()->json(['message' => 'Invalid expiredAt parameter'], 400);
            }

            $links = Links::where('expires_at', '<', $expiredAt)
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json($links);
        }

        $links = Links::where('user_id', $user->id)->orderBy('created_at', 'desc')->paginate(15);
        
        return response()->json($links);
    }

    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'originalUrl' => 'required|url|max:2048',
        ]);
        
        $link = new Links();
        
        $link->original_url = $validatedData['originalUrl'];

        $code = substr(md5(uniqid(rand(), true)), 0, 6);
        $link->short_code = $code;

        $link->expires_at = Carbon::now()->addDays(30);

        $user = $request->user();
        if ($user) {
            $link->user_id = $user->id;
        }

        $link->save();

        return response()->json($link, 201);
    }

    public function redirectToOriginalUrl($shortCode)
    {
        $cacheKey = "links:short_code:{$shortCode}";
        $data = Cache::get($cacheKey);

        if (! $data) {
            $link = Links::where('short_code', $shortCode)->first();

            if (! $link) {
                return response()->json(['message' => 'Link not found'], 404);
            }

            if ($link->expires_at && Carbon::now()->greaterThan($link->expires_at)) {
                return response()->json(['message' => 'Link has expired'], 410);
            }

            $data = [
                'id' => $link->id,
                'original_url' => $link->original_url,
                'expires_at' => $link->expires_at ? $link->expires_at->toDateTimeString() : null,
            ];

            Cache::put($cacheKey, $data, now()->addMinutes(60));
        } else {
            $expiresAt = $data['expires_at'] ? Carbon::parse($data['expires_at']) : null;
            if ($expiresAt && Carbon::now()->greaterThan($expiresAt)) {
                Cache::forget($cacheKey);
                return response()->json(['message' => 'Link has expired'], 410);
            }
        }

        return redirect($data['original_url']);
    }
}
