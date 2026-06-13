<?php
require_once 'includes/db.php';

if (isLoggedIn()) {
    if (isAdmin())      redirect('admin/dashboard.php');
    elseif (isStaff())  redirect('staff/dashboard.php');
    else                redirect('customer/landing.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = e($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password!';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if ($user && verifyPassword($password, $user['password'])) {
            if (strlen($user['password']) === 32) {
                $new_hash = hashPassword($password);
                mysqli_query($conn, "UPDATE users SET password='$new_hash' WHERE user_id={$user['user_id']}");
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];
            if ($user['role'] === 'admin')      redirect('admin/dashboard.php');
            elseif ($user['role'] === 'staff')  redirect('staff/dashboard.php');
            else                                redirect('customer/landing.php');
        } else {
            $error = 'Invalid email or password!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:#1a0a0f;font-family:'Segoe UI',sans-serif;display:flex;align-items:center;justify-content:center;padding:30px 20px;position:relative;overflow:hidden;}
body::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(201,151,58,0.06) 1px,transparent 1px);background-size:40px 40px;}
body::after{content:'';position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(201,151,58,0.07),transparent 70%);top:-150px;right:-100px;pointer-events:none;}
.login-container{display:flex;width:100%;max-width:960px;min-height:580px;border-radius:20px;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,0.6);position:relative;z-index:2;}
/* LEFT */
.left-panel{flex:1;background:linear-gradient(160deg,#220d14 0%,#2a1018 50%,#1a0a0f 100%);border:1px solid rgba(201,151,58,0.2);border-right:none;border-radius:20px 0 0 20px;padding:52px 44px;display:flex;flex-direction:column;align-items:center;justify-content:center;position:relative;overflow:hidden;}
.left-panel::before{content:'';position:absolute;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(201,151,58,0.08),transparent 70%);bottom:-80px;left:-80px;}
.brand-icon{font-size:60px;margin-bottom:18px;filter:drop-shadow(0 6px 16px rgba(201,151,58,0.35));animation:float 4s ease-in-out infinite;}
@keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-12px);}}
.brand-name{font-family:'Playfair Display',serif;font-size:32px;font-weight:900;color:#e8c870;letter-spacing:4px;margin-bottom:5px;}
.brand-sub{font-family:'Playfair Display',serif;font-size:12px;color:#c9973a;letter-spacing:5px;margin-bottom:6px;}
.brand-loc{font-size:12px;color:#5a3a28;margin-bottom:32px;}
.divider-gold{width:60px;height:1px;background:linear-gradient(90deg,transparent,#c9973a,transparent);margin:0 auto 32px;}
.feature-list{list-style:none;width:100%;display:flex;flex-direction:column;gap:14px;}
.feature-list li{display:flex;align-items:center;gap:12px;font-size:14px;color:#c9b090;padding-bottom:14px;border-bottom:1px solid rgba(201,151,58,0.1);}
.feature-list li:last-child{border-bottom:none;padding-bottom:0;}
.feature-list li i{color:#c9973a;font-size:16px;flex-shrink:0;}
/* RIGHT */
.right-panel{width:420px;flex-shrink:0;background:#fdf5ec;border-radius:0 20px 20px 0;padding:52px 44px;display:flex;flex-direction:column;justify-content:center;border-left:4px solid #c9973a;}
.login-title{font-family:'Playfair Display',serif;font-size:28px;font-weight:900;color:#1a0a0f;margin-bottom:6px;}
.login-sub{font-size:14px;color:#8a6a4a;margin-bottom:32px;}
.form-label{display:block;font-size:12px;font-weight:700;color:#c9973a;letter-spacing:1px;text-transform:uppercase;margin-bottom:8px;}
.input-wrap{position:relative;margin-bottom:20px;}
.input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#b09070;font-size:15px;pointer-events:none;}
.form-input{width:100%;background:#fff;border:1.5px solid #e8d8c0;border-radius:10px;padding:13px 14px 13px 44px;font-size:14px;color:#1a0a0f;outline:none;transition:all .2s;font-family:'Segoe UI',sans-serif;}
.form-input:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.12);}
.form-input::placeholder{color:#c9b090;}
.eye-btn{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:none;color:#b09070;cursor:pointer;font-size:15px;padding:4px;transition:color .2s;}
.eye-btn:hover{color:#c9973a;}
.error-box{background:#fff3f0;border:1px solid #f5c0b0;border-left:3px solid #ef5350;border-radius:8px;padding:11px 14px;font-size:13px;color:#c62828;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.btn-signin{width:100%;background:#c9973a;border:none;border-radius:10px;color:#fff;font-weight:700;padding:14px;font-size:15px;cursor:pointer;font-family:'Segoe UI',sans-serif;transition:all .2s;letter-spacing:.3px;display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:12px;}
.btn-signin:hover{background:#a07828;transform:translateY(-1px);box-shadow:0 6px 20px rgba(201,151,58,0.4);}
.btn-register{width:100%;background:transparent;border:2px solid #1a0a0f;border-radius:10px;color:#1a0a0f;font-weight:700;padding:12px;font-size:14px;cursor:pointer;font-family:'Segoe UI',sans-serif;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;margin-bottom:16px;}
.btn-register:hover{background:#1a0a0f;color:#e8c870;}
.divider{display:flex;align-items:center;gap:10px;margin:4px 0 12px;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e8d8c0;}
.divider span{font-size:12px;color:#b09070;}
.visit-link{text-align:center;font-size:13px;color:#b09070;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;transition:color .2s;}
.visit-link:hover{color:#c9973a;}
@media(max-width:768px){.left-panel{display:none;}.right-panel{width:100%;border-radius:20px;border-left:none;border-top:4px solid #c9973a;}.login-container{max-width:420px;}}
</style>
</head>
<body>
<div class="login-container">
  <div class="left-panel">
    <div class="brand-icon">🎂</div>
    <div class="brand-name">THILHA</div>
    <div class="brand-sub">DIVINE BAKES</div>
    <div class="brand-loc">~ Central Camp, Ampara, Sri Lanka ~</div>
    <div class="divider-gold"></div>
    <ul class="feature-list">
      <li><i class="bi bi-check-circle-fill"></i> Easy Online Ordering</li>
      <li><i class="bi bi-check-circle-fill"></i> Real-Time Order Tracking</li>
      <li><i class="bi bi-check-circle-fill"></i> Loyalty Rewards Program</li>
      <li><i class="bi bi-check-circle-fill"></i> Digital Bills & Invoices</li>
      <li><i class="bi bi-check-circle-fill"></i> Custom Cake Orders</li>
      <li><i class="bi bi-check-circle-fill"></i> WhatsApp Order Support</li>
    </ul>
  </div>
  <div class="right-panel">
    <div class="login-title">Welcome Back!</div>
    <div class="login-sub">Sign in to your Thilha Divine Bakes account</div>
    <?php if ($error): ?>
    <div class="error-box"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <label class="form-label">Email Address</label>
      <div class="input-wrap">
        <i class="bi bi-envelope input-icon"></i>
        <input type="email" name="email" class="form-input" placeholder="admin@thilhabakes.com" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
      </div>
      <label class="form-label">Password</label>
      <div class="input-wrap">
        <i class="bi bi-lock input-icon"></i>
        <input type="password" name="password" id="pwdInput" class="form-input" placeholder="••••••••" required>
        <button type="button" class="eye-btn" onclick="togglePwd()"><i class="bi bi-eye" id="eyeIcon"></i></button>
      </div>
      <button type="submit" class="btn-signin"><i class="bi bi-box-arrow-in-right"></i> Sign In</button>
    </form>
    <div class="divider"><span>or</span></div>
    <a href="register.php" class="btn-register"><i class="bi bi-person-plus"></i> Create Customer Account</a>
    <a href="customer/landing.php" class="visit-link"><i class="bi bi-globe"></i> Visit our Website</a>
  </div>
</div>
<script>
function togglePwd(){
  const i=document.getElementById('pwdInput'),e=document.getElementById('eyeIcon');
  i.type=i.type==='password'?'text':'password';
  e.className=i.type==='password'?'bi bi-eye':'bi bi-eye-slash';
}
</script>
</body>
</html>
