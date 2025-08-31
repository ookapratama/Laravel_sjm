<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::with(['packageProducts.product'])
            ->withCount('packageProducts')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate totals for each package
        foreach ($packages as $package) {
            $package->calculated_total = $package->total_value;
        }
        // dd($packages);

        return view('admin.produk.manage.index', compact('packages'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('admin.produk.manage.create', compact('products'));
    }

    public function store(Request $request)
    {
        // dd($request->has('is_active'));
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'max_value' => 'required|numeric|max:999999999',
                'products' => 'required|array|min:1',
                'products.*.id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1|max:999'
            ], [
                'name.required' => 'Nama paket harus diisi',
                'max_value.required' => 'Nilai maksimal paket harus diisi',
                'products.required' => 'Minimal harus ada 1 produk dalam paket',
                'products.*.id.required' => 'ID produk harus diisi',
                'products.*.id.exists' => 'Produk tidak valid',
                'products.*.quantity.required' => 'Jumlah produk harus diisi',
                'products.*.quantity.integer' => 'Jumlah harus berupa angka',
                'products.*.quantity.min' => 'Jumlah minimal 1',
                'products.*.quantity.max' => 'Jumlah maksimal 999',
            ]);

            $totalValue = $this->calculatePackageValue($request->products);

            if ($totalValue > $request->max_value) {
                throw ValidationException::withMessages([
                    'products' => "Total nilai paket (Rp " . number_format($totalValue, 0, ',', '.') . ") melebihi batas maksimal (Rp " . number_format($request->max_value, 0, ',', '.') . ")"
                ]);
            }

            DB::transaction(function () use ($request) {
                $package = Package::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'max_value' => $request->max_value,
                    'is_active' => $request->has('is_active')
                ]);

                foreach ($request->products as $productData) {
                    ProductPackage::create([
                        'package_id' => $package->id,
                        'product_id' => $productData['id'],
                        'quantity' => $productData['quantity']
                    ]);
                }
            });

            return redirect()->route('admin.products.manage-package')
                ->with('success', 'Paket berhasil dibuat!');
        } catch (ValidationException $e) {
            \Log::info('Message : ' . $e->getMessage());

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::info('Message : ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(Package $package)
    {
        $package->load(['packageProducts.product']);

        return view('admin.produk.manage.detail', compact('package'));
    }

    public function edit(Package $package)
    {
        $package->load(['packageProducts.product']);
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $usedProductIds = $package->packageProducts->pluck('product_id')->toArray();
        $availableProducts = $products->whereNotIn('id', $usedProductIds);

        return view('admin.produk.manage.edit', compact('package', 'products', 'availableProducts'));
    }

    public function update(Request $request, Package $package)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'max_value' => 'required|numeric|min:1|max:999999999',
                'products' => 'required|array|min:1',
                'products.*.id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1|max:999'
            ]);

            $totalValue = $this->calculatePackageValue($request->products);

            if ($totalValue > $request->max_value) {
                throw ValidationException::withMessages([
                    'products' => "Total nilai paket (Rp " . number_format($totalValue, 0, ',', '.') . ") melebihi batas maksimal (Rp " . number_format($request->max_value, 0, ',', '.') . ")"
                ]);
            }

            DB::transaction(function () use ($request, $package) {
                $package->update([
                    'name' => $request->name,
                    'description' => $request->description,
                    'max_value' => $request->max_value,
                    'is_active' => $request->has('is_active')
                ]);

                // Clear existing products
                $package->packageProducts()->delete();

                // Add new products
                foreach ($request->products as $productData) {
                    ProductPackage::create([
                        'package_id' => $package->id,
                        'product_id' => $productData['id'],
                        'quantity' => $productData['quantity']
                    ]);
                }
            });

            return redirect()->route('admin.packages.index')
                ->with('success', 'Paket berhasil diperbarui!');
        } catch (ValidationException $e) {
            \Log::info('Message : ' . $e->getMessage());

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::info('Message : ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(Package $package)
    {
        try {
            $package->delete();
            return redirect()->route('admin.packages.index')
                ->with('success', 'Paket berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Gagal menghapus paket: ' . $e->getMessage()]);
        }
    }

    public function toggleStatus(Package $package)
    {
        try {
            $package->update(['is_active' => !$package->is_active]);
            $status = $package->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return redirect()->back()
                ->with('success', "Paket berhasil {$status}!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Gagal mengubah status paket: ' . $e->getMessage()]);
        }
    }

    private function calculatePackageValue(array $products)
    {
        $totalValue = 0;

        foreach ($products as $productData) {
            $product = Product::find($productData['id']);
            if (!$product || !$product->is_active) {
                throw ValidationException::withMessages([
                    'products' => "Produk tidak aktif atau tidak ditemukan"
                ]);
            }

            $quantity = (int) $productData['quantity'];

            // Check stock
            if ($product->stock < $quantity) {
                throw ValidationException::withMessages([
                    'products' => "Stok {$product->name} tidak mencukupi (tersisa: {$product->stock})"
                ]);
            }

            $totalValue += $product->price * $quantity;
        }

        return $totalValue;
    }
}
