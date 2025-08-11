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

    public function ajax($id)
    {
        $user = User::with(['left', 'right'])->findOrFail($id);
        return view('users.detail', compact('user'));
    }
    public function getAvailableUsers($id)
    {
        try {
            $users = User::whereNull('position')
                ->where('sponsor_id', $id)
                ->select('id', 'username', 'name', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json($users);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    public function loadTree(Request $request, $id)
    {
        $max = $request->get('limit', 7); // default 7 level
        $user = User::findOrFail($id);
        $tree = $this->buildTreeRecursive($user, 1, $max);
        return response()->json($tree);
    }

    private function buildTreeRecursive($user, $level = 1, $max = 7)
    {
        if (!$user || $level > $max) return null;

        $left = $user->leftChild()->first();
        $right = $user->rightChild()->first();

        $children = [];
        $leftChild = $left;
        $rightChild = $right;
        $leftCount = $leftChild ? $this->countAllDownlines($leftChild) : 0;

        $rightCount = $rightChild ? $this->countAllDownlines($rightChild) : 0;

        if ($left) {
            $childLeft = $this->buildTreeRecursive($left, $level + 1, $max);
            if ($childLeft !== null) $children[] = $childLeft;
        } else {
            $children[] = [
                'id' => 'add-' . $user->id . '-left',
                'name' => 'Tambah',
                'isAddButton' => true,
                'position' => 'left',
                'parent_id' => $user->id,
                'children' => [],
                'is_active_bagan_1' => $user->is_active_bagan_1,
                'is_active_bagan_2' => $user->is_active_bagan_2,
                'is_active_bagan_3' => $user->is_active_bagan_3,
                'is_active_bagan_4' => $user->is_active_bagan_4,
                'is_active_bagan_5' => $user->is_active_bagan_5,
                'left_count' => $leftCount,
                'right_count' => $rightCount,
            ];
        }

        if ($right) {
            $childRight = $this->buildTreeRecursive($right, $level + 1, $max);
            if ($childRight !== null) $children[] = $childRight;
        } else {
            $children[] = [
                'id' => 'add-' . $user->id . '-right',
                'name' => 'Tambah',
                'isAddButton' => true,
                'position' => 'right',
                'parent_id' => $user->id,
                'children' => [],
                'is_active_bagan_1' => $user->is_active_bagan_1,
                'is_active_bagan_2' => $user->is_active_bagan_2,
                'is_active_bagan_3' => $user->is_active_bagan_3,
                'is_active_bagan_4' => $user->is_active_bagan_4,
                'is_active_bagan_5' => $user->is_active_bagan_5,
                'left_count' => $leftCount,
                'right_count' => $rightCount,
            ];
        }

        return [
            'id' => $user->id,
            'name' => $user->username,
            'status' => $user->is_active ? 'aktif' : 'tidak aktif',
            'pairing_count' => $user->pairing_count ?? 0,
            'voucher' => $user->voucher ?? 0,
            'position' => $user->position ?? 0,
            'children' => $children,
            'left_count' => $leftCount,
            'right_count' => $rightCount,
            'is_active_bagan_1' => $user->is_active_bagan_1,
            'is_active_bagan_2' => $user->is_active_bagan_2,
            'is_active_bagan_3' => $user->is_active_bagan_3,
            'is_active_bagan_4' => $user->is_active_bagan_4,
            'is_active_bagan_5' => $user->is_active_bagan_5,
        ];
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
        $keyword = $request->query('query');
        $user = User::where('name', 'like', "%$keyword%")
            ->orWhere('username', 'like', "%$keyword%")
            ->first();

        if (!$user) return response()->json(null);

        return response()->json(['id' => $user->id]);
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
