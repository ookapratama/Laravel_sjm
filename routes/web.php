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
use App\Http\Controllers\Super\SuperWithdrawController as SuperWithdrawController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\AccessController;
use App\Events\MemberCountUpdated;
use App\Events\NotificationService;
use App\Http\Controllers\Admin\PackageController;
use App\Models\User;
use App\Models\Notification;
use App\Http\Controllers\ReferralQrController;
use App\Http\Controllers\Admin\PinCtrl as AdminPinCtrl;
use App\Http\Controllers\Admin\ProdukController;
use App\Http\Controllers\Admin\TransactionPackageController;
use App\Http\Controllers\Finance\PinCtrl as FinancePinCtrl;
use App\Http\Controllers\Member\PinCtrl as MemberPinCtrl;
use App\Http\Controllers\Auth\ReferralRegisterController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\GuestEntryController;

use App\Http\Controllers\GuestbookController;
use App\Models\PinRequest;
use Illuminate\Support\Facades\Broadcast;

Route::middleware('auth')->group(function () {
    Route::get('member/guestbook',        [GuestbookController::class, 'index'])->name('guestbook.index');
    Route::get('member/guestbook/export', [GuestbookController::class, 'export'])->name('guestbook.export');
});

Route::middleware('auth')->group(function () {
    Route::get('/member/invitations',                 [InvitationController::class, 'index'])->name('inv.index');
    Route::get('/member/invitations/create',          [InvitationController::class, 'create'])->name('inv.create');
    Route::post('/member/invitations',                [InvitationController::class, 'store'])->name('inv.store');
    Route::get('/member/invitations/{invitation}/edit', [InvitationController::class, 'edit'])->name('inv.edit');
    Route::put('/member/invitations/{invitation}',    [InvitationController::class, 'update'])->name('inv.update');
    Route::get('/member/invitations/{invitation}/qr', [InvitationController::class, 'qr'])->name('inv.qr');
});
Route::get('/i/{slug}/r/{ref}', function (string $slug, string $ref) {
    return redirect()->to(route('inv.public', $slug) . '?ref=' . $ref, 302);
})->name('inv.public.ref');
Route::get('/i/{slug}',                  [InvitationController::class, 'publicShow'])->name('inv.public');
Route::get('/i/{slug}/g',                [GuestEntryController::class, 'form'])->name('guest.form.inv');
Route::post('/i/{slug}/g',               [GuestEntryController::class, 'store'])->name('guest.store.inv');
Route::get('/i/{slug}/thanks',           [GuestEntryController::class, 'thanks'])->name('guest.thanks.inv');

// ambil QR ulang
Route::get('/inv/{invitation:slug}/my-qr',  [GuestEntryController::class, 'myQrForm'])->name('guest_entries.myqr.form');
Route::post('/inv/{invitation:slug}/my-qr',  [GuestEntryController::class, 'myQrFetch'])->name('guest_entries.myqr.fetch');

// check-in (signed)
Route::get(
    '/inv/{invitation:slug}/entry/{entry}/check-in',
    [GuestEntryController::class, 'checkInScan']
)->name('guest_entries.scan_checkin')->middleware(['signed', 'throttle:30,1']);

Route::middleware(['auth'])->group(function () {
    Route::get('/pins/unused', [\App\Http\Controllers\TreeCloneController::class, 'unusedPins'])->name('pins.unused');
    Route::get('/tree/clone/preview', [\App\Http\Controllers\TreeCloneController::class, 'preview'])->name('tree.clone.preview');
    Route::post('/tree/clone', [\App\Http\Controllers\TreeCloneController::class, 'store'])->name('tree.clone.store');
    Route::middleware(['auth'])->group(function () {
        Route::get('/tree/available-users/{id}/count', [\App\Http\Controllers\MLMController::class, 'getAvailableUsersCount']);
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/me/ref-qr.png', [ReferralQrController::class, 'png'])->name('member.ref.qr.png');
    Route::get('/me/ref-qr/download', [ReferralQrController::class, 'download'])->name('member.ref.qr.download');
    Route::get('/inv-qr/{slug}', [ReferralQrController::class, 'invitationPng'])->name('inv.qr.show');
    Route::get('/inv-qr/{slug}/download', [ReferralQrController::class, 'invitationDownload'])->name('inv.qr.dl');
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

    // verifikasi username
    Route::post('/check-username', [ReferralRegisterController::class, 'checkUsername'])->name('check.username');
    Route::post('/check-pin', [ReferralRegisterController::class, 'checkPin'])->name('check.pin');
    Route::post('/check-sponsor', [ReferralRegisterController::class, 'checkSponsor'])->name('check.sponsor');
    Route::post('/check-whatsapp', [ReferralRegisterController::class, 'checkWhatsApp'])->name('check.whatsapp');
});


Route::post('/notifications/{id}/read', function ($id) {
    $notif = Notification::where('id', $id)
        ->where('user_id', auth()->id())
        ->firstOrFail();

    $notif->update(['is_read' => true]);

    return response()->json(['success' => true]);
})->middleware('auth');

Route::post('/notifications/mark-all-read', function () {
    $updated = Notification::where('user_id', auth()->id())
        ->where('is_read', false)
        ->update(['is_read' => true]);

    return response()->json([
        'success' => true,
        'marked_count' => $updated
    ]);
})->middleware('auth');

Route::get('/test-notification/{userId}', function ($userId) {
    try {
        NotificationService::sendNotification(
            $userId,
            'test_notification',
            'Ini adalah notifikasi test',
            route('home'),
            ['test' => true]
        );

        return response()->json(['message' => 'Test notification sent']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
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

    Route::prefix('products')->group(function () {
        Route::get('/', [ProdukController::class, 'index'])->name('products.index');
        Route::post('/store', [ProdukController::class, 'store'])->name('products.store');
        Route::get('/{id}/edit', [ProdukController::class, 'edit'])->name('products.edit');
        Route::put('/update/{id}', [ProdukController::class, 'update'])->name('products.update');
        Route::delete('/delete/{id}', [ProdukController::class, 'destroy'])->name('products.destroy');

        // kelola paket
        Route::get('/manage-package', [PackageController::class, 'index'])->name('products.manage-package');
        Route::post('/manage-package', [PackageController::class, 'update'])->name('products.manage-package.update');

        Route::get('/transaction-packages', [TransactionPackageController::class, 'index'])->name('products.transaction.index');
        Route::get('/assigned', [TransactionPackageController::class, 'assignedPins'])->name('product.transaction.assigned');
        Route::post('/bulk-assign-package', [TransactionPackageController::class, 'bulkAssignPackage'])->name('product.transaction.bulk-assign-package');

        Route::post('/{id}/transaction-packages', [TransactionPackageController::class, 'assignPackage'])->name('products.transaction.assigned');
        Route::delete('/{pin}/unassign-package', [TransactionPackageController::class, 'unassignPackage'])->name('product.transaction.unassign-package');
        Route::get('stats', [TransactionPackageController::class, 'getDashboardStats'])->name('product.transaction.stats');
    });

    Route::get('packages/{package}/preview', [TransactionPackageController::class, 'getPackagePreview'])->name('packages.preview');


    Route::resource('packages', PackageController::class);

    // Additional package routes
    Route::patch('packages/{package}/toggle-status', [PackageController::class, 'toggleStatus'])
        ->name('packages.toggle-status');
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

        Route::get('/', [ProfileController::class, 'index'])->name('profile.index');
        Route::post('/update', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/update-photo', [ProfileController::class, 'updatePhoto'])->name('profile.update.photo');
        Route::post('/profile/update-password', [ProfileController::class, 'updatePassword'])
            ->name('profile.update-password');
    });

    // ✅ Change Credentials
    Route::get('/change-credentials', [ChangeCredentialsController::class, 'edit'])->name('change.credentials');
    Route::post('/change-credentials', [ChangeCredentialsController::class, 'update'])->name('change.credentials.update');

    // ✅ Super Admin
    Route::prefix('super-admin')->middleware('role:super-admin')->group(function () {
        Route::get('/', [DashboardController::class, 'superAdmin'])->name('super-admin');
        Route::get('/withdraw', [SuperWithdrawController::class, 'index'])->name('super.withdraw');
        Route::post('/withdraw', [SuperWithdrawController::class, 'store'])->name('super.withdraw.store');
        Route::get('/withdraw/bonus', [SuperWithdrawController::class, 'getBonusAvailable'])->name('super.withdraw.bonus');
        Route::get('/withdraw/history', [SuperWithdrawController::class, 'history'])->name('super.withdraw.history');
        Route::post('/withdraw/drain/{group}', [SuperWithdrawController::class, 'drainGroup'])
            ->name('super.withdraw.drain');
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

        Route::get('/report', [FinanceController::class, 'withdrawalHistoryPage']);
        Route::get('/users/search', [FinanceController::class, 'searchUsers']);
        Route::get('/users/{id}/withdrawal-history', [FinanceController::class, 'getUserWithdrawalHistory']);
        Route::get('/withdraws/{id}/detail', [FinanceController::class, 'getWithdrawalDetail']);

        // Routes baru
        Route::get('/all-withdrawals', [FinanceController::class, 'getAllWithdrawals']);
        Route::get('/today-stats', [FinanceController::class, 'getTodayStats']);

        Route::get('/pin-requests', [FinancePinCtrl::class, 'index'])->name('finance.pin.index');
        Route::put('/pin-requests/{id}/approve', [FinancePinCtrl::class, 'approve'])->name('finance.pin.approve');
        Route::put('/pin-requests/{id}/reject', [FinancePinCtrl::class, 'reject'])->name('finance.pin.reject');
        Route::get('/cash-report', [FinanceController::class, 'cashReport'])->name('finance.cash.report');
        Route::get('/cash-report/data', [FinanceController::class, 'cashReportData'])->name('finance.cash.report.data');
        Route::post('/expenses', [FinanceController::class, 'storeOtherExpense'])->name('finance.expense.store');
    });

    // ✅ Member
    Route::prefix('member')->middleware('role:member,super-admin')->group(function () {
        Route::get('/downline', [UserController::class, 'index'])->name('users.downline');
        Route::post('/register', [UserController::class, 'store_member'])->name('users.downline.store');

        Route::get('/pins', [MemberPinCtrl::class, 'index'])->name('member.pin.index');
        Route::post('/pins/request', [MemberPinCtrl::class, 'store'])->name('member.pin.request');
        Route::post('/transfer', [MemberPinCtrl::class, 'transfer'])->name('member.pin.transfer');
        Route::post('/pin/bulk-transfer', [MemberPinCtrl::class, 'bulkTransfer'])->name('member.pin.bulk-transfer');
        Route::get('/pin/available-for-bulk', [MemberPinCtrl::class, 'getAvailablePins'])->name('member.pin.available-for-bulk');

        Route::get('/', [DashboardController::class, 'member'])->name('member');
        Route::get('/withdraw', [MemberWithdrawController::class, 'index'])->name('member.withdraw');
        Route::post('/withdraw', [MemberWithdrawController::class, 'store'])->name('member.withdraw.store');
        Route::get('/withdraw/bonus', [MemberWithdrawController::class, 'getBonusAvailable'])->name('member.withdraw.bonus');
        Route::get('/withdraw/history', [MemberWithdrawController::class, 'history'])->name('member.withdraw.history');
        // Bagan Upgrade

        Route::post('/bagan/cek-saldo/{bagan}', [MemberController::class, 'cekSaldo']);
        Route::post('/bagan/upgrade/{bagan}', [MemberController::class, 'upgradeBagan'])->name('member.bagan.upgrade');

        Route::post('/check-username', [ReferralRegisterController::class, 'checkUsername'])->name('check.username');
        Route::post('/check-pin', [ReferralRegisterController::class, 'checkPin'])->name('check.pin');
        Route::post('/check-sponsor', [ReferralRegisterController::class, 'checkSponsor'])->name('check.sponsor');
        Route::post('/check-whatsapp', [ReferralRegisterController::class, 'checkWhatsApp'])->name('check.whatsapp');
    });
    Route::middleware(['auth', 'role:member'])->get('/member/pins/status', function () {
        $open = PinRequest::where('requester_id', auth()->id())
            ->whereIn('status', ['requested', 'finance_approved'])->exists();
        return response()->json(['hasOpen' => $open]);
    })->name('member.pin.status');

    // ✅ Bonus
    Route::get('/bonus', [BonusController::class, 'index'])->name('bonus.index');

    // ✅ Users (Data Member)
    Route::prefix('data-member')->middleware('role:super-admin,admin')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::post('/', [UserController::class, 'store'])->name('users.store');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });
    Route::middleware('role:super-admin,finance')->group(function () {
        Route::get('/tree-master', [MLMController::class, 'master'])->name('master.index');
    });
    // ✅ Tree & Binary MLM
    Route::prefix('')->middleware(['auth', 'tree.access'])->group(function () {
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
});

// ✅ Tree API (Public)
Route::get('tree/root', [TreeApiController::class, 'getRoot']);
Route::get('tree/children/{id}', [TreeApiController::class, 'getChildren']);

// ✅ Auth Routes
require __DIR__ . '/auth.php';
