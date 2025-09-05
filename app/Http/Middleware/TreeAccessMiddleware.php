<?php

// App/Http/Middleware/TreeAccessMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TreeAccessMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Super admin dan admin bypass semua validasi
        if (in_array($user->role, ['super-admin', 'admin'])) {
            return $next($request);
        }
        
        // Untuk member, validasi akses
        if ($user->role === 'member') {
            $nodeId = $request->route('nodeId') ?? $request->route('id') ?? $request->get('node_id');
            
            if ($nodeId) {
                $nodeId = (int) $nodeId;
                
                // Log akses untuk monitoring
                Log::info("Member {$user->id} trying to access node {$nodeId}");
                
                if (!$this->canMemberAccessNode($nodeId, $user)) {
                    return response()->json([
                        'error' => true,
                        'access_denied' => true,
                        'message' => 'Anda tidak memiliki akses untuk melihat data ini.'
                    ], 403);
                }
            }
        }
        
        return $next($request);
    }
    
    /**
     * Check if member can access specific node
     */
    private function canMemberAccessNode($nodeId, $user)
    {
        // Bisa akses diri sendiri
        if ($nodeId === $user->id) {
            return true;
        }
        
        // Bisa akses upline langsung (sponsor)
        if ($nodeId === $user->referrer_id) {
            return true;
        }
        
        // Cek apakah nodeId adalah downline user
        return $this->isUserDownline($nodeId, $user->id);
    }
    
    /**
     * Check if targetUserId is downline of parentUserId
     */
    private function isUserDownline($targetUserId, $parentUserId, $maxDepth = 10)
    {
        if ($maxDepth <= 0) return false;
        
        $targetUser = User::find($targetUserId);
        if (!$targetUser || !$targetUser->referrer_id) {
            return false;
        }
        
        // Direct downline
        if ($targetUser->referrer_id === $parentUserId) {
            return true;
        }
        
        // Recursive check untuk indirect downline
        return $this->isUserDownline($targetUser->referrer_id, $parentUserId, $maxDepth - 1);
    }
}