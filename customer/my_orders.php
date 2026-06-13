<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isCustomer()) redirect('../login.php');

$uid=$_SESSION['user_id'];
$orders=mysqli_query($conn,"SELECT o.*,b.bill_number,b.total as bill_total FROM orders o LEFT JOIN bills b ON o.order_id=b.order_id WHERE o.user_id=$uid ORDER BY o.created_at DESC");
$loyalty=mysqli_fetch_assoc(mysqli_query($conn,"SELECT loyalty_points FROM users WHERE user_id=$uid"))['loyalty_points'];
$total_orders=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM orders WHERE user_id=$uid"))['c'];
$total_spent=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(b.total),0) as amt FROM orders o LEFT JOIN bills b ON o.order_id=b.order_id WHERE o.user_id=$uid"))['amt'];
$statuses=['pending','confirmed','baking','ready','delivered'];
$status_colors=['pending'=>'#e65c00','confirmed'=>'#2e7d32','baking'=>'#7b1fa2','ready'=>'#1565c0','delivered'=>'#1b5e20'];
$status_bg=['pending'=>'rgba(230,92,0,0.1)','confirmed'=>'rgba(46,125,50,0.1)','baking'=>'rgba(123,31,162,0.1)','ready'=>'rgba(21,101,192,0.1)','delivered'=>'rgba(27,94,32,0.1)'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Orders — Thilha Divine Bakes</title>
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

/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;}
.stat-card{background:var(--dark2);border:1.5px solid rgba(201,151,58,0.12);border-top:3px solid var(--gold);border-radius:14px;padding:18px 20px;}
.stat-num{font-size:24px;font-weight:700;color:var(--gold-light);margin-bottom:4px;}
.stat-label{font-size:12px;color:var(--muted);font-weight:700;letter-spacing:1px;text-transform:uppercase;}

/* LOYALTY CARD */
.loyalty-card{
  background:linear-gradient(135deg,var(--dark2),var(--dark3));
  border:1.5px solid rgba(201,151,58,0.25);
  border-radius:16px;padding:20px 24px;
  margin-bottom:28px;
  display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;
}
.loyalty-pts{font-family:'Playfair Display',serif;font-size:32px;font-weight:900;color:var(--gold-light);}

/* ORDER CARD */
.order-card{background:var(--dark2);border:1.5px solid rgba(201,151,58,0.12);border-radius:16px;padding:20px;margin-bottom:16px;transition:border .2s;}
.order-card:hover{border-color:rgba(201,151,58,0.3);}
.order-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px;}
.order-id{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#fff;}
.order-date{font-size:12px;color:var(--muted);margin-top:3px;}

/* STEPPER */
.stepper{display:flex;align-items:center;margin:16px 0;}
.step{display:flex;flex-direction:column;align-items:center;gap:4px;flex:1;}
.step-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;}
.step-dot.done{background:var(--gold);color:var(--dark);}
.step-dot.active{background:rgba(201,151,58,0.15);border:2px solid var(--gold);color:var(--gold);}
.step-dot.todo{background:rgba(255,255,255,0.04);color:rgba(255,255,255,0.2);}
.step-label{font-size:10px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;}
.step-label.done{color:var(--gold);}
.step-label.active{color:var(--gold-light);}
.step-label.todo{color:rgba(255,255,255,0.2);}
.step-line{flex:1;height:2px;background:rgba(255,255,255,0.05);margin-bottom:16px;}
.step-line.done{background:var(--gold);}

/* ORDER FOOTER */
.order-footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding-top:14px;border-top:1px solid rgba(201,151,58,0.08);}
.order-amount{font-size:20px;font-weight:700;color:var(--gold-light);}
.btn-view-bill{background:rgba(201,151,58,0.08);border:1.5px solid rgba(201,151,58,0.25);border-radius:99px;color:var(--gold);font-size:13px;font-weight:700;padding:7px 18px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s;}
.btn-view-bill:hover{background:var(--gold);color:var(--dark);}

/* EMPTY STATE */
.empty-state{text-align:center;padding:80px 20px;}
.empty-state i{font-size:72px;color:rgba(201,151,58,0.1);display:block;margin-bottom:20px;}

/* ORDER NOW BUTTON — fixed alignment */
.btn-order-now{
  display:inline-flex;align-items:center;justify-content:center;gap:10px;
  background:linear-gradient(135deg,var(--gold),#a07828);
  color:#fff;border-radius:99px;
  padding:14px 40px;font-weight:700;
  text-decoration:none;font-size:15px;
  transition:all .2s;
  box-shadow:0 4px 16px rgba(201,151,58,0.25);
  letter-spacing:.3px;
}
.btn-order-now:hover{
  background:linear-gradient(135deg,var(--gold-light),var(--gold));
  color:#1a0a0f;transform:translateY(-2px);
  box-shadow:0 8px 24px rgba(201,151,58,0.35);
}
</style>
</head>
<body>
<?php include 'customer_nav.php'; ?>

<div class="page-wrap">
  <div class="page-heading">My <span style="color:var(--gold-light);font-style:italic;">Orders</span></div>
  <div class="page-sub">Track and manage all your orders</div>

  <!-- STATS -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-num"><?= $total_orders ?></div>
      <div class="stat-label">Total Orders</div>
    </div>
    <div class="stat-card" style="border-top-color:#e8789a;">
      <div class="stat-num">Rs. <?= number_format($total_spent,0) ?></div>
      <div class="stat-label">Total Spent</div>
    </div>
    <div class="stat-card" style="border-top-color:#66bb6a;">
      <div class="stat-num">⭐ <?= $loyalty ?></div>
      <div class="stat-label">Loyalty Points</div>
    </div>
  </div>

  <!-- LOYALTY CARD -->
  <?php if($loyalty>0): ?>
  <div class="loyalty-card">
    <div>
      <div style="font-size:12px;color:var(--gold);font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:4px;">Loyalty Balance</div>
      <div class="loyalty-pts">⭐ <?= $loyalty ?> Points</div>
      <div style="font-size:13px;color:var(--muted);margin-top:4px;">Worth Rs. <?= floor($loyalty/10) ?> — Redeem at checkout!</div>
    </div>
    <a href="home.php" class="btn-order-now">
      <i class="bi bi-bag-heart-fill"></i> Shop Now
    </a>
  </div>
  <?php endif; ?>

  <!-- ORDERS -->
  <?php if(mysqli_num_rows($orders)===0): ?>
  <div class="empty-state">
    <i class="bi bi-bag-x"></i>
    <p style="font-size:22px;color:var(--muted);margin-bottom:8px;">No orders yet!</p>
    <p style="font-size:14px;color:var(--muted);margin-bottom:28px;">Place your first order today and earn loyalty points!</p>
    <a href="home.php" class="btn-order-now">
      <i class="bi bi-bag-heart-fill"></i> Order Now
    </a>
  </div>

  <?php else: while($o=mysqli_fetch_assoc($orders)):
    $curr_idx=array_search($o['status'],$statuses);
  ?>
  <div class="order-card">
    <div class="order-header">
      <div>
        <div class="order-id">#<?= $o['order_id'] ?></div>
        <div class="order-date"><?= date('d M Y, h:i A',strtotime($o['created_at'])) ?></div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="font-size:11px;padding:4px 12px;border-radius:99px;font-weight:700;background:rgba(201,151,58,0.1);color:var(--gold);">
          <?= ucfirst($o['order_type']) ?>
        </span>
        <span style="font-size:11px;padding:4px 12px;border-radius:99px;font-weight:700;background:<?= $status_bg[$o['status']] ?>;color:<?= $status_colors[$o['status']] ?>;">
          <?= ucfirst($o['status']) ?>
        </span>
      </div>
    </div>

    <!-- STEPPER -->
    <div class="stepper">
      <?php
      $step_icons=['bi-clock','bi-check-circle','bi-fire','bi-box','bi-check-all'];
      foreach($statuses as $idx=>$st):
        $state=$idx<$curr_idx?'done':($idx===$curr_idx?'active':'todo');
      ?>
      <div class="step">
        <div class="step-dot <?= $state ?>">
          <?php if($state==='done'): ?>
            <i class="bi bi-check"></i>
          <?php else: ?>
            <i class="bi <?= $step_icons[$idx] ?>"></i>
          <?php endif; ?>
        </div>
        <span class="step-label <?= $state ?>"><?= ucfirst($st) ?></span>
      </div>
      <?php if($idx<count($statuses)-1): ?>
      <div class="step-line <?= $idx<$curr_idx?'done':'' ?>"></div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <div class="order-footer">
      <div>
        <div class="order-amount">Rs. <?= number_format($o['total_amount'],2) ?></div>
        <div style="font-size:12px;color:var(--muted);margin-top:2px;">
          <?= ucfirst($o['payment_method']) ?> ·
          <?= $o['payment_status']==='paid'
            ? '<span style="color:#66bb6a;">✅ Paid</span>'
            : '<span style="color:#e65c00;">⏳ Unpaid</span>' ?>
        </div>
      </div>
      <?php if($o['bill_number']): ?>
      <a href="bill_view.php?id=<?= $o['order_id'] ?>" class="btn-view-bill">
        <i class="bi bi-receipt"></i> View Bill
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endwhile; endif; ?>
</div>
</body>
</html>
