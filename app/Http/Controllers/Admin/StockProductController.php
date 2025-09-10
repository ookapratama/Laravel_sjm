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
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            // 'reference_code' => 'nullable|string|max:255',
            'transaction_date' => 'required|date', // Field baru
            'notes' => 'nullable|string|max:500'
        ]);
        // dd($request->all());

        try {
            DB::beginTransaction();

            $transactionGroup = ProductOutgoing::generateTransactionGroup();

            $totalItems = 0;
            $totalValue = 0;
            $totalPv = 0;
            $processedItems = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw new Exception("Stok {$product->name} tidak mencukupi. Stok tersedia: {$product->stock}");
                }

                // Create product outgoing
                $outgoing = ProductOutgoing::create([
                    'transaction_group' => $transactionGroup,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'transaction_date' => $request->transaction_date,
                    'notes' => $request->notes,
                    'created_by' => auth()->id()
                ]);

                $totalItems += $item['quantity'];
                $totalValue += $outgoing->total_price;
                $totalPv += $outgoing->total_pv;

                $processedItems[] = [
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'total_price' => $outgoing->total_price
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Transaksi {$transactionGroup} berhasil dicatat",
                'data' => [
                    'transaction_group' => $transactionGroup,
                    'total_items' => $totalItems,
                    'total_value' => $totalValue,
                    'total_pv' => $totalPv,
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

        // Filter berdasarkan transaction_date (bukan created_at)
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        // Filter berdasarkan produk
        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        // Filter berdasarkan admin
        if ($request->has('admin_id') && $request->admin_id) {
            $query->where('created_by', $request->admin_id);
        }

        $outgoings = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Produk untuk filter
        $products = Product::where('is_active', true)->orderBy('name')->get();

        $filters = [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'product_id' => $request->product_id,
            'admin_id' => $request->admin_id,
        ];

        $outgoings = ProductOutgoing::getGroupedTransactions($filters)->paginate(20);

        return view('admin.produk.manage.history', compact('outgoings', 'products'));
    }

    public function detail($transactionGroup)
    {
        try {
            $items = ProductOutgoing::getByTransactionGroup($transactionGroup);

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan'
                ], 404);
            }

            $summary = $items->first()->getTransactionSummary();
            // dump($items);
            return response()->json([
                'success' => true,
                'transaction' => $summary,
                'items' => $items
            ]);
        } catch (Exception $e) {
            \Log::info($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail transaksi'
            ], 500);
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

    public function getTransactionForRefund($transactionGroup)
    {
        try {
            $items = ProductOutgoing::getByTransactionGroup($transactionGroup);

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan'
                ], 404);
            }


            $summary = $items->first()->getTransactionSummary();

            if ($summary['status'] === 'fully_refunded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi sudah fully refunded'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'transaction' => [
                    'transaction_group' => $transactionGroup,
                    'transaction_date' => $summary['transaction_date']->format('d M Y'),
                    'total_amount' => $summary['total_amount'],
                    'total_pv' => $summary['total_pv'],
                    'total_items' => $summary['total_items'],
                    'status' => $summary['status'],
                    'reference_code' => $summary['reference_code'],
                    'notes' => $summary['notes'],
                    'created_at' => $summary['created_at']->format('d M Y H:i'),
                    'items' => $items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'sku' => $item->product->sku,
                                'current_stock' => $item->product->stock
                            ],
                            'original_quantity' => $item->quantity,
                            'refunded_quantity' => $item->refunded_quantity,
                            'available_for_refund' => $item->quantity - $item->refunded_quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->total_price,
                            'unit_pv' => $item->unit_pv,
                            'total_pv' => $item->total_pv,
                            'can_refund' => ($item->quantity - $item->refunded_quantity) > 0
                        ];
                    })
                ]
            ]);
        } catch (Exception $e) {
            \Log::info($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }
    }

    public function processRefund(Request $request, $transactionGroup)
    {
        // dd($request->all());
        $request->validate([
            'refund_items' => 'required|array|min:0',
            'refund_items.*.item_id' => 'required|exists:product_outgoing,id',
            'refund_items.*.refund_quantity' => 'required|integer|min:1',
            'refund_reason' => 'nullable|string|max:500'
        ]);
        // dd($request->refund_items);

        try {
            DB::beginTransaction();

            $totalRefundedAmount = 0;
            $totalRefundedPv = 0;
            $totalRefundedItems = 0;
            $refundSummary = [];
            foreach ($request->refund_items as $refundData) {
                $item = ProductOutgoing::where('transaction_group', $transactionGroup)
                    ->findOrFail($refundData['item_id']);
                // dump($refundData);
                // dd($refundData['item_id']);
                $result = $item->processRefund($refundData['refund_quantity'], $request->refund_reason);

                $totalRefundedAmount += $result['refunded_amount'];
                $totalRefundedPv += $result['refunded_pv'];
                $totalRefundedItems += $result['refunded_quantity'];

                $refundSummary[] = [
                    'product_name' => $item->product->name,
                    'quantity' => $result['refunded_quantity'],
                    'amount' => $result['refunded_amount']
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Refund berhasil untuk {$totalRefundedItems} item",
                'data' => [
                    'total_refunded_amount' => $totalRefundedAmount,
                    'total_refunded_pv' => $totalRefundedPv,
                    'total_refunded_items' => $totalRefundedItems,
                    'refund_summary' => $refundSummary
                ]
            ]);
        } catch (Exception $e) {
            \Log::info($e->getMessage());
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses refund: ' . $e->getMessage()
            ], 500);
        }
    }
}
