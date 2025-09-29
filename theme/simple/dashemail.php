<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }

// Include MailketingHelper
require_once __DIR__ . '/../../class/MailketingHelper.php';

$head['pagetitle']='Setting Email Mailketing';
$head['scripthead'] = '
<style type="text/css">
.mailketing-section {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    border: 2px solid #FF8C00;
}
.mailketing-section h4 {
    color: #8B4513;
    font-weight: bold;
    margin-bottom: 15px;
}
.mailketing-toggle {
    background: #28a745;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 5px;
    cursor: pointer;
}
.mailketing-toggle.disabled {
    background: #dc3545;
}
.email-log-table {
    max-height: 400px;
    overflow-y: auto;
}
.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}
.status-sent { background: #28a745; color: white; }
.status-failed { background: #dc3545; color: white; }
.status-pending { background: #ffc107; color: black; }

.stat-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    margin-bottom: 15px;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
}

.credit-info {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    border-radius: 8px;
    padding: 20px;
    color: #333;
}

.credit-amount {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 10px 0;
}

.provider-stats {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-top: 15px;
}

.legacy-section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
    opacity: 0.7;
}

.legacy-section h5 {
    color: #6c757d;
    font-size: 1rem;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}
</style>';

showheader($head);

// Handle Mailketing configuration update
if (isset($_POST['mailketing_config'])) {
    $mailketingHelper = new MailketingHelper();
    
    // Update Mailketing configuration
    $configData = [
        'api_key' => $_POST['mailketing_api_key'] ?? '',
        'api_url' => $_POST['mailketing_api_url'] ?? 'https://api.mailketing.co.id',
        'from_email' => $_POST['mailketing_from_email'] ?? '',
        'from_name' => $_POST['mailketing_from_name'] ?? '',
        'default_list_id' => $_POST['mailketing_default_list_id'] ?? '',
        'is_enabled' => isset($_POST['mailketing_enabled']) ? '1' : '0',
        'test_mode' => isset($_POST['mailketing_test_mode']) ? '1' : '0'
    ];
    
    try {
        // Hapus konfigurasi lama
        db_query("DELETE FROM epi_mailketing_config");
        
        // Insert konfigurasi baru
        foreach ($configData as $key => $value) {
            $key_escaped = db_escape($key);
            $value_escaped = db_escape($value);
            
            $sql = "INSERT INTO epi_mailketing_config (config_key, config_value) VALUES ('$key_escaped', '$value_escaped')
                    ON DUPLICATE KEY UPDATE config_value = '$value_escaped'";
            db_query($sql);
        }
        
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Ok!</strong> Konfigurasi Mailketing telah disimpan.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> Gagal menyimpan konfigurasi Mailketing.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
}

// Handle Mailketing test email
if (isset($_GET['mailketing_test'])) {
    $mailketingHelper = new MailketingHelper();
    $testResult = $mailketingHelper->testConnection();
    
    if ($testResult['success']) {
        // Send test email
        $testEmail = $mailketingHelper->sendTestEmail($datamember['mem_email']);
        
        if ($testEmail['success']) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Berhasil!</strong> Test email Mailketing berhasil dikirim ke ' . $datamember['mem_email'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        } else {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> ' . ($testEmail['message'] ?? 'Gagal mengirim test email').'
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }
    } else {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> ' . ($testResult['message'] ?? 'Koneksi ke Mailketing gagal').'
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
}

// Get Mailketing configuration
$mailketingConfig = [];
$configResult = db_select("SELECT config_key, config_value FROM epi_mailketing_config");
foreach ($configResult as $config) {
    $mailketingConfig[$config['config_key']] = $config['config_value'];
}
?>

<div class="alert alert-info" role="alert">
    <h5><i class="fas fa-info-circle"></i> Informasi Penting</h5>
    <p><strong>Sistem email telah diupgrade ke Mailketing!</strong></p>
    <ul class="mb-0">
        <li>Template email editor telah dihapus dan diganti dengan integrasi Mailketing penuh</li>
        <li>Semua pengiriman email sekarang menggunakan API Mailketing</li>
        <li>Konfigurasi SMTP legacy masih tersedia sebagai fallback</li>
        <li>Pastikan API Key Mailketing sudah dikonfigurasi dengan benar</li>
    </ul>
</div>

<!-- Mailketing Configuration Section -->
<div class="mailketing-section">
    <h4><i class="fas fa-envelope-open-text"></i> Konfigurasi Mailketing API</h4>
    <form action="" method="post">
        <input type="hidden" name="mailketing_config" value="1">
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Status Mailketing</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="mailketing_enabled" id="mailketing_enabled"
                               <?= ($mailketingConfig['is_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="mailketing_enabled">
                            Aktifkan Mailketing (Menggantikan SMTP)
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">API Key Mailketing</label>
                    <input type="password" class="form-control" name="mailketing_api_key"
                           value="<?= $mailketingConfig['api_key'] ?? '' ?>"
                           placeholder="Masukkan API Key Mailketing">
                    <small class="form-text text-muted">Dapatkan API Key dari dashboard Mailketing Anda</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">API URL</label>
                    <input type="text" class="form-control" name="mailketing_api_url"
                           value="<?= $mailketingConfig['api_url'] ?? 'https://api.mailketing.co.id' ?>">
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Email Pengirim</label>
                    <input type="email" class="form-control" name="mailketing_from_email"
                           value="<?= $mailketingConfig['from_email'] ?? '' ?>"
                           placeholder="noreply@domain.com">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nama Pengirim</label>
                    <input type="text" class="form-control" name="mailketing_from_name"
                           value="<?= $mailketingConfig['from_name'] ?? 'Simple Aff Plus' ?>"
                           placeholder="Nama Pengirim">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Default List ID</label>
                    <input type="text" class="form-control" name="mailketing_default_list_id"
                           value="<?= $mailketingConfig['default_list_id'] ?? '' ?>"
                           placeholder="ID List default untuk subscriber">
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="mailketing_test_mode" id="mailketing_test_mode"
                               <?= ($mailketingConfig['test_mode'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="mailketing_test_mode">
                            Mode Testing (Email tidak benar-benar dikirim)
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Konfigurasi
            </button>
            <a href="?mailketing_test=1" class="btn btn-success">
                <i class="fas fa-paper-plane"></i> Test Koneksi
            </a>
        </div>
    </form>
</div>

<!-- Email Activity Log -->
<div class="card mb-3">
    <div class="card-header">
        <h5><i class="fas fa-history"></i> Log Aktivitas Email</h5>
    </div>
    <div class="card-body">
        <div class="email-log-table">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Jenis</th>
                        <th>Penerima</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Provider</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $emailLogs = db_select("SELECT * FROM epi_email_logs ORDER BY created_at DESC LIMIT 20");
                    foreach ($emailLogs as $log):
                    ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                        <td><?= ucwords(str_replace('_', ' ', $log['email_type'])) ?></td>
                        <td><?= $log['recipient_email'] ?></td>
                        <td><?= $log['subject'] ?></td>
                        <td>
                            <span class="status-badge status-<?= $log['status'] ?>">
                                <?= ucfirst($log['status']) ?>
                            </span>
                        </td>
                        <td><?= ucfirst($log['provider']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Monitoring Section -->
<div class="card mb-3">
    <div class="card-header">
        <h5><i class="fas fa-chart-line"></i> Monitoring Email</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php
            if (class_exists('EmailService')) {
                $emailService = new EmailService();
                $stats = $emailService->getEmailStats(7);
                $credits = $emailService->checkMailketingCredits();
            ?>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-primary"><?= $stats['total'] ?? 0 ?></div>
                    <div class="stat-label">Total Email (7 hari)</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-success"><?= $stats['sent'] ?? 0 ?></div>
                    <div class="stat-label">Berhasil Dikirim</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-danger"><?= $stats['failed'] ?? 0 ?></div>
                    <div class="stat-label">Gagal Dikirim</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-warning"><?= $stats['pending'] ?? 0 ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <?php } ?>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <h6><i class="fas fa-coins"></i> Saldo Kredit Mailketing</h6>
                <div class="credit-info">
                    <div class="credit-amount">
                        <?= isset($credits['remaining']) ? number_format($credits['remaining']) : 'N/A' ?> kredit
                    </div>
                    <small>Sisa kredit email Mailketing</small>
                </div>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-server"></i> Provider Email</h6>
                <div class="provider-stats">
                    <div class="d-flex justify-content-between">
                        <span>Mailketing:</span>
                        <span class="badge bg-<?= ($mailketingConfig['is_enabled'] ?? '0') == '1' ? 'success' : 'secondary' ?>">
                            <?= ($mailketingConfig['is_enabled'] ?? '0') == '1' ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>SMTP Fallback:</span>
                        <span class="badge bg-info">Tersedia</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Email Section -->
<div class="card mb-3">
    <div class="card-header">
        <h5><i class="fas fa-paper-plane"></i> Test Email</h5>
    </div>
    <div class="card-body">
        <form action="" method="post">
            <input type="hidden" name="test_email_action" value="1">
            
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Email Tujuan Test</label>
                    <input type="email" class="form-control" name="test_email_address"
                           value="<?= $datamember['mem_email'] ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jenis Test</label>
                    <select class="form-control" name="test_email_type">
                        <option value="simple">Test Sederhana</option>
                        <option value="registration_member">Notifikasi Registrasi</option>
                        <option value="upgrade_member">Notifikasi Upgrade</option>
                        <option value="order_member">Notifikasi Order</option>
                        <option value="withdrawal_member">Notifikasi Pencairan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-paper-plane"></i> Kirim Test Email
                    </button>
                </div>
            </div>
        </form>
        
        <?php if (isset($_POST['test_email_action'])): ?>
        <div class="mt-3">
            <?php
            try {
                $testEmail = $_POST['test_email_address'];
                $testType = $_POST['test_email_type'];
                
                if ($testType == 'simple') {
                    $mailketingHelper = new MailketingHelper();
                    
                    // Test koneksi dan kirim email test sederhana
                    $connectionResult = $mailketingHelper->testConnection();
                    
                    if ($connectionResult['success']) {
                        $emailResult = $mailketingHelper->sendTestEmail($testEmail);
                        
                        if ($emailResult['success']) {
                            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Berhasil!</strong> Test email berhasil dikirim ke ' . htmlspecialchars($testEmail) . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                        } else {
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> ' . htmlspecialchars($emailResult['message'] ?? 'Gagal mengirim test email') . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                        }
                    } else {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> ' . htmlspecialchars($connectionResult['message'] ?? 'Koneksi ke Mailketing gagal') . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
                    }
                } else {
                    // Test dengan template notifikasi
                    $mailketingHelper = new MailketingHelper();
                    
                    switch ($testType) {
                        case 'registration_member':
                            $subject = 'Test Email - Registrasi Member';
                            $content = '<h2>Selamat Datang!</h2><p>Ini adalah test email untuk notifikasi registrasi member.</p>';
                            break;
                        case 'upgrade_member':
                            $subject = 'Test Email - Upgrade Member';
                            $content = '<h2>Upgrade Berhasil!</h2><p>Ini adalah test email untuk notifikasi upgrade member.</p>';
                            break;
                        case 'order_member':
                            $subject = 'Test Email - Order Produk';
                            $content = '<h2>Order Diterima!</h2><p>Ini adalah test email untuk notifikasi order produk.</p>';
                            break;
                        case 'withdrawal_member':
                            $subject = 'Test Email - Pencairan Komisi';
                            $content = '<h2>Pencairan Diproses!</h2><p>Ini adalah test email untuk notifikasi pencairan komisi.</p>';
                            break;
                        default:
                            $subject = 'Test Email';
                            $content = '<h2>Test Email</h2><p>Ini adalah test email dari sistem.</p>';
                    }
                    
                    $emailResult = $mailketingHelper->sendEmail($testEmail, $subject, $content);
                    
                    if ($emailResult['success']) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Berhasil!</strong> Test email "' . htmlspecialchars($subject) . '" berhasil dikirim ke ' . htmlspecialchars($testEmail) . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
                    } else {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> ' . htmlspecialchars($emailResult['message'] ?? 'Gagal mengirim test email') . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
                    }
                }
                
            } catch (Exception $e) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> ' . htmlspecialchars($e->getMessage()) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
            }
            ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Legacy SMTP Section (Collapsed) -->
<div class="legacy-section">
    <h5><i class="fas fa-cog"></i> Konfigurasi SMTP Legacy (Fallback)</h5>
    <p class="text-muted mb-3">Konfigurasi ini hanya digunakan sebagai fallback jika Mailketing tidak tersedia.</p>
    
    <div class="accordion" id="smtpAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="smtpHeading">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#smtpCollapse" aria-expanded="false" aria-controls="smtpCollapse">
                    <i class="fas fa-server me-2"></i> Setting SMTP
                </button>
            </h2>
            <div id="smtpCollapse" class="accordion-collapse collapse" aria-labelledby="smtpHeading" data-bs-parent="#smtpAccordion">
                <div class="accordion-body">
                    <form action="" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Alamat Email</label>
                                    <input type="text" class="form-control" name="smtp_from" value="<?= $settings['smtp_from'] ?? '' ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Pengirim</label>
                                    <input type="text" class="form-control" name="smtp_sender" value="<?= $settings['smtp_sender'] ?? '' ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Outgoing Server</label>
                                    <input type="text" class="form-control" name="smtp_server" value="<?= $settings['smtp_server'] ?? '' ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="text" class="form-control" name="smtp_port" value="<?= $settings['smtp_port'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">SMTP Secure</label>
                                    <select name="smtp_secure" class="form-select">
                                        <?php 
                                        $securesel = array('ssl'=>'SSL','tls'=>'TLS','false'=>'false');
                                        foreach ($securesel as $key => $value) {
                                            echo '<option value="'.$key.'"';
                                            if (isset($settings['smtp_secure']) && $settings['smtp_secure'] == $key) {
                                                echo ' selected';
                                            }
                                            echo '>'.$value.'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">SMTP Authentication</label>
                                    <select name="smtp_auth" class="form-select">
                                        <?php if (isset($settings['smtp_auth']) && $settings['smtp_auth'] == 'false') {
                                            $sel1 = '';
                                            $sel2 = ' selected';
                                        } else {
                                            $sel1 = ' selected';
                                            $sel2 = '';
                                        }
                                        ?>
                                        <option value="true"<?php echo $sel1;?>>true</option>
                                        <option value="false"<?php echo $sel2;?>>false</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="smtp_username" value="<?= $settings['smtp_username'] ?? '' ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="smtp_password" value="<?= $settings['smtp_password'] ?? '' ?>">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-save"></i> Simpan SMTP
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php showfooter(); ?>