<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class MLMController extends Controller
{
    public function tree()
    {
        $root = User::find(auth()->id()); // Atau super admin
        return view('mlm.tree', compact('root'));
    }

    public function master()
    {
        $root = User::find(1); // Atau super admin
        return view('mlm.tree-master', compact('root'));
    }

    public function ajax($id)
    {
        $user = User::with(['left', 'right'])->findOrFail($id);
        return view('users.detail', compact('user'));
    }
    public function parentId($id)
    {
        $user = User::select('upline_id')->findOrFail($id);
        return response()->json(['id' => $user->upline_id]); // null jika root
    }
    public function getAvailableUsers($id)
    {
        try {
            $users = User::whereNull('position')
                ->where('sponsor_id', $id)
                ->select('id', 'username', 'name', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(30)
                ->get();

            return response()->json($users);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    public function getAvailableUsersCount($id)
    {
        // Pending = belum ditempel (upline_id null), milik sponsor {id}
        $count = User::whereNull('position')
            ->where('sponsor_id', $id)     // kalau kamu pakai sponsor_code, sesuaikan
            ->count();

        return response()->json(['count' => $count]);
    }
    public function loadTree(Request $request, $id)
    {
        try {
            //code...
            $max = $request->get('limit', 3); // default 7 level
            $user = User::findOrFail($id);
            $tree = $this->buildTreeRecursive($user, 1, $max);
            return response()->json($tree);
        } catch (\Exception $e) {
            //throw $th;
            \Log::error('Gagal menyimpan user: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function buildTreeRecursive($user, $level = 1, $max = 7)
    {
        if (!$user || $level > $max) return null;

        // eager-load bagans untuk node ini
        if (!$user->relationLoaded('bagans')) {
            $user->load(['bagans:id,user_id,bagan,is_active']);
        }

        // ambil anak kiri/kanan + eager bagans juga
        $left  = $user->leftChild()->with('bagans:id,user_id,bagan,is_active')->first();
        $right = $user->rightChild()->with('bagans:id,user_id,bagan,is_active')->first();

        $leftCount  = $left  ? $this->countAllDownlines($left)  : 0;
        $rightCount = $right ? $this->countAllDownlines($right) : 0;

        // ===== hitung star dari user_bagans =====
        [$starCount, $activeBagans, $baganFlags] = $this->computeStar($user);

        $children = [];

        if ($left) {
            $childLeft = $this->buildTreeRecursive($left, $level + 1, $max);
            if ($childLeft !== null) $children[] = $childLeft;
        } else {
            $children[] = array_merge([
                'id'         => 'add-' . $user->id . '-left',
                'name'       => 'Tambah',
                'isAddButton' => true,
                'position'   => 'left',
                'parent_id'  => $user->id,
                'children'   => [],
                'left_count' => $leftCount,
                'right_count' => $rightCount,
                'photo' => $user->photo,
                // kirim juga info star untuk konsistensi UI (opsional)
                'star_count'     => $starCount,
                'active_bagans'  => $activeBagans,
            ], $baganFlags);
        }

        if ($right) {
            $childRight = $this->buildTreeRecursive($right, $level + 1, $max);
            if ($childRight !== null) $children[] = $childRight;
        } else {
            $children[] = array_merge([
                'id'         => 'add-' . $user->id . '-right',
                'name'       => 'Tambah',
                'isAddButton' => true,
                'position'   => 'right',
                'parent_id'  => $user->id,
                'children'   => [],
                'photo' => $user->photo,
                'left_count' => $leftCount,
                'right_count' => $rightCount,
                'star_count'     => $starCount,
                'active_bagans'  => $activeBagans,
            ], $baganFlags);
        }

        return array_merge([
            'id'            => $user->id,
            'name'          => $user->username,
            'status'        => $user->is_active ? 'aktif' : 'tidak aktif',
            'pairing_count' => $user->pairing_count ?? 0,
            'voucher'       => $user->voucher ?? 0,
            'position'      => $user->position ?? 0,
            'children'      => $children,
            'left_count'    => $leftCount,
            'right_count'   => $rightCount,
            'photo' => $user->photo,
            // ⭐️ data berbasis user_bagans
            'star_count'    => $starCount,         // angka
            'active_bagans' => $activeBagans,      // [1,2,3,...]
        ], $baganFlags);                            // is_active_bagan_1..5 (kompat)
    }

    /**
     * Hitung star dari relasi bagans
     * return [starCount, activeBagans[], baganFlags[]]
     */
    private function computeStar(\App\Models\User $user): array
    {
        // pastikan 'bagans' sudah ada
        if (!$user->relationLoaded('bagans')) {
            $user->load(['bagans:id,user_id,bagan,is_active']);
        }

        $active = $user->bagans
            ->where('is_active', true)
            ->sortBy('bagan')
            ->pluck('bagan')
            ->values()
            ->all();

        $starCount = count($active); // atau min(count($active), 5) kalau mau batasi tampilan

        // untuk kompat UI lama
        $flags = [];
        for ($i = 1; $i <= 5; $i++) {
            $flags["is_active_bagan_{$i}"] = in_array($i, $active) ? 1 : 0;
        }

        return [$starCount, $active, $flags];
    }
    private function countAllDownlines(User $user): int
    {
        $count = 1; // hitung diri sendiri

        $left = User::where('upline_id', $user->id)->where('position', 'left')->first();
        $right = User::where('upline_id', $user->id)->where('position', 'right')->first();
        if ($left) $count += $this->countAllDownlines($left);
        if ($right) $count += $this->countAllDownlines($right);

        return $count;
    }

    public function searchDownline(Request $request)
    {
        $keyword = trim((string) $request->query('query', ''));
        if ($keyword === '') return response()->json([]);

        $users = User::select('id', 'username', 'name')
            ->where(function ($w) use ($keyword) {
                if (ctype_digit($keyword)) $w->orWhere('id', (int)$keyword);
                $w->orWhere('username', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            })
            ->orderByRaw("
            CASE 
              WHEN id = ? THEN 0
              WHEN username LIKE ? THEN 1
              WHEN name LIKE ? THEN 2
              ELSE 3
            END, id DESC
        ", [(int)$keyword, "{$keyword}%", "{$keyword}%"])
            ->limit(10)
            ->get();

        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('users.show', compact('user'));
    }

    public function getNode($id)
    {
        $user = User::findOrFail($id);

        $left = $user->getLeftChild();
        $right = $user->getRightChild();

        return response()->json([
            'left' => $left ? [
                'id' => $left->id,
                'name' => $left->name,
                'position' => $left->position,
                'status' => $left->is_active ? 'Aktif' : 'Tidak Aktif',
                'has_children' => $left->hasAnyChild(),
            ] : null,
            'right' => $right ? [
                'id' => $right->id,
                'name' => $right->name,
                'position' => $right->position,
                'status' => $right->is_active ? 'Aktif' : 'Tidak Aktif',
                'has_children' => $right->hasAnyChild(),
            ] : null,
        ]);
    }
}
