<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResignController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ChecklistMasterController;
use App\Http\Controllers\NotificationController;

// Redirect root ke dashboard atau login
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Auth routes (guest only)
Route::middleware('guest.user')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// Authenticated routes
Route::middleware('auth.user')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Resign
    Route::get('/resign', [ResignController::class, 'create'])->name('resign.create');
    Route::post('/resign', [ResignController::class, 'submit'])->name('resign.submit');
    Route::get('/resign/{id}', [ResignController::class, 'detail'])->name('resign.detail');
    Route::get('/resign/{id}/edit', [ResignController::class, 'edit'])->name('resign.edit');
    Route::post('/resign/{id}/update', [ResignController::class, 'update'])->name('resign.update');
    Route::post('/resign/{id}/cancel', [ResignController::class, 'cancel'])->name('resign.cancel');
    Route::get('/resign-view', [ResignController::class, 'listAll'])->name('resign.view');
    Route::get('/resign-list', [ResignController::class, 'listAll'])->name('resign.list');

    // Approval PM
    Route::get('/approval/pm', [ApprovalController::class, 'pmIndex'])->name('approval.pm');
    Route::post('/approval/pm/action', [ApprovalController::class, 'pmAction'])->name('approval.pm.action');
    Route::post('/approval/pm/mark-seen', [ApprovalController::class, 'markPmSeen'])->name('approval.pm.mark-seen');

    // Approval HC
    Route::get('/approval/hc', [ApprovalController::class, 'hcIndex'])->name('approval.hc');
    Route::post('/approval/hc/action', [ApprovalController::class, 'hcAction'])->name('approval.hc.action');
    Route::post('/approval/hc/mark-seen', [ApprovalController::class, 'markHcSeen'])->name('approval.hc.mark-seen');

    // Checklist
    Route::get('/checklist', [ChecklistController::class, 'index'])->name('checklist.index');
    Route::post('/checklist/update', [ChecklistController::class, 'update'])->name('checklist.update');
    Route::post('/checklist/mark-seen', [ChecklistController::class, 'markSeen'])->name('checklist.mark-seen');
    Route::get('/checklist/master', [ChecklistMasterController::class, 'index'])->name('checklist.master.index');
    Route::post('/checklist/master', [ChecklistMasterController::class, 'store'])->name('checklist.master.store');
    Route::post('/checklist/master/{id}/update', [ChecklistMasterController::class, 'update'])->name('checklist.master.update');
    Route::post('/checklist/master/{id}/delete', [ChecklistMasterController::class, 'destroy'])->name('checklist.master.destroy');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

});
