<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Path di mana gambar akan disimpan
  $uploadDir = 'upload/';
  
  // Buat direktori jika belum ada
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }
  
  // Menangani upload file
  if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Mendapatkan judul artikel
    $judulArtikel = isset($_POST['judul']) ? $_POST['judul'] : 'untitled';
    $judulArtikel = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($judulArtikel)); // Hanya karakter yang valid

    // Menambahkan 3 angka unik di akhir nama file
    $uniqueId = mt_rand(100, 999);

    // Menentukan ekstensi file
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = [
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/gif' => 'gif'
    ];

    $fileExtension = isset($extensions[$mime]) ? $extensions[$mime] : '';

    // Menentukan nama file baru
    $newFileName = $judulArtikel . '-' . $uniqueId . ($fileExtension ? '.' . $fileExtension : '');
    $uploadFilePath = $uploadDir . $newFileName;

    // Memindahkan file yang diupload ke direktori yang ditentukan
    if (move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
      // Mengembalikan URL absolut dari gambar yang berhasil diupload
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      $domain = $_SERVER['HTTP_HOST'];
      $absoluteUrl = $protocol . $domain . '/' . $uploadFilePath;

      echo json_encode([
        'link' => $absoluteUrl
      ]);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Failed to move uploaded file.']);
    }
  } else {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded.']);
  }
} else {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed.']);
}
?>
