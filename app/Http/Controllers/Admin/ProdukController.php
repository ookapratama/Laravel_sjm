<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProdukController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('created_at', 'desc')->get();

        return view('admin.produk.index', compact('products'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'sku' => 'required|string|unique:products,sku|max:50',
                'price' => 'required|numeric|min:0',
                'pv' => 'nullable|numeric|min:0',
                'stock' => 'nullable|integer|min:0'
            ], [
                'name.required' => 'Nama produk wajib diisi',
                'sku.required' => 'SKU wajib diisi',
                'sku.unique' => 'SKU sudah digunakan produk lain',
                'price.required' => 'Harga wajib diisi',
                'price.numeric' => 'Harga harus berupa angka',
                'price.min' => 'Harga tidak boleh negatif',
                'pv.numeric' => 'PV harus berupa angka',
                'stock.integer' => 'Stok harus berupa angka bulat'
            ]);

            Product::create([
                'name' => $request->name,
                'sku' => strtoupper($request->sku),
                'price' => $request->price,
                'pv' => $request->pv ?? 0,
                'stock' => $request->stock ?? 0,
                'is_active' => $request->has('is_active')
            ]);

            return redirect()->route('admin.products.index')
                ->with('success', 'Produk berhasil ditambahkan!');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan produk: ' . $e->getMessage())
                ->withInput();
        }
    }

    // Edit produk (return JSON untuk AJAX)
    public function edit($id)
    {
        try {
            $product = Product::findOrFail($id);
            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Produk tidak ditemukan'
            ], 404);
        }
    }

    // Update produk
    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'sku' => 'required|string|unique:products,sku,' . $id . '|max:50',
                'price' => 'required|numeric|min:0',
                'pv' => 'nullable|numeric|min:0',
                'stock' => 'nullable|integer|min:0'
            ], [
                'name.required' => 'Nama produk wajib diisi',
                'sku.required' => 'SKU wajib diisi',
                'sku.unique' => 'SKU sudah digunakan produk lain',
                'price.required' => 'Harga wajib diisi',
                'price.numeric' => 'Harga harus berupa angka',
                'price.min' => 'Harga tidak boleh negatif'
            ]);

            $product->update([
                'name' => $request->name,
                'sku' => strtoupper($request->sku),
                'price' => $request->price,
                'pv' => $request->pv ?? 0,
                'stock' => $request->stock ?? 0,
                'is_active' => $request->has('is_active')
            ]);

            return redirect()->route('products.index')
                ->with('success', 'Produk berhasil diperbarui!');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui produk: ' . $e->getMessage())
                ->withInput();
        }
    }

    // Hapus produk
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            // Cek apakah produk sedang digunakan di paket
            $inPackage = ProductPackage::where('product_id', $id)
                ->where('is_active', true)
                ->exists();

            if ($inPackage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak dapat dihapus karena sedang digunakan dalam paket basic!'
                ]);
            }

            // Cek apakah produk pernah terjual (jika ada tabel product_sales)
            if (class_exists('\App\Models\ProductSale')) {
                $hasSales = \App\Models\ProductSale::where('product_id', $id)->exists();
                if ($hasSales) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Produk tidak dapat dihapus karena memiliki riwayat penjualan!'
                    ]);
                }
            }

            $productName = $product->name;
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => "Produk '{$productName}' berhasil dihapus!"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus produk: ' . $e->getMessage()
            ]);
        }
    }

    public function manage_index()
    {
        // Get current package products
        $packageProducts = ProductPackage::where('is_active', true)
            ->with(['product' => function ($query) {
                $query->select('id', 'name', 'sku', 'price', 'pv', 'stock', 'is_active');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate total package value
        $totalValue = $packageProducts->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        // Get available products (not in current package and active)
        $usedProductIds = $packageProducts->pluck('product_id')->toArray();
        $availableProducts = Product::where('is_active', true)
            ->whereNotIn('id', $usedProductIds)
            ->orderBy('name')
            ->get();

        return view('admin.produk.manage.index', compact(
            'packageProducts',
            'totalValue',
            'availableProducts'
        ));
    }

    public function update_package(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'products' => 'required|array|min:1',
                'products.*.id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1|max:999'
            ], [
                'products.required' => 'Minimal harus ada 1 produk dalam paket',
                'products.*.id.required' => 'ID produk harus diisi',
                'products.*.id.exists' => 'Produk tidak valid',
                'products.*.quantity.required' => 'Jumlah produk harus diisi',
                'products.*.quantity.integer' => 'Jumlah harus berupa angka',
                'products.*.quantity.min' => 'Jumlah minimal 1',
                'products.*.quantity.max' => 'Jumlah maksimal 999',
            ]);

            // Calculate total package value
            $totalValue = 0;
            $productDetails = [];

            foreach ($request->products as $productData) {
                $product = Product::find($productData['id']);
                if (!$product || !$product->is_active) {
                    throw ValidationException::withMessages([
                        'products' => "Produk  " . $product->name ?? 'Unknown' . " tidak aktif atau tidak ditemukan"
                    ]);
                }

                $quantity = (int) $productData['quantity'];
                $subtotal = $product->price * $quantity;
                $totalValue += $subtotal;

                $productDetails[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal
                ];

                // Check stock availability
                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'products' => "Stok {$product->name} tidak mencukupi (tersisa: {$product->stock})"
                    ]);
                }
            }

            // Optional: Add business logic validation
            // You can add warning or restriction if total value exceeds certain threshold
            $maxAllowedValue = 1500000; // 1.5 juta
            if ($totalValue > $maxAllowedValue) {
                throw ValidationException::withMessages([
                    'products' => "Total nilai paket (Rp " . number_format($totalValue, 0, ',', '.') . ") melebihi batas maksimal (Rp " . number_format($maxAllowedValue, 0, ',', '.') . ")"
                ]);
            }

            // Update package in database transaction
            DB::transaction(function () use ($request) {
                // Clear existing package products
                ProductPackage::where('is_active', true)->delete();

                // Insert new package products
                foreach ($request->products as $productData) {
                    ProductPackage::create([
                        'product_id' => $productData['id'],
                        'quantity' => $productData['quantity'],
                        'is_active' => true
                    ]);
                }
            });

            // Prepare success message with package details
            $message = "Paket Basic berhasil diperbarui! ";
            $message .= "Total nilai: Rp " . number_format($totalValue, 0, ',', '.') . " ";
            $message .= "(" . count($request->products) . " jenis produk, ";
            $message .= array_sum(array_column($request->products, 'quantity')) . " total item)";

            return redirect()->route('admin.basic-package.index')
                ->with('success', $message);
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
