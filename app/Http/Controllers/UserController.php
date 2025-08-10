<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Events\MemberCountUpdated;
use App\Services\BonusManager;
use DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessPairingJob;
use App\Events\UserNotificationReceived;
class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['sponsor', 'upline'])->latest()->get();
        return view('users.index', compact('users'));
        
    }

public function store(Request $request)
{
   try {
    if ($request->upline_id) {
        $upline = User::find($request->upline_id);

        $usedPositions = User::where('upline_id', $upline->id)->pluck('position')->toArray();

        $autoPosition = null;
        if (!in_array('left', $usedPositions)) {
            $autoPosition = 'left';
        } elseif (!in_array('right', $usedPositions)) {
            $autoPosition = 'right';
        }

        $position = $request->position ?? $autoPosition;

        if (!$position) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'upline_id' => ['Upline sudah penuh. Silakan pilih upline lain.']
                ]
            ], 422);
        }

        if (in_array($position, $usedPositions)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'upline_id' => ['Posisi "' . $position . '" sudah diisi.']
                ]
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users',
            'password' => 'nullable|confirmed|min:3',
            'sponsor_id' => 'nullable|exists:users,id',
            'upline_id' => 'required|exists:users,id',
            'position' => 'nullable|in:left,right',
            'joined_at' => 'nullable|date',
            'tax_id' => 'nullable',
            'bank_account' => 'nullable',
            'address' => 'nullable',
            'level' => 'nullable',
            'no_telp' => 'nullable',

        ]);

        $validated['position'] = $position;
    } else {
        $validated = $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users',
            'password' => 'nullable|confirmed|min:3',
            'sponsor_id' => 'nullable|exists:users,id',
            'upline_id' => 'required|exists:users,id',
            'position' => 'nullable|in:left,right',
            'joined_at' => 'nullable|date',
            'tax_id' => 'nullable',
            'bank_account' => 'nullable',
            'level' => 'nullable',
            'address' => 'nullable',
            'no_telp' => 'nullable',

        ]);
    }

    // Handle password hanya jika diisi
    if (!empty($validated['password'])) {
        $validated['password'] = bcrypt($validated['password']);
    } else {
        $validated['password'] = bcrypt('123456'); // atau generate random default
    }

    $validated['level'] = 1;

    DB::beginTransaction();

    $user = User::create($validated);

    if ($user->upline) {
        Log::info("Upline: " . ($user->upline->username ?? '-') . " | Posisi: " . $user->position);
        $user->upline->incrementLegCount($user->position);
    }
// Notifikasi ke sponsor (referral)
if ($user->sponsor_id) {
    $sponsor = $user->sponsor;
    $notif = [
        'type' => 'new_referral',
        'message' => "Member baru: {$user->name} bergabung melalui referral Anda.",
        'url' => route('members.show', $user->id),
        'created_at' => now()->toDateTimeString(),
    ];
    event(new UserNotificationReceived($sponsor->id, $notif));
}
    // Proses bonus + refresh count up ke atas
$bonusManager = new BonusManager();

$current = $user;
while ($current) {
    $bonusManager->processPairing($current);
    $current->refreshChildCounts();
    $current = $current->upline;
}

    DB::commit();
//BonusManager::processPairing($user);

    ProcessPairingJob::dispatch($user);
    // Trigger event jumlah member
       $totalMembers = User::count();
event(new MemberCountUpdated($totalMembers));

    return response()->json([
        'message' => 'User berhasil disimpan',
        'user_id' => $user->id
    ]);
} catch (\Exception $e) {
    DB::rollBack();
    \Log::error('Gagal menyimpan user: ' . $e->getMessage());
    return response()->json([
        'message' => 'Terjadi kesalahan saat menyimpan',
        'error' => $e->getMessage()
    ], 500);
}
}
public function edit($id) {
    return response()->json(User::findOrFail($id));
}

public function update(Request $request, $id)
{
    $validated = $request->validate([
        'upline_id' => 'required|exists:users,id',
        'position' => 'required|in:left,right',
    ]);

    DB::beginTransaction();

    try {
        $user = User::findOrFail($request->input('user_id'));

        $user->upline_id = $validated['upline_id'];
        $user->position = $validated['position'];
        $user->save();

        // ✅ Ganti ini:
        $bonusManager = new BonusManager();
        $bonusManager->assignToUpline($user, $user->upline, $user->position);

        DB::commit();

        ProcessPairingJob::dispatch($user); // Jika ingin tetap asynchronous

        return response()->json([
            'message' => 'User berhasil dipasang ke tree dan pairing diproses.',
            'id' => $user->id,
            'name' => $user->username,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('❌ Gagal update dan pasang user', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'message' => 'Terjadi kesalahan.',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function destroy($id)
{
    $user = User::findOrFail($id);
    $user->delete();

  $totalMembers = User::count();
    event(new MemberCountUpdated($totalMembers));

    return response()->json(['message' => 'User deleted']);
}

}

