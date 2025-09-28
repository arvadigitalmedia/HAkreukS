<?php
// ============================================================================
// Test Premium Access System
// File: test_premium_access.php
// Untuk testing berbagai skenario akses ke welcome-epi-premium.php
// ============================================================================

session_start();

// Handle test actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'clear':
            session_destroy();
            header('Location: test_premium_access.php');
            exit();
            
        case 'set_free':
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = 123;
            $_SESSION['user_name'] = 'Free Member Test';
            $_SESSION['user_email'] = 'free@test.com';
            $_SESSION['user_status'] = 1; // Free member
            $_SESSION['last_activity'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            header('Location: test_premium_access.php');
            exit();
            
        case 'set_premium':
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = 456;
            $_SESSION['user_name'] = 'Premium Member Test';
            $_SESSION['user_email'] = 'premium@test.com';
            $_SESSION['user_status'] = 2; // Premium member
            $_SESSION['last_activity'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            header('Location: test_premium_access.php');
            exit();
            
        case 'set_admin':
            $_SESSION['sauser'] = 'admin123';
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_role'] = 5; // Staff/Admin
            $_SESSION['admin_name'] = 'Test Administrator';
            $_SESSION['admin_id'] = 'ADM001';
            header('Location: test_premium_access.php');
            exit();
            
        case 'set_super_admin':
            $_SESSION['sauser'] = 'superadmin123';
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_role'] = 9; // Super Admin
            $_SESSION['admin_name'] = 'Test Super Administrator';
            $_SESSION['admin_id'] = 'SADM001';
            header('Location: test_premium_access.php');
            exit();
            
        case 'expire_session':
            $_SESSION['last_activity'] = time() - 2000; // Set expired
            header('Location: test_premium_access.php');
            exit();
    }
}

// Get current session status
$session_status = 'Tidak ada session';
$user_info = '';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $status_text = '';
    switch ($_SESSION['user_status'] ?? 0) {
        case 1:
            $status_text = 'Free Member';
            break;
        case 2:
            $status_text = 'Premium Member';
            break;
        default:
            $status_text = 'Unknown Status';
    }
    
    $session_status = 'Login sebagai: ' . $status_text;
    $user_info = 'User ID: ' . ($_SESSION['user_id'] ?? 'N/A') . '<br>';
    $user_info .= 'Nama: ' . ($_SESSION['user_name'] ?? 'N/A') . '<br>';
    $user_info .= 'Email: ' . ($_SESSION['user_email'] ?? 'N/A') . '<br>';
    $user_info .= 'Status: ' . ($_SESSION['user_status'] ?? 'N/A') . '<br>';
    $user_info .= 'Last Activity: ' . date('Y-m-d H:i:s', $_SESSION['last_activity'] ?? 0) . '<br>';
    
    // Check if session expired
    $session_timeout = 1800; // 30 menit
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
        $user_info .= '<span style="color: red;">⚠️ Session EXPIRED</span><br>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Premium Access System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #d4af37;
        }
        .status-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #d4af37;
        }
        .test-section {
            margin-bottom: 30px;
        }
        .test-btn {
            display: inline-block;
            background: linear-gradient(45deg, #d4af37, #ffd700);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 5px;
            transition: all 0.3s ease;
        }
        .test-btn:hover {
            background: linear-gradient(45deg, #b8860b, #d4af37);
            transform: translateY(-2px);
        }
        .test-btn.danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }
        .test-btn.danger:hover {
            background: linear-gradient(45deg, #c82333, #bd2130);
        }
        .test-btn.success {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        .test-btn.success:hover {
            background: linear-gradient(45deg, #20c997, #17a2b8);
        }
        .access-link {
            background: linear-gradient(45deg, #007bff, #0056b3);
            font-size: 1.1rem;
            padding: 15px 30px;
        }
        .access-link:hover {
            background: linear-gradient(45deg, #0056b3, #004085);
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ffeaa7;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #bee5eb;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Test Premium Access System</h1>
            <p>Testing untuk sistem validasi akses premium member</p>
        </div>

        <div class="status-box">
            <h3>📊 Status Session Saat Ini</h3>
            <p><strong><?php echo $session_status; ?></strong></p>
            <?php if ($user_info): ?>
                <div style="margin-top: 10px; font-size: 0.9rem;">
                    <?php echo $user_info; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="test-section">
            <h3>🎭 Simulasi User Status</h3>
            <p>Klik tombol di bawah untuk mensimulasikan berbagai status user:</p>
            
            <a href="?action=clear" class="test-btn danger">🚫 Clear Session (Logout)</a>
            <a href="?action=set_free" class="test-btn">👤 Set sebagai Free Member</a>
            <a href="?action=set_premium" class="test-btn success">💎 Set sebagai Premium Member</a>
            <a href="?action=set_admin" class="test-btn success">👨‍💼 Set sebagai Admin</a>
            <a href="?action=set_super_admin" class="test-btn success">🔑 Set sebagai Super Admin</a>
            <a href="?action=expire_session" class="test-btn danger">⏰ Expire Session</a>
        </div>

        <div class="test-section">
            <h3>🎯 Test Akses ke Halaman Premium</h3>
            <p>Klik tombol di bawah untuk mengakses halaman premium:</p>
            
            <a href="welcome-epi-premium.php" class="test-btn access-link" target="_blank">
                🔐 Akses Halaman Premium
            </a>
        </div>

        <div class="warning">
            <h4>⚠️ Skenario Testing:</h4>
            <ul>
                <li><strong>Tanpa Login:</strong> Harus redirect ke login dengan pesan</li>
                <li><strong>Free Member:</strong> Harus redirect ke upgrade dengan pesan khusus</li>
                <li><strong>Premium Member:</strong> Harus bisa akses halaman penuh</li>
                <li><strong>Session Expired:</strong> Harus redirect ke login dengan pesan timeout</li>
            </ul>
        </div>

        <div class="info">
            <h4>📋 Checklist Validasi:</h4>
            <ul>
                <li>✅ Session validation (logged_in = true)</li>
                <li>✅ Premium status check (user_status = 2)</li>
                <li>✅ Session timeout (30 menit)</li>
                <li>✅ User agent consistency</li>
                <li>✅ CSRF token generation</li>
                <li>✅ Security headers</li>
                <li>✅ Access logging</li>
            </ul>
        </div>

        <div class="test-section">
            <h3>🔗 Link Terkait</h3>
            <a href="https://bisnisemasperak.com/login" class="test-btn" target="_blank">🔑 Halaman Login</a>
            <a href="logout.php" class="test-btn danger">🚪 Logout</a>
            <a href="welcome-epi.html" class="test-btn">📄 Halaman Original (HTML)</a>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666;">
            <p>© 2025 EPI - Premium Access Testing System</p>
            <p style="font-size: 0.9rem;">⚠️ File ini hanya untuk testing, hapus setelah implementasi selesai</p>
        </div>
    </div>
</body>
</html>