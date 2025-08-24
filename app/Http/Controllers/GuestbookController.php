<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GuestEntry;
use App\Models\Invitation;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestbookController extends Controller
{
public function index(Request $r)
{
    $statuses = [
        'confirmed'   => 'Confirmed',
        'maybe'       => 'Maybe',
        'declined'    => 'Declined',
        'checked_in'  => 'Checked In',
    ];

    $user      = $r->user();
    $role      = $user->role ?? 'member';
    $isElevated= in_array($role, ['admin','super-admin','finance'], true);

    // Base query with relations
    $base = GuestEntry::with(['invitation:id,title,city', 'referrer:id,username,name']);

    // Visibility rule
    if (!$isElevated) {
        $ref = $user->referral_code ?: $user->username;
        if ($ref) {
            $base->where('referral_code', $ref);
        } else {
            // kalau user tidak punya kode sama sekali, kosongkan hasil
            $base->whereRaw('1=0');
        }
    }

    // Helper: apply common filters (tanpa status)
    $applyCommonFilters = function ($q) use ($r) {
        $q->when($r->filled('invitation_id'), fn($qq) => $qq->where('invitation_id', $r->invitation_id))
          ->when($r->filled('date_from'),     fn($qq) => $qq->whereDate('created_at', '>=', $r->date_from))
          ->when($r->filled('date_to'),       fn($qq) => $qq->whereDate('created_at', '<=', $r->date_to))
          ->when($r->filled('q'), function ($qq) use ($r) {
              $s = trim($r->q);
              $qq->where(function($w) use ($s){
                  $w->where('name','like',"%$s%")
                    ->orWhere('phone','like',"%$s%")
                    ->orWhere('email','like',"%$s%")
                    ->orWhere('notes','like',"%$s%")
                    ->orWhere('referral_code','like',"%$s%");
              });
          });
    };

    // Rows (pakai semua filter, termasuk status bila diisi)
    $rowsQ = (clone $base);
    $applyCommonFilters($rowsQ);
    $rowsQ->when($r->filled('attend_status'), fn($q) => $q->where('attend_status', $r->attend_status));
    $rows = $rowsQ->orderByDesc('created_at')->paginate(25)->withQueryString();

    // Invitations list:
    // - elevated: semua undangan
    // - member: hanya undangan yang punya entri sesuai visibilitasnya
    if ($isElevated) {
        $invitations = Invitation::orderBy('title')->get(['id','title','city']);
    } else {
        $invitationIds = (clone $base)
            ->select('invitation_id')->distinct()->pluck('invitation_id');
        $invitations = Invitation::whereIn('id', $invitationIds)
            ->orderBy('title')->get(['id','title','city']);
    }

    // Stats (mengikuti filter umum, TAPI tidak terkunci ke satu status)
    $statsQ = (clone $base);
    $applyCommonFilters($statsQ);

    $stats = [
        'total'      => (clone $statsQ)->count(),
        'confirmed'  => (clone $statsQ)->where('attend_status','confirmed')->count(),
        'maybe'      => (clone $statsQ)->where('attend_status','maybe')->count(),
        'declined'   => (clone $statsQ)->where('attend_status','declined')->count(),
        'checked_in' => (clone $statsQ)->where('attend_status','checked_in')->count(),
    ];

    return view('inv.guestbook.index', compact('rows','stats','invitations','statuses'));
}


    public function export(Request $r): StreamedResponse
    {
        $file = 'guest-entries-'.now()->format('Ymd_His').'.csv';

        $query = GuestEntry::with('invitation:id,title')
            ->when($r->filled('invitation_id'), fn($q)=>$q->where('invitation_id',$r->invitation_id))
            ->when($r->filled('attend_status'), fn($q)=>$q->where('attend_status',$r->attend_status))
            ->when($r->filled('date_from'), fn($q)=>$q->whereDate('created_at','>=',$r->date_from))
            ->when($r->filled('date_to'),   fn($q)=>$q->whereDate('created_at','<=',$r->date_to))
            ->when($r->filled('q'), function($q) use ($r){
                $s = trim($r->q);
                $q->where(function($qq) use ($s){
                    $qq->where('name','like',"%$s%")
                       ->orWhere('phone','like',"%$s%")
                       ->orWhere('email','like',"%$s%")
                       ->orWhere('notes','like',"%$s%")
                       ->orWhere('referral_code','like',"%$s%");
                });
            })
            ->orderBy('created_at');

        return response()->streamDownload(function() use ($query){
            $out = fopen('php://output','w');
            fputcsv($out, [
                'Tanggal','Acara','Nama','Telepon','Email',
                'Status','Checked In At','Referral Code','Referrer',
                'IP Address','User Agent','Catatan'
            ]);
            $query->chunk(500, function($chunk) use ($out){
                foreach ($chunk as $m){
                    fputcsv($out, [
                        optional($m->created_at)->format('Y-m-d H:i'),
                        optional($m->invitation)->title,
                        $m->name,
                        $m->phone,
                        $m->email,
                        $m->attend_status,
                        optional($m->check_in_at)?->format('Y-m-d H:i'),
                        $m->referral_code,
                        optional($m->referrer)->username ?? optional($m->referrer)->name,
                        $m->ip_address,
                        $m->user_agent,
                        preg_replace("/\r|\n/", ' ', (string)$m->notes),
                    ]);
                }
            });
            fclose($out);
        }, $file, ['Content-Type'=>'text/csv']);
    }
}
