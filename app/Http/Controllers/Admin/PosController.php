<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivationPin;
use App\Models\PosItems;
use App\Models\PosSessions;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function dashboard()
    {
        // PIN yang sudah used tapi belum dikasih produk
        $pendingPins = ActivationPin::with('usedBy')
            ->where('status', 'used')
            ->whereNull('product_package_id')
            ->whereDoesntHave('posSession')
            ->orderBy('used_at', 'desc')
            ->get();

        // Session POS yang sedang aktif
        $activeSessions = PosSessions::with(['activationPin.usedBy', 'items.product'])
            ->where('session_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        // Statistik
        $stats = [
            'pending_pins' => $pendingPins->count(),
            'active_sessions' => $activeSessions->count(),
            'completed_today' => PosSessions::where('session_status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
            'total_value_today' => PosSessions::where('session_status', 'completed')
                ->whereDate('completed_at', today())
                ->sum('used_budget')
        ];

        return view('admin.produk.pos.dashboard', compact('pendingPins', 'activeSessions', 'stats'));
    }

    /**
     * Mulai session POS untuk PIN tertentu
     */
    public function startSession(Request $request, $pinId)
    {
        try {
            DB::beginTransaction();

            $pin = ActivationPin::findOrFail($pinId);

            // Validasi PIN
            if ($pin->status !== 'used') {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN belum digunakan atau tidak valid'
                ], 400);
            }

            if ($pin->product_package_id !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN sudah mendapat produk melalui sistem package'
                ], 400);
            }

            if ($pin->posSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN sudah memiliki session POS'
                ], 400);
            }

            // Buat session baru
            $session = PosSessions::create([
                'activation_pin_id' => $pin->id,
                'member_id' => $pin->used_by,
                'admin_id' => auth()->id(),
                'session_status' => 'pending',
                'total_budget' => $pin->price,
                'used_budget' => 0,
                'remaining_budget' => $pin->price,
                'max_products' => null,
                'products_count' => 0,
                'started_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Session POS berhasil dimulai',
                'session_id' => $session->id,
                'redirect_url' => route('admin.pos.session', $session->id)
            ]);

        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memulai session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Interface POS untuk session tertentu
     */
    public function session($sessionId)
    {
        $session = PosSessions::with([
            'activationPin.usedBy',
            'items.product',
            'admin'
        ])->findOrFail($sessionId);
        // dd($session->used_budget);
        // Validasi session masih aktif
        if ($session->session_status !== 'pending') {
            return redirect()->route('admin.pos.dashboard')
                ->with('error', 'Session POS sudah selesai atau dibatalkan');
        }

        // Get active products
        $products = Product::where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('admin.produk.pos.session', compact('session', 'products'));
    }

    /**
     * Tambah produk ke session POS
     */
    public function addProduct(Request $request, $sessionId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            $session = PosSessions::findOrFail($sessionId);
            $product = Product::findOrFail($request->product_id);

            // Validasi session
            if ($session->session_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Session sudah tidak aktif'
                ], 400);
            }

            // Validasi budget
            $totalPrice = $product->price * $request->quantity;
            if ($session->remaining_budget < $totalPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Budget tidak mencukupi. Sisa budget: Rp ' . number_format($session->remaining_budget, 0, ',', '.')
                ], 400);
            }

            // Validasi limit produk
            if ($session->max_products && ($session->products_count + $request->quantity) > $session->max_products) {
                return response()->json([
                    'success' => false,
                    'message' => 'Melebihi batas maksimal produk: ' . $session->max_products
                ], 400);
            }

            // Validasi stok
            if ($product->stock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok tidak mencukupi. Stok tersedia: ' . $product->stock
                ], 400);
            }

            // Tambah item ke session
            $item = PosItems::create([
                'session_id' => $session->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'unit_price' => $product->price,
                'total_price' => $totalPrice,
                'unit_pv' => $product->pv,
                'total_pv' => $product->pv * $request->quantity,
                'added_by' => auth()->id()
            ]);

            // Update session totals
            $session->used_budget += $totalPrice;
            $session->remaining_budget = $session->total_budget - $session->used_budget;
            $session->products_count += $request->quantity;
            $session->save();

            // Update stock produk
            $product->decrement('stock', $request->quantity);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil ditambahkan',
                'item' => $item->load('product'),
                'session' => $session->fresh()
            ]);

        } catch (Exception $e) {
            DB::rollback();
            \Log::info($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambah produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus item dari session POS
     */
    public function removeItem(Request $request, $sessionId, $itemId)
    {
        try {
            DB::beginTransaction();
            
            $session = PosSessions::findOrFail($sessionId);
            $item = PosItems::where('session_id', $sessionId)->findOrFail($itemId);
            
            // Validasi session
            if ($session->session_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Session sudah tidak aktif'
                ], 400);
            }
            // dump($session->products_count - $item->quantity);
            // dd($item->quantity);

            // // Update session totals
            // $session->used_budget -= $item->total_price;
            // $session->remaining_budget = $session->total_budget - $session->used_budget;
            // $session->products_count -= $item->quantity;
            // $session->save();

            // Kembalikan stok
            $item->product->increment('stock', $item->quantity);

            // Hapus item
            $item->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil dihapus',
                'session' => $session->fresh(),
                'redirect_url' => route('admin.pos.session', $sessionId)
            ]);

        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Selesaikan session POS
     */
    public function completeSession(Request $request, $sessionId)
    {
        // dump($sessionId);
        // dd($request->all());
        try {
            DB::beginTransaction();

            $session = PosSessions::with('items')->findOrFail($sessionId);

            // Validasi session
            if ($session->session_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Session sudah tidak aktif'
                ], 400);
            }

            // Validasi minimal harus ada 1 item
            if ($session->items->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal harus ada 1 produk untuk menyelesaikan session'
                ], 400);
            }

            // Update session
            $session->update([
                'session_status' => 'completed',
                'completed_at' => now(),
                'notes' => $request->notes
            ]);

            // Mark PIN bahwa sudah dikasih produk via POS
            $session->activationPin->update([
                'product_package_id' => 1 // Special value untuk POS
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Session POS berhasil diselesaikan',
                'redirect_url' => route('admin.pos.dashboard')
            ]);

        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batalkan session POS
     */
    public function cancelSession(Request $request, $sessionId)
    {
        try {
            DB::beginTransaction();

            $session = PosSessions::with('items.product')->findOrFail($sessionId);

            // Validasi session
            if ($session->session_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Session sudah tidak aktif'
                ], 400);
            }

            // Kembalikan semua stok
            foreach ($session->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            // Update session
            $session->update([
                'session_status' => 'cancelled',
                'completed_at' => now(),
                'notes' => $request->notes ?? 'Session dibatalkan'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Session POS berhasil dibatalkan',
                'redirect_url' => route('admin.pos.dashboard')
            ]);

        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * History session POS
     */
    public function history()
    {
        $sessions = PosSessions::with([
            'activationPin.usedBy',
            'admin',
            'items.product'
        ])
        ->whereIn('session_status', ['completed', 'cancelled'])
        ->orderBy('completed_at', 'desc')
        ->paginate(20);

        return view('admin.produk.pos.history', compact('sessions'));
    }
}
