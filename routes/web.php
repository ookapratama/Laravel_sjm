<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    DashboardController,
    ProfileController,
    LandingController,
    PreRegistrationController,
    MidtransController,
    MidtransCallbackController,
    TreeApiController,
    MLMController,
    UserController,
    BonusController,
    MemberController
};
use App\Http\Controllers\Admin\BonusSettingController;
use App\Http\Controllers\Admin\PreRegistrationApprovalController;
use App\Http\Controllers\Auth\ChangeCredentialsController;
use App\Http\Controllers\Admin\WithdrawController as AdminWithdrawController;
use App\Http\Controllers\Member\WithdrawController as MemberWithdrawController;
use App\Http\Controllers\Finance\WithdrawController as FinanceWithdrawController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\AccessController;
use App\Events\MemberCountUpdated;
use App\Models\User;
use App\Models\Notification;
use App\Http\Controllers\ReferralQrController;
use App\Http\Controllers\Admin\PinCtrl as AdminPinCtrl;
use App\Http\Controllers\Finance\PinCtrl as FinancePinCtrl;
use App\Http\Controllers\Member\PinCtrl as MemberPinCtrl;
use App\Http\Controllers\Auth\ReferralRegisterController;

Route::middleware('auth')->group(function () {
    Route::get('/me/ref-qr.png', [ReferralQrController::class, 'png'])->name('member.ref.qr.png');
    Route::get('/me/ref-qr/download', [ReferralQrController::class, 'download'])->name('member.ref.qr.download');
});

Broadcast::routes(['middleware' => ['web', 'auth']]);
Route::middleware('guest')->group(function () {
    // Halaman form
    Route::get('/register', [ReferralRegisterController::class, 'create'])
        ->name('referral.register')
        ->withoutMiddleware(['auth']);

    // Submit form
    Route::post('/register', [ReferralRegisterController::class, 'store'])
        ->name('referral.register.store')
        ->withoutMiddleware(['auth']);
});


Route::post('/notifications/{id}/read', function ($id) {
    $notif = Notification::where('id', $id)
        ->where('user_id', auth()->id())
        ->firstOrFail();

    $notif->update(['is_read' => true]);

    return response()->json(['success' => true]);
})->middleware('auth');

Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    return Broadcast::auth($request);
});
Route::get('/pusher-test', function () {
    $user = User::first(); // Ambil user pertama di database
    if (!$user) {
        return "Tidak ada user di database. Pastikan ada user.";
    }

    return view('pusher-test', ['user' => $user]);
});
Route::middleware(['auth', 'role:super-admin'])->name('management.')->group(function () {
    Route::get('/management', [AccessController::class, 'index'])->name('access.index');
    Route::post('/management/update', [AccessController::class, 'update'])->name('access.update');
});



// ✅ Public Routes
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/produk', [LandingController::class, 'produk'])->name('produk');
Route::get('/tentang', [LandingController::class, 'tentang'])->name('tentang');

Route::get('/api/member-count', fn() => response()->json(['count' => \App\Models\User::where('role', 'member')->count()]));
Route::get('/check-payment-status/{orderId}', function ($orderId) {
    if (!preg_match('/^REG-[A-Z0-9]{10}$/', $orderId)) return response()->json(['status' => 'invalid']);
    $reg = \App\Models\PreRegistration::where('payment_proof', $orderId)->first();
    return response()->json(['status' => $reg?->status ?? 'not_found']);
});
Route::post('/midtrans/callback', [MidtransCallbackController::class, 'handle']);

//✅ Pre-registration
Route::get('/pre-register', [PreRegistrationController::class, 'create'])->name('pre-register.form');
Route::post('/pre-register', [PreRegistrationController::class, 'store'])->name('pre-register.store');

Route::get('/pre-registration/qris', [PreRegistrationController::class, 'showQris'])->name('preregistration.qris');

Route::middleware(['auth', 'role:finance'])->group(function () {
    Route::get('/finance/pre-registrations', [\App\Http\Controllers\Finance\PreRegistrationVerificationController::class, 'index'])->name('finance.pre-registrations');
    Route::post('/finance/verify-payment/{id}', [\App\Http\Controllers\Finance\PreRegistrationVerificationController::class, 'verify'])->name('finance.verify.payment');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/pre-register', [PreRegistrationController::class, 'create'])->name('pre-register.form');
    Route::post('/approve-member/{id}', [PreRegistrationApprovalController::class, 'approve'])->name('approve-member');
    Route::get('/pin-requests', [AdminPinCtrl::class, 'index'])->name('admin.pin.index');
    Route::post('/pin-requests/{id}/generate', [AdminPinCtrl::class, 'generate'])->name('pin.generate');
});

// ✅ Admin
Route::prefix('admin')->middleware('role:admin,super-admin')->group(function () {
    Route::get('/', [DashboardController::class, 'admin'])->name('admin');

    Route::get('/withdraws', [AdminWithdrawController::class, 'index'])->name('withdraws.index');
    Route::put('admin/withdraws/approve/{id}', [AdminWithdrawController::class, 'approve'])->name('withdraws.approve');
    Route::put('admin/withdraws/{id}/reject', [AdminWithdrawController::class, 'reject'])->name('withdraws.reject');
});


// ✅ Authenticated Only
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/redirect', function () {
        return match (Auth::user()->role) {
            'super-admin' => redirect()->route('super-admin'),
            'admin' => redirect()->route('admin'),
            'finance' => redirect()->route('finance'),
            'member' => redirect()->route('member'),
            default => redirect('/')
        };
    })->name('redirect');

    Route::middleware(['auth'])->prefix('profile')->group(function () {
        Route::get('/', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
        Route::post('/update', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    });

    // ✅ Change Credentials
    Route::get('/change-credentials', [ChangeCredentialsController::class, 'edit'])->name('change.credentials');
    Route::post('/change-credentials', [ChangeCredentialsController::class, 'update'])->name('change.credentials.update');

    // ✅ Super Admin
    Route::prefix('super-admin')->middleware('role:super-admin')->group(function () {
        Route::get('/', [DashboardController::class, 'superAdmin'])->name('super-admin');
    });
    Route::prefix('bonus-settings')->middleware('role:super-admin')->group(function () {
        Route::get('/json', [BonusSettingController::class, 'json']);
        Route::get('/', [BonusSettingController::class, 'index']);
        Route::get('/{id}', [BonusSettingController::class, 'show']);
        Route::post('/', [BonusSettingController::class, 'store']);
        Route::put('/{id}', [BonusSettingController::class, 'update']);
        Route::delete('/{id}', [BonusSettingController::class, 'destroy']);
    });

    // ✅ Admin
    Route::prefix('admin')->middleware('role:admin,super-admin')->group(function () {
        Route::get('/', [DashboardController::class, 'admin'])->name('admin');
        Route::get('/withdraw', [MemberWithdrawController::class, 'index'])->name('member.withdraw');
        Route::post('/withdraw', [MemberWithdrawController::class, 'store'])->name('member.withdraw.store');
        Route::get('/withdraws', [AdminWithdrawController::class, 'index'])->name('withdraws.index');
        Route::put('admin/withdraws/approve/{id}', [AdminWithdrawController::class, 'approve'])->name('withdraws.approve');
        Route::put('admin/withdraws/{id}/reject', [AdminWithdrawController::class, 'reject'])->name('withdraws.reject');
    });

    // ✅ Finance
    Route::prefix('finance')->middleware('role:finance')->group(function () {
        Route::get('/statistics', [FinanceController::class, 'index'])->name('finance.statistics.index');
        Route::get('/', [DashboardController::class, 'finance'])->name('finance');
        Route::get('withdraws', [FinanceWithdrawController::class, 'index'])->name('finance.withdraws.index');
        Route::put('/withdraws/{id}/process', [FinanceWithdrawController::class, 'process'])->name('finance.withdraws.process');
        Route::get('/cashflow', [FinanceController::class, 'cashflowSummary'])->name('finance.cashflow');
        Route::get('/bonus-rekap', [FinanceController::class, 'rekapBonus'])->name('finance.bonus-rekap');
        Route::get('/poin-reward', [FinanceController::class, 'poinReward'])->name('finance.poin-reward');
        Route::get('/target-vs-actual', [FinanceController::class, 'targetPendaftaran'])->name('finance.target');
        Route::get('/growth-chart', [FinanceController::class, 'growthChart'])->name('finance.growth');
        Route::get('/top-bonus', [FinanceController::class, 'topBonus'])->name('finance.topbonus');
        Route::get('/pin-requests', [FinancePinCtrl::class, 'index'])->name('finance.pin.index');
        Route::put('/pin-requests/{id}/approve', [FinancePinCtrl::class, 'approve'])->name('finance.pin.approve');
        Route::put('/pin-requests/{id}/reject', [FinancePinCtrl::class, 'reject'])->name('finance.pin.reject');
    });

    // ✅ Member
    Route::prefix('member')->middleware('role:member')->group(function () {
        Route::get('/pins', [MemberPinCtrl::class, 'index'])->name('member.pin.index');
        Route::post('/pins/request', [MemberPinCtrl::class, 'store'])->name('member.pin.request');
        Route::get('/', [DashboardController::class, 'member'])->name('member');
        Route::get('/withdraw', [MemberWithdrawController::class, 'index'])->name('member.withdraw');
        Route::post('/withdraw', [MemberWithdrawController::class, 'store'])->name('member.withdraw.store');
        Route::get('/withdraw/bonus', [MemberWithdrawController::class, 'getBonusAvailable'])->name('member.withdraw.bonus');
        Route::get('/withdraw/history', [MemberWithdrawController::class, 'history'])->name('member.withdraw.history');
        // Bagan Upgrade

        Route::post('/bagan/cek-saldo/{bagan}', [MemberController::class, 'cekSaldo']);
        Route::post('/bagan/upgrade/{bagan}', [MemberController::class, 'upgradeBagan'])->name('member.bagan.upgrade');
    });
    Route::middleware(['auth', 'role:member'])->get('/member/pins/status', function () {
        $open = \App\Models\PinRequest::where('requester_id', auth()->id())
            ->whereIn('status', ['requested', 'finance_approved'])->exists();
        return response()->json(['hasOpen' => $open]);
    })->name('member.pin.status');

    // ✅ Bonus
    Route::get('/bonus', [BonusController::class, 'index'])->name('bonus.index');

    // ✅ Users (Data Member)
    Route::prefix('data-member')->middleware('role:super-admin,member,admin')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::post('/', [UserController::class, 'store'])->name('users.store');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // ✅ Tree & Binary MLM
    Route::get('/tree', [MLMController::class, 'tree'])->name('tree.index');
    Route::get('/tree/node/{id}', [MLMController::class, 'getNode']);
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/ajax/{id}', [MLMController::class, 'ajax']);
    Route::get('/tree/load/{id}', [MLMController::class, 'loadTree']);
    Route::get('/tree/search', [MLMController::class, 'searchDownline']);
    Route::get('/tree/available-users/{id}', [MLMController::class, 'getAvailableUsers']);
    Route::put('/tree/{id}', [UserController::class, 'update'])->name('users.update');
    Route::get('/tree/parent/{id}', [MLMController::class, 'parentId']);
});

// ✅ Tree API (Public)
Route::get('tree/root', [TreeApiController::class, 'getRoot']);
Route::get('tree/children/{id}', [TreeApiController::class, 'getChildren']);

// ✅ Auth Routes
require __DIR__ . '/auth.php';
