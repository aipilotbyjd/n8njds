<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

class ApiVersioningService
{
    public const CURRENT_VERSION = 'v1';

    public const SUPPORTED_VERSIONS = ['v1', 'v2'];

    /**
     * Register API routes for all versions
     */
    public static function registerRoutes(): void
    {
        // Register v1 routes
        self::registerV1Routes();

        // Future: Register v2 routes when available
        // self::registerV2Routes();
    }

    /**
     * Register v1 API routes
     */
    private static function registerV1Routes(): void
    {
        Route::prefix('v1')->group(function () {
            // Public authentication routes
            Route::group(['as' => 'api.v1.auth.'], function () {
                Route::post('/register', [\App\Http\Controllers\Api\V1\Auth\RegisterController::class, 'register'])->name('register');
                Route::post('/login', [\App\Http\Controllers\Api\V1\Auth\LoginController::class, 'login'])->name('login');
            });

            // Protected routes
            Route::middleware(['auth:api'])->group(function () {
                // Authentication domain (protected)
                Route::group(['as' => 'api.v1.auth.'], function () {
                    Route::post('/logout', [\App\Http\Controllers\Api\V1\Auth\LoginController::class, 'logout'])->name('logout');
                    Route::get('/user', [\App\Http\Controllers\Api\V1\Auth\LoginController::class, 'me'])->name('user');
                    Route::get('/profile', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'index'])->name('profile');
                });

                // User management domain
                Route::group(['as' => 'api.v1.user.'], function () {
                    Route::get('/users/profile', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'index'])->name('profile.show');
                    Route::put('/users/profile', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'update'])->name('profile.update');
                });

                // Organization domain
                Route::group(['as' => 'api.v1.organizations.'], function () {
                    Route::apiResource('/organizations', \App\Http\Controllers\Api\V1\OrganizationController::class);
                    Route::post('/organizations/{organization}/switch', [\App\Http\Controllers\Api\V1\OrganizationController::class, 'switch'])->name('switch');
                    Route::apiResource('/organizations/{organization}/members', \App\Http\Controllers\Api\V1\OrganizationMemberController::class)->except(['show', 'update']);
                    Route::put('/organizations/{organization}/members/{member}', [\App\Http\Controllers\Api\V1\OrganizationMemberController::class, 'update'])->name('members.update');
                });

                // Credential domain
                Route::group(['as' => 'api.v1.credentials.'], function () {
                    Route::apiResource('/credentials', \App\Http\Controllers\Api\V1\CredentialController::class);
                    Route::post('/credentials/{credential}/rotate', [\App\Http\Controllers\Api\V1\CredentialController::class, 'rotate'])->name('rotate');
                });

                // Workflow domain
                Route::group(['as' => 'api.v1.workflows.'], function () {
                    Route::apiResource('/workflows', \App\Http\Controllers\Api\V1\WorkflowController::class);
                    Route::get('/workflows/{workflow}/executions', [\App\Http\Controllers\Api\V1\WorkflowController::class, 'executions'])->name('executions');
                    Route::post('/workflows/{workflow}/execute', [\App\Http\Controllers\Api\V1\WorkflowController::class, 'execute'])->name('execute');
                    Route::get('/workflows/{workflow}/nodes', [\App\Http\Controllers\Api\V1\WorkflowController::class, 'nodes'])->name('nodes');
                });

                // Workflow execution domain
                Route::group(['as' => 'api.v1.executions.'], function () {
                    Route::apiResource('/executions', \App\Http\Controllers\Api\V1\WorkflowExecutionController::class);
                    Route::get('/executions/{execution}/logs', [\App\Http\Controllers\Api\V1\WorkflowExecutionController::class, 'logs'])->name('logs');
                });

                // Node domain
                Route::group(['as' => 'api.v1.nodes.'], function () {
                    Route::get('/nodes', [\App\Http\Controllers\Api\V1\NodeController::class, 'index'])->name('index');
                    Route::get('/nodes/{node}/config', [\App\Http\Controllers\Api\V1\NodeController::class, 'config'])->name('config');
                });

                // Admin domain
                Route::group(['prefix' => 'admin', 'as' => 'api.v1.admin.', 'middleware' => ['role:admin']], function () {
                    Route::get('/dashboard', [\App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'index'])->name('dashboard');
                    Route::apiResource('/users', \App\Http\Controllers\Api\V1\Admin\UserController::class);
                    Route::apiResource('/organizations', \App\Http\Controllers\Api\V1\Admin\OrganizationController::class);
                });
            });
        });
    }
}
