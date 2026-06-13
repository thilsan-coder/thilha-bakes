<?php
require_once 'includes/db.php';
if (isLoggedIn()) redirect('customer/landing.php');

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    $name     = e($conn, $_POST['name']     ?? '');
    $email    = e($conn, $_POST['email']    ?? '');
    $phone    = e($conn, $_POST['phone']    ?? '');
    $address  = e($conn, $_POST['address']  ?? '');
    $birthday = e($conn, $_POST['birthday'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email and password are required!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters!';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address!';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email=?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Email already registered!';
        } else {
            $hashed = hashPassword($password);
            $bday   = !empty($birthday) ? $birthday : null;
            $stmt2  = mysqli_prepare($conn,"INSERT INTO users (name,email,password,phone,address,birthday,role) VALUES (?,?,?,?,?,?,'customer')");
            mysqli_stmt_bind_param($stmt2,'ssssss',$name,$email,$hashed,$phone,$address,$bday);
            if (mysqli_stmt_execute($stmt2)) {
                $success = 'Account created successfully!';
            } else {
                $error = 'Something went wrong. Try again!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:#1a0a0f;font-family:'Segoe UI',sans-serif;display:flex;align-items:center;justify-content:center;padding:30px 20px;position:relative;overflow:hidden;}
body::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(201,151,58,0.06) 1px,transparent 1px);background-size:40px 40px;}
body::after{content:'';position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(201,151,58,0.07),transparent 70%);top:-150px;right:-100px;pointer-events:none;}
.reg-container{display:flex;width:100%;max-width:1020px;border-radius:20px;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,0.6);position:relative;z-index:2;}
/* LEFT */
.left-panel{width:300px;flex-shrink:0;background:linear-gradient(160deg,#220d14 0%,#2a1018 50%,#1a0a0f 100%);border:1px solid rgba(201,151,58,0.2);border-right:none;border-radius:20px 0 0 20px;padding:48px 36px;display:flex;flex-direction:column;align-items:center;justify-content:center;position:relative;overflow:hidden;}
.left-panel::before{content:'';position:absolute;width:250px;height:250px;border-radius:50%;background:radial-gradient(circle,rgba(201,151,58,0.08),transparent 70%);bottom:-60px;left:-60px;}
.brand-icon{font-size:54px;margin-bottom:16px;filter:drop-shadow(0 6px 16px rgba(201,151,58,0.35));animation:float 4s ease-in-out infinite;}
@keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}
.brand-name{font-family:'Playfair Display',serif;font-size:28px;font-weight:900;color:#e8c870;letter-spacing:4px;margin-bottom:4px;}
.brand-sub{font-family:'Playfair Display',serif;font-size:11px;color:#c9973a;letter-spacing:5px;margin-bottom:6px;}
.brand-loc{font-size:11px;color:#5a3a28;margin-bottom:28px;}
.divider-gold{width:50px;height:1px;background:linear-gradient(90deg,transparent,#c9973a,transparent);margin:0 auto 28px;}
.benefit-list{list-style:none;width:100%;display:flex;flex-direction:column;gap:12px;}
.benefit-list li{display:flex;align-items:center;gap:10px;font-size:13px;color:#c9b090;padding-bottom:12px;border-bottom:1px solid rgba(201,151,58,0.1);}
.benefit-list li:last-child{border-bottom:none;padding-bottom:0;}
.benefit-list li i{color:#c9973a;font-size:14px;flex-shrink:0;}
/* RIGHT */
.right-panel{flex:1;background:#fdf5ec;border-radius:0 20px 20px 0;padding:40px 44px;border-left:4px solid #c9973a;overflow-y:auto;}
.reg-title{font-family:'Playfair Display',serif;font-size:26px;font-weight:900;color:#1a0a0f;margin-bottom:4px;}
.reg-sub{font-size:14px;color:#8a6a4a;margin-bottom:26px;}
.section-label{font-size:11px;font-weight:700;color:#c9973a;letter-spacing:2px;text-transform:uppercase;margin:0 0 16px;display:flex;align-items:center;gap:8px;}
.section-label::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,#e8d8c0,transparent);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:12px;font-weight:700;color:#c9973a;letter-spacing:1px;text-transform:uppercase;margin-bottom:7px;}
.form-label span{color:#ef5350;}
.input-wrap{position:relative;}
.input-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#b09070;font-size:14px;pointer-events:none;}
.form-input{width:100%;background:#fff;border:1.5px solid #e8d8c0;border-radius:10px;padding:12px 12px 12px 40px;font-size:14px;color:#1a0a0f;outline:none;transition:all .2s;font-family:'Segoe UI',sans-serif;}
.form-input:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.12);}
.form-input::placeholder{color:#c9b090;}
.eye-btn{position:absolute;right:11px;top:50%;transform:translateY(-50%);background:none;border:none;color:#b09070;cursor:pointer;font-size:14px;padding:4px;transition:color .2s;}
.eye-btn:hover{color:#c9973a;}
.strength-bar{height:3px;border-radius:2px;margin-top:5px;transition:all .3s;background:#e8d8c0;}
.strength-text{font-size:11px;margin-top:3px;}
.match-text{font-size:11px;margin-top:3px;}
.error-box{background:#fff3f0;border:1px solid #f5c0b0;border-left:3px solid #ef5350;border-radius:8px;padding:11px 14px;font-size:13px;color:#c62828;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.success-box{background:#f0fff4;border:1px solid #b2dfdb;border-left:3px solid #2e7d32;border-radius:8px;padding:11px 14px;font-size:13px;color:#1b5e20;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.success-box a{color:#c9973a;font-weight:700;margin-left:6px;}
.btn-create{width:100%;background:#c9973a;border:none;border-radius:10px;color:#fff;font-weight:700;padding:14px;font-size:15px;cursor:pointer;font-family:'Segoe UI',sans-serif;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:8px;margin-bottom:16px;letter-spacing:.3px;}
.btn-create:hover{background:#a07828;transform:translateY(-1px);box-shadow:0 6px 20px rgba(201,151,58,0.4);}
.bottom-links{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:13px;color:#8a6a4a;}
.bottom-links a{color:#1a0a0f;font-weight:700;text-decoration:none;}
.bottom-links a:hover{color:#c9973a;}
.back-link{color:#b09070;font-size:13px;text-decoration:none;display:flex;align-items:center;gap:5px;}
.back-link:hover{color:#c9973a;}
@media(max-width:768px){.left-panel{display:none;}.right-panel{border-radius:20px;border-left:none;border-top:4px solid #c9973a;}.form-row{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="reg-container">
  <div class="left-panel">
    <div class="brand-icon">🎂</div>
    <div class="brand-name">THILHA</div>
    <div class="brand-sub">DIVINE BAKES</div>
    <div class="brand-loc">~ Central Camp, Ampara ~</div>
    <div class="divider-gold"></div>
    <ul class="benefit-list">
      <li><i class="bi bi-star-fill"></i> Free to register</li>
      <li><i class="bi bi-bag-check-fill"></i> Track all your orders</li>
      <li><i class="bi bi-gift-fill"></i> Earn loyalty points</li>
      <li><i class="bi bi-receipt"></i> Access your invoices</li>
      <li><i class="bi bi-cake2-fill"></i> Birthday special offers</li>
      <li><i class="bi bi-whatsapp"></i> WhatsApp order support</li>
      <li><i class="bi bi-truck"></i> Fast delivery tracking</li>
    </ul>
  </div>
  <div class="right-panel">
    <div class="reg-title">Create Account</div>
    <div class="reg-sub">Join Thilha Divine Bakes — it's free!</div>

    <?php if ($error): ?>
    <div class="error-box"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="success-box"><i class="bi bi-check-circle-fill"></i><?= $success ?><a href="login.php">Login now →</a></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <div class="section-label">Personal Info</div>
      <div class="form-group">
        <label class="form-label">Full Name <span>*</span></label>
        <div class="input-wrap">
          <i class="bi bi-person input-icon"></i>
          <input type="text" name="name" class="form-input" placeholder="Your full name" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Email <span>*</span></label>
          <div class="input-wrap">
            <i class="bi bi-envelope input-icon"></i>
            <input type="email" name="email" class="form-input" placeholder="you@email.com" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <div class="input-wrap">
            <i class="bi bi-telephone input-icon"></i>
            <input type="text" name="phone" class="form-input" placeholder="07X XXX XXXX" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
          </div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Address</label>
          <div class="input-wrap">
            <i class="bi bi-geo-alt input-icon"></i>
            <input type="text" name="address" class="form-input" placeholder="Your address" value="<?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Birthday 🎂</label>
          <div class="input-wrap">
            <i class="bi bi-calendar-heart input-icon"></i>
            <input type="date" name="birthday" class="form-input" value="<?= isset($_POST['birthday']) ? $_POST['birthday'] : '' ?>">
          </div>
        </div>
      </div>
      <div class="section-label" style="margin-top:4px;">Security</div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password <span>*</span></label>
          <div class="input-wrap">
            <i class="bi bi-lock input-icon"></i>
            <input type="password" name="password" id="pwd" class="form-input" placeholder="Min 6 characters" required oninput="checkStrength()">
            <button type="button" class="eye-btn" onclick="togglePwd('pwd','eye1')"><i class="bi bi-eye" id="eye1"></i></button>
          </div>
          <div class="strength-bar" id="sBar"></div>
          <div class="strength-text" id="sText"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password <span>*</span></label>
          <div class="input-wrap">
            <i class="bi bi-lock-check input-icon"></i>
            <input type="password" name="confirm" id="conf" class="form-input" placeholder="Repeat password" required oninput="checkMatch()">
            <button type="button" class="eye-btn" onclick="togglePwd('conf','eye2')"><i class="bi bi-eye" id="eye2"></i></button>
          </div>
          <div class="match-text" id="mText"></div>
        </div>
      </div>
      <button type="submit" class="btn-create">
        <i class="bi bi-person-check-fill"></i> Create My Account
      </button>
    </form>
    <div class="bottom-links">
      <span>Already have an account? <a href="login.php">Sign In</a></span>
      <a href="customer/landing.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Home</a>
    </div>
  </div>
</div>
<script>
function togglePwd(id,iconId){const i=document.getElementById(id),e=document.getElementById(iconId);i.type=i.type==='password'?'text':'password';e.className=i.type==='password'?'bi bi-eye':'bi bi-eye-slash';}
function checkStrength(){
  const pwd=document.getElementById('pwd').value;
  const bar=document.getElementById('sBar'),txt=document.getElementById('sText');
  let s=0;
  if(pwd.length>=6)s++;if(pwd.length>=8)s++;if(/[A-Z]/.test(pwd))s++;if(/[0-9]/.test(pwd))s++;if(/[^A-Za-z0-9]/.test(pwd))s++;
  const c=['#ef5350','#ff9800','#ffc107','#66bb6a','#2e7d32'],l=['Very Weak','Weak','Fair','Strong','Very Strong'];
  bar.style.width=(s*20)+'%';bar.style.background=c[s-1]||'#e8d8c0';
  txt.style.color=c[s-1]||'#b09070';txt.textContent=pwd.length>0?(l[s-1]||''):'';
}
function checkMatch(){
  const pwd=document.getElementById('pwd').value,conf=document.getElementById('conf').value,txt=document.getElementById('mText');
  if(!conf.length){txt.textContent='';return;}
  if(pwd===conf){txt.style.color='#2e7d32';txt.textContent='✓ Passwords match';}
  else{txt.style.color='#ef5350';txt.textContent='✗ Passwords do not match';}
}
</script>
</body>
</html>
