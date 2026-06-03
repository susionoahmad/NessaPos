<?php

namespace App\Http\Controllers;

use App\Models\AffiliateLink;
use App\Models\Page;
use App\Models\Post;
use App\Models\Plan;
use App\Models\RecommendedDevice;
use Illuminate\Http\Request;

class PublicContentController extends Controller
{
    public function page(string $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_active', true)
            ->with(['sections' => function ($query) {
                $query->where('is_active', true)->with('contents')->orderBy('order');
            }])
            ->firstOrFail();

        return response()->json($page);
    }

    public function posts(Request $request)
    {
        $posts = Post::with('categories')
            ->where('status', 'published')
            ->when($request->category, function ($query, $category) {
                $query->whereHas('categories', fn ($q) => $q->where('slug', $category));
            })
            ->latest('published_at')
            ->paginate($request->integer('per_page', 10));

        return response()->json($posts);
    }

    public function post(string $slug)
    {
        $post = Post::with(['categories', 'affiliates' => fn ($q) => $q->where('is_active', true)])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json($post);
    }

    public function deviceRecommendation(Request $request)
    {
        $context = $request->query('context', 'pos');

        $recommendation = RecommendedDevice::where('context', $context)
            ->with(['devices' => function ($query) {
                $query->where('is_active', true)
                    ->with(['affiliates' => fn ($q) => $q->where('is_active', true)]);
            }])
            ->firstOrFail();

        return response()->json($recommendation);
    }

    public function plans()
    {
        $plans = Plan::where('is_active', true)
            ->with(['promotions' => fn ($q) => $q->where('is_active', true)])
            ->get();

        return response()->json($plans);
    }

    public function affiliateClick(Request $request, AffiliateLink $affiliate)
    {
        $affiliate->newQuery()->whereKey($affiliate->id)->where('is_active', true)->firstOrFail();

        $affiliate->clicks()->create([
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'url' => $affiliate->url,
        ]);
    }
}
