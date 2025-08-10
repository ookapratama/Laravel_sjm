<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Log;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;     // ✅ perbaiki ini
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Logo\Logo;

class ReferralQrController extends Controller
{
    protected function buildTargetUrl(string $ref): string
    {
        if (RouteFacade::has('register.form')) {
            return route('register.form', ['ref' => $ref]);
        }
        if (RouteFacade::has('pre-register.form')) {
            return route('pre-register.form', ['ref' => $ref]);
        }
        return url('/register?ref='.$ref); // fallback
    }

    protected function makePng(string $url, int $size, ?int $logoWidth = null): string
    {
        $qr = QrCode::create($url)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)   // ✅ v5 style
            ->setSize($size)
            ->setMargin(10)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $writer = new PngWriter();

        $logo = null;
        $logoPath = public_path('images/logo1.png'); // sesuai asset('images/logo.png')
        if (is_file($logoPath)) {
            $logo = Logo::create($logoPath)
                ->setResizeToWidth($logoWidth ?? (int)round($size * 0.22))
                ->setPunchoutBackground(true);
        }

        return $writer->write($qr, $logo)->getString();
    }

    public function png(Request $request)
    {
        try {
            $user = $request->user();
            abort_unless($user && $user->referral_code, 404, 'Referral tidak ditemukan');

            $url = $this->buildTargetUrl($user->referral_code);
            $png = $this->makePng($url, 400, 90);

            return response($png)->header('Content-Type', 'image/png');
        } catch (\Throwable $e) {
            Log::error('QR PNG error', ['msg' => $e->getMessage()]);
            return response('QR gagal dibuat', 500);
        }
    }

    public function download(Request $request)
    {
        try {
            $user = $request->user();
            abort_unless($user && $user->referral_code, 404, 'Referral tidak ditemukan');

            $url = $this->buildTargetUrl($user->referral_code);
            $png = $this->makePng($url, 800, 160);

            return response($png, 200, [
                'Content-Type'        => 'image/png',
                'Content-Disposition' => 'attachment; filename="ref-'.$user->referral_code.'.png"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'              => 'no-cache',
            ]);
        } catch (\Throwable $e) {
            Log::error('QR DOWNLOAD error', ['msg' => $e->getMessage()]);
            return response('QR gagal diunduh', 500);
        }
    }
}
