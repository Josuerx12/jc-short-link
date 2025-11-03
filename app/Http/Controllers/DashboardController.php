<?php

namespace App\Http\Controllers;

use App\Http\Resources\LinkResource;
use App\Models\Links;
use App\Models\LinkStats;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function overview(Request $request) 
    {
        $user = $request->user();

        $totalLinks = Links::where('user_id', $user->id)->count();
        $activeLinks = Links::where('user_id', $user->id)
            ->where(function ($qr) {
                    $qr
                        ->whereNull('expires_at')
                        ->orWhere('expires_at', '>', Carbon::now());
                })->count();
        $expiredLinks = $totalLinks - $activeLinks;
        
        $linkIds = Links::where('user_id', $user->id)->pluck('id');

        $totalClicks = LinkStats::whereIn('link_id', $linkIds)->count();
        $clicksToday = LinkStats::whereIn('link_id', $linkIds)->whereDate('created_at', today())->count();
        $avgClicksPerLink = $totalLinks ? round($totalClicks / $totalLinks, 2) : 0;

        $topLink = Links::where('user_id', $user->id)
                    ->withCount(['linkStats as visitsCount'])
                    ->orderByDesc('visitsCount')
                    ->first();

        $lastCreated = Links::where('user_id', $user->id)
            ->withCount(['linkStats as visitsCount'])
            ->orderByDesc('created_at')
            ->first();

        $data = [
            'totalLinks' => $totalLinks,
            'activeLinks' => $activeLinks,
            'expiredLinks' => $expiredLinks,
            'totalClicks' => $totalClicks,
            'clicksToday' => $clicksToday,
            'avgClicksPerLink' => $avgClicksPerLink,
            'topLink' => $topLink ? new LinkResource($topLink) : null,
            'lastCreatedLink' => $lastCreated ? new LinkResource($lastCreated) : null,
        ];

        return response()->json($data);
    }
}
