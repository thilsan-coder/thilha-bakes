<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isCustomer()) redirect('../login.php');

$uid=$_SESSION['user_id'];
$msg=''; $err='';

if (isset($_POST['update_profile'])) {
    checkCSRF();
    $name=e($conn,$_POST['name']); $phone=e($conn,$_POST['phone']); $address=e($conn,$_POST['address']); $bday=e($conn,$_POST['birthday']??'');
    $bday_val=!empty($bday)?"'$bday'":'NULL';
    mysqli_query($conn,"UPDATE users SET name='$name',phone='$phone',address='$address',birthday=$bday_val WHERE user_id=$uid");
    $_SESSION['name']=$name; $msg='Profile updated!';
}
if (isset($_POST['change_password'])) {
    checkCSRF();
    $current=$_POST['current_password']; $new_pw=$_POST['new_password']; $confirm=$_POST['confirm_password'];
    $user=mysqli_fetch_assoc(mysqli_query($conn,"SELECT password FROM users WHERE user_id=$uid"));
    if (!verifyPassword($current,$user['password'])) $err='Current password incorrect!';
    elseif (strlen($new_pw)<6) $err='Password must be at least 6 characters!';
    elseif ($new_pw!==$confirm) $err='Passwords do not match!';
    else { $hash=hashPassword($new_pw); mysqli_query($conn,"UPDATE users SET password='$hash' WHERE user_id=$uid"); $msg='Password changed!'; }
}

$user=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE user_id=$uid"));
$loyalty_log=mysqli_query($conn,"SELECT * FROM loyalty_log WHERE user_id=$uid ORDER BY created_at DESC LIMIT 10");
$total_orders=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM orders WHERE user_id=$uid"))['c'];
$total_spent=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(b.total),0) as amt FROM orders o LEFT JOIN bills b ON o.order_id=b.order_id WHERE o.user_id=$uid"))['amt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Profile — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{--gold:#c9973a;--gold-light:#e8c870;--dark:#1a0a0f;--dark2:#220d14;--dark3:#2a1018;--text:#f0e0c0;--muted:#9a7a58;}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--dark);font-family:'Inter',sans-serif;color:var(--text);min-height:100vh;}
.page-wrap{padding:36px 5%;}
.page-heading{font-family:'Playfair Display',serif;font-size:32px;font-weight:900;color:#fff;margin-bottom:6px;}
.page-sub{font-size:14px;color:var(--muted);margin-bottom:28px;}

/* PROFILE HERO */
.profile-hero{
  background:linear-gradient(135deg,var(--dark2),var(--dark3));
  border:1.5px solid rgba(201,151,58,0.2);border-radius:20px;
  padding:28px;margin-bottom:28px;
  display:flex;align-items:center;gap:24px;flex-wrap:wrap;
}
.profile-avatar{
  width:80px;height:80px;border-radius:50%;
  background:linear-gradient(135deg,var(--gold),#a07828);
  border:3px solid rgba(201,151,58,0.4);
  display:flex;align-items:center;justify-content:center;
  font-family:'Playfair Display',serif;font-size:32px;font-weight:700;color:#fff;flex-shrink:0;
}
.profile-name{font-family:'Playfair Display',serif;font-size:24px;font-weight:700;color:#fff;margin-bottom:4px;}
.profile-email{font-size:14px;color:var(--muted);}
.profile-stats{display:flex;gap:24px;margin-top:12px;flex-wrap:wrap;}
.p-stat-num{font-size:20px;font-weight:700;color:var(--gold-light);}
.p-stat-label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;}
.loyalty-big{
  margin-left:auto;text-align:center;
  background:rgba(201,151,58,0.07);
  border:1px solid rgba(201,151,58,0.2);
  border-radius:16px;padding:16px 24px;
}
.loyalty-big .pts{font-family:'Playfair Display',serif;font-size:36px;font-weight:900;color:var(--gold-light);}

/* TABS */
.tabs{display:flex;gap:4px;background:var(--dark2);border:1.5px solid rgba(201,151,58,0.12);border-radius:12px;padding:4px;margin-bottom:24px;flex-wrap:wrap;}
.tab-btn{flex:1;padding:10px 16px;border-radius:8px;border:none;background:transparent;font-size:13px;font-weight:700;color:var(--muted);cursor:pointer;transition:all .2s;min-width:80px;}
.tab-btn.active{background:var(--gold);color:#fff;}
.tab-content{display:none;}
.tab-content.active{display:block;}

/* FORM CARD */
.form-card{background:var(--dark2);border:1.5px solid rgba(201,151,58,0.12);border-radius:16px;padding:28px;}
.form-title{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#fff;margin-bottom:20px;}
.form-label{font-size:12px;color:var(--gold);font-weight:700;margin-bottom:6px;display:block;letter-spacing:.5px;text-transform:uppercase;}
.form-control{width:100%;background:var(--dark3);border:1.5px solid rgba(201,151,58,0.18);border-radius:10px;padding:12px 16px;font-size:14px;color:var(--text);outline:none;transition:border .2s;font-family:'Inter',sans-serif;}
.form-control:focus{border-color:var(--gold);}
.form-control::placeholder{color:var(--muted);}
.btn-save{background:linear-gradient(135deg,var(--gold),#a07828);color:#fff;border:none;border-radius:99px;padding:12px 32px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:8px;}
.btn-save:hover{background:linear-gradient(135deg,var(--gold-light),var(--gold));color:#1a0a0f;transform:translateY(-1px);}
.alert-s{background:rgba(46,125,50,0.08);border:1px solid rgba(46,125,50,0.25);border-left:3px solid #2e7d32;border-radius:10px;padding:12px 16px;font-size:13px;color:#66bb6a;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.alert-e{background:rgba(239,83,80,0.08);border:1px solid rgba(239,83,80,0.25);border-left:3px solid #ef5350;border-radius:10px;padding:12px 16px;font-size:13px;color:#ef5350;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.log-item{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid rgba(201,151,58,0.07);font-size:14px;}
.log-item:last-child{border-bottom:none;}
.log-earn{color:#66bb6a;font-weight:700;}
.log-redeem{color:#ef5350;font-weight:700;}
</style>
</head>
<body>
<?php include 'customer_nav.php'; ?>

<div class="page-wrap">
  <div class="page-heading">My <span style="color:var(--gold-light);font-style:italic;">Profile</span></div>
  <div class="page-sub">Manage your account details</div>

  <!-- PROFILE HERO -->
  <div class="profile-hero">
    <div class="profile-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
    <div>
      <div class="profile-name"><?= htmlspecialchars($user['name']) ?></div>
      <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
      <div class="profile-stats">
        <div><div class="p-stat-num"><?= $total_orders ?></div><div class="p-stat-label">Orders</div></div>
        <div><div class="p-stat-num">Rs. <?= number_format($total_spent,0) ?></div><div class="p-stat-label">Spent</div></div>
        <div><div class="p-stat-num"><?= date('M Y',strtotime($user['created_at'])) ?></div><div class="p-stat-label">Member Since</div></div>
      </div>
    </div>
    <div class="loyalty-big">
      <div style="font-size:11px;color:var(--gold);font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:4px;">⭐ Loyalty</div>
      <div class="pts"><?= $user['loyalty_points'] ?></div>
      <div style="font-size:12px;color:var(--muted);margin-top:2px;">Worth Rs. <?= floor($user['loyalty_points']/10) ?></div>
    </div>
  </div>

  <!-- TABS -->
  <div class="tabs">
    <button class="tab-btn active" onclick="switchTab('profile',this)"><i class="bi bi-person me-1"></i>Profile</button>
    <button class="tab-btn" onclick="switchTab('password',this)"><i class="bi bi-lock me-1"></i>Password</button>
    <button class="tab-btn" onclick="switchTab('loyalty',this)"><i class="bi bi-star me-1"></i>Loyalty Log</button>
  </div>

  <?php if($msg): ?><div class="alert-s"><i class="bi bi-check-circle-fill"></i><?= $msg ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert-e"><i class="bi bi-exclamation-circle-fill"></i><?= $err ?></div><?php endif; ?>

  <!-- PROFILE TAB -->
  <div class="tab-content active" id="tab-profile">
    <div class="form-card">
      <div class="form-title">Edit Profile</div>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="update_profile" value="1">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']??'') ?>" placeholder="07X XXX XXXX">
          </div>
          <div class="col-12">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:.5;cursor:not-allowed;">
          </div>
          <div class="col-md-8">
            <label class="form-label">Delivery Address</label>
            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address']??'') ?>" placeholder="Your address">
          </div>
          <div class="col-md-4">
            <label class="form-label">Birthday 🎂</label>
            <input type="date" name="birthday" class="form-control" value="<?= $user['birthday']??'' ?>">
          </div>
        </div>
        <div style="margin-top:20px;">
          <button type="submit" class="btn-save"><i class="bi bi-check-lg"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- PASSWORD TAB -->
  <div class="tab-content" id="tab-password">
    <div class="form-card">
      <div class="form-title">Change Password</div>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="change_password" value="1">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
          </div>
          <div class="col-md-6">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" required placeholder="Min 6 characters">
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
          </div>
        </div>
        <div style="margin-top:20px;">
          <button type="submit" class="btn-save"><i class="bi bi-lock-fill"></i> Change Password</button>
        </div>
      </form>
    </div>
  </div>

  <!-- LOYALTY TAB -->
  <div class="tab-content" id="tab-loyalty">
    <div class="form-card">
      <div class="form-title">Loyalty Points History</div>
      <?php if(mysqli_num_rows($loyalty_log)===0): ?>
      <div style="text-align:center;padding:40px;color:var(--muted);">
        <i class="bi bi-star" style="font-size:40px;color:rgba(201,151,58,0.15);display:block;margin-bottom:12px;"></i>
        No loyalty activity yet. Start ordering to earn points!
      </div>
      <?php else: while($log=mysqli_fetch_assoc($loyalty_log)): ?>
      <div class="log-item">
        <div>
          <div style="font-size:14px;color:var(--text);">Order #<?= $log['order_id'] ?></div>
          <div style="font-size:12px;color:var(--muted);"><?= date('d M Y, h:i A',strtotime($log['created_at'])) ?></div>
        </div>
        <div class="<?= $log['type']==='earn'?'log-earn':'log-redeem' ?>">
          <?= $log['type']==='earn'?'+':'-' ?><?= $log['points'] ?> pts
        </div>
      </div>
      <?php endwhile; endif; ?>
    </div>
  </div>
</div>

<script>
function switchTab(tab,btn){
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-'+tab).classList.add('active');
}
</script>
</body>
</html>
