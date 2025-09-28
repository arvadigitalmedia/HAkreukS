<?php
/**
 * Mailketing Helper Class
 * Integrasi API Mailketing untuk Simple Aff Plus
 * Menggantikan fungsi SMTP dengan API Mailketing
 */

class MailketingHelper {
    private $apiToken;
    private $fromEmail;
    private $fromName;
    private $defaultListId;
    private $apiUrl = 'https://api.mailketing.co.id/api/v1/';
    
    public function __construct() {
        global $settings;
        
        // Load dari database jika tidak ada di $settings
        if (empty($settings['mailketing_api_token']) && empty($settings['api_key'])) {
            $this->loadConfigFromDatabase();
        }
        
        $this->apiToken = $settings['mailketing_api_token'] ?? $settings['api_key'] ?? '';
        $this->fromEmail = $settings['mailketing_from_email'] ?? $settings['from_email'] ?? '';
        $this->fromName = $settings['mailketing_from_name'] ?? $settings['from_name'] ?? 'Simple Aff Plus';
        $this->defaultListId = $settings['mailketing_default_list_id'] ?? $settings['default_list_id'] ?? 1;
    }
    
    /**
     * Load konfigurasi dari database
     */
    private function loadConfigFromDatabase() {
        global $settings;
        
        $sql = "SELECT config_key, config_value FROM epi_mailketing_config";
        $result = db_query($sql);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $settings[$row['config_key']] = $row['config_value'];
                // Mapping untuk backward compatibility
                if ($row['config_key'] == 'api_key') {
                    $settings['mailketing_api_token'] = $row['config_value'];
                }
            }
        }
    }
    
    /**
     * Kirim email via Mailketing API
     */
    public function sendEmail($to, $subject, $content, $fromName = null) {
        if (empty($this->apiToken) || empty($this->fromEmail)) {
            $this->logActivity($to, $subject, 'failed', 'API Token atau From Email belum dikonfigurasi');
            return false;
        }
        
        $params = [
            'from_name' => $fromName ?? $this->fromName,
            'from_email' => $this->fromEmail,
            'recipient' => $to,
            'subject' => $subject,
            'content' => $content,
            'api_token' => $this->apiToken
        ];
        
        $response = $this->makeApiCall('send', $params);
        
        // Log aktivitas
        if ($response && isset($response['status']) && ($response['status'] === true || $response['status'] == 'success' || $response['status'] == '1')) {
            $this->logActivity($to, $subject, 'success', 'Email berhasil dikirim');
            return true;
        } else {
            $errorMsg = isset($response['message']) ? $response['message'] : (isset($response['response']) ? $response['response'] : 'Unknown error');
            $this->logActivity($to, $subject, 'failed', $errorMsg);
            return false;
        }
    }
    
    /**
     * Tambah subscriber ke list
     */
    public function addSubscriber($email, $firstName, $lastName = '', $listId = null) {
        if (empty($this->apiToken)) {
            return false;
        }
        
        $params = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'api_token' => $this->apiToken,
            'list_id' => $listId ?? $this->defaultListId,
            'country' => 'Indonesia'
        ];
        
        $response = $this->makeApiCall('addsubtolist', $params);
        return $response && isset($response['status']) && $response['status'] == 'success';
    }
    
    /**
     * Cek saldo credits
     */
    public function checkCredits() {
        if (empty($this->apiToken)) {
            return false;
        }
        
        $params = ['api_token' => $this->apiToken];
        return $this->makeApiCall('ceksaldo', $params);
    }
    
    /**
     * Get all lists
     */
    public function getLists() {
        if (empty($this->apiToken)) {
            return false;
        }
        
        $params = ['api_token' => $this->apiToken];
        return $this->makeApiCall('viewlist', $params);
    }
    
    /**
     * Kirim notifikasi registrasi
     */
    public function sendRegistrationNotification($memberData, $sponsorData = null, $target = 'member') {
        global $settings;
        
        $templateKey = 'mailketing_template_daftar_' . $target;
        $template = $settings[$templateKey] ?? '';
        
        if (empty($template)) {
            return false;
        }
        
        // Replace shortcodes
        $content = $this->replaceShortcodes($template, $memberData, $sponsorData);
        $subject = $this->getSubject('registrasi', $target);
        
        switch ($target) {
            case 'member':
                $to = $memberData['mem_email'];
                break;
            case 'sponsor':
                $to = $sponsorData ? $sponsorData['mem_email'] : '';
                break;
            case 'admin':
                $to = $settings['admin_email'] ?? '';
                break;
            default:
                return false;
        }
        
        if (empty($to)) {
            return false;
        }
        
        return $this->sendEmail($to, $subject, $content);
    }
    
    /**
     * Kirim notifikasi upgrade
     */
    public function sendUpgradeNotification($memberData, $sponsorData = null, $target = 'member') {
        global $settings;
        
        $templateKey = 'mailketing_template_upgrade_' . $target;
        $template = $settings[$templateKey] ?? '';
        
        if (empty($template)) {
            return false;
        }
        
        $content = $this->replaceShortcodes($template, $memberData, $sponsorData);
        $subject = $this->getSubject('upgrade', $target);
        
        switch ($target) {
            case 'member':
                $to = $memberData['mem_email'];
                break;
            case 'sponsor':
                $to = $sponsorData ? $sponsorData['mem_email'] : '';
                break;
            default:
                return false;
        }
        
        if (empty($to)) {
            return false;
        }
        
        return $this->sendEmail($to, $subject, $content);
    }
    
    /**
     * Kirim notifikasi order produk
     */
    public function sendOrderNotification($memberData, $orderData, $sponsorData = null, $target = 'member') {
        global $settings;
        
        $templateKey = 'mailketing_template_order_' . $target;
        $template = $settings[$templateKey] ?? '';
        
        if (empty($template)) {
            return false;
        }
        
        $content = $this->replaceShortcodes($template, $memberData, $sponsorData, $orderData);
        $subject = $this->getSubject('order', $target);
        
        switch ($target) {
            case 'member':
                $to = $memberData['mem_email'];
                break;
            case 'sponsor':
                $to = $sponsorData ? $sponsorData['mem_email'] : '';
                break;
            default:
                return false;
        }
        
        if (empty($to)) {
            return false;
        }
        
        return $this->sendEmail($to, $subject, $content);
    }
    
    /**
     * Kirim notifikasi proses order
     */
    public function sendProcessOrderNotification($memberData, $orderData, $sponsorData = null, $target = 'member') {
        global $settings;
        
        $templateKey = 'mailketing_template_prosesorder_' . $target;
        $template = $settings[$templateKey] ?? '';
        
        if (empty($template)) {
            return false;
        }
        
        $content = $this->replaceShortcodes($template, $memberData, $sponsorData, $orderData);
        $subject = $this->getSubject('prosesorder', $target);
        
        switch ($target) {
            case 'member':
                $to = $memberData['mem_email'];
                break;
            case 'sponsor':
                $to = $sponsorData ? $sponsorData['mem_email'] : '';
                break;
            default:
                return false;
        }
        
        if (empty($to)) {
            return false;
        }
        
        return $this->sendEmail($to, $subject, $content);
    }
    
    /**
     * Kirim notifikasi pencairan komisi
     */
    public function sendCommissionNotification($memberData, $commissionData) {
        global $settings;
        
        $template = $settings['mailketing_template_cair_komisi_member'] ?? '';
        
        if (empty($template)) {
            return false;
        }
        
        $content = $this->replaceShortcodes($template, $memberData, null, null, $commissionData);
        $subject = $this->getSubject('cair_komisi', 'member');
        
        return $this->sendEmail($memberData['mem_email'], $subject, $content);
    }
    
    /**
     * Replace shortcodes dalam template
     */
    private function replaceShortcodes($template, $memberData, $sponsorData = null, $orderData = null, $commissionData = null) {
        // Member shortcodes
        if ($memberData) {
            foreach ($memberData as $key => $value) {
                $shortcode = '[member_' . str_replace('mem_', '', $key) . ']';
                $template = str_replace($shortcode, $value, $template);
            }
        }
        
        // Sponsor shortcodes
        if ($sponsorData) {
            foreach ($sponsorData as $key => $value) {
                $shortcode = '[sponsor_' . str_replace('mem_', '', $key) . ']';
                $template = str_replace($shortcode, $value, $template);
            }
        }
        
        // Order shortcodes
        if ($orderData) {
            $template = str_replace('[idorder]', $orderData['order_id'] ?? '', $template);
            $template = str_replace('[hrgunik]', $orderData['harga_unik'] ?? '', $template);
            $template = str_replace('[hrgproduk]', $orderData['harga_produk'] ?? '', $template);
            $template = str_replace('[namaproduk]', $orderData['nama_produk'] ?? '', $template);
            $template = str_replace('[urlproduk]', $orderData['url_produk'] ?? '', $template);
        }
        
        // Commission shortcodes
        if ($commissionData) {
            $template = str_replace('[komisi]', $commissionData['jumlah'] ?? '', $template);
        }
        
        return $template;
    }
    
    /**
     * Get subject berdasarkan jenis dan target
     */
    private function getSubject($type, $target) {
        global $settings;
        
        $subjectKey = 'mailketing_subject_' . $type . '_' . $target;
        $defaultSubjects = [
            'registrasi_member' => 'Selamat Datang di Simple Aff Plus!',
            'registrasi_sponsor' => 'Member Baru Bergabung',
            'registrasi_admin' => 'Registrasi Member Baru',
            'upgrade_member' => 'Upgrade Berhasil!',
            'upgrade_sponsor' => 'Member Upgrade',
            'order_member' => 'Konfirmasi Order',
            'order_sponsor' => 'Order Baru dari Member',
            'prosesorder_member' => 'Order Sedang Diproses',
            'prosesorder_sponsor' => 'Order Member Diproses',
            'cair_komisi_member' => 'Pencairan Komisi'
        ];
        
        return $settings[$subjectKey] ?? $defaultSubjects[$type . '_' . $target] ?? 'Notifikasi Simple Aff Plus';
    }
    
    /**
     * Make API call ke Mailketing
     */
    private function makeApiCall($endpoint, $params) {
        $url = $this->apiUrl . $endpoint;
        
        // Debug log
        error_log("Mailketing API Call: " . $url);
        error_log("Mailketing API Params: " . print_r($params, true));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: SimpleAffPlus/1.0',
            'Accept: application/json'
        ]);
        
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Debug log
        error_log("Mailketing API Response Code: " . $httpCode);
        error_log("Mailketing API Response: " . $output);
        if ($curlError) {
            error_log("Mailketing API Curl Error: " . $curlError);
        }
        
        if ($curlError) {
            return [
                'status' => 'error',
                'message' => 'Curl Error: ' . $curlError
            ];
        }
        
        if ($httpCode == 200 && $output) {
            $decoded = json_decode($output, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Invalid JSON response: ' . $output
                ];
            }
        }
        
        return [
            'status' => 'error',
            'message' => 'HTTP Error ' . $httpCode . ': ' . $output
        ];
    }
    
    /**
     * Log aktivitas email
     */
    private function logActivity($email, $subject, $status, $response = '') {
        global $con;
        
        if (!$con) {
            return; // Skip logging jika tidak ada koneksi
        }
        
        $sql = "INSERT INTO `epi_email_log` 
                (`email`, `subject`, `status`, `response`, `sent_at`) 
                VALUES (?, ?, ?, ?, NOW())";
        
        try {
            $stmt = $con->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ssss', $email, $subject, $status, $response);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            // Silent fail untuk logging
            error_log("MailketingHelper logActivity error: " . $e->getMessage());
        }
    }
    
    /**
     * Get email logs
     */
    public function getEmailLogs($limit = 50, $offset = 0) {
        global $con;
        
        if (!$con) {
            return []; // Return empty jika tidak ada koneksi
        }
        
        $sql = "SELECT * FROM `epi_email_log` 
                ORDER BY `sent_at` DESC 
                LIMIT ? OFFSET ?";
        
        try {
            $stmt = $con->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ii', $limit, $offset);
                $stmt->execute();
                $result = $stmt->get_result();
                $logs = [];
                while ($row = $result->fetch_assoc()) {
                    $logs[] = $row;
                }
                $stmt->close();
                return $logs;
            }
            return [];
        } catch (Exception $e) {
            error_log("MailketingHelper getEmailLogs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Test koneksi ke Mailketing API
     */
    public function testConnection() {
        try {
            // Debug: Cek konfigurasi
            if (empty($this->apiToken)) {
                return [
                    'status' => 'error',
                    'success' => false,
                    'message' => 'API Token tidak ditemukan. Pastikan konfigurasi sudah disimpan.',
                    'debug' => 'API Token kosong'
                ];
            }
            
            $credits = $this->checkCredits();
            
            // Debug: Log response
            error_log("Mailketing API Response: " . print_r($credits, true));
            
            if ($credits && isset($credits['status']) && ($credits['status'] === true || $credits['status'] == 'success' || $credits['status'] == '1')) {
                $result = [
                    'status' => 'success',
                    'success' => true,
                    'message' => 'Koneksi berhasil! Sisa kredit: ' . ($credits['credits'] ?? 'N/A'),
                    'credits' => $credits['credits'] ?? 0,
                    'user_info' => $credits['user_info'] ?? []
                ];
            } else {
                $errorMsg = 'Koneksi gagal atau API token tidak valid';
                if (is_array($credits)) {
                    $errorMsg .= ' - ' . ($credits['message'] ?? $credits['response'] ?? 'Unknown error');
                } elseif (is_string($credits)) {
                    $errorMsg .= ' - ' . $credits;
                }
                
                $result = [
                    'status' => 'error',
                    'success' => false,
                    'message' => $errorMsg,
                    'debug' => [
                        'api_token' => substr($this->apiToken, 0, 8) . '...',
                        'api_url' => $this->apiUrl,
                        'response' => $credits
                    ]
                ];
            }
            
            return $result;
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'debug' => $e->getTraceAsString()
            ];
        }
    }
    
    /**
     * Kirim test email
     */
    public function sendTestEmail($email) {
        try {
            // Debug: Log konfigurasi
            error_log("=== MAILKETING TEST EMAIL DEBUG ===");
            error_log("API Token: " . (empty($this->apiToken) ? 'KOSONG' : 'ADA (' . strlen($this->apiToken) . ' chars)'));
            error_log("From Email: " . $this->fromEmail);
            error_log("From Name: " . $this->fromName);
            error_log("Target Email: " . $email);
            
            $subject = 'Test Email dari Mailketing - ' . date('d/m/Y H:i');
            $content = '
                <h2>Test Email Berhasil!</h2>
                <p>Selamat! Koneksi Mailketing Anda berfungsi dengan baik.</p>
                <hr>
                <p><strong>Detail Test:</strong></p>
                <ul>
                    <li>Email Tujuan: ' . $email . '</li>
                    <li>Waktu Kirim: ' . date('d/m/Y H:i:s') . '</li>
                    <li>Provider: Mailketing API</li>
                    <li>Status: Berhasil</li>
                </ul>
                <hr>
                <p><small>Email ini dikirim otomatis dari sistem Simple Aff Plus untuk testing koneksi Mailketing.</small></p>
            ';
            
            // Debug: Test API call langsung
            $params = [
                'from_name' => $this->fromName,
                'from_email' => $this->fromEmail,
                'recipient' => $email,
                'subject' => $subject,
                'content' => $content,
                'api_token' => $this->apiToken
            ];
            
            error_log("API Parameters: " . json_encode($params, JSON_UNESCAPED_UNICODE));
            
            $response = $this->makeApiCall('send', $params);
            error_log("API Response: " . json_encode($response, JSON_UNESCAPED_UNICODE));
            
            // Analisis response
            if ($response && isset($response['status'])) {
                if ($response['status'] === true || $response['status'] == 'success' || $response['status'] == '1') {
                    $this->logActivity($email, $subject, 'success', 'Test email berhasil dikirim');
                    return [
                        'success' => true,
                        'message' => 'Test email Mailketing berhasil dikirim ke ' . $email,
                        'debug' => $response
                    ];
                } else {
                    $errorMsg = isset($response['message']) ? $response['message'] : (isset($response['response']) ? $response['response'] : 'Status tidak success');
                    $this->logActivity($email, $subject, 'failed', $errorMsg);
                    return [
                        'success' => false,
                        'message' => 'Gagal mengirim test email: ' . $errorMsg,
                        'debug' => $response
                    ];
                }
            } else {
                $this->logActivity($email, $subject, 'failed', 'Response API tidak valid');
                return [
                    'success' => false,
                    'message' => 'Response API tidak valid atau kosong',
                    'debug' => $response
                ];
            }
        } catch (Exception $e) {
            error_log("Exception in sendTestEmail: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create email template in Mailketing
     */
    public function createTemplate($name, $content) {
        try {
            $data = [
                'name' => $name,
                'content' => $content,
                'type' => 'html'
            ];
            
            $response = $this->makeRequest('POST', '/templates', $data);
            
            return [
                'success' => true,
                'template_id' => $response['id'] ?? null,
                'message' => 'Template berhasil dibuat',
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update email template in Mailketing
     */
    public function updateTemplate($templateId, $name, $content) {
        try {
            $data = [
                'name' => $name,
                'content' => $content,
                'type' => 'html'
            ];
            
            $response = $this->makeRequest('PUT', '/templates/' . $templateId, $data);
            
            return [
                'success' => true,
                'message' => 'Template berhasil diupdate',
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get template from Mailketing
     */
    public function getTemplate($templateId) {
        try {
            $response = $this->makeRequest('GET', '/templates/' . $templateId);
            
            return [
                'success' => true,
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all templates from Mailketing
     */
    public function getTemplates() {
        try {
            $response = $this->makeRequest('GET', '/templates');
            
            return [
                'success' => true,
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
?>