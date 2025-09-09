<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOutgoing;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockProductController extends Controller
{
    public function index(Request $request)
    {
        // Produk yang aktif dan ada stok
        $products = Product::where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        // Statistik singkat
        $stats = [
            'today_count' => ProductOutgoing::today()->count(),
            'today_stock' => ProductOutgoing::today()->sum('quantity'),
            'today_value' => ProductOutgoing::today()->sum('total_price'),
            'low_stock_count' => Product::where('stock', '<=', 10)->where('is_active', true)->count()
        ];

        return view('admin.produk.manage.index', compact('products', 'stats'));
    }

    /**
     * Proses transaksi barang keluar
     */
    public function processTransaction(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            // 'reference_code' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $totalItems = 0;
            $totalValue = 0;
            $processedItems = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Validasi stok
                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stok {$product->name} tidak mencukupi. Stok tersedia: {$product->stock}"
                    ], 400);
                }

                // Buat record barang keluar
                $outgoing = ProductOutgoing::create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $request->notes,
                    // 'reference_code' => $request->reference_code,
                    'created_by' => auth()->id()
                ]);

                $totalItems += $item['quantity'];
                $totalValue += $outgoing->total_price;

                $processedItems[] = [
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'total_price' => $outgoing->total_price
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil mencatat {$totalItems} item keluar",
                'data' => [
                    'total_items' => $totalItems,
                    'total_value' => $totalValue,
                    'processed_items' => $processedItems
                ]
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get updated product info (untuk refresh stok)
     */
    public function getProductInfo($productId)
    {
        $product = Product::findOrFail($productId);

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'pv' => $product->pv,
                'stock' => $product->stock
            ]
        ]);
    }

    /**
     * History transaksi (halaman terpisah)
     */
    public function history(Request $request)
    {
        $query = ProductOutgoing::with(['product', 'createdBy']);

        // Filter berdasarkan tanggal
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter berdasarkan produk
        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        // Filter berdasarkan admin
        if ($request->has('admin_id') && $request->admin_id) {
            $query->where('created_by', $request->admin_id);
        }

        $outgoings = $query->orderBy('created_at', 'desc')->paginate(20);

        // Produk untuk filter
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('admin.produk.manage.history', compact('outgoings', 'products'));
    }

    public function detail($id)
    {
        try {
            $outgoing = ProductOutgoing::with(['product', 'createdBy'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'outgoing' => [
                    'id' => $outgoing->id,
                    'quantity' => $outgoing->quantity,
                    'unit_price' => $outgoing->unit_price,
                    'total_price' => $outgoing->total_price,
                    'unit_pv' => $outgoing->unit_pv,
                    'total_pv' => $outgoing->total_pv,
                    'notes' => $outgoing->notes,
                    'reference_code' => $outgoing->reference_code,
                    'created_at' => $outgoing->created_at->format('d M Y H:i:s'),
                    'product' => [
                        'name' => $outgoing->product->name,
                        'sku' => $outgoing->product->sku,
                        'category' => $outgoing->product->category ?? '-'
                    ],
                    'created_by' => $outgoing->createdBy ? [
                        'name' => $outgoing->createdBy->name,
                        'email' => $outgoing->createdBy->email
                    ] : null
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Hapus record barang keluar (rollback)
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $outgoing = ProductOutgoing::findOrFail($id);

            // Validasi: hanya bisa rollback transaksi hari ini
            if (!$outgoing->created_at->isToday()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya bisa rollback transaksi hari ini'
                ], 400);
            }

            // Kembalikan stok
            $outgoing->product->increment('stock', $outgoing->quantity);

            // Simpan info untuk response
            $productName = $outgoing->product->name;
            $quantity = $outgoing->quantity;

            // Hapus record
            $outgoing->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Record berhasil dihapus. Stok {$productName} dikembalikan +{$quantity}"
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stats untuk auto-refresh
     */
    public function getStats()
    {
        try {
            $stats = [
                'today_count' => ProductOutgoing::today()->count(),
                'today_value' => ProductOutgoing::today()->sum('total_price'),
                'week_count' => ProductOutgoing::where('created_at', '>=', now()->startOfWeek())->count(),
                'month_count' => ProductOutgoing::where('created_at', '>=', now()->startOfMonth())->count()
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik'
            ], 500);
        }
    }
}
