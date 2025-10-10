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
    'middleware' => ['api'],
], function () {
    // Public authentication routes
    Route::group(['as' => 'auth.'], function () {
        Route::post('/register', [\App\Domains\Auth\Http\Controllers\V1\RegisterController::class, 'register'])->name('register');
        Route::post('/login', [\App\Domains\Auth\Http\Controllers\V1\LoginController::class, 'login'])->name('login');
        Route::post('/forgot-password', [\App\Domains\Auth\Http\Controllers\V1\PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::post('/reset-password', [\App\Domains\Auth\Http\Controllers\V1\PasswordResetController::class, 'reset'])->name('password.store');
    });

    // Protected routes (require authentication)
    Route::middleware(['auth:api'])->group(function () {
        // Authentication domain (protected)
        Route::group(['as' => 'auth.'], function () {
            Route::post('/logout', [\App\Domains\Auth\Http\Controllers\V1\LoginController::class, 'logout'])->name('logout');
            Route::get('/user', [\App\Domains\Auth\Http\Controllers\V1\LoginController::class, 'me'])->name('user');
            Route::get('/profile', [\App\Domains\User\Http\Controllers\V1\UserProfileController::class, 'index'])->name('profile');
            Route::post('/refresh', [\App\Domains\Auth\Http\Controllers\V1\LoginController::class, 'refresh'])->name('refresh');
            Route::post('/email/verification-notification', [\App\Domains\Auth\Http\Controllers\V1\EmailVerificationController::class, 'resend'])->name('verification.send');
            Route::get('/verify-email/{id}/{hash}', [\App\Domains\Auth\Http\Controllers\V1\EmailVerificationController::class, 'verify'])->name('verification.verify')->middleware('signed');
        });

        // User management domain
        Route::group(['as' => 'user.', 'prefix' => 'user'], function () {
            Route::get('/profile', [\App\Domains\User\Http\Controllers\V1\UserProfileController::class, 'index'])->name('profile.show');
            Route::put('/profile', [\App\Domains\User\Http\Controllers\V1\UserProfileController::class, 'update'])->name('profile.update');
        });

        // Organization domain

        Route::group(['as' => 'organizations.', 'middleware' => ['organization']], function () {
            Route::apiResource('organizations', \App\Domains\Organization\Http\Controllers\V1\OrganizationController::class);
            Route::post('organizations/{organization}/switch', [\App\Domains\Organization\Http\Controllers\V1\OrganizationController::class, 'switch'])->name('switch');
            Route::apiResource('organizations/{organization}/members', \App\Domains\Organization\Http\Controllers\V1\OrganizationMemberController::class)->except(['show', 'update']);
            Route::put('organizations/{organization}/members/{member}', [\App\Domains\Organization\Http\Controllers\V1\OrganizationMemberController::class, 'update'])->name('members.update');
        });

        // Credential domain
        Route::group(['as' => 'credentials.', 'prefix' => 'credentials'], function () {
            Route::apiResource('/', \App\Domains\Auth\Http\Controllers\V1\CredentialController::class);
            Route::post('/{credential}/rotate', [\App\Domains\Auth\Http\Controllers\V1\CredentialController::class, 'rotate'])->name('rotate');
        });

        // Workflow domain
        Route::group(['as' => 'workflows.', 'prefix' => 'workflows', 'middleware' => ['workflow']], function () {
            Route::apiResource('/', \App\Domains\Workflow\Http\Controllers\V1\WorkflowController::class);
            Route::get('/{workflow}/executions', [\App\Domains\Workflow\Http\Controllers\V1\WorkflowController::class, 'executions'])->name('executions');
            Route::post('/{workflow}/execute', [\App\Domains\Workflow\Http\Controllers\V1\WorkflowController::class, 'execute'])->name('execute');
            Route::get('/{workflow}/nodes', [\App\Domains\Workflow\Http\Controllers\V1\WorkflowController::class, 'nodes'])->name('nodes');
        });

        // Workflow execution domain
        Route::group(['as' => 'executions.', 'prefix' => 'executions'], function () {
            Route::apiResource('/', \App\Domains\Workflow\Http\Controllers\V1\WorkflowExecutionController::class);
            Route::get('/{execution}/logs', [\App\Domains\Workflow\Http\Controllers\V1\WorkflowExecutionController::class, 'logs'])->name('logs');
        });

        // Node domain
        Route::group(['as' => 'nodes.', 'prefix' => 'nodes'], function () {
            Route::get('/', [\App\Domains\Workflow\Http\Controllers\V1\NodeController::class, 'index'])->name('index');
            Route::get('/{node}/config', [\App\Domains\Workflow\Http\Controllers\V1\NodeController::class, 'config'])->name('config');
        });

        // Admin domain
        Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['role:admin']], function () {
            Route::get('/dashboard', [\App\Domains\Auth\Http\Controllers\V1\Admin\DashboardController::class, 'index'])->name('dashboard');
            Route::apiResource('/users', [\App\Domains\Auth\Http\Controllers\V1\Admin\UserController::class]);
            Route::apiResource('/organizations', [\App\Domains\Auth\Http\Controllers\V1\Admin\OrganizationController::class]);
        });
    });
});
