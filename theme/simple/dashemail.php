<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }

// Include MailketingHelper
require_once __DIR__ . '/../../class/MailketingHelper.php';

$head['pagetitle']='Setting Email';
$head['scripthead'] = '
<link href="'.$weburl.'editor/css/froala_editor.pkgd.min.css" rel="stylesheet" type="text/css" />
<link href="'.$weburl.'editor/css/froala_style.min.css" rel="stylesheet" type="text/css" />
<style type="text/css">
a[id="fr-logo"] {
  height:1px !important;
  color:#ffffff !important;
}
#Layer_1 { height:1px !important; }
p[data-f-id="pbf"] {
  height:1px !important;
}
a[href*="www.froala.com"] {
  height:1px !important;
  background: #fff !important;
  pointer-events: none;
}
#fr-logo {
    visibility: hidden;
}
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
    padding: 20px;
}

.provider-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid #dee2e6;
}

.provider-item:last-child {
    border-bottom: none;
}

.provider-name {
    font-weight: 500;
}

.provider-count {
    background: #007bff;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
}
</style>';
showheader($head);

// Handle Mailketing configuration update
if (isset($_POST['mailketing_config'])) {
    $mailketingHelper = new MailketingHelper();
    
    // Update Mailketing configuration
    $configs = [
        'api_key' => $_POST['mailketing_api_key'] ?? '',
        'api_url' => $_POST['mailketing_api_url'] ?? 'https://api.mailketing.co.id',
        'default_list_id' => $_POST['mailketing_default_list_id'] ?? '',
        'is_enabled' => isset($_POST['mailketing_enabled']) ? '1' : '0',
        'test_mode' => isset($_POST['mailketing_test_mode']) ? '1' : '0'
    ];
    
    $success = true;
    foreach ($configs as $key => $value) {
        global $con;
        $key_escaped = mysqli_real_escape_string($con, $key);
        $value_escaped = mysqli_real_escape_string($con, $value);
        $sql = "INSERT INTO epi_mailketing_config (config_key, config_value) VALUES ('$key_escaped', '$value_escaped') 
                ON DUPLICATE KEY UPDATE config_value = '$value_escaped'";
        if (!db_query($sql)) {
            $success = false;
            break;
        }
    }
    
    if ($success) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
          <strong>Ok!</strong> Konfigurasi Mailketing telah disimpan.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    } else {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
          <strong>Error!</strong> Gagal menyimpan konfigurasi Mailketing.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
}

// Handle template configuration update
if (isset($_POST['template_config'])) {
    $templateType = $_POST['template_type'];
    $templateData = [
        'mailketing_template_id' => $_POST['mailketing_template_id'] ?? '',
        'mailketing_list_id' => $_POST['mailketing_list_id'] ?? '',
        'subject' => $_POST['template_subject'] ?? '',
        'is_active' => isset($_POST['template_active']) ? 1 : 0
    ];
    
    $sql = "UPDATE epi_email_templates SET 
            mailketing_template_id = ?, 
            mailketing_list_id = ?, 
            subject = ?, 
            is_active = ? 
            WHERE email_type = ?";
    
    if (db_query($sql, [
        $templateData['mailketing_template_id'],
        $templateData['mailketing_list_id'], 
        $templateData['subject'],
        $templateData['is_active'],
        $templateType
    ])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
          <strong>Ok!</strong> Template ' . $templateType . ' telah diperbarui.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    } else {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
          <strong>Error!</strong> Gagal memperbarui template.
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

if (isset($_POST) && count($_POST) > 0 && !isset($_POST['mailketing_config']) && !isset($_POST['template_config']) && !isset($_POST['test_email_action'])) {
	$post = str_replace('<p data-f-id="pbf" style="text-align: center; font-size: 14px; margin-top: 30px; opacity: 0.65; font-family: sans-serif;">Powered by <a href="https://www.froala.com/wysiwyg-editor?pb=1" title="Froala Editor">Froala Editor</a></p>','',$_POST);
	$settings = updatesettings($post);
	if ($settings === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Setting Email telah disimpan.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
} elseif (isset($_GET['test']) && !empty($_GET['test'])) {
	if (isset($settings['judul_'.$_GET['test']]) && isset($settings['isi_'.$_GET['test']])) {
		$cek = @smtpmailer($datamember['mem_email'],$settings['judul_'.$_GET['test']],$settings['isi_'.$_GET['test']]);

		if ($cek['status'] !== true) {
			echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
			  '.($cek['message']??='').'
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		} else {
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  '.($cek['message']??='').'
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		}		
	}
}

// Get Mailketing configuration
$mailketingConfig = [];
$configResult = db_select("SELECT config_key, config_value FROM epi_mailketing_config");
foreach ($configResult as $config) {
    $mailketingConfig[$config['config_key']] = $config['config_value'];
}

// Get email templates
$emailTemplates = db_select("SELECT * FROM epi_email_templates ORDER BY email_type");
?>

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
                    <label class="form-label">Default List ID</label>
                    <input type="text" class="form-control" name="mailketing_default_list_id" 
                           value="<?= $mailketingConfig['default_list_id'] ?? '' ?>" 
                           placeholder="ID List default untuk subscriber">
                    <small class="form-text text-muted">List ID untuk menambahkan subscriber baru</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Mode Testing</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="mailketing_test_mode" id="mailketing_test_mode" 
                               <?= ($mailketingConfig['test_mode'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="mailketing_test_mode">
                            Mode Testing (Email tidak benar-benar dikirim)
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Konfigurasi
                    </button>
                    <a href="?mailketing_test=1" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Test Koneksi
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Email Templates Configuration -->
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-template"></i> Template Email Mailketing</h5>
        <a href="<?php echo $weburl; ?>admin/template_editor.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Editor Template
                        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Jenis Email</th>
                        <th>Template Name</th>
                        <th>Subject</th>
                        <th>Template ID</th>
                        <th>List ID</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emailTemplates as $template): ?>
                    <tr>
                        <td><?= ucwords(str_replace('_', ' ', $template['email_type'])) ?></td>
                        <td><?= $template['name'] ?></td>
                        <td><?= $template['subject'] ?></td>
                        <td>
                            <form action="" method="post" class="d-inline">
                                <input type="hidden" name="template_config" value="1">
                                <input type="hidden" name="template_type" value="<?= $template['email_type'] ?>">
                                <input type="text" class="form-control form-control-sm" 
                                       name="mailketing_template_id" 
                                       value="<?= $template['mailketing_template_id'] ?? '' ?>" 
                                       placeholder="Template ID">
                        </td>
                        <td>
                                <input type="text" class="form-control form-control-sm" 
                                       name="mailketing_list_id" 
                                       value="<?= $template['mailketing_list_id'] ?? '' ?>" 
                                       placeholder="List ID">
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="template_active" 
                                       <?= $template['is_active'] ? 'checked' : '' ?>>
                            </div>
                        </td>
                        <td>
                                <input type="text" class="form-control form-control-sm mb-1" 
                                       name="template_subject" 
                                       value="<?= $template['subject'] ?>" 
                                       placeholder="Subject">
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Email Activity Log -->
<div class="card mb-3">
    <div class="card-header">
        <h5><i class="fas fa-history"></i> Log Aktivitas Email</h5>
    </div>
    <div class="card-body">
        <div class="email-log-table">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Jenis</th>
                        <th>Penerima</th>
                        <th>Subject</th>
                        <th>Status</th>
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
                        <td><?= substr($log['subject'], 0, 50) ?>...</td>
                        <td>
                            <span class="status-badge status-<?= $log['status'] ?>">
                                <?= strtoupper($log['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Monitoring & Statistics -->
<div class="card mb-3">
    <div class="card-header">
        <h5><i class="fas fa-chart-line"></i> Monitoring Email</h5>
    </div>
    <div class="card-body">
        <?php 
        if (class_exists('EmailService')) {
            $emailService = new EmailService();
            $stats = $emailService->getEmailStats(7);
            $credits = $emailService->checkMailketingCredits();
        ?>
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total Email (7 hari)</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-success"><?= $stats['sent'] ?></div>
                    <div class="stat-label">Berhasil Dikirim</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-danger"><?= $stats['failed'] ?></div>
                    <div class="stat-label">Gagal Dikirim</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-warning"><?= $stats['test_mode'] ?></div>
                    <div class="stat-label">Mode Testing</div>
                </div>
            </div>
        </div>
        
        <?php if ($credits['success']): ?>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="credit-info">
                    <h6><i class="fas fa-coins"></i> Saldo Kredit Mailketing</h6>
                    <div class="credit-amount">
                        <?= number_format($credits['credits']) ?> kredit
                    </div>
                    <small class="text-muted">Terakhir update: <?= date('d/m/Y H:i') ?></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="provider-stats">
                    <h6><i class="fas fa-server"></i> Provider Email</h6>
                    <?php foreach ($stats['by_provider'] as $provider => $count): ?>
                    <div class="provider-item">
                        <span class="provider-name"><?= ucfirst($provider) ?></span>
                        <span class="provider-count"><?= $count ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php } ?>
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
                    <div class="mb-3">
                        <label class="form-label">Email Tujuan Test</label>
                        <input type="email" class="form-control" name="test_email_address" 
                               placeholder="test@example.com" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Jenis Test</label>
                        <select class="form-control" name="test_email_type">
                            <option value="connection">Test Koneksi</option>
                            <option value="registration_member">Test Registrasi Member</option>
                            <option value="upgrade_member">Test Upgrade Member</option>
                            <option value="order_member">Test Order Member</option>
                            <option value="withdrawal_member">Test Pencairan</option>
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Kirim Test Email
            </button>
        </form>
        
        <?php if (isset($_POST['test_email_action'])): ?>
        <div class="mt-3">
            <?php
            $testEmail = $_POST['test_email_address'];
            $testType = $_POST['test_email_type'];
            
            try {
                $mailketingHelper = new MailketingHelper();
                
                if ($testType == 'connection') {
                    // Test koneksi dan kirim email test sederhana
                    $connectionResult = $mailketingHelper->testConnection();
                    
                    if ($connectionResult['success']) {
                        $emailResult = $mailketingHelper->sendTestEmail($testEmail);
                        
                        if ($emailResult['success']) {
                            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Berhasil!</strong> Test email berhasil dikirim ke ' . htmlspecialchars($testEmail) . '
                                <br><small>Sisa kredit: ' . number_format($connectionResult['credits']) . '</small>
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
                    $subject = '';
                    $content = '';
                    
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

<form action="" method="post">
<div class="card">
  <div class="card-header">
      Setting Email SMTP (Legacy)
  </div>
  <div class="card-body">
  	<div class="table-responsive">
		<table class="table table-hover table-bordered">
			<tbody>
				<tr><td>
					<a class="info" data-target="kontensetting">Setting Email</a>
					<div class="kontensetting konten mt-2">
						<div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Alamat Email</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" name="smtp_from" value="<?= $settings['smtp_from'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Nama Pengirim</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" name="smtp_sender" value="<?= $settings['smtp_sender'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Outgoing Server</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" name="smtp_server" value="<?= $settings['smtp_server'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">SMTP Port</label>
					    <div class="col-sm-3">
					      <input type="text" class="form-control" name="smtp_port" value="<?= $settings['smtp_port'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">SMTP Secure</label>
					    <div class="col-sm-3">
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
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">SMTP Authentication</label>
					    <div class="col-sm-3">
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
					  </div>	  
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Username</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" name="smtp_username" value="<?= $settings['smtp_username'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Password</label>
					    <div class="col-sm-10">
					      <input type="password" class="form-control" name="smtp_password" value="<?= $settings['smtp_password'] ??= '';?>">
					    </div>
					  </div>	  
				  </div>
				</td></tr>
				<?php
				$notif = array(
					array('daftar','Registrasi',3),
					array('upgrade','Upgrade',2),
					array('order','Order Produk',2,'
								<code>[idorder]</code>: Nomor ID Invoice
								<br/><code>[hrgunik]</code>: Harga dengan kode unik
								<br/><code>[hrgproduk]</code>: Harga produk asli
								<br/><code>[namaproduk]</code>: Nama Produk
								<br/><code>[urlproduk]</code>: kode URL Produk
								'),
					array('prosesorder','Proses Order',2,'
								<code>[idorder]</code>: Nomor ID Invoice
								<br/><code>[hrgunik]</code>: Harga dengan kode unik
								<br/><code>[hrgproduk]</code>: Harga produk asli
								<br/><code>[namaproduk]</code>: Nama Produk
								<br/><code>[urlproduk]</code>: kode URL Produk
								'),
					array('cair_komisi','Pencairan Komisi',1,'<code>[komisi]</code>: Jumlah Komisi yg ditransfer')
				);

				$target = array('member','sponsor','admin');

				foreach ($notif as $notif) {
					for ($i=0; $i < $notif[2]; $i++) { 						
						if (isset($notif[3]) && !empty($notif[3])) {
							$shortcode = '<small class="form-text text-muted"><strong>Shortcode Khusus:</strong><br/>'.$notif[3].'</small><br/>';
						} else {
							$shortcode = '';
						}
						echo '
						<tr><td>
							<a class="info" data-target="konten_'.$notif[0].'_'.$target[$i].'">Notif '.$notif[1].' ke '.ucwords($target[$i]).'</a>
							<div class="konten_'.$notif[0].'_'.$target[$i].' konten mt-2">
								<input type="text" class="form-control mb-2" name="judul_'.$notif[0].'_'.$target[$i].'" value="'.($settings['judul_'.$notif[0].'_'.$target[$i]] ??= '').'">
					      <textarea class="form-control ckeditor" rows="5" id="editor" data-judul="isi_'.$notif[0].'_'.$target[$i].'" name="isi_'.$notif[0].'_'.$target[$i].'">'.
					      htmlspecialchars($settings['isi_'.$notif[0].'_'.$target[$i]]  ?? '', ENT_QUOTES, 'UTF-8').'</textarea>
					      '.$shortcode.'
					      <a href="?test='.$notif[0].'_'.$target[$i].'" class="btn btn-primary mt-1">Test Email</a>
							</div>
						</td></tr>
						';
					}
				}
				?>
			</tbody>
		</table>
		</div>
		<input type="submit" class="btn btn-success mt-3" name="" value=" SIMPAN ">
	</div>	  
</div>
</form>

<div class="card mt-3">
  <div class="card-header">
      Daftar Shortcode
      <?php
      $scmember = $scsponsor = '';
      $form = db_select("SELECT * FROM `sa_form` ORDER BY `ff_sort`");
      if (count($form) > 0) {
      	$default = array('nama','email','whatsapp','kodeaff');
      	foreach ($form as $form) {
      		if (!in_array($form['ff_field'], $default)) {
      			$scmember .= '<code>[member_'.$form['ff_field'].']</code> : '.$form['ff_label'].'<br/>';
      			$scsponsor .= '<code>[sponsor_'.$form['ff_field'].']</code> : '.$form['ff_label'].'<br/>';
      		}
      	}
      }
      ?>
  </div>
  <div class="card-body">
  	<div class="row">
  		<div class="col-sm-6">
  			<strong>Data Member yang mendaftar / upgrade:</strong><br/>
  			<code>[member_nama]</code> : Nama member<br/>
  			<code>[member_email]</code> : Email member<br/>
  			<code>[member_whatsapp]</code> : WhatsApp member<br/>
  			<code>[member_kodeaff]</code> : URL Affiliasi member<br/>
  			<?php echo $scmember;?>
  		</div>
  		<div class="col-sm-6">
  			<strong>Data sponsor dari member yang mendaftar / upgrade:</strong><br/>
  			<code>[sponsor_nama]</code> : Nama sponsor<br/>
  			<code>[sponsor_email]</code> : Email sponsor<br/>
  			<code>[sponsor_whatsapp]</code> : WhatsApp sponsor<br/>
  			<code>[sponsor_kodeaff]</code> : URL Affiliasi sponsor<br/>
  			<?php echo $scsponsor;?>
  		</div>
  	</div>
  </div>
</div>
<?php 
$footer['scriptfoot'] = '
<script type="text/javascript" src="'.$weburl.'editor/js/froala_editor.pkgd.min.js"></script>
<script>
  document.addEventListener(\'DOMContentLoaded\', function () {
    new FroalaEditor(\'#editor\', {
      imageUploadURL: \''.$weburl.'upload_image.php\',
      imageUploadParams: {
        id: \'my_editor\'
      },
      codeViewKeepOriginal: true,
      htmlUntouched: true,
      htmlAllowedTags: [\'.*\'], // Allow all HTML tags
      htmlAllowedAttrs: [\'.*\'], // Allow all attributes
      htmlRemoveTags: [],
      events: {
        \'image.beforeUpload\': function (files) {
          var editor = this;

          // Create a FormData object.
          var formData = new FormData();

          // Append the uploaded image to the form data.
          formData.append(\'file\', files[0]);

          // Get the article title and append it to the form data.
          var namafile = document.querySelector(\'#editor\').getAttribute(\'data-judul\');
          formData.append(\'judul\', namafile);

          // Make the AJAX request.
          fetch(\''.$weburl.'upload_image.php\', {
            method: \'POST\',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.link) {
              // Insert the image into the editor.
              editor.image.insert(data.link, null, null, editor.image.get());
            } else {
              console.error(\'Upload failed:\', data.error);
            }
          })
          .catch(error => {
            console.error(\'Error:\', error);
          });

          // Prevent the default behavior.
          return false;
        }
      }
    });
  });
</script>';
showfooter($footer); ?>