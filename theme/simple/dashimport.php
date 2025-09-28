<?php
# path: theme/simple/dashimport.php
# Import Data Member via CSV

$head['pagetitle'] = 'Import Data Member';
$head['description'] = 'Import data member menggunakan file CSV';

// Cek akses admin/staff - hanya tampilkan pesan jika bukan admin
if (isset($datauser) && $datauser['mem_role'] < 5) {
    echo '<div class="alert alert-danger"><i class="fas fa-lock"></i> Akses ditolak. Hanya admin yang dapat mengakses halaman ini.</div>';
    return;
}

// Proses upload CSV
$uploadResult = '';
$importStats = array();

if (isset($_POST['upload_csv']) && isset($_FILES['csv_file'])) {
    $uploadResult = processCSVUpload($_FILES['csv_file']);
}

function processCSVUpload($file) {
    global $importStats;
    
    // Validasi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error upload file: ' . $file['error'] . '</div>';
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> File terlalu besar. Maksimal 5MB.</div>';
    }
    
    $fileInfo = pathinfo($file['name']);
    if (strtolower($fileInfo['extension']) !== 'csv') {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> File harus berformat CSV.</div>';
    }
    
    // Baca file CSV
    $csvData = array();
    if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
        $header = fgetcsv($handle); // Baca header
        
        // Validasi header (case-insensitive)
        $requiredColumns = array('sponsor', 'nama', 'email', 'whatsapp', 'password');
        $headerLower = array_map('strtolower', array_map('trim', $header));
        $missingColumns = array();
        
        foreach ($requiredColumns as $col) {
            if (!in_array(strtolower($col), $headerLower)) {
                $missingColumns[] = $col;
            }
        }
        
        if (!empty($missingColumns)) {
            fclose($handle);
            return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Kolom wajib tidak ditemukan: ' . implode(', ', $missingColumns) . '</div>';
        }
        
        // Mapping kolom (case-insensitive)
        $columnMap = array();
        foreach ($header as $index => $columnName) {
            $cleanName = strtolower(trim($columnName));
            $columnMap[$cleanName] = $index;
        }
        
        // Baca data
        $rowNumber = 1;
        $successCount = 0;
        $errorCount = 0;
        $errors = array();
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $rowNumber++;
            
            // Ambil data berdasarkan mapping kolom
            $sponsor = isset($columnMap['sponsor']) ? trim($data[$columnMap['sponsor']]) : '';
            $nama = isset($columnMap['nama']) ? trim($data[$columnMap['nama']]) : '';
            $email = isset($columnMap['email']) ? trim($data[$columnMap['email']]) : '';
            $whatsapp = isset($columnMap['whatsapp']) ? trim($data[$columnMap['whatsapp']]) : '';
            $password = isset($columnMap['password']) ? trim($data[$columnMap['password']]) : '';
            
            // Validasi data
            $rowErrors = array();
            
            if (empty($nama)) $rowErrors[] = 'Nama kosong';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $rowErrors[] = 'Email tidak valid';
            if (empty($whatsapp)) $rowErrors[] = 'WhatsApp kosong';
            if (empty($password)) $rowErrors[] = 'Password kosong';
            
            // Cek email sudah ada
            if (!empty($email) && db_exist("SELECT `mem_email` FROM `sa_member` WHERE `mem_email`='" . cek($email) . "'")) {
                $rowErrors[] = 'Email sudah terdaftar';
            }
            
            // Cek sponsor
            $idsponsor = 0;
            if (!empty($sponsor)) {
                $sponsorId = db_var("SELECT `mem_id` FROM `sa_member` WHERE `mem_kodeaff`='" . txtonly(strtolower($sponsor)) . "'");
                if (is_numeric($sponsorId)) {
                    $idsponsor = $sponsorId;
                } else {
                    $rowErrors[] = 'Sponsor tidak ditemukan';
                }
            }
            
            if (!empty($rowErrors)) {
                $errorCount++;
                $errors[] = "Baris $rowNumber: " . implode(', ', $rowErrors);
                continue;
            }
            
            // Insert data member
            $kodeaff = generateKodeAff($nama);
            $whatsappFormatted = formatwa($whatsapp);
            
            $newuserid = db_insert("INSERT INTO `sa_member` 
                (`mem_nama`, `mem_email`, `mem_password`, `mem_whatsapp`, `mem_kodeaff`, 
                `mem_tgldaftar`, `mem_status`, `mem_role`) 
                VALUES ('" . cek($nama) . "', '" . cek($email) . "', '" . create_hash($password) . "', 
                '" . cek($whatsappFormatted) . "', '" . cek($kodeaff) . "', 
                '" . date('Y-m-d H:i:s') . "', 1, 1)");
            
            if (is_numeric($newuserid)) {
                // Insert sponsor relationship
                if ($idsponsor > 0) {
                    $network = '[' . numonly($idsponsor) . ']' . db_var("SELECT `sp_network` FROM `sa_sponsor` WHERE `sp_mem_id`=" . $idsponsor);
                    db_insert("INSERT INTO `sa_sponsor` (`sp_mem_id`, `sp_sponsor_id`, `sp_network`) VALUES ($newuserid, $idsponsor, '" . $network . "')");
                }
                
                $successCount++;
                
                // Kirim notifikasi (opsional)
                // $customfield['newpass'] = $password;
                // sa_notif('daftar', $newuserid, $customfield);
            } else {
                $errorCount++;
                $errors[] = "Baris $rowNumber: Gagal menyimpan ke database";
            }
        }
        
        fclose($handle);
        
        $importStats = array(
            'success' => $successCount,
            'error' => $errorCount,
            'total' => $successCount + $errorCount
        );
        
        $result = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> <strong>Import Selesai!</strong><br/>';
        $result .= "Total data: {$importStats['total']}<br/>";
        $result .= "Berhasil: {$importStats['success']}<br/>";
        $result .= "Error: {$importStats['error']}</div>";
        
        if (!empty($errors)) {
            $result .= '<div class="alert alert-warning"><strong>Detail Error:</strong><br/>' . implode('<br/>', array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $result .= '<br/>... dan ' . (count($errors) - 10) . ' error lainnya';
            }
            $result .= '</div>';
        }
        
        return $result;
    } else {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal membaca file CSV.</div>';
    }
}

function generateKodeAff($nama) {
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nama));
    $kode = substr($base, 0, 8) . rand(100, 999);
    
    // Pastikan unik
    while (db_exist("SELECT `mem_kodeaff` FROM `sa_member` WHERE `mem_kodeaff`='" . $kode . "'")) {
        $kode = substr($base, 0, 8) . rand(100, 999);
    }
    
    return $kode;
}

include('dashhead.php');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-upload"></i> Import Data Member</h5>
                </div>
                <div class="card-body">
                    
                    <?php if (!empty($uploadResult)) echo $uploadResult; ?>
                    
                    <!-- Form Upload -->
                    <form method="post" enctype="multipart/form-data" class="mb-4">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="csv_file" class="form-label">Pilih File CSV</label>
                                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                    <div class="form-text">Format file: CSV, maksimal 5MB</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" name="upload_csv" class="btn btn-primary d-block">
                                        <i class="fas fa-upload"></i> Upload & Import
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Panduan Format CSV -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Format CSV Wajib</h6>
                                </div>
                                <div class="card-body">
                                    <p>File CSV harus memiliki kolom-kolom berikut (urutan bebas):</p>
                                    <ul class="list-unstyled">
                                        <li><strong>sponsor</strong> - Kode affiliasi sponsor (opsional)</li>
                                        <li><strong>nama</strong> - Nama lengkap member</li>
                                        <li><strong>email</strong> - Email valid dan unik</li>
                                        <li><strong>whatsapp</strong> - Nomor WhatsApp (format: 08123456789)</li>
                                        <li><strong>password</strong> - Password untuk login</li>
                                    </ul>
                                    
                                    <div class="alert alert-warning">
                                        <small><i class="fas fa-exclamation-triangle"></i> 
                                        <strong>Penting:</strong> Baris pertama harus berisi nama kolom (header)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-download"></i> Template CSV</h6>
                                </div>
                                <div class="card-body">
                                    <p>Download template CSV untuk memudahkan import:</p>
                                    <a href="?download_template=1" class="btn btn-success btn-sm">
                                        <i class="fas fa-download"></i> Download Template
                                    </a>
                                    
                                    <hr>
                                    <p><strong>Contoh format CSV:</strong></p>
                                    <div class="bg-light p-2 rounded">
                                        <code>
                                        sponsor,nama,email,whatsapp,password<br>
                                        admin123,John Doe,john@email.com,081234567890,password123<br>
                                        admin123,Jane Smith,jane@email.com,081234567891,password456
                                        </code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Catatan Penting -->
                    <div class="card border-warning mt-3">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Catatan Penting</h6>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li>Email yang sudah terdaftar akan dilewati</li>
                                <li>Kode affiliasi akan dibuat otomatis jika tidak ada sponsor</li>
                                <li>Semua member yang diimport akan memiliki status aktif</li>
                                <li>Backup database sebelum melakukan import data besar</li>
                                <li>Notifikasi email registrasi tidak dikirim otomatis saat import</li>
                            </ul>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Handle download template
if (isset($_GET['download_template'])) {
    $template_file = '../../template_import_member.csv';
    if (file_exists($template_file)) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="template_import_member.csv"');
        readfile($template_file);
    } else {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="template_import_member.csv"');
        echo "Sponsor,Nama,Email,WhatsApp,Password\n";
        echo "admin,John Doe,john@example.com,081234567890,password123\n";
        echo "admin,Jane Smith,jane@example.com,081234567891,password456\n";
        echo "admin,Bob Johnson,bob@example.com,081234567892,password789\n";
    }
    exit;
}

include('dashfoot.php');
?>