<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route as RouteFacade;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Logo\Logo;

class ReferralQrController extends Controller
{
    /* ====================== Helpers ====================== */

    /** Set ECC = HIGH yang aman untuk v5 (enum) maupun v4 (class). */
    private function applyEcc($qr) {
        // v5: enum ErrorCorrectionLevel::High
        if (function_exists('enum_exists') && enum_exists(\Endroid\QrCode\ErrorCorrectionLevel::class)) {
            return $qr->setErrorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High);
        }
        // v4: class ErrorCorrectionLevelHigh (atau value object)
        if (class_exists(\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh::class)) {
            return $qr->setErrorCorrectionLevel(
                new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh()
            );
        }
        if (class_exists(\Endroid\QrCode\ErrorCorrectionLevel::class)) {
            $lvl = \Endroid\QrCode\ErrorCorrectionLevel::class;
            return $qr->setErrorCorrectionLevel(new $lvl($lvl::HIGH));
        }
        return $qr;
    }

    /** Generator PNG generic untuk URL apa pun. */
    private function makeQrPng(string $url, int $size = 400, ?int $logoWidth = null): string
    {
        $qr = QrCode::create($url)
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize($size)
            ->setMargin(10)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $qr = $this->applyEcc($qr);

        $logo = null;
        $logoPath = public_path('images/logo1.png'); // opsional
        if (is_file($logoPath)) {
            $logo = Logo::create($logoPath)
                ->setResizeToWidth($logoWidth ?? (int) round($size * 0.22))
                ->setPunchoutBackground(true);
        }

        return (new PngWriter())->write($qr, $logo)->getString();
    }

    /* ====================== QR Referral (pendaftaran) ====================== */

    protected function buildTargetUrl(string $ref): string
    {
        if (RouteFacade::has('register.form')) {
            return route('register.form', ['ref' => $ref]);
        }
        return url('/register?ref='.$ref);
    }

    /** <img> QR referral */
    public function png(Request $request)
    {
        try {
            $user = $request->user();
            abort_unless($user && $user->referral_code, 404, 'Referral tidak ditemukan');

            $url = $this->buildTargetUrl($user->referral_code);
            $png = $this->makeQrPng($url, 400, 90);

            return response($png)->header('Content-Type', 'image/png');
        } catch (\Throwable $e) {
            Log::error('QR PNG error', ['msg' => $e->getMessage()]);
            return response('QR gagal dibuat', 500);
        }
    }

    /** Unduh QR referral */
    public function download(Request $request)
    {
        try {
            $user = $request->user();
            abort_unless($user && $user->referral_code, 404, 'Referral tidak ditemukan');

            $url = $this->buildTargetUrl($user->referral_code);
            $png = $this->makeQrPng($url, 800, 160);

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

    /* ====================== QR Undangan (buku tamu) ====================== */

    /** Target URL form buku tamu undangan: /i/{slug}/g?ref=...&src=INV */
    protected function buildInvitationUrl(string $ref, string $slug): string
    {
        $u = route('guest.form.inv', $slug);
        $sep = str_contains($u, '?') ? '&' : '?';
        return $u.$sep.http_build_query(['ref' => $ref, 'src' => 'INV']);
    }

    /** <img> QR undangan */
    public function invitationPng(Request $request, string $slug)
    {
        try {
            $user = $request->user();
            abort_unless($user, 403);

            $inv = Invitation::where('slug', $slug)->firstOrFail();
            abort_unless($inv->created_by == $user->id || in_array($user->role, ['admin','super-admin']), 403);

            $ref  = $user->referral_code ?? $user->username ?? ('REF'.str_pad((string)$user->id, 4, '0', STR_PAD_LEFT));
            $size = (int) $request->query('size', 420);

            $url = $this->buildInvitationUrl($ref, $slug);
            $png = $this->makeQrPng($url, $size, (int) round($size * 0.2));

            return response($png)->header('Content-Type', 'image/png');
        } catch (\Throwable $e) {
            Log::error('INV QR error', ['msg' => $e->getMessage()]);
            return response('QR gagal dibuat', 500);
        }
    }

    /** Unduh QR undangan */
    public function invitationDownload(Request $request, string $slug)
    {
        try {
            $user = $request->user();
            abort_unless($user, 403);

            $inv = Invitation::where('slug', $slug)->firstOrFail();
            abort_unless($inv->created_by == $user->id || in_array($user->role, ['admin','super-admin']), 403);

            $ref  = $user->referral_code ?? $user->username ?? ('REF'.str_pad((string)$user->id, 4, '0', STR_PAD_LEFT));
            $size = (int) $request->query('size', 1000);

            $url = $this->buildInvitationUrl($ref, $slug);
            $png = $this->makeQrPng($url, $size, (int) round($size * 0.2));

            return response($png, 200, [
                'Content-Type'        => 'image/png',
                'Content-Disposition' => 'attachment; filename="inv-'.$slug.'-'.$ref.'.png"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'              => 'no-cache',
            ]);
        } catch (\Throwable $e) {
            Log::error('INV QR DL error', ['msg' => $e->getMessage()]);
            return response('QR gagal diunduh', 500);
        }
    }
}
