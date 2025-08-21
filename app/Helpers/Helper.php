<?php

namespace App\Helpers;

if (!function_exists('getNotificationIcon')) {
    /**
     * Get notification icon based on type
     *
     * @param string $type
     * @return string
     */
    function getNotificationIcon($type)
    {
        $icons = [
            'new_referral' => 'fa-user-plus',
            'withdraw_request' => 'fa-money-bill',
            'withdraw_approved' => 'fa-check-circle',
            'bonus_received' => 'fa-gift',
            'finance_approved' => 'fa-check-circle',
            'finance_rejected' => 'fa-times-circle',
            'admin_generate' => 'fa-check-circle',
            'member_request_bonus' => 'fa-money-bill',
            'pairing_downline' => 'fa-users',
            'new_member_registered' => 'fa-user-check',
            'rejected_pin' => 'fa-times-circle',
            'approved_pin' => 'fa-check-circle'
        ];
        
        return $icons[$type] ?? 'fa-bell';
    }
}

if (!function_exists('getNotificationTitle')) {
    /**
     * Get notification title based on type
     *
     * @param string $type
     * @return string
     */
    function getNotificationTitle($type)
    {
        $titles = [
            'new_referral' => 'Referral Baru',
            'withdraw_request' => 'Withdraw Masuk',
            'withdraw_approved' => 'Withdraw Disetujui',
            'bonus_received' => 'Bonus Masuk',
            'finance_approved' => 'Finance menyetujui aktivasi pin',
            'finance_rejected' => 'Finance menolak aktivasi pin',
            'admin_generate' => 'Admin telah generate pin aktivasi anda',
            'member_request_bonus' => 'Member meminta pengajuan penarikan bonus',
            'pairing_downline' => 'User berhasil dipasang ke tree',
            'new_member_registered' => 'User berhasil register menggunakan Kode Referal dan Pin anda',
            'rejected_pin' => 'PIN Ditolak',
            'approved_pin' => 'PIN Disetujui'
        ];
        
        return $titles[$type] ?? 'Notifikasi Baru';
    }
}

if (!function_exists('getNotificationColor')) {
    /**
     * Get notification color/class based on type
     *
     * @param string $type
     * @return string
     */
    function getNotificationColor($type)
    {
        $colors = [
            'new_referral' => 'success',
            'withdraw_request' => 'warning',
            'withdraw_approved' => 'success',
            'bonus_received' => 'primary',
            'finance_approved' => 'success',
            'finance_rejected' => 'danger',
            'admin_generate' => 'info',
            'member_request_bonus' => 'warning',
            'pairing_downline' => 'primary',
            'new_member_registered' => 'success',
            'rejected_pin' => 'danger',
            'approved_pin' => 'success'
        ];
        
        return $colors[$type] ?? 'secondary';
    }
}

if (!function_exists('formatRupiah')) {
    /**
     * Format currency to Indonesian Rupiah
     *
     * @param float $amount
     * @return string
     */
    function formatRupiah($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}