<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TreeApiController extends Controller
{
    public function getRoot()
    {
        $root = User::whereNull('upline_id')->first();

        return response()->json([
            [
                'id' => $root->id,
                'text' => $root->name . ' (' . $root->position . ')',
                'nodes' => [], // kosong, agar bisa diload saat expand
                'lazyLoad' => true
            ]
        ]);
    }

    public function getChildren($id)
    {
        $user = User::findOrFail($id);

        $children = [];

        if ($user->leftChild) {
            $children[] = [
                'id' => $user->leftChild->id,
                'text' => $user->leftChild->name . ' (left)',
                'nodes' => [],
                'lazyLoad' => true
            ];
        }

        if ($user->rightChild) {
            $children[] = [
                'id' => $user->rightChild->id,
                'text' => $user->rightChild->name . ' (right)',
                'nodes' => [],
                'lazyLoad' => true
            ];
        }

        return response()->json($children);
    }
}
