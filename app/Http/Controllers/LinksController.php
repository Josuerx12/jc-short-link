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
        $includeStats = filter_var($request->query('includeStats', true), FILTER_VALIDATE_BOOLEAN);

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
        $user = $request->user();

        $validatedData = $request->validate([
            'originalUrl' => 'required|url|max:2048',
        ]);
        
        $link = new Links();
        
        $link->original_url = $validatedData['originalUrl'];

        $code = substr(md5(uniqid(rand(), true)), 0, 6);
        $link->short_code = $code;

        if ($user) {
            $link->user_id = $user->id;

            $link->expires_at = Carbon::now()->addDays(30);
        } else {
            $link->expires_at = Carbon::now()->addDays(1);
        }

        $link->save();

        return response()->json($link, 201);
    }

    public function getLinkByShortCode($shortCode, Request $request)
    {
        $linkData = $this->getLinkData($shortCode);

        if ($linkData === null) {
            return response()->json(['message' => 'Link not found'], 404);
        }

        if ($linkData === 'expired') {
            return response()->json(['message' => 'Link has expired'], 410);
        }

        $linkStat = LinkStats::create([
            'link_id' => $linkData['id'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        $linkStat->save();

        return response()->json($linkData);
    }


    public function redirectToOriginalUrl($shortCode, Request $request)
    {
        $linkData = $this->getLinkData($shortCode);

        $linkStat = LinkStats::create([
            'link_id' => $linkData['id'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        $linkStat->save();

        return redirect($linkData['original_url']);
    }

    private function getLinkData($shortCode)
    {
        $cacheKey = "links:short_code:{$shortCode}";
        $data = Cache::get($cacheKey);

        if (! $data) {
            $link = Links::where('short_code', $shortCode)->first();

        if (! $link) {
            return null;
        }

        if ($link->expires_at && Carbon::now()->greaterThan($link->expires_at)) {
            return 'expired';
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
                return 'expired';
            }
        }

        return $data;
    }
}
