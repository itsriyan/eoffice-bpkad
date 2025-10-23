<?php

namespace App\Http\Controllers;

use App\Models\IncomingLetter;
use App\Models\Disposition;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Permissions: simple view gate (superadmin/admin/pimpinan/staff all can see summary)
        if (!user()->can('incoming_letter.view')) {
            // Fallback: if user lacks this, still allow minimal dashboard
        }

        $lettersTotal = IncomingLetter::count();
        $lettersNew = IncomingLetter::where('status', 'new')->count();
        $lettersDisposed = IncomingLetter::where('status', 'disposed')->count();
        $lettersFollowedUp = IncomingLetter::where('status', 'followed_up')->count();
        $lettersCompleted = IncomingLetter::where('status', 'completed')->count();
        $lettersArchived = IncomingLetter::where('status', 'archived')->count();
        $lettersRejected = IncomingLetter::where('status', 'rejected')->count();

        $dispositionsTotal = Disposition::count();
        $dispositionsReceived = Disposition::where('status', 'received')->count();
        $dispositionsFollowed = Disposition::where('status', 'followed_up')->count();
        $dispositionsCompleted = Disposition::where('status', 'completed')->count();

        // Recent letters (limit 8 by creation date desc)
        $recentLetters = IncomingLetter::latest()->limit(8)->get();

        // Throughput last 7 days (group by date)
        $dailyCounts = IncomingLetter::selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('d')
            ->orderBy('d')
            ->get();
        $dailySeries = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $found = $dailyCounts->firstWhere('d', $day);
            $dailySeries[] = ['date' => $day, 'count' => $found?->c ?? 0];
        }

        return view('dashboard.index', compact(
            'lettersTotal',
            'lettersNew',
            'lettersDisposed',
            'lettersFollowedUp',
            'lettersCompleted',
            'lettersArchived',
            'lettersRejected',
            'dispositionsTotal',
            'dispositionsReceived',
            'dispositionsFollowed',
            'dispositionsCompleted',
            'recentLetters',
            'dailySeries'
        ));
    }
}
