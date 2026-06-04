<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\VaultController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\SuperAdminContentController;
use App\Http\Controllers\SubscriptionRenewalController;
use App\Http\Controllers\DesktopLicenseController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/desktop/activate', [DesktopLicenseController::class, 'activate']); // New desktop activation
Route::get('/packages', [PackageController::class, 'index']); // Public access to packages
Route::get('/pages/{slug}', [PublicContentController::class, 'page']);
Route::get('/posts', [PublicContentController::class, 'posts']);
Route::get('/posts/{slug}', [PublicContentController::class, 'post']);
Route::get('/devices/recommendation', [PublicContentController::class, 'deviceRecommendation']);
Route::post('/affiliate-links/{affiliate}/click', [PublicContentController::class, 'affiliateClick']);

// Authenticated routes
Route::middleware(['auth:sanctum', 'subscription'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/settings/subscription', [SettingController::class, 'updateSubscription']);
    Route::post('/settings/setup', [SettingController::class, 'setup']);
    Route::post('/subscription/renew', [SubscriptionRenewalController::class, 'store']);
    Route::get('/tenant/info', function () {
        $tenant = auth()->user()->tenant;
        $latestRequest = \App\Models\SubscriptionRenewalRequest::where('tenant_id', $tenant->id)
            ->with('package')
            ->latest()
            ->first();

        return response()->json(array_merge($tenant->toArray(), [
            'subscription_status' => $tenant->subscriptionStatus(),
            'superadmin_phone' => config('app.superadmin_phone'),
            'latest_renewal' => $latestRequest
        ]));
    });

    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products/import', [ProductImportController::class, 'import']); // bulk import
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::get('/stock-mutations', [ProductController::class, 'stockMutations']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/stats', [OrderController::class, 'dailyStats']);
    Route::post('/orders', [OrderController::class, 'store']);

    // Sessions
    Route::get('/sessions', [SessionController::class, 'index']);
    Route::get('/sessions/current', [SessionController::class, 'current']);
    Route::post('/sessions/open', [SessionController::class, 'open']);
    Route::post('/sessions/close', [SessionController::class, 'close']);
    Route::get('/cashier-transactions', [SessionController::class, 'transactions']);

    // Vault
    Route::get('/vault', [VaultController::class, 'index']);
    Route::get('/vault/transactions', [VaultController::class, 'transactions']);
    Route::post('/vault/deposit', [VaultController::class, 'deposit']);

    // Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);

    // User management (admin only — enforced in controller)
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    Route::prefix('superadmin')->middleware('superadmin')->group(function () {
        Route::get('/stats', [SuperAdminController::class, 'stats']);
        Route::get('/tenants', [SuperAdminController::class, 'index']);
        Route::post('/tenants', [SuperAdminController::class, 'createTenant']);
        Route::put('/tenants/{id}/subscription', [SuperAdminController::class, 'updateSubscription']);
        Route::post('/tenants/{id}/toggle', [SuperAdminController::class, 'toggleActive']);
        Route::delete('/tenants/{id}', [SuperAdminController::class, 'destroy']);
        
        // Subscription Packages
        Route::get('/packages', [SuperAdminController::class, 'listPackages']);
        Route::put('/packages/{id}', [SuperAdminController::class, 'updatePackage']);

        // Renewal Approvals
        Route::get('/renewals', [SubscriptionRenewalController::class, 'index']);
        Route::post('/renewals/{id}/approve', [SubscriptionRenewalController::class, 'approve']);
        Route::post('/renewals/{id}/reject', [SubscriptionRenewalController::class, 'reject']);

        // Landing Page CMS
        Route::get('/cms/pages', [SuperAdminContentController::class, 'pages']);
        Route::post('/cms/pages', [SuperAdminContentController::class, 'storePage']);
        Route::put('/cms/pages/{page}', [SuperAdminContentController::class, 'updatePage']);
        Route::delete('/cms/pages/{page}', [SuperAdminContentController::class, 'deletePage']);

        // Blog + SEO + Affiliate
        Route::get('/cms/blog-categories', [SuperAdminContentController::class, 'blogCategories']);
        Route::post('/cms/blog-categories', [SuperAdminContentController::class, 'storeBlogCategory']);
        Route::get('/cms/posts', [SuperAdminContentController::class, 'posts']);
        Route::post('/cms/posts', [SuperAdminContentController::class, 'storePost']);
        Route::put('/cms/posts/{post}', [SuperAdminContentController::class, 'updatePost']);
        Route::delete('/cms/posts/{post}', [SuperAdminContentController::class, 'deletePost']);
        Route::get('/cms/affiliate-links', [SuperAdminContentController::class, 'affiliateLinks']);
        Route::post('/cms/affiliate-links', [SuperAdminContentController::class, 'storeAffiliateLink']);
        Route::put('/cms/affiliate-links/{affiliateLink}', [SuperAdminContentController::class, 'updateAffiliateLink']);
        Route::delete('/cms/affiliate-links/{affiliateLink}', [SuperAdminContentController::class, 'deleteAffiliateLink']);

        // Device Recommendation
        Route::get('/cms/devices', [SuperAdminContentController::class, 'devices']);
        Route::post('/cms/devices', [SuperAdminContentController::class, 'storeDevice']);
        Route::put('/cms/devices/{device}', [SuperAdminContentController::class, 'updateDevice']);
        Route::delete('/cms/devices/{device}', [SuperAdminContentController::class, 'deleteDevice']);
        Route::get('/cms/device-recommendations', [SuperAdminContentController::class, 'recommendations']);
        Route::post('/cms/device-recommendations', [SuperAdminContentController::class, 'storeRecommendation']);
        Route::put('/cms/device-recommendations/{recommendedDevice}', [SuperAdminContentController::class, 'updateRecommendation']);
        Route::delete('/cms/device-recommendations/{recommendedDevice}', [SuperAdminContentController::class, 'deleteRecommendation']);

        // Promotion + Marketing Plans
        Route::get('/cms/plans', [SuperAdminContentController::class, 'plans']);
        Route::post('/cms/plans', [SuperAdminContentController::class, 'storePlan']);
        Route::put('/cms/plans/{plan}', [SuperAdminContentController::class, 'updatePlan']);
        Route::delete('/cms/plans/{plan}', [SuperAdminContentController::class, 'deletePlan']);
        Route::get('/cms/promotions', [SuperAdminContentController::class, 'promotions']);
        Route::post('/cms/promotions', [SuperAdminContentController::class, 'storePromotion']);
        Route::put('/cms/promotions/{promotion}', [SuperAdminContentController::class, 'updatePromotion']);
        Route::delete('/cms/promotions/{promotion}', [SuperAdminContentController::class, 'destroyPromotion']);

        // Desktop Licenses
        Route::get('/desktop-licenses', [DesktopLicenseController::class, 'index']);
        Route::post('/desktop-licenses', [DesktopLicenseController::class, 'store']);
        Route::put('/desktop-licenses/{id}', [DesktopLicenseController::class, 'update']);
        Route::delete('/desktop-licenses/{id}', [DesktopLicenseController::class, 'destroy']);

        // CMS Downloads
        Route::get('/cms/downloads', [\App\Http\Controllers\DownloadController::class, 'index']);
        Route::post('/cms/downloads', [\App\Http\Controllers\DownloadController::class, 'store']);
        Route::post('/cms/downloads/{id}', [\App\Http\Controllers\DownloadController::class, 'update']); // Use POST for multipart update with file
        Route::delete('/cms/downloads/{id}', [\App\Http\Controllers\DownloadController::class, 'destroy']);
    });
});
