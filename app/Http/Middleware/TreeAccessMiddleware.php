<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TreeAccessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->deny($request, 401, 'Unauthorized');
        }

        // Bypass untuk peran istimewa
        if (in_array($user->role, ['super-admin', 'admin', 'finance'], true)) {
            return $next($request);
        }

        // --- Ambil target node yang diminta ---
        // Dukungan beragam nama param: /tree/load/{id?}?root_id=...&node_id=...
        $nodeId = $request->route('nodeId')
            ?? $request->route('id')
            ?? $request->query('node_id')
            ?? $request->query('root_id');

        if ($nodeId !== null) {
            $nodeId = (int) $nodeId;

            // Log audit
            Log::info("TreeAccess: member {$user->id} requests node {$nodeId}");

            if (!$this->canMemberAccessNode($user->id, $nodeId)) {
                return $this->deny($request, 403, 'Anda tidak memiliki akses untuk melihat data ini.');
            }
        }

        return $next($request);
    }

    /**
     * Aturan akses member:
     * - diri sendiri
     * - siapa pun di rantai UPLINE (ancestor) mereka
     * - siapa pun di rantai DOWNLINE (descendant) mereka
     */
    private function canMemberAccessNode(int $authId, int $targetId): bool
    {
        if ($authId === $targetId) {
            return true;
        }

        // Cek: target adalah downline saya?
        if ($this->isDescendantOf($targetId, $authId)) {
            return true;
        }

        // Cek: target adalah upline saya?
        if ($this->isAncestorOf($targetId, $authId)) {
            return true;
        }

        return false;
    }

    /**
     * Cek apakah $candidate adalah **descendant** dari $ancestor.
     * Struktur tree memakai kolom `upline_id`.
     *
     * WITH RECURSIVE downlines( id ) AS (
     *   SELECT id FROM users WHERE upline_id = :ancestor
     *   UNION ALL
     *   SELECT u.id FROM users u
     *     JOIN downlines d ON u.upline_id = d.id
     * )
     * SELECT 1 FROM downlines WHERE id = :candidate LIMIT 1;
     */
    private function isDescendantOf(int $candidateId, int $ancestorId): bool
    {
        $row = DB::selectOne(
            <<<SQL
            WITH RECURSIVE downlines AS (
              SELECT id
              FROM users
              WHERE upline_id = ?

              UNION ALL

              SELECT u.id
              FROM users u
              INNER JOIN downlines d ON u.upline_id = d.id
            )
            SELECT 1 AS ok
            FROM downlines
            WHERE id = ?
            LIMIT 1
            SQL,
            [$ancestorId, $candidateId]
        );

        return (bool) $row;
    }

    /**
     * Cek apakah $candidate adalah **ancestor (upline)** dari $descendant.
     * Naikkan rantai upline mulai dari $descendant.
     *
     * WITH RECURSIVE ancestors( id ) AS (
     *   SELECT upline_id FROM users WHERE id = :descendant
     *   UNION ALL
     *   SELECT u.upline_id FROM users u
     *     JOIN ancestors a ON u.id = a.id
     * )
     * SELECT 1 FROM ancestors WHERE id = :candidate LIMIT 1;
     */
    private function isAncestorOf(int $candidateId, int $descendantId): bool
    {
        $row = DB::selectOne(
            <<<SQL
            WITH RECURSIVE ancestors AS (
              SELECT upline_id AS id
              FROM users
              WHERE id = ?

              UNION ALL

              SELECT u.upline_id
              FROM users u
              INNER JOIN ancestors a ON u.id = a.id
            )
            SELECT 1 AS ok
            FROM ancestors
            WHERE id = ?
            LIMIT 1
            SQL,
            [$descendantId, $candidateId]
        );

        return (bool) $row;
    }

    private function deny(Request $request, int $status, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'error'         => true,
                'access_denied' => true,
                'message'       => $message,
            ], $status);
        }

        // Non-AJAX: arahkan balik dengan pesan
        return redirect()->back()->withErrors(['access' => $message])->setStatusCode($status);
    }
}
