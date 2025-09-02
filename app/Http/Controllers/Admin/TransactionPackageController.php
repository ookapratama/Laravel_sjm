<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionPackageController extends Controller
{
    public function index()
    {
        $unassignedPins = DB::table('activation_pins as ap')
            ->join('users as u', 'ap.used_by', '=', 'u.id')
            ->where('ap.status', 'used')
            ->whereNull('ap.product_package_id')
            ->select('ap.*', 'u.name as user_name', 'u.username as user_username')
            ->get();

        $assignedCount = DB::table('activation_pins')
            ->where('status', 'used')
            ->whereNotNull('product_package_id')
            ->count();

        $packages = Package::where('is_active', true)->get();

        return view('admin.produk.transaction.index', [
            'unassignedPins' => $unassignedPins,
            'unassignedCount' => $unassignedPins->count(),
            'assignedCount' => $assignedCount,
            'totalUsedPins' => $unassignedPins->count() + $assignedCount,
            'availablePackages' => $packages->count(),
            'packages' => $packages
        ]);
    }

    public function assignPackage(Request $request, $pinId)
    {
        $pin = DB::table('activation_pins')->where('id', $pinId)->first();

        if (!$pin || $pin->status !== 'used' || $pin->product_package_id) {
            return response()->json(['success' => false, 'message' => 'Pin tidak valid']);
        }

        DB::table('activation_pins')
            ->where('id', $pinId)
            ->update(['product_package_id' => $request->package_id]);

        $productPackages = DB::table('product_packages')
            ->where('package_id', $request->package_id)
            ->get();

        foreach ($productPackages as $i => $productPackage) {
            $stok = $productPackage->quantity;
            $product =  Product::find($productPackage->product_id);

            $stok = $product->stock - $stok;

            $product->update([
                'stock' => $stok
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Paket berhasil diassign']);
    }

    public function bulkAssignPackage(Request $request)
    {
        $request->validate([
            'pin_ids' => 'required|array|min:1',
            'pin_ids.*' => 'required|integer',
            'package_id' => 'required|exists:packages,id'
        ], [
            'pin_ids.required' => 'Pilih minimal 1 pin untuk diassign',
            'pin_ids.array' => 'Format pin IDs tidak valid',
            'pin_ids.min' => 'Pilih minimal 1 pin untuk diassign',
            'package_id.required' => 'Pilih package yang akan diassign',
            'package_id.exists' => 'Package tidak ditemukan'
        ]);

        try {
            DB::beginTransaction();

            $pinIds = $request->pin_ids;
            $packageId = $request->package_id;

            // Validasi package aktif
            $package = Package::where('id', $packageId)
                ->where('is_active', true)
                ->first();

            if (!$package) {
                throw new \Exception('Package tidak ditemukan atau tidak aktif');
            }

            $productPackages = DB::table('product_packages')
                ->where('package_id', $packageId)
                ->first();


            foreach ($pinIds as $pin) {
                $stok = $productPackages->quantity;
                $product =  Product::find($productPackages->product_id);

                $stok = $product->stock - $stok;

                $product->update([
                    'stock' => $stok
                ]);
            }

            // Ambil pins yang valid untuk diassign
            $validPins = DB::table('activation_pins')
                ->whereIn('id', $pinIds)
                ->where('status', 'used')
                ->whereNull('product_package_id')
                ->get();

            if ($validPins->isEmpty()) {
                throw new \Exception('Tidak ada pin yang valid untuk diassign package');
            }

            // Cek jika ada pin yang tidak valid
            $invalidCount = count($pinIds) - $validPins->count();
            if ($invalidCount > 0) {
                $warningMessage = "{$invalidCount} pin diabaikan karena sudah diassign atau tidak valid";
            }

            // Update semua valid pins dengan package_id
            $updatedCount = DB::table('activation_pins')
                ->whereIn('id', $validPins->pluck('id'))
                ->update([
                    'product_package_id' => $packageId,
                    'updated_at' => now()
                ]);

            DB::commit();

            $successMessage = "Berhasil assign paket '{$package->name}' ke {$updatedCount} pin";

            if (isset($warningMessage)) {
                $successMessage .= ". {$warningMessage}";
            }

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data' => [
                    'updated_count' => $updatedCount,
                    'invalid_count' => $invalidCount ?? 0,
                    'package_name' => $package->name
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getPackagePreview($packageId)
    {
        try {
            $package = Package::with(['packageProducts.product'])
                ->where('is_active', true)
                ->findOrFail($packageId);

            $products = $package->packageProducts->map(function ($pp) {
                return [
                    'id' => $pp->product->id,
                    'name' => $pp->product->name,
                    'sku' => $pp->product->sku,
                    'quantity' => $pp->quantity,
                    'stock' => $pp->product->stock,
                    'price' => $pp->product->price,
                    'subtotal' => $pp->product->price * $pp->quantity
                ];
            });

            return response()->json([
                'success' => true,
                'package' => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'max_value' => $package->max_value
                ],
                'products' => $products,
                'total_items' => $products->sum('quantity'),
                'total_value' => $products->sum('subtotal'),
                'total_products' => $products->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Package tidak ditemukan'
            ], 404);
        }
    }

    // Method untuk melihat pins yang sudah diassign
    public function assignedPins()
    {
        $assignedPins = DB::table('activation_pins as ap')
            ->join('users as u', 'ap.used_by', '=', 'u.id')
            ->join('packages as p', 'ap.product_package_id', '=', 'p.id')
            ->where('ap.status', 'used')
            ->whereNotNull('ap.product_package_id')
            ->select([
                'ap.id',
                'ap.code',
                'ap.used_by',
                'ap.used_at',
                'ap.updated_at as assigned_at',
                'u.name as user_name',
                'u.username as user_username',
                'p.name as package_name',
                'p.id as package_id'
            ])
            ->orderBy('ap.updated_at', 'desc')
            ->paginate(20);

        return view('admin.produk.transaction.assigned', compact('assignedPins'));
    }

    // Method untuk unassign package (jika diperlukan)
    public function unassignPackage(Request $request, $pinId)
    {
        try {
            DB::beginTransaction();

            $pin = DB::table('activation_pins')
                ->where('id', $pinId)
                ->where('status', 'used')
                ->whereNotNull('product_package_id')
                ->first();

            if (!$pin) {
                throw new \Exception('Pin tidak ditemukan atau belum diassign package');
            }

            // Update pin, remove package assignment
            DB::table('activation_pins')
                ->where('id', $pinId)
                ->update([
                    'product_package_id' => null,
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Package assignment berhasil dihapus dari pin'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Method untuk statistik dashboard
    public function getDashboardStats()
    {
        $stats = [
            'total_pins' => DB::table('activation_pins')->count(),
            'used_pins' => DB::table('activation_pins')->where('status', 'used')->count(),
            'assigned_pins' => DB::table('activation_pins')
                ->where('status', 'used')
                ->whereNotNull('product_package_id')
                ->count(),
            'unassigned_pins' => DB::table('activation_pins')
                ->where('status', 'used')
                ->whereNull('product_package_id')
                ->count(),
            'active_packages' => Package::where('is_active', true)->count(),
            'recent_assignments' => DB::table('activation_pins as ap')
                ->join('users as u', 'ap.used_by', '=', 'u.id')
                ->join('packages as p', 'ap.product_package_id', '=', 'p.id')
                ->where('ap.status', 'used')
                ->whereNotNull('ap.product_package_id')
                ->whereDate('ap.updated_at', '>=', now()->subDays(7))
                ->count()
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}
