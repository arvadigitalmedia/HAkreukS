<?php
/**
 * EmailService - Pengganti SMTP dengan Mailketing
 * Menggantikan fungsi smtpmailer() yang ada
 * 
 * @author Arva Digital Media
 * @version 1.0
 */

require_once 'MailketingHelper.php';

class EmailService {
    private $mailketingHelper;
    private $config;
    private $isMailketingEnabled = false;
    
    public function __construct() {
        $this->loadConfig();
        $this->mailketingHelper = new MailketingHelper();
    }
    
    /**
     * Load konfigurasi dari database
     */
    private function loadConfig() {
        // Load konfigurasi Mailketing
        $mailketingConfig = db_select_one("SELECT * FROM epi_mailketing_config WHERE id = 1");
        $this->config = $mailketingConfig ?: [];
        $this->isMailketingEnabled = ($this->config['is_enabled'] ?? '0') == '1';
    }
    
    /**
     * Fungsi utama untuk mengirim email
     * Menggantikan smtpmailer()
     * 
     * @param string $to Email penerima
     * @param string $subject Subject email
     * @param string $message Isi email (HTML)
     * @param string $from Email pengirim (optional)
     * @param string $fromName Nama pengirim (optional)
     * @return bool Status pengiriman
     */
    public function sendEmail($to, $subject, $message, $from = null, $fromName = null) {
        try {
            if ($this->isMailketingEnabled) {
                return $this->sendViaMailketing($to, $subject, $message, $from, $fromName);
            } else {
                return $this->sendViaSMTP($to, $subject, $message, $from, $fromName);
            }
        } catch (Exception $e) {
            error_log("EmailService Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kirim email via Mailketing
     */
    private function sendViaMailketing($to, $subject, $message, $from = null, $fromName = null) {
        // Cek apakah dalam mode testing
        if (($this->config['test_mode'] ?? '1') == '1') {
            $this->logEmail('test', $to, $subject, 'test_mode', 'Email dalam mode testing');
            return true;
        }
        
        $result = $this->mailketingHelper->sendEmail($to, $subject, $message);
        
        if ($result['success']) {
            $this->logEmail('mailketing', $to, $subject, 'sent', 'Email berhasil dikirim via Mailketing');
            return true;
        } else {
            $this->logEmail('mailketing', $to, $subject, 'failed', $result['message']);
            return false;
        }
    }
    
    /**
     * Kirim email via SMTP (fallback)
     */
    private function sendViaSMTP($to, $subject, $message, $from = null, $fromName = null) {
        // Gunakan fungsi SMTP yang sudah ada
        if (function_exists('smtpmailer')) {
            $result = smtpmailer($to, $subject, $message, $from, $fromName);
            $status = $result ? 'sent' : 'failed';
            $this->logEmail('smtp', $to, $subject, $status, 'Email dikirim via SMTP');
            return $result;
        }
        
        return false;
    }
    
    /**
     * Kirim notifikasi registrasi member
     */
    public function sendRegistrationNotification($memberData, $sponsorData = null) {
        $notifications = [];
        
        // Notifikasi ke Member
        $memberResult = $this->mailketingHelper->sendRegistrationMember($memberData);
        $notifications['member'] = $memberResult;
        
        // Notifikasi ke Sponsor (jika ada)
        if ($sponsorData) {
            $sponsorResult = $this->mailketingHelper->sendRegistrationSponsor($memberData, $sponsorData);
            $notifications['sponsor'] = $sponsorResult;
        }
        
        // Notifikasi ke Admin
        $adminResult = $this->mailketingHelper->sendRegistrationAdmin($memberData);
        $notifications['admin'] = $adminResult;
        
        return $notifications;
    }
    
    /**
     * Kirim notifikasi upgrade member
     */
    public function sendUpgradeNotification($memberData, $upgradeData, $sponsorData = null) {
        $notifications = [];
        
        // Notifikasi ke Member
        $memberResult = $this->mailketingHelper->sendUpgradeMember($memberData, $upgradeData);
        $notifications['member'] = $memberResult;
        
        // Notifikasi ke Sponsor (jika ada)
        if ($sponsorData) {
            $sponsorResult = $this->mailketingHelper->sendUpgradeSponsor($memberData, $upgradeData, $sponsorData);
            $notifications['sponsor'] = $sponsorResult;
        }
        
        // Notifikasi ke Admin
        $adminResult = $this->mailketingHelper->sendUpgradeAdmin($memberData, $upgradeData);
        $notifications['admin'] = $adminResult;
        
        return $notifications;
    }
    
    /**
     * Kirim notifikasi order produk
     */
    public function sendOrderNotification($memberData, $orderData, $sponsorData = null) {
        $notifications = [];
        
        // Notifikasi ke Member
        $memberResult = $this->mailketingHelper->sendOrderMember($memberData, $orderData);
        $notifications['member'] = $memberResult;
        
        // Notifikasi ke Sponsor (jika ada)
        if ($sponsorData) {
            $sponsorResult = $this->mailketingHelper->sendOrderSponsor($memberData, $orderData, $sponsorData);
            $notifications['sponsor'] = $sponsorResult;
        }
        
        // Notifikasi ke Admin
        $adminResult = $this->mailketingHelper->sendOrderAdmin($memberData, $orderData);
        $notifications['admin'] = $adminResult;
        
        return $notifications;
    }
    
    /**
     * Kirim notifikasi proses order
     */
    public function sendProcessOrderNotification($memberData, $orderData, $sponsorData = null) {
        $notifications = [];
        
        // Notifikasi ke Member
        $memberResult = $this->mailketingHelper->sendProcessOrderMember($memberData, $orderData);
        $notifications['member'] = $memberResult;
        
        // Notifikasi ke Admin
        $adminResult = $this->mailketingHelper->sendProcessOrderAdmin($memberData, $orderData);
        $notifications['admin'] = $adminResult;
        
        return $notifications;
    }
    
    /**
     * Kirim notifikasi pencairan komisi
     */
    public function sendWithdrawalNotification($memberData, $withdrawalData) {
        $notifications = [];
        
        // Notifikasi ke Member
        $memberResult = $this->mailketingHelper->sendWithdrawalMember($memberData, $withdrawalData);
        $notifications['member'] = $memberResult;
        
        // Notifikasi ke Admin
        $adminResult = $this->mailketingHelper->sendWithdrawalAdmin($memberData, $withdrawalData);
        $notifications['admin'] = $adminResult;
        
        return $notifications;
    }
    
    /**
     * Kirim email reset password
     */
    public function sendPasswordReset($memberData, $resetToken) {
        $subject = "Reset Password - " . SITE_NAME;
        $resetLink = SITE_URL . "/sareset.php?token=" . $resetToken;
        
        $message = "
        <h3>Reset Password</h3>
        <p>Halo {$memberData['nama']},</p>
        <p>Anda telah meminta reset password untuk akun Anda.</p>
        <p>Klik link berikut untuk reset password:</p>
        <p><a href='{$resetLink}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
        <p>Link ini akan expired dalam 24 jam.</p>
        <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
        <br>
        <p>Terima kasih,<br>" . SITE_NAME . "</p>
        ";
        
        return $this->sendEmail($memberData['email'], $subject, $message);
    }
    
    /**
     * Test koneksi email
     */
    public function testConnection($testEmail = null) {
        if (!$testEmail) {
            $testEmail = 'test@example.com';
        }
        
        $subject = "Test Email - " . SITE_NAME;
        $message = "
        <h3>Test Email</h3>
        <p>Ini adalah email test dari sistem " . SITE_NAME . "</p>
        <p>Waktu: " . date('Y-m-d H:i:s') . "</p>
        <p>Status Mailketing: " . ($this->isMailketingEnabled ? 'Aktif' : 'Tidak Aktif') . "</p>
        ";
        
        return $this->sendEmail($testEmail, $subject, $message);
    }
    
    /**
     * Log aktivitas email
     */
    private function logEmail($provider, $recipient, $subject, $status, $message = '') {
        $data = [
            'email_type' => 'general',
            'recipient_email' => $recipient,
            'subject' => $subject,
            'provider' => $provider,
            'status' => $status,
            'response_message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        db_insert('epi_email_logs', $data);
    }
    
    /**
     * Get statistik email
     */
    public function getEmailStats($days = 7) {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        
        $stats = [
            'total' => 0,
            'sent' => 0,
            'failed' => 0,
            'test_mode' => 0,
            'by_provider' => []
        ];
        
        $logs = db_select("SELECT provider, status, COUNT(*) as count 
                          FROM epi_email_logs 
                          WHERE created_at >= '{$dateFrom}' 
                          GROUP BY provider, status");
        
        foreach ($logs as $log) {
            $stats['total'] += $log['count'];
            
            if ($log['status'] == 'sent') {
                $stats['sent'] += $log['count'];
            } elseif ($log['status'] == 'failed') {
                $stats['failed'] += $log['count'];
            } elseif ($log['status'] == 'test_mode') {
                $stats['test_mode'] += $log['count'];
            }
            
            if (!isset($stats['by_provider'][$log['provider']])) {
                $stats['by_provider'][$log['provider']] = 0;
            }
            $stats['by_provider'][$log['provider']] += $log['count'];
        }
        
        return $stats;
    }
    
    /**
     * Cek saldo kredit Mailketing
     */
    public function checkMailketingCredits() {
        if ($this->isMailketingEnabled) {
            return $this->mailketingHelper->checkCredits();
        }
        
        return ['success' => false, 'message' => 'Mailketing tidak aktif'];
    }
}

/**
 * Fungsi wrapper untuk kompatibilitas dengan kode yang sudah ada
 * Menggantikan smtpmailer()
 */
function sendEmailNotification($to, $subject, $message, $from = null, $fromName = null) {
    $emailService = new EmailService();
    return $emailService->sendEmail($to, $subject, $message, $from, $fromName);
}

/**
 * Fungsi untuk mengirim notifikasi berdasarkan jenis
 */
function sendNotificationByType($type, $memberData, $additionalData = [], $sponsorData = null) {
    $emailService = new EmailService();
    
    switch ($type) {
        case 'registration':
            return $emailService->sendRegistrationNotification($memberData, $sponsorData);
            
        case 'upgrade':
            return $emailService->sendUpgradeNotification($memberData, $additionalData, $sponsorData);
            
        case 'order':
            return $emailService->sendOrderNotification($memberData, $additionalData, $sponsorData);
            
        case 'process_order':
            return $emailService->sendProcessOrderNotification($memberData, $additionalData, $sponsorData);
            
        case 'withdrawal':
            return $emailService->sendWithdrawalNotification($memberData, $additionalData);
            
        case 'password_reset':
            return $emailService->sendPasswordReset($memberData, $additionalData['token']);
            
        default:
            return ['success' => false, 'message' => 'Jenis notifikasi tidak dikenal'];
    }
}
?>