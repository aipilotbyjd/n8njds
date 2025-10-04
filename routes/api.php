<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
| Current Version: v1
| Supported Versions: v1
|
*/

// API Version 1 Routes
Route::group([
    'prefix' => 'v1',
    'as' => 'api.v1.',
    'middleware' => ['api']
], function () {
    // Public authentication routes
    Route::group(['as' => 'auth.'], function () {
        Route::post('/register', [\App\Http\Controllers\Api\V1\Auth\RegisterController::class, 'register'])->name('register');
        Route::post('/login', [\App\Http\Controllers\Api\V1\Auth\LoginController::class, 'login'])->name('login');
    });

    // Protected routes (require authentication)
    Route::middleware(['auth:api'])->group(function () {
        // Authentication domain (protected)
        Route::group(['as' => 'auth.'], function () {
            Route::post('/logout', [\App\Http\Controllers\Api\V1\Auth\LoginController::class, 'logout'])->name('logout');
            Route::get('/user', [\App\Http\Controllers\Api\V1\Auth\LoginController::class, 'me'])->name('user');
            Route::get('/profile', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'index'])->name('profile');
        });

        // User management domain
        Route::group(['as' => 'user.', 'prefix' => 'user'], function () {
            Route::get('/profile', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'index'])->name('profile.show');
            Route::put('/profile', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'update'])->name('profile.update');
        });

        // Organization domain
        Route::group(['as' => 'organizations.', 'prefix' => 'organizations', 'middleware' => ['organization']], function () {
            Route::apiResource('/', \App\Http\Controllers\Api\V1\OrganizationController::class);
            Route::post('/{organization}/switch', [\App\Http\Controllers\Api\V1\OrganizationController::class, 'switch'])->name('switch');
            Route::apiResource('/{organization}/members', \App\Http\Controllers\Api\V1\OrganizationMemberController::class)->except(['show', 'update']);
            Route::put('/{organization}/members/{member}', [\App\Http\Controllers\Api\V1\OrganizationMemberController::class, 'update'])->name('members.update');
        });

        // Credential domain
        Route::group(['as' => 'credentials.', 'prefix' => 'credentials'], function () {
            Route::apiResource('/', \App\Http\Controllers\Api\V1\CredentialController::class);
            Route::post('/{credential}/rotate', [\App\Http\Controllers\Api\V1\CredentialController::class, 'rotate'])->name('rotate');
        });

        // Workflow domain
        Route::group(['as' => 'workflows.', 'prefix' => 'workflows', 'middleware' => ['workflow']], function () {
            Route::apiResource('/', \App\Http\Controllers\Api\V1\WorkflowController::class);
            Route::get('/{workflow}/executions', [\App\Http\Controllers\Api\V1\WorkflowController::class, 'executions'])->name('executions');
            Route::post('/{workflow}/execute', [\App\Http\Controllers\Api\V1\WorkflowController::class, 'execute'])->name('execute');
            Route::get('/{workflow}/nodes', [\App\Http\Controllers\Api\V1\WorkflowController::class, 'nodes'])->name('nodes');
        });

        // Workflow execution domain
        Route::group(['as' => 'executions.', 'prefix' => 'executions'], function () {
            Route::apiResource('/', \App\Http\Controllers\Api\V1\WorkflowExecutionController::class);
            Route::get('/{execution}/logs', [\App\Http\Controllers\Api\V1\WorkflowExecutionController::class, 'logs'])->name('logs');
        });

        // Node domain
        Route::group(['as' => 'nodes.', 'prefix' => 'nodes'], function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\NodeController::class, 'index'])->name('index');
            Route::get('/{node}/config', [\App\Http\Controllers\Api\V1\NodeController::class, 'config'])->name('config');
        });

        // Admin domain
        Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['role:admin']], function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'index'])->name('dashboard');
            Route::apiResource('/users', \App\Http\Controllers\Api\V1\Admin\UserController::class);
            Route::apiResource('/organizations', \App\Http\Controllers\Api\V1\Admin\OrganizationController::class);
        });
    });
});