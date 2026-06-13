<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'thilha_bakes');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("<h3 style='color:red;font-family:sans-serif;padding:20px;'>Database Connection Failed!</h3>");
}
mysqli_set_charset($conn, 'utf8');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>86400,'path'=>'/','httponly'=>true,'samesite'=>'Strict']);
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function isLoggedIn() { return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']); }
function isAdmin()    { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function isCustomer() { return isset($_SESSION['role']) && ($_SESSION['role'] === 'customer' || $_SESSION['role'] === 'admin'); }
function isStaff()    { return isset($_SESSION['role']) && ($_SESSION['role'] === 'staff' || $_SESSION['role'] === 'admin'); }
function redirect($url) { header("Location: $url"); exit(); }
function e($conn, $str) { return mysqli_real_escape_string($conn, trim($str)); }
function checkCSRF() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("<h3 style='color:red;padding:20px;'>Invalid request!</h3>");
    }
}
function hashPassword($p) { return password_hash($p, PASSWORD_BCRYPT, ['cost'=>12]); }
function verifyPassword($p, $h) {
    if (strlen($h) === 32) return md5($p) === $h;
    return password_verify($p, $h);
}
function uploadImage($file, $folder='../assets/images/') {
    $allowed = ['jpg','jpeg','png','gif','webp'];
    $allowed_mime = ['image/jpeg','image/png','image/gif','image/webp'];
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > 5*1024*1024) return false;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed_mime)) return false;
    $filename = 'img_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
    if (move_uploaded_file($file['tmp_name'], $folder.$filename)) return $filename;
    return false;
}
?>
