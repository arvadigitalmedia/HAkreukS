<?php 
require_once '../config.php';
require_once '../fungsi.php';

// Cek login menggunakan sistem yang sama dengan dashboard
$iduser = is_login();
if (!$iduser) {
    header('Location: ../login');
    exit();
}

// Get member data
$datamember = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$iduser);

// Cek akses admin
if (!$datamember || $datamember['mem_role'] < 5) {
    header('Location: ../dashboard');
    exit();
}

// Include MailketingHelper
require_once __DIR__ . '/../class/MailketingHelper.php';

// Set variabel yang diperlukan untuk dashboard
$settings = getsettings();
if (!isset($settings['logoweb'])) { 
    $logoweb = 'img/simpleaff-logo.png'; 
} else { 
    $logoweb = 'upload/'.$settings['logoweb']; 
}

// Include menudata untuk variabel $menu
require_once '../menudata.php';

$head['pagetitle'] = 'Editor Template Email Marketing';
$head['scripthead'] = '
<link href="'.$weburl.'editor/css/froala_editor.pkgd.min.css" rel="stylesheet" type="text/css" />
<link href="'.$weburl.'editor/css/froala_style.min.css" rel="stylesheet" type="text/css" />
<style>
.template-editor {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}
.template-preview {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    min-height: 400px;
}
.shortcode-list {
    background: #e3f2fd;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
}
.shortcode-tag {
    background: linear-gradient(135deg, #DAA520, #B8860B);
    color: white;
    padding: 4px 8px;
    border-radius: 5px;
    font-size: 12px;
    margin: 2px;
    display: inline-block;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 5px rgba(218, 165, 32, 0.2);
}
.shortcode-tag:hover {
    background: linear-gradient(135deg, #B8860B, #DAA520);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(218, 165, 32, 0.3);
}
.recipient-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 10px;
}
</style>';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['save_template'])) {
        $templateId = $_POST['template_id'];
        $subject = $_POST['template_subject'];
        $content = $_POST['template_content'];
        $mailketingTemplateId = $_POST['mailketing_template_id'];
        $mailketingListId = $_POST['mailketing_list_id'];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Update template di database
        $sql = "UPDATE epi_email_templates SET 
                subject = '".db_cek($subject)."', 
                mailketing_template_id = '".db_cek($mailketingTemplateId)."', 
                mailketing_list_id = '".db_cek($mailketingListId)."', 
                is_active = ".intval($isActive).",
                updated_at = NOW()
                WHERE id = ".intval($templateId);
        
        if (db_query($sql)) {
            // Simpan konten template ke file terpisah
            $templateFile = __DIR__ . '/../templates/email/' . $templateId . '.html';
            if (!is_dir(dirname($templateFile))) {
                mkdir(dirname($templateFile), 0755, true);
            }
            file_put_contents($templateFile, $content);
            
            $success_message = "Template berhasil disimpan!";
        } else {
            $error_message = "Gagal menyimpan template: " . db_error();
        }
    }
    
    if (isset($_POST['create_mailketing_template'])) {
        $templateId = $_POST['template_id'];
        $templateName = $_POST['template_name'];
        $templateContent = $_POST['template_content'];
        
        try {
            $mailketing = new MailketingHelper();
            
            // Buat template di Mailketing
            $result = $mailketing->createTemplate($templateName, $templateContent);
            
            if ($result['success']) {
                $success_message = "Template berhasil dibuat di Mailketing dengan ID: " . $result['template_id'];
                
                // Update template ID di database
                $sql = "UPDATE epi_email_templates SET mailketing_template_id = '".db_cek($result['template_id'])."' WHERE id = ".intval($templateId);
                db_query($sql);
            } else {
                $error_message = "Gagal membuat template di Mailketing: " . $result['message'];
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get template data
$templateId = $_GET['id'] ?? 1;
$template = db_row("SELECT * FROM epi_email_templates WHERE id = ".$templateId);

if (!$template) {
    header('Location: ../dashboard/email');
    exit();
}

// Load template content
$templateFile = __DIR__ . '/../templates/email/' . $templateId . '.html';
$templateContent = '';
if (file_exists($templateFile)) {
    $templateContent = file_get_contents($templateFile);
} else {
    // Default template content
    $templateContent = '
    <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
        <div style="background: linear-gradient(135deg, #FFD700, #FFA500); padding: 20px; text-align: center;">
            <h1 style="color: #333; margin: 0;">{{SITE_NAME}}</h1>
        </div>
        <div style="padding: 30px; background: white;">
            <h2 style="color: #333;">Halo {{MEMBER_NAME}},</h2>
            <p style="line-height: 1.6; color: #666;">
                Terima kasih telah bergabung dengan {{SITE_NAME}}. 
                Kami senang menyambut Anda sebagai member baru.
            </p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{LOGIN_URL}}" style="background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    Login ke Dashboard
                </a>
            </div>
            <p style="line-height: 1.6; color: #666;">
                Jika Anda memiliki pertanyaan, jangan ragu untuk menghubungi kami.
            </p>
        </div>
        <div style="background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px;">
            <p>&copy; 2025 {{SITE_NAME}}. All rights reserved.</p>
            <p>{{SITE_URL}}</p>
        </div>
    </div>';
}

// Get available shortcodes
$shortcodes = [
    'Member' => ['{{MEMBER_NAME}}', '{{MEMBER_EMAIL}}', '{{MEMBER_USERNAME}}', '{{MEMBER_PHONE}}'],
    'Sponsor' => ['{{SPONSOR_NAME}}', '{{SPONSOR_EMAIL}}', '{{SPONSOR_USERNAME}}'],
    'Site' => ['{{SITE_NAME}}', '{{SITE_URL}}', '{{LOGIN_URL}}', '{{DASHBOARD_URL}}'],
    'Order' => ['{{ORDER_ID}}', '{{PRODUCT_NAME}}', '{{AMOUNT}}', '{{ORDER_DATE}}'],
    'System' => ['{{DATE}}', '{{TIME}}', '{{CURRENT_YEAR}}']
];

// Get email templates list
$emailTemplates = db_select("SELECT * FROM epi_email_templates ORDER BY email_type");

// Simpan working directory saat ini
$originalCwd = getcwd();
// Pindah ke root directory untuk path relatif yang benar
chdir('..');
include 'theme/simple/dashhead.php';
// Kembali ke working directory asli
chdir($originalCwd);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-edit"></i> Editor Template Email Marketing</h4>
                    <div class="float-end">
                        <a href="../dashboard/email" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <strong>Berhasil!</strong> <?= $success_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <strong>Error!</strong> <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Template Selector -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Pilih Template:</label>
                            <select class="form-select" onchange="window.location.href='?id='+this.value">
                                <?php foreach ($emailTemplates as $tmpl): ?>
                                <option value="<?= $tmpl['id'] ?>" <?= $tmpl['id'] == $templateId ? 'selected' : '' ?>>
                                    <?= ucwords(str_replace('_', ' ', $tmpl['email_type'])) ?> - <?= $tmpl['name'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status Template:</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="template_status" 
                                       <?= $template['is_active'] ? 'checked' : '' ?> disabled>
                                <label class="form-check-label" for="template_status">
                                    <?= $template['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <form method="post" action="">
                        <input type="hidden" name="template_id" value="<?= $templateId ?>">
                        <input type="hidden" name="save_template" value="1">
                        
                        <div class="row">
                            <!-- Editor Panel -->
                            <div class="col-md-8">
                                <div class="template-editor">
                                    <h5><i class="fas fa-code"></i> Editor Konten Template</h5>
                                    
                                    <!-- Subject -->
                                    <div class="mb-3">
                                        <label class="form-label">Subject Email:</label>
                                        <input type="text" class="form-control" name="template_subject" 
                                               value="<?= htmlspecialchars($template['subject']) ?>" required>
                                    </div>
                                    
                                    <!-- Content Editor -->
                                    <div class="mb-3">
                                        <label class="form-label">Konten Email:</label>
                                        <textarea name="template_content" id="template_content" class="form-control" 
                                                  rows="20"><?= htmlspecialchars($templateContent) ?></textarea>
                                    </div>
                                    
                                    <!-- Mailketing Configuration -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Mailketing Template ID:</label>
                                            <input type="text" class="form-control" name="mailketing_template_id" 
                                                   value="<?= $template['mailketing_template_id'] ?? '' ?>" 
                                                   placeholder="Kosongkan jika belum ada">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Mailketing List ID:</label>
                                            <input type="text" class="form-control" name="mailketing_list_id" 
                                                   value="<?= $template['mailketing_list_id'] ?? '' ?>" 
                                                   placeholder="Kosongkan untuk default list">
                                        </div>
                                    </div>
                                    
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" name="is_active" 
                                               <?= $template['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label">Template Aktif</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sidebar -->
                            <div class="col-md-4">
                                <!-- Shortcodes -->
                                <div class="shortcode-list">
                                    <h6><i class="fas fa-tags"></i> Shortcodes Tersedia</h6>
                                    <small class="text-muted">Klik untuk menyalin ke clipboard</small>
                                    
                                    <?php foreach ($shortcodes as $category => $codes): ?>
                                    <div class="mt-2">
                                        <strong><?= $category ?>:</strong><br>
                                        <?php foreach ($codes as $code): ?>
                                        <span class="shortcode-tag" onclick="copyToClipboard('<?= $code ?>')" 
                                              title="Klik untuk copy"><?= $code ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Preview -->
                                <div class="card">
                                    <div class="card-header">
                                        <h6><i class="fas fa-eye"></i> Preview Template</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="template-preview" id="template_preview">
                                            <!-- Preview akan dimuat di sini -->
                                        </div>
                                        <button type="button" class="btn btn-sm btn-info mt-2" onclick="updatePreview()">
                                            <i class="fas fa-refresh"></i> Update Preview
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h6><i class="fas fa-cogs"></i> Actions</h6>
                                    </div>
                                    <div class="card-body">
                                        <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">
                                            <i class="fas fa-save"></i> Simpan Template
                                        </button>
                                        
                                        <button type="button" class="btn btn-success btn-sm w-100 mb-2" 
                                                onclick="createMailketingTemplate()">
                                            <i class="fas fa-cloud-upload"></i> Upload ke Mailketing
                                        </button>
                                        
                                        <button type="button" class="btn btn-warning btn-sm w-100 mb-2" 
                                                onclick="sendTestEmail()">
                                            <i class="fas fa-paper-plane"></i> Test Email
                                        </button>
                                        
                                        <a href="../dashboard/email" class="btn btn-secondary btn-sm w-100">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Email Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testEmailForm">
                    <div class="mb-3">
                        <label class="form-label">Email Tujuan:</label>
                        <input type="email" class="form-control" id="test_email" required 
                               placeholder="contoh@email.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data Test (JSON):</label>
                        <textarea class="form-control" id="test_data" rows="5">{
    "MEMBER_NAME": "John Doe",
    "MEMBER_EMAIL": "john@example.com",
    "SITE_NAME": "Bisnis Emas Perak",
    "SITE_URL": "https://bisnisemasperak.com",
    "LOGIN_URL": "https://bisnisemasperak.com/login"
}</textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="executeTestEmail()">Kirim Test</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= $weburl ?>editor/js/froala_editor.pkgd.min.js"></script>
<script>
// Initialize Froala Editor
new FroalaEditor('#template_content', {
    height: 400,
    toolbarButtons: {
        'moreText': {
            'buttons': ['bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', 'fontFamily', 'fontSize', 'textColor', 'backgroundColor', 'inlineClass', 'inlineStyle', 'clearFormatting']
        },
        'moreParagraph': {
            'buttons': ['alignLeft', 'alignCenter', 'formatOLSimple', 'alignRight', 'alignJustify', 'formatOL', 'formatUL', 'paragraphFormat', 'paragraphStyle', 'lineHeight', 'outdent', 'indent', 'quote']
        },
        'moreRich': {
            'buttons': ['insertLink', 'insertImage', 'insertVideo', 'insertTable', 'emoticons', 'fontAwesome', 'specialCharacters', 'embedly', 'insertFile', 'insertHR']
        },
        'moreMisc': {
            'buttons': ['undo', 'redo', 'fullscreen', 'print', 'getPDF', 'spellChecker', 'selectAll', 'html', 'help']
        }
    }
});

// Copy shortcode to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show toast notification
        showToast('Shortcode ' + text + ' berhasil disalin!');
        
        // Add visual feedback to clicked element
        const clickedElements = document.querySelectorAll('.shortcode-tag');
        clickedElements.forEach(el => {
            if (el.textContent === text) {
                el.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    el.style.transform = '';
                }, 150);
            }
        });
    }).catch(function(err) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('Shortcode ' + text + ' berhasil disalin!');
    });
}

// Show toast notification
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'position-fixed d-flex align-items-center justify-content-center';
    toast.style.cssText = `
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 9999;
        background: linear-gradient(135deg, #DAA520, #B8860B);
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3);
        font-weight: 500;
        min-width: 300px;
        text-align: center;
        animation: fadeInScale 0.3s ease-out;
    `;
    toast.innerHTML = `
        <i class="fas fa-check-circle me-2" style="color: white; font-size: 18px;"></i>
        <span>${message}</span>
    `;
    
    // Add CSS animation if not exists
    if (!document.getElementById('toast-animation-style')) {
        const style = document.createElement('style');
        style.id = 'toast-animation-style';
        style.textContent = `
            @keyframes fadeInScale {
                0% {
                    opacity: 0;
                    transform: translate(-50%, -50%) scale(0.8);
                }
                100% {
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(1);
                }
            }
            @keyframes fadeOutScale {
                0% {
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(1);
                }
                100% {
                    opacity: 0;
                    transform: translate(-50%, -50%) scale(0.8);
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(toast);
    
    // Remove with fade out animation
    setTimeout(() => {
        toast.style.animation = 'fadeOutScale 0.3s ease-in';
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

// Update preview
function updatePreview() {
    const content = document.getElementById('template_content').value;
    const preview = document.getElementById('template_preview');
    
    // Replace shortcodes with sample data
    let previewContent = content
        .replace(/\{\{MEMBER_NAME\}\}/g, 'John Doe')
        .replace(/\{\{MEMBER_EMAIL\}\}/g, 'john@example.com')
        .replace(/\{\{SITE_NAME\}\}/g, 'Bisnis Emas Perak')
        .replace(/\{\{SITE_URL\}\}/g, 'https://bisnisemasperak.com')
        .replace(/\{\{LOGIN_URL\}\}/g, 'https://bisnisemasperak.com/login')
        .replace(/\{\{DASHBOARD_URL\}\}/g, 'https://bisnisemasperak.com/dashboard')
        .replace(/\{\{DATE\}\}/g, new Date().toLocaleDateString('id-ID'))
        .replace(/\{\{TIME\}\}/g, new Date().toLocaleTimeString('id-ID'))
        .replace(/\{\{CURRENT_YEAR\}\}/g, new Date().getFullYear());
    
    preview.innerHTML = previewContent;
}

// Create Mailketing template
function createMailketingTemplate() {
    const templateName = prompt('Nama template di Mailketing:');
    if (!templateName) return;
    
    const form = document.createElement('form');
    form.method = 'post';
    form.innerHTML = `
        <input type="hidden" name="create_mailketing_template" value="1">
        <input type="hidden" name="template_id" value="<?= $templateId ?>">
        <input type="hidden" name="template_name" value="${templateName}">
        <input type="hidden" name="template_content" value="${document.getElementById('template_content').value}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Send test email
function sendTestEmail() {
    const modal = new bootstrap.Modal(document.getElementById('testEmailModal'));
    modal.show();
}

// Execute test email
function executeTestEmail() {
    const email = document.getElementById('test_email').value;
    const testData = document.getElementById('test_data').value;
    
    if (!email) {
        alert('Email tujuan harus diisi!');
        return;
    }
    
    // Send AJAX request to test email
    fetch('../api/test_template_email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            template_id: <?= $templateId ?>,
            email: email,
            test_data: JSON.parse(testData),
            content: document.getElementById('template_content').value,
            subject: document.querySelector('input[name="template_subject"]').value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Test email berhasil dikirim ke ' + email);
            bootstrap.Modal.getInstance(document.getElementById('testEmailModal')).hide();
        } else {
            alert('Gagal mengirim test email: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

// Load initial preview
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
});
</script>

<?php include '../theme/simple/dashfoot.php'; ?>