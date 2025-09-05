<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MLMController extends Controller
{
    public function tree()
    {
        $user = auth()->user();

        // Untuk member, mulai dari diri sendiri
        // Untuk admin/super_admin, mulai dari root yang ditentukan
        if ($user->role === 'member') {
            $root = $user;
        } else {
            $root = User::find($user->id); // atau bisa User::find(1) untuk super admin
        }

        return view('mlm.tree', compact('root'));
    }

    public function master()
    {
        $user = auth()->user();

        // Hanya admin dan super_admin yang bisa akses master view
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $root = User::find(1); // Super admin root
        return view('mlm.tree-master', compact('root'));
    }

    /**
     * Check if user can access specific node
     */
    private function canAccessNode($nodeId, $user = null)
    {
        $user = $user ?? auth()->user();
        $nodeId = (int) $nodeId;

        // Super admin dan admin bisa akses semua
        if (in_array($user->role, ['super-admin', 'admin'])) {
            return true;
        }
        
        // Member hanya bisa akses terbatas
        if ($user->role === 'member') {
            // Bisa akses diri sendiri
            if ($nodeId === $user->id) {
                return true;
            }

            // Bisa akses upline langsung
            if ($nodeId === $user->upline_id) {
                return true;
            }

            // Cek apakah nodeId adalah downline user
            return $this->isUserDownline($nodeId, $user->id);
        }

        return false;
    }

    /**
     * Check if targetUserId is downline of parentUserId
     */
    private function isUserDownline($targetUserId, $parentUserId, $maxDepth = 10)
    {
        if ($maxDepth <= 0) return false;

        $targetUser = User::find($targetUserId);
        if (!$targetUser || !$targetUser->upline_id) {
            return false;
        }

        // Direct downline
        if ($targetUser->upline_id === $parentUserId) {
            return true;
        }

        // Recursive check untuk indirect downline
        return $this->isUserDownline($targetUser->upline_id, $parentUserId, $maxDepth - 1);
    }

    public function ajax($id)
    {
        $user = auth()->user();

        // Security check
        if (!$this->canAccessNode($id, $user)) {
            abort(403, 'Anda tidak memiliki akses untuk melihat data ini.' . $id . '-' . $user);
        }

        $targetUser = User::with(['left', 'right'])->findOrFail($id);
        return view('users.detail', compact('targetUser'));
    }

    public function parentId($id)
    {
        $user = auth()->user();

        // Security check
        if (!$this->canAccessNode($id, $user)) {
            return response()->json([
                'error' => true,
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $targetUser = User::select('upline_id')->findOrFail($id);

        // Untuk member, cek juga apakah mereka bisa akses parent
        if ($user->role === 'member' && $targetUser->upline_id) {
            $parentUser = User::find($targetUser->upline_id);

            // Jika parent adalah admin/super_admin, member tidak boleh akses
            if ($parentUser && in_array($parentUser->role, ['admin', 'super_admin'])) {
                return response()->json([
                    'error' => true,
                    'message' => 'Anda tidak memiliki akses ke level tersebut.'
                ], 403);
            }

            // Cek akses normal
            if (!$this->canAccessNode($targetUser->upline_id, $user)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Anda tidak memiliki akses ke level tersebut.'
                ], 403);
            }
        }

        return response()->json(['id' => $targetUser->upline_id]);
    }

    public function getAvailableUsers($id)
    {
        try {
            $user = auth()->user();

            // Security check - hanya bisa akses users yang di-sponsor oleh user yang bisa diakses
            if (!$this->canAccessNode($id, $user)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Anda tidak memiliki akses untuk melihat data ini. : ' . $id . '-' . $user
                ], 403);
            }

            // Untuk member, pastikan mereka hanya bisa lihat pending users yang mereka sponsor
            if ($user->role === 'member' && $id !== $user->id) {
                return response()->json([
                    'error' => true,
                    'message' => 'Anda hanya bisa melihat pending users yang Anda sponsor.'
                ], 403);
            }

            $users = User::whereNull('position')
                ->where('sponsor_id', $id)
                ->select('id', 'username', 'name', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(30)
                ->get();

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Error getting available users: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Terjadi kesalahan sistem.'
            ], 500);
        }
    }

    public function getAvailableUsersCount($id)
    {
        $user = auth()->user();

        // Security check
        if (!$this->canAccessNode($id, $user)) {
            return response()->json([
                'error' => true,
                'message' => 'Akses ditolak.'
            ], 403);
        }

        // Untuk member, hanya bisa cek count untuk diri sendiri
        if ($user->role === 'member' && $id != $user->id) {
            return response()->json(['count' => 0]);
        }

        $count = User::whereNull('position')
            ->where('sponsor_id', $id)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function loadTree(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $nodeId = (int) $id;

            // Security check
            if (!$this->canAccessNode($nodeId, $user)) {
                return response()->json([
                    'error' => true,
                    'access_denied' => true,
                    'message' => 'Anda tidak memiliki akses untuk melihat data ini.' . $id . '-' . $user
                ], 403);
            }

            // Validate user role from request (additional security)
            $requestRole = $request->get('user_role');
            if ($requestRole && $requestRole !== $user->role) {
                return response()->json([
                    'error' => true,
                    'message' => 'Role validation failed.'
                ], 403);
            }

            $max = $request->get('limit', 3);
            $targetUser = User::findOrFail($nodeId);

            // Build tree dengan security restrictions
            $tree = $this->buildSecureTreeRecursive($targetUser, 1, $max, $user);

            return response()->json($tree);
        } catch (\Exception $e) {
            Log::error('Gagal memuat tree: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Terjadi kesalahan saat memuat tree'
            ], 500);
        }
    }

    /**
     * Build tree dengan security restrictions
     */
    private function buildSecureTreeRecursive($user, $level = 1, $max = 7, $currentUser = null)
    {
        if (!$user || $level > $max) return null;

        $currentUser = $currentUser ?? auth()->user();

        // eager-load bagans untuk node ini
        if (!$user->relationLoaded('bagans')) {
            $user->load(['bagans:id,user_id,bagan,is_active']);
        }

        // ambil anak kiri/kanan + eager bagans juga
        $left  = $user->leftChild()->with('bagans:id,user_id,bagan,is_active')->first();
        $right = $user->rightChild()->with('bagans:id,user_id,bagan,is_active')->first();

        $leftCount  = $left  ? $this->countAllDownlines($left)  : 0;
        $rightCount = $right ? $this->countAllDownlines($right) : 0;

        // hitung star dari user_bagans
        [$starCount, $activeBagans, $baganFlags] = $this->computeStar($user);

        $children = [];

        // Left child dengan security check
        if ($left) {
            if ($this->canAccessNode($left->id, $currentUser)) {
                $childLeft = $this->buildSecureTreeRecursive($left, $level + 1, $max, $currentUser);
                if ($childLeft !== null) $children[] = $childLeft;
            } else {
                // Placeholder untuk node yang tidak bisa diakses
                $children[] = array_merge([
                    'id'         => null,
                    'name'       => 'Akses Terbatas',
                    'isAddButton' => true,
                    'position'   => 'left',
                    'parent_id'  => $user->id,
                    'children'   => [],
                    'restricted' => true,
                    'left_count' => 0,
                    'right_count' => 0,
                    'star_count' => 0,
                    'active_bagans' => [],
                ], $baganFlags);
            }
        } else {
            // Add button untuk posisi kosong (hanya jika user bisa akses parent)
            $canAdd = $this->canAccessNode($user->id, $currentUser);
            $children[] = array_merge([
                'id'         => $canAdd ? 'add-' . $user->id . '-left' : null,
                'name'       => $canAdd ? 'Tambah' : 'Akses Terbatas',
                'isAddButton' => $canAdd,
                'position'   => 'left',
                'parent_id'  => $user->id,
                'children'   => [],
                'restricted' => !$canAdd,
                'left_count' => $leftCount,
                'right_count' => $rightCount,
                'photo' => $user->photo,
                'star_count' => $starCount,
                'active_bagans' => $activeBagans,
            ], $baganFlags);
        }

        // Right child dengan security check
        if ($right) {
            if ($this->canAccessNode($right->id, $currentUser)) {
                $childRight = $this->buildSecureTreeRecursive($right, $level + 1, $max, $currentUser);
                if ($childRight !== null) $children[] = $childRight;
            } else {
                // Placeholder untuk node yang tidak bisa diakses
                $children[] = array_merge([
                    'id'         => null,
                    'name'       => 'Akses Terbatas',
                    'isAddButton' => true,
                    'position'   => 'right',
                    'parent_id'  => $user->id,
                    'children'   => [],
                    'restricted' => true,
                    'left_count' => 0,
                    'right_count' => 0,
                    'star_count' => 0,
                    'active_bagans' => [],
                ], $baganFlags);
            }
        } else {
            // Add button untuk posisi kosong
            $canAdd = $this->canAccessNode($user->id, $currentUser);
            $children[] = array_merge([
                'id'         => $canAdd ? 'add-' . $user->id . '-right' : null,
                'name'       => $canAdd ? 'Tambah' : 'Akses Terbatas',
                'isAddButton' => $canAdd,
                'position'   => 'right',
                'parent_id'  => $user->id,
                'children'   => [],
                'restricted' => !$canAdd,
                'photo' => $user->photo,
                'left_count' => $leftCount,
                'right_count' => $rightCount,
                'star_count' => $starCount,
                'active_bagans' => $activeBagans,
            ], $baganFlags);
        }

        return array_merge([
            'id'            => $user->id,
            'name'          => $user->username,
            'status'        => $user->is_active ? 'aktif' : 'tidak aktif',
            'pairing_count' => $user->pairing_count ?? 0,
            'voucher'       => $user->voucher ?? 0,
            'position'      => $user->position ?? null,
            'upline_id'     => $user->upline_id,
            'parent_id'     => $user->upline_id, // untuk kompatibilitas
            'children'      => $children,
            'left_count'    => $leftCount,
            'right_count'   => $rightCount,
            'photo'         => $user->photo,
            'star_count'    => $starCount,
            'active_bagans' => $activeBagans,
        ], $baganFlags);
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

        $starCount = count($active);

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
        $user = auth()->user();
        $keyword = trim((string) $request->query('query', ''));
        if ($keyword === '') return response()->json([]);

        $query = User::select('id', 'username', 'name')
            ->where(function ($w) use ($keyword) {
                if (ctype_digit($keyword)) $w->orWhere('id', (int)$keyword);
                $w->orWhere('username', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            });

        // Untuk member, batasi hasil pencarian hanya ke downline mereka
        if ($user->role === 'member') {
            // Dapatkan semua downline IDs
            $downlineIds = $this->getAllDownlineIds($user->id);
            $downlineIds[] = $user->id; // include diri sendiri

            if ($user->upline_id) {
                $downlineIds[] = $user->upline_id; // include upline langsung
            }

            $query->whereIn('id', $downlineIds);
        }

        $users = $query->orderByRaw("
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

    /**
     * Get all downline IDs for a user (recursive)
     */
    private function getAllDownlineIds($userId, $maxDepth = 10): array
    {
        if ($maxDepth <= 0) return [];

        $downlines = User::where('upline_id', $userId)->pluck('id')->toArray();

        foreach ($downlines as $downlineId) {
            $subDownlines = $this->getAllDownlineIds($downlineId, $maxDepth - 1);
            $downlines = array_merge($downlines, $subDownlines);
        }

        return array_unique($downlines);
    }

    public function show($id)
    {
        $user = auth()->user();

        // Security check
        if (!$this->canAccessNode($id, $user)) {
            abort(403, 'Anda tidak memiliki akses untuk melihat data ini.' . $id . '-' . $user);
        }

        $targetUser = User::findOrFail($id);
        return view('users.show', compact('targetUser'));
    }

    public function getNode($id)
    {
        $user = auth()->user();

        // Security check
        if (!$this->canAccessNode($id, $user)) {
            return response()->json([
                'error' => true,
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $targetUser = User::findOrFail($id);

        $left = $targetUser->getLeftChild();
        $right = $targetUser->getRightChild();

        // Filter children berdasarkan akses
        $leftData = null;
        $rightData = null;

        if ($left && $this->canAccessNode($left->id, $user)) {
            $leftData = [
                'id' => $left->id,
                'name' => $left->name,
                'position' => $left->position,
                'status' => $left->is_active ? 'Aktif' : 'Tidak Aktif',
                'has_children' => $left->hasAnyChild(),
            ];
        } elseif ($left) {
            $leftData = [
                'id' => null,
                'name' => 'Akses Terbatas',
                'position' => 'left',
                'status' => 'Terbatas',
                'has_children' => false,
                'restricted' => true
            ];
        }

        if ($right && $this->canAccessNode($right->id, $user)) {
            $rightData = [
                'id' => $right->id,
                'name' => $right->name,
                'position' => $right->position,
                'status' => $right->is_active ? 'Aktif' : 'Tidak Aktif',
                'has_children' => $right->hasAnyChild(),
            ];
        } elseif ($right) {
            $rightData = [
                'id' => null,
                'name' => 'Akses Terbatas',
                'position' => 'right',
                'status' => 'Terbatas',
                'has_children' => false,
                'restricted' => true
            ];
        }

        return response()->json([
            'left' => $leftData,
            'right' => $rightData,
        ]);
    }
}
