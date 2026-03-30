<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = BlogPost::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('asset')) {
            $query->where('asset', $request->input('asset'));
        }

        $posts = $query->paginate(20)->withQueryString();

        $allStatuses = ['draft', 'pending_review', 'published', 'rejected'];

        $statusCounts = [];
        foreach ($allStatuses as $s) {
            $statusCounts[$s] = BlogPost::where('status', $s)->count();
        }

        $assetCounts = BlogPost::selectRaw('asset, COUNT(*) as count')
            ->groupBy('asset')
            ->pluck('count', 'asset')
            ->toArray();

        return Inertia::render('Content/Index', [
            'posts' => $posts,
            'filters' => $request->only(['status', 'asset']),
            'stats' => [
                'total' => BlogPost::count(),
                'published' => $statusCounts['published'] ?? 0,
                'pending_review' => $statusCounts['pending_review'] ?? 0,
                'draft' => $statusCounts['draft'] ?? 0,
                'rejected' => $statusCounts['rejected'] ?? 0,
                'published_this_week' => BlogPost::where('status', 'published')->where('published_at', '>=', now()->subDays(7))->count(),
            ],
            'statusCounts' => $statusCounts,
            'assetCounts' => $assetCounts,
        ]);
    }
}
