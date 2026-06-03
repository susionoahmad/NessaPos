<?php

namespace App\Http\Controllers;

use App\Models\AffiliateLink;
use App\Models\BlogCategory;
use App\Models\Device;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\RecommendedDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SuperAdminContentController extends Controller
{
    public function pages()
    {
        return response()->json(Page::with('sections.contents')->latest()->get());
    }

    public function storePage(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:pages,slug',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean',
            'sections' => 'array',
        ]);

        $page = Page::create([
            ...collect($data)->except('sections')->all(),
            'slug' => $data['slug'] ?? Str::slug($data['title']),
        ]);

        $this->syncSections($page, $request->input('sections', []));

        return response()->json($page->load('sections.contents'), 201);
    }

    public function updatePage(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:pages,slug,' . $page->id,
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean',
            'sections' => 'array',
        ]);

        $page->update(collect($data)->except('sections')->all());

        if ($request->has('sections')) {
            $this->syncSections($page, $request->input('sections', []));
        }

        return response()->json($page->load('sections.contents'));
    }

    public function deletePage(Page $page)
    {
        $page->delete();
        return response()->json(['message' => 'Page deleted']);
    }

    public function posts()
    {
        return response()->json(Post::with(['categories', 'affiliates'])->latest()->get());
    }

    public function blogCategories()
    {
        return response()->json(BlogCategory::orderBy('name')->get());
    }

    public function storeBlogCategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:blog_categories,slug',
        ]);

        $category = BlogCategory::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
        ]);

        return response()->json($category, 201);
    }

    public function storePost(Request $request)
    {
        $data = $this->validatePost($request);
        $post = Post::create([
            ...collect($data)->except(['category_ids', 'affiliate_ids'])->all(),
            'slug' => $data['slug'] ?? Str::slug($data['title']),
        ]);
        $post->categories()->sync($data['category_ids'] ?? []);
        $post->affiliates()->sync($data['affiliate_ids'] ?? []);

        return response()->json($post->load(['categories', 'affiliates']), 201);
    }

    public function updatePost(Request $request, Post $post)
    {
        $data = $this->validatePost($request, $post->id, true);
        $post->update(collect($data)->except(['category_ids', 'affiliate_ids'])->all());

        if (array_key_exists('category_ids', $data)) {
            $post->categories()->sync($data['category_ids']);
        }
        if (array_key_exists('affiliate_ids', $data)) {
            $post->affiliates()->sync($data['affiliate_ids']);
        }

        return response()->json($post->load(['categories', 'affiliates']));
    }

    public function deletePost(Post $post)
    {
        $post->delete();
        return response()->json(['message' => 'Post deleted']);
    }

    public function affiliateLinks()
    {
        return response()->json(AffiliateLink::latest()->get());
    }

    public function storeAffiliateLink(Request $request)
    {
        $link = AffiliateLink::create($this->validateAffiliate($request));
        return response()->json($link, 201);
    }

    public function updateAffiliateLink(Request $request, AffiliateLink $affiliateLink)
    {
        $affiliateLink->update($this->validateAffiliate($request, true));
        return response()->json($affiliateLink);
    }

    public function deleteAffiliateLink(AffiliateLink $affiliateLink)
    {
        $affiliateLink->delete();
        return response()->json(['message' => 'Affiliate link deleted']);
    }

    public function devices()
    {
        return response()->json(Device::with('affiliates')->latest()->get());
    }

    public function storeDevice(Request $request)
    {
        $data = $this->validateDevice($request);
        $device = Device::create(collect($data)->except('affiliate_ids')->all());
        $device->affiliates()->sync($data['affiliate_ids'] ?? []);

        return response()->json($device->load('affiliates'), 201);
    }

    public function updateDevice(Request $request, Device $device)
    {
        $data = $this->validateDevice($request, true);
        $device->update(collect($data)->except('affiliate_ids')->all());

        if (array_key_exists('affiliate_ids', $data)) {
            $device->affiliates()->sync($data['affiliate_ids']);
        }

        return response()->json($device->load('affiliates'));
    }

    public function deleteDevice(Device $device)
    {
        $device->delete();
        return response()->json(['message' => 'Device deleted']);
    }

    public function recommendations()
    {
        return response()->json(RecommendedDevice::with('devices')->latest()->get());
    }

    public function storeRecommendation(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'context' => 'required|string|max:100',
            'device_ids' => 'array',
            'device_ids.*' => 'exists:devices,id',
        ]);

        $recommendation = RecommendedDevice::create(collect($data)->except('device_ids')->all());
        $recommendation->devices()->sync($data['device_ids'] ?? []);

        return response()->json($recommendation->load('devices'), 201);
    }

    public function updateRecommendation(Request $request, RecommendedDevice $recommendedDevice)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'context' => 'sometimes|string|max:100',
            'device_ids' => 'array',
            'device_ids.*' => 'exists:devices,id',
        ]);

        $recommendedDevice->update(collect($data)->except('device_ids')->all());

        if (array_key_exists('device_ids', $data)) {
            $recommendedDevice->devices()->sync($data['device_ids']);
        }

        return response()->json($recommendedDevice->load('devices'));
    }

    public function deleteRecommendation(RecommendedDevice $recommendedDevice)
    {
        $recommendedDevice->delete();
        return response()->json(['message' => 'Recommendation deleted']);
    }

    public function plans()
    {
        return response()->json(Plan::with('promotions')->latest()->get());
    }

    public function storePlan(Request $request)
    {
        $data = $this->validatePlan($request);
        $plan = Plan::create(collect($data)->except('promotion_ids')->all());
        $plan->promotions()->sync($data['promotion_ids'] ?? []);

        return response()->json($plan->load('promotions'), 201);
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        $data = $this->validatePlan($request, true);
        $plan->update(collect($data)->except('promotion_ids')->all());

        if (array_key_exists('promotion_ids', $data)) {
            $plan->promotions()->sync($data['promotion_ids']);
        }

        return response()->json($plan->load('promotions'));
    }

    public function deletePlan(Plan $plan)
    {
        $plan->delete();
        return response()->json(['message' => 'Plan deleted']);
    }

    public function promotions()
    {
        return response()->json(Promotion::with('plans')->latest()->get());
    }

    public function storePromotion(Request $request)
    {
        $data = $this->validatePromotion($request);
        $promotion = Promotion::create(collect($data)->except('plan_ids')->all());
        $promotion->plans()->sync($data['plan_ids'] ?? []);

        return response()->json($promotion->load('plans'), 201);
    }

    public function updatePromotion(Request $request, Promotion $promotion)
    {
        $data = $this->validatePromotion($request, true);
        $promotion->update(collect($data)->except('plan_ids')->all());

        if (array_key_exists('plan_ids', $data)) {
            $promotion->plans()->sync($data['plan_ids']);
        }

        return response()->json($promotion->load('plans'));
    }

    public function deletePromotion(Promotion $promotion)
    {
        $promotion->delete();
        return response()->json(['message' => 'Promotion deleted']);
    }

    private function syncSections(Page $page, array $sections): void
    {
        foreach ($sections as $sectionData) {
            $section = $page->sections()->updateOrCreate(
                ['name' => $sectionData['name']],
                [
                    'order' => $sectionData['order'] ?? 0,
                    'is_active' => $sectionData['is_active'] ?? true,
                ]
            );

            foreach (($sectionData['contents'] ?? []) as $contentData) {
                $value = $contentData['value'] ?? null;
                
                // If type is json, try to decode it so it stores as a proper array/object
                // since SectionContent model has a 'value' => 'array' cast.
                if ($contentData['type'] === 'json' && is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $decoded;
                    }
                }

                $section->contents()->updateOrCreate(
                    ['key' => $contentData['key']],
                    [
                        'type' => $contentData['type'],
                        'value' => $value,
                    ]
                );
            }
        }
    }

    private function validatePost(Request $request, ?int $id = null, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => "$required|string|max:255",
            'slug' => 'nullable|string|unique:posts,slug,' . $id,
            'content' => "$required|string",
            'excerpt' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'status' => 'sometimes|in:draft,published',
            'published_at' => 'nullable|date',
            'category_ids' => 'array',
            'category_ids.*' => 'exists:blog_categories,id',
            'affiliate_ids' => 'array',
            'affiliate_ids.*' => 'exists:affiliate_links,id',
        ]);
    }

    private function validateAffiliate(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => "$required|string|max:255",
            'url' => "$required|url",
            'platform' => "$required|string|max:100",
            'product_name' => "$required|string|max:255",
            'image' => 'nullable|string',
            'price' => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);
    }

    private function validateDevice(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => "$required|string|max:255",
            'type' => "$required|string|max:100",
            'brand' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
            'affiliate_ids' => 'array',
            'affiliate_ids.*' => 'exists:affiliate_links,id',
        ]);
    }

    private function validatePlan(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => "$required|string|max:255",
            'price' => "$required|numeric",
            'billing_type' => "$required|in:monthly,yearly",
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'promotion_ids' => 'array',
            'promotion_ids.*' => 'exists:promotions,id',
        ]);
    }

    private function validatePromotion(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => "$required|string|max:255",
            'description' => 'nullable|string',
            'discount_type' => "$required|in:percentage,fixed",
            'discount_value' => "$required|numeric|min:0",
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'plan_ids' => 'array',
            'plan_ids.*' => 'exists:plans,id',
        ]);
    }
}
