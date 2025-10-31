<?php

namespace App\Http\Controllers;

use App\Http\Resources\LinkResource;
use App\Models\Links;
use App\Models\LinkStats;
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

        $query = Links::where('user_id', $user->id)->withCount('linkStats');

        if ($expiredAtParam) {
            try {
                $expiredAt = Carbon::parse($expiredAtParam);
                $query->where('expires_at', '<', $expiredAt);
            } catch (Exception $e) {
                return response()->json(['message' => 'Invalid expiredAt parameter'], 400);
            }
        }

        $links = $query->orderBy('created_at', 'desc')->paginate(15);

        return LinkResource::collection($links);
    }

    public function getById(Request $request, string $id)
    {
        $includeStats = $request->query('includeStats', true);

        $query = Links::where('id', $id)->where('user_id', $request->user()->id)->withCount(['linkStats as visitsCount']);

        if ($includeStats) {
            $query->with('linkStats');
        }

        $link = $query->first();

        if (!$link) {
            return response()->json(['message' => 'Link not found'], 404);
        }

        return new LinkResource($link);
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

    public function redirectToOriginalUrl($shortCode, Request $request)
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

        $linkStat = LinkStats::create([
            'link_id' => $data['id'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        $linkStat->save();


        return redirect($data['original_url']);
    }
}
