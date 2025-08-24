<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\GuestEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\URL;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Color\Color;
use GuzzleHttp\Client;

class GuestEntryController extends Controller
{
    public function form(string $slug)
    {
        $inv = Invitation::where('slug',$slug)->where('is_active',true)->firstOrFail();
        return view('inv.guestbook.form_inv', ['invitation'=>$inv]);
    }

 public function store(Request $r, string $slug)
{
    $inv = Invitation::where('slug',$slug)->where('is_active',true)->firstOrFail();

    $v = $r->validate([
        'name'          => ['required','string','max:120'],
        'phone'         => ['nullable','string','max:30'],
        'email'         => ['nullable','email','max:120'],
        'notes'         => ['nullable','string','max:500'],
        'attend_status' => ['required','in:confirmed,maybe,declined'],
        'referral_code' => ['nullable','string','max:255'],
    ]);

    $entry = GuestEntry::create([
        'invitation_id'    => $inv->id,
        'referrer_user_id' => optional($r->user())->id,
        'referral_code'    => $v['referral_code'] ?? $r->query('ref'),
        'name'             => $v['name'],
        'phone'            => $v['phone'] ?? null,
        'email'            => $v['email'] ?? null,
        'notes'            => $v['notes'] ?? null,
        'attend_status'    => $v['attend_status'],
        'check_in_at'      => null,
        'ip_address'       => $r->ip(),
        'user_agent'       => substr($r->userAgent() ?? '', 0, 255),
    ]);

    // signed URL (kadaluarsa H+2 acara, fallback 7 hari)
    $expires = optional($inv->event_datetime)?->copy()->addDays(2) ?? now()->addDays(7);
    $qrUrl   = URL::temporarySignedRoute('guest_entries.scan_checkin', $expires, [
        'invitation' => $inv->slug, 'entry' => $entry->id
    ]);

    // QR base64 untuk ditampilkan
    $qrPng  = $this->makeQrBase64($qrUrl);

    // Link WA (kalau user isi phone)
    $msg = "Halo {$entry->name},\n"
         . "Ini tautan QR check-in untuk acara \"{$inv->title}\".\n"
         . "Tunjukkan saat di lokasi ya:\n{$qrUrl}";
    $waLink = $this->makeWaLink($entry->phone, $msg);
    $this->sendWhatsApp($entry->phone, $msg);
    return redirect()->route('guest.thanks.inv', $slug)->with([
        'ok'        => 'Terima kasih! Simpan/ kirim QR ini dan tunjukkan saat check-in.',
        'qrUrl'     => $qrUrl,
        'qrPng'     => $qrPng,
        'entryName' => $entry->name,
        'waLink'    => $waLink, // <— tombol “Kirim ke WhatsApp”
    ]);
}



    public function thanks(string $slug)
    {
         $inv = Invitation::where('slug',$slug)->firstOrFail();

    return view('inv.guestbook.thank', [
        'invitation' => $inv,
        'qrUrl'      => session('qrUrl'),
        'qrPng'      => session('qrPng'),
        'entryName'  => session('entryName'),
        'flashOk'    => session('ok'),
    ]);
    }
 

public function checkInScan(Invitation $invitation, GuestEntry $entry)
{
    abort_unless($entry->invitation_id === $invitation->id, 404);

    // Idempotent: kalau sudah check-in, tidak diubah lagi
    if ($entry->attend_status !== 'checked_in') {
        // Bolehkan dari confirmed/maybe/declined → checked_in (kondisi venue menang)
        $entry->update([
            'attend_status' => 'checked_in',
            'check_in_at'   => now(),
        ]);
    }

    return view('inv.guestbook.checked_in_success', [
        'invitation' => $invitation,
        'entry'      => $entry,
    ]);
}
private function toMsisdn(?string $phone, string $defaultCc = '62'): ?string
{
    if (!$phone) return null;
    $d = preg_replace('/\D+/', '', $phone);
    if ($d === '') return null;
    if (str_starts_with($d, '0'))  $d = $defaultCc . substr($d, 1);
    if (str_starts_with($d, '62')) $d = $d; // ok
    return $d;
}

// --- helper: buat QR base64 dari URL ---
private function makeQrBase64(string $url, int $size = 240): string
{
    $qr = QrCode::create($url)
        ->setEncoding(new Encoding('UTF-8'))
        ->setSize($size)
        ->setMargin(10)
        ->setForegroundColor(new Color(0,0,0))
        ->setBackgroundColor(new Color(255,255,255));
    $writer = new PngWriter();
    return base64_encode($writer->write($qr)->getString());
}

// --- helper: buat wa.me deep link ---
private function makeWaLink(?string $rawPhone, string $message): ?string
{
    $msisdn = $this->toMsisdn($rawPhone);
    $text   = rawurlencode($message);
    return $msisdn ? "https://wa.me/{$msisdn}?text={$text}" : null;
}
public function myQrForm(Invitation $invitation)
{
    return view('inv.guestbook.my_qr_form', compact('invitation'));
}

public function myQrFetch(Request $r, Invitation $invitation)
{
    $data = $r->validate([
        'phone' => ['nullable','string','max:30'],
        'name'  => ['required_without:phone','string','max:120'],
        'code'  => ['nullable','string','max:255'],
    ]);

    $entry = GuestEntry::where('invitation_id',$invitation->id)
        ->when($data['phone'] ?? null, fn($q)=>$q->where('phone',$data['phone']))
        ->when($data['code']  ?? null, fn($q)=>$q->where('referral_code',$data['code']))
        ->when(($data['phone'] ?? null)===null && ($data['code'] ?? null)===null,
               fn($q)=>$q->where('name','like','%'.$data['name'].'%'))
        ->latest()->first();

    if (!$entry){
        return back()->withErrors(['notfound'=>'Data tidak ditemukan. Coba periksa nama/HP/kode.'])->withInput();
    }

    $expires = optional($invitation->event_datetime)?->copy()->addDays(2) ?? now()->addDays(7);
    $qrUrl   = URL::temporarySignedRoute('guest_entries.scan_checkin', $expires, [
        'invitation' => $invitation->slug, 'entry' => $entry->id
    ]);

    $qrPng  = $this->makeQrBase64($qrUrl);
    $msg    = "Halo {$entry->name},\nQR check-in untuk \"{$invitation->title}\":\n{$qrUrl}";
    $waLink = $this->makeWaLink($entry->phone, $msg);

    return view('inv.guestbook.my_qr_show', compact('invitation','entry','qrUrl','qrPng','waLink'));
}
protected function sendWhatsApp($phone, $message)
    {
        
        if (str_starts_with($phone, '0')) {
            $phone = '+62' . substr($phone, 1);
        }

        try {
            $client = new Client();
            $client->post('https://api.fonnte.com/send', [
                'headers' => [
                    'Authorization' => env('FONNTE_TOKEN'),
                ],
                'form_params' => [
                    'target' => $phone,
                    'message' => $message,
                    'delay' => 2,
                ]
            ]);
           
        } catch (\Exception $e) {
            \Log::error("❌ Gagal kirim WA ke {$phone}: " . $e->getMessage());
        }
    }
}
