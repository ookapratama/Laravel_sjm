<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductOutgoing extends Model
{
    use HasFactory;
    protected $table = 'product_outgoing';

    protected $fillable = [
        'transaction_group',
        'product_id',
        'quantity',
        'refunded_quantity',
        'unit_price',
        'total_price',
        'unit_pv',
        'total_pv',
        'transaction_date',
        'notes',
        'reference_code',
        'status',
        'created_by'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'unit_pv' => 'decimal:2',
        'total_pv' => 'decimal:2',
        'transaction_date' => 'date'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Auto-calculate saat creating
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($outgoing) {
            if ($outgoing->product) {
                $outgoing->unit_price = $outgoing->product->price;
                $outgoing->unit_pv = $outgoing->product->pv;
                $outgoing->total_price = $outgoing->unit_price * $outgoing->quantity;
                $outgoing->total_pv = $outgoing->unit_pv * $outgoing->quantity;
            }

            // Set default transaction_date jika tidak diisi
            if (!$outgoing->transaction_date) {
                $outgoing->transaction_date = today();
            }
        });

        // Update stock produk setelah record dibuat
        static::created(function ($outgoing) {
            $outgoing->product->decrement('stock', $outgoing->quantity);
        });
    }

    // Generate transaction group code
    public static function generateTransactionGroup()
    {
        $date = now()->format('Ymd');
        $lastTransaction = self::where('transaction_group', 'like', "TRX-{$date}-%")
            ->orderBy('transaction_group', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->transaction_group, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return "TRX-{$date}-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Get items by transaction group
    public static function getByTransactionGroup($transactionGroup)
    {
        return self::with(['product', 'createdBy'])
            ->where('transaction_group', $transactionGroup)
            ->orderBy('created_at')
            ->get();
    }

    // Get transaction summary
    public function getTransactionSummary()
    {
        if (!$this->transaction_group) return null;

        $items = self::where('transaction_group', $this->transaction_group)->get();

        return [
            'transaction_group' => $this->transaction_group,
            'transaction_date' => $this->transaction_date,
            'reference_code' => $this->reference_code,
            'notes' => $this->notes,
            'total_items' => $items->sum('quantity'),
            'total_refunded' => $items->sum('refunded_quantity'),
            'total_amount' => $items->sum('total_price'),
            'total_pv' => $items->sum('total_pv'),
            'status' => $this->getGroupStatus(),
            'created_by' => $this->createdBy,
            'created_at' => $this->created_at,
            'items_count' => $items->count()
        ];
    }

    // Get group status
    public function getGroupStatus()
    {
        if (!$this->transaction_group) return $this->status;

        $items = self::where('transaction_group', $this->transaction_group)->get();
        $totalQuantity = $items->sum('quantity');
        $totalRefunded = $items->sum('refunded_quantity');

        if ($totalRefunded == 0) {
            return 'active';
        } elseif ($totalRefunded >= $totalQuantity) {
            return 'fully_refunded';
        } else {
            return 'partial_refunded';
        }
    }

    // Process refund
    public function processRefund($refundQuantity, $reason = null)
    {
        $availableQty = $this->quantity - $this->refunded_quantity;

        if ($refundQuantity > $availableQty) {
            throw new \Exception('Quantity refund melebihi yang tersedia');
        }

        DB::beginTransaction();
        try {
            $refundAmount = $this->unit_price * $refundQuantity;
            $refundPv = $this->unit_pv * $refundQuantity;

            // Update refunded quantity
            $this->refunded_quantity += $refundQuantity;

            // Update total berdasarkan sisa quantity
            $remainingQty = $this->quantity - $this->refunded_quantity;
            $this->total_price = $this->unit_price * $remainingQty;
            $this->total_pv = $this->unit_pv * $remainingQty;

            // Update status item
            if ($this->refunded_quantity >= $this->quantity) {
                $this->status = 'fully_refunded';
            } else {
                $this->status = 'partial_refunded';
            }

            $this->save();

            // Kembalikan stok
            $this->product->increment('stock', $refundQuantity);

            // Update status untuk semua item dalam group yang sama
            if ($this->transaction_group) {
                $this->updateGroupStatus();
            }

            DB::commit();

            return [
                'refunded_quantity' => $refundQuantity,
                'refunded_amount' => $refundAmount,
                'refunded_pv' => $refundPv
            ];
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    // Update status untuk semua item dalam group
    private function updateGroupStatus()
    {
        $groupStatus = $this->getGroupStatus();

        // Update semua item dalam group dengan status yang sama
        self::where('transaction_group', $this->transaction_group)
            ->update(['status' => $groupStatus]);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('transaction_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year);
    }

    public function scopeByTransactionGroup($query, $group)
    {
        return $query->where('transaction_group', $group);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCanRefund($query)
    {
        return $query->where('created_at', '>=', now()->subDays(7))
            ->whereIn('status', ['active', 'partial_refunded']);
    }

    // Get grouped transactions untuk history
    public static function getGroupedTransactions($filters = [])
    {
        $query = self::select([
            'transaction_group',
            'transaction_date',
            'notes',
            'created_by',
            'created_at',
            DB::raw('COUNT(*) as items_count'),
            DB::raw('SUM(quantity) as total_items'),
            DB::raw('SUM(refunded_quantity) as total_refunded'),
            DB::raw('SUM(total_price) as total_amount'),
            DB::raw('SUM(total_pv) as total_pv'),
            DB::raw('CASE 
                    WHEN SUM(refunded_quantity) = 0 THEN "active"
                    WHEN SUM(refunded_quantity) >= SUM(quantity) THEN "fully_refunded"
                    ELSE "partial_refunded"
                END as group_status')
        ])
            ->with('createdBy')
            ->whereNotNull('transaction_group')
            ->groupBy([
                'transaction_group',
                'transaction_date',
                'notes',
                'created_by',
                'created_at'
            ]);

        // Apply filters
        if (isset($filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['end_date']);
        }

        return $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');
    }
}
