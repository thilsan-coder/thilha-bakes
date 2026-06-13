<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isStaff()) redirect('../login.php');

$uid       = (int)$_SESSION['user_id'];
$staff_name = $_SESSION['name'];

// Update order status
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status   = e($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE orders SET status='$status' WHERE order_id=$order_id");
    redirect('dashboard.php?updated=1');
}

// Today's stats
$today_orders  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE DATE(created_at)=CURDATE()"))['c'];
$today_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(b.total),0) as amt FROM bills b JOIN orders o ON b.order_id=o.order_id WHERE DATE(o.created_at)=CURDATE()"))['amt'];
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='pending'"))['c'];
$ready_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='ready'"))['c'];
$low_stock     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM inventory WHERE quantity<=min_level"))['c'];

// Today's orders
$today_list = mysqli_query($conn,
    "SELECT o.*, u.name as customer, u.phone
     FROM orders o
     LEFT JOIN users u ON o.user_id=u.user_id
     WHERE DATE(o.created_at)=CURDATE()
     ORDER BY FIELD(o.status,'pending','confirmed','baking','ready','delivered'), o.created_at DESC");

// Production plans today
$production = mysqli_query($conn,
    "SELECT pp.*, p.name as product_name, p.category,
            u.name as assigned_name
     FROM production_plans pp
     LEFT JOIN products p ON pp.product_id=p.product_id
     LEFT JOIN users u ON pp.assigned_to=u.user_id
     WHERE pp.plan_date=CURDATE()
     ORDER BY pp.status");

// Low stock items
$low_items = mysqli_query($conn,
    "SELECT * FROM inventory WHERE quantity<=min_level ORDER BY quantity ASC LIMIT 5");

$icons = ['cakes'=>'🎂','brownies'=>'🍫','cupcakes'=>'🧁','pastries'=>'🥐','breads'=>'🍞'];
$status_colors = [
    'pending'  =>'#e65c00','confirmed'=>'#1565c0',
    'baking'   =>'#6a1b9a','ready'    =>'#2e7d32',
    'delivered'=>'#1b5e20'
];
$status_bg = [
    'pending'  =>'rgba(230,92,0,0.1)','confirmed'=>'rgba(21,101,192,0.1)',
    'baking'   =>'rgba(106,27,154,0.1)','ready'   =>'rgba(46,125,50,0.1)',
    'delivered'=>'rgba(27,94,32,0.1)'
];
$next_status = [
    'pending'=>'confirmed','confirmed'=>'baking',
    'baking' =>'ready','ready'=>'delivered'
];
$next_label = [
    'pending'  =>'Confirm','confirmed'=>'Start Baking',
    'baking'   =>'Mark Ready','ready'=>'Delivered'
];
$next_icon = [
    'pending'  =>'bi-check-circle','confirmed'=>'bi-fire',
    'baking'   =>'bi-box','ready'=>'bi-check-all'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Staff Dashboard — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{background:#f0ebe4;font-family:'Inter',sans-serif;color:#2a1a10;min-height:100vh;}

/* TOPBAR */
.staff-topbar{
  background:linear-gradient(135deg,#1a0a0f,#2a1018);
  border-bottom:3px solid #c9973a;
  padding:0 5%;height:64px;
  display:flex;align-items:center;
  justify-content:space-between;
  position:sticky;top:0;z-index:100;
  box-shadow:0 4px 20px rgba(0,0,0,0.3);
}
.topbar-brand{display:flex;align-items:center;gap:10px;}
.topbar-brand-icon{font-size:28px;}
.topbar-brand-text .name{font-family:'Playfair Display',serif;font-size:18px;font-weight:900;color:#e8c870;letter-spacing:3px;line-height:1;}
.topbar-brand-text .sub{font-size:10px;color:#c9973a;letter-spacing:3px;}
.topbar-right{display:flex;align-items:center;gap:16px;}
.staff-badge{
  background:rgba(201,151,58,0.15);
  border:1px solid rgba(201,151,58,0.25);
  border-radius:99px;padding:6px 16px;
  font-size:13px;font-weight:700;color:#e8c870;
  display:flex;align-items:center;gap:8px;
}
.topbar-nav{display:flex;align-items:center;gap:4px;}
.topbar-nav a{
  color:rgba(201,176,144,0.7);font-size:13px;font-weight:600;
  text-decoration:none;padding:7px 14px;border-radius:8px;
  display:flex;align-items:center;gap:6px;transition:all .2s;
}
.topbar-nav a:hover,.topbar-nav a.active{color:#e8c870;background:rgba(201,151,58,0.12);}
.btn-logout{
  background:rgba(239,83,80,0.1);border:1px solid rgba(239,83,80,0.2);
  color:#ef9090;border-radius:8px;padding:7px 14px;
  font-size:13px;font-weight:600;text-decoration:none;
  display:flex;align-items:center;gap:6px;transition:all .2s;
}
.btn-logout:hover{background:#ef5350;color:#fff;}

/* PAGE */
.page-wrap{padding:28px 5%;}
.page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:3px;}
.page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}

/* STAT CARDS */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:24px;}
.stat-card{
  background:#fff;border:1px solid #e4d8c8;
  border-top:4px solid #c9973a;border-radius:14px;
  padding:18px 20px;display:flex;align-items:center;gap:14px;
  box-shadow:0 2px 8px rgba(0,0,0,0.04);transition:all .2s;
}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,0.08);}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.stat-num{font-size:24px;font-weight:700;color:#1a0a0f;line-height:1;}
.stat-label{font-size:11px;color:#8a6a4a;margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}

/* SECTION CARD */
.section-card{background:#fff;border:1px solid #e4d8c8;border-radius:16px;padding:22px;margin-bottom:22px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.section-title{font-size:16px;font-weight:700;color:#1a0a0f;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f0e4d0;display:flex;align-items:center;justify-content:space-between;gap:12px;}
.section-title i{color:#c9973a;}

/* ORDER CARDS */
.order-card{
  background:#fdf5ec;border:1.5px solid #e8d8c0;
  border-radius:12px;padding:16px;margin-bottom:12px;
  transition:all .2s;
}
.order-card:hover{border-color:#c9973a;box-shadow:0 4px 12px rgba(201,151,58,0.1);}
.order-card.priority{border-color:#ef5350;background:#fff8f8;}
.order-meta{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;}
.order-id{font-size:16px;font-weight:700;color:#1a0a0f;}
.order-info{font-size:12px;color:#8a6a4a;margin-top:2px;}
.status-pill{font-size:11px;padding:4px 12px;border-radius:99px;font-weight:700;}
.order-details{
  display:flex;align-items:center;justify-content:space-between;
  flex-wrap:wrap;gap:10px;
}
.order-amount{font-size:18px;font-weight:700;color:#c9973a;}
.order-time{font-size:12px;color:#8a6a4a;}
.btn-status{
  display:inline-flex;align-items:center;gap:6px;
  padding:8px 18px;border-radius:99px;border:none;
  font-size:13px;font-weight:700;cursor:pointer;
  transition:all .2s;color:#fff;
  background:linear-gradient(135deg,#c9973a,#a07828);
}
.btn-status:hover{background:linear-gradient(135deg,#a07828,#c9973a);transform:translateY(-1px);box-shadow:0 4px 12px rgba(201,151,58,0.3);}
.btn-status.baking{background:linear-gradient(135deg,#7b1fa2,#6a1b9a);}
.btn-status.ready{background:linear-gradient(135deg,#1565c0,#0d47a1);}
.btn-status.delivered{background:linear-gradient(135deg,#2e7d32,#1b5e20);}
.btn-view-sm{
  display:inline-flex;align-items:center;gap:5px;
  padding:6px 14px;border-radius:99px;
  background:rgba(201,151,58,0.1);
  border:1px solid rgba(201,151,58,0.25);
  color:#c9973a;font-size:12px;font-weight:700;
  text-decoration:none;transition:all .2s;
}
.btn-view-sm:hover{background:#c9973a;color:#fff;}

/* PRODUCTION */
.prod-item{
  background:#fdf5ec;border:1.5px solid #e8d8c0;
  border-radius:10px;padding:14px;margin-bottom:10px;
  display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
}
.prod-icon{font-size:28px;flex-shrink:0;}
.prod-info{flex:1;}
.prod-name{font-size:14px;font-weight:700;color:#1a0a0f;}
.prod-meta{font-size:12px;color:#8a6a4a;margin-top:2px;}
.prod-status{font-size:11px;padding:4px 12px;border-radius:99px;font-weight:700;}
.ps-planned{background:#e3f2fd;color:#1565c0;}
.ps-in_progress{background:#f3e5f5;color:#6a1b9a;}
.ps-completed{background:#e8f5e9;color:#2e7d32;}
.ps-cancelled{background:#ffebee;color:#c62828;}

/* LOW STOCK */
.low-item{
  display:flex;align-items:center;justify-content:space-between;
  padding:10px 0;border-bottom:1px solid #f0e4d0;font-size:14px;
}
.low-item:last-child{border-bottom:none;}
.low-qty{font-weight:700;color:#ef5350;}

/* POS QUICK BUTTON */
.pos-btn{
  display:flex;align-items:center;justify-content:center;gap:12px;
  background:linear-gradient(135deg,#1a0a0f,#2a1018);
  color:#e8c870;border:none;border-radius:14px;
  padding:20px;font-size:18px;font-weight:700;
  cursor:pointer;width:100%;text-decoration:none;
  transition:all .2s;letter-spacing:.5px;
  box-shadow:0 4px 16px rgba(0,0,0,0.2);
}
.pos-btn:hover{background:linear-gradient(135deg,#c9973a,#a07828);color:#1a0a0f;transform:translateY(-2px);box-shadow:0 8px 24px rgba(201,151,58,0.3);}
.pos-btn i{font-size:28px;}

/* SUCCESS ALERT */
.alert-success{background:#e8f5e9;border:1px solid #a5d6a7;border-left:4px solid #2e7d32;border-radius:10px;padding:12px 16px;font-size:13px;color:#1b5e20;margin-bottom:20px;display:flex;align-items:center;gap:8px;}

/* EMPTY */
.empty-msg{text-align:center;padding:30px;color:#8a6a4a;font-size:14px;}
.empty-msg i{font-size:36px;color:#e8d8c0;display:block;margin-bottom:10px;}

@media(max-width:768px){
  .topbar-nav{display:none;}
  .staff-badge span{display:none;}
}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="staff-topbar">
  <div class="topbar-brand">
    <div class="topbar-brand-icon">🎂</div>
    <div class="topbar-brand-text">
      <div class="name">THILHA</div>
      <div class="sub">DIVINE BAKES</div>
    </div>
  </div>
  <div class="topbar-nav">
    <a href="dashboard.php" class="active"><i class="bi bi-grid-fill"></i> Dashboard</a>
    
  </div>
  <div class="topbar-right">
    <div class="staff-badge">
      <i class="bi bi-person-badge-fill"></i>
      <span><?= htmlspecialchars($staff_name) ?></span>
    </div>
    <a href="../logout.php" class="btn-logout">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</div>

<!-- PAGE -->
<div class="page-wrap">

  <?php if(isset($_GET['updated'])): ?>
  <div class="alert-success"><i class="bi bi-check-circle-fill"></i> Order status updated!</div>
  <?php endif; ?>

  <!-- GREETING -->
  <div class="page-title">
    Good <?= (date('H')<12)?'Morning ☀️':((date('H')<17)?'Afternoon 🌤️':'Evening 🌙') ?>,
    <?= htmlspecialchars($staff_name) ?>!
  </div>
  <div class="page-sub">
    <i class="bi bi-calendar3 me-1"></i><?= date('l, d F Y') ?>
    &nbsp;·&nbsp; Staff Dashboard
  </div>

  <!-- STAT CARDS -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:linear-gradient(135deg,#fff8e1,#ffecc0);">📦</div>
      <div>
        <div class="stat-num"><?= $today_orders ?></div>
        <div class="stat-label">Today's Orders</div>
      </div>
    </div>
    <div class="stat-card" style="border-top-color:#ef5350;">
      <div class="stat-icon" style="background:linear-gradient(135deg,#ffebee,#ffcdd2);">⏳</div>
      <div>
        <div class="stat-num" style="color:#ef5350;"><?= $pending_count ?></div>
        <div class="stat-label">Pending</div>
      </div>
    </div>
    <div class="stat-card" style="border-top-color:#2e7d32;">
      <div class="stat-icon" style="background:linear-gradient(135deg,#e8f5e9,#c8e6c9);">✅</div>
      <div>
        <div class="stat-num" style="color:#2e7d32;"><?= $ready_count ?></div>
        <div class="stat-label">Ready to Deliver</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:linear-gradient(135deg,#e3f2fd,#bbdefb);">💰</div>
      <div>
        <div class="stat-num">Rs. <?= number_format($today_revenue,0) ?></div>
        <div class="stat-label">Today's Sales</div>
      </div>
    </div>
  </div>

  <!-- LOW STOCK WARNING -->
  <?php if($low_stock>0): ?>
  <div style="background:#fff8e8;border:1px solid #f0d080;border-left:4px solid #c9973a;border-radius:12px;padding:14px 20px;margin-bottom:22px;display:flex;align-items:center;gap:12px;">
    <div style="font-size:22px;">⚠️</div>
    <div>
      <div style="font-size:14px;font-weight:700;color:#7a5000;"><?= $low_stock ?> ingredient(s) running low!</div>
      <div style="font-size:12px;color:#b08020;">Inform admin to reorder soon</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-8">

      <!-- TODAY'S ORDERS -->
      <div class="section-card">
        <div class="section-title">
          <span><i class="bi bi-bag-fill"></i> Today's Orders — <?= $today_orders ?> total</span>
          <?php if($pending_count>0): ?>
          <span style="background:#ffebee;color:#c62828;font-size:12px;padding:3px 12px;border-radius:99px;font-weight:700;">
            <?= $pending_count ?> pending!
          </span>
          <?php endif; ?>
        </div>

        <?php if(mysqli_num_rows($today_list)===0): ?>
        <div class="empty-msg">
          <i class="bi bi-bag-x"></i>
          No orders today yet!
        </div>
        <?php else: while($o=mysqli_fetch_assoc($today_list)):
          $is_priority = $o['status']==='pending';
        ?>
        <div class="order-card <?= $is_priority?'priority':'' ?>">
          <div class="order-meta">
            <div>
              <div class="order-id">
                <?php if($is_priority): ?>
                <i class="bi bi-exclamation-circle-fill" style="color:#ef5350;margin-right:4px;font-size:14px;"></i>
                <?php endif; ?>
                Order #<?= $o['order_id'] ?>
              </div>
              <div class="order-info">
                <?= htmlspecialchars($o['customer']??'Walk-in') ?>
                <?php if($o['phone']): ?> · <?= $o['phone'] ?><?php endif; ?>
                · <?= ucfirst($o['order_type']) ?>
              </div>
            </div>
            <span class="status-pill"
              style="background:<?= $status_bg[$o['status']] ?>;color:<?= $status_colors[$o['status']] ?>;">
              <?= ucfirst($o['status']) ?>
            </span>
          </div>
          <div class="order-details">
            <div>
              <div class="order-amount">Rs. <?= number_format($o['total_amount'],2) ?></div>
              <div class="order-time">
                <i class="bi bi-clock" style="margin-right:4px;"></i>
                <?= date('h:i A',strtotime($o['created_at'])) ?>
              </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <!-- STATUS UPDATE BUTTON -->
              <?php if(isset($next_status[$o['status']])): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="status" value="<?= $next_status[$o['status']] ?>">
                <button type="submit" class="btn-status <?= $o['status'] ?>">
                  <i class="bi <?= $next_icon[$o['status']] ?>"></i>
                  <?= $next_label[$o['status']] ?>
                </button>
              </form>
              <?php else: ?>
              <span style="font-size:12px;color:#2e7d32;font-weight:700;padding:8px 0;">
                <i class="bi bi-check-circle-fill me-1"></i>Completed!
              </span>
              <?php endif; ?>
              <a href="../admin/order_view.php?id=<?= $o['order_id'] ?>" class="btn-view-sm">
                <i class="bi bi-eye"></i> View
              </a>
            </div>
          </div>
        </div>
        <?php endwhile; endif; ?>
      </div>

      <!-- PRODUCTION PLAN -->
      <div class="section-card">
        <div class="section-title">
          <span><i class="bi bi-clipboard2-check"></i> Today's Production Plan</span>
          <span style="font-size:12px;color:#8a6a4a;"><?= date('d M Y') ?></span>
        </div>
        <?php if(mysqli_num_rows($production)===0): ?>
        <div class="empty-msg">
          <i class="bi bi-clipboard-x"></i>
          No production plan for today!
        </div>
        <?php else: while($p=mysqli_fetch_assoc($production)): ?>
        <div class="prod-item">
          <div class="prod-icon"><?= $icons[$p['category']]??'🍰' ?></div>
          <div class="prod-info">
            <div class="prod-name"><?= htmlspecialchars($p['product_name']??'Unknown') ?></div>
            <div class="prod-meta">
              Planned: <strong><?= $p['planned_qty'] ?></strong> pcs
              <?php if($p['actual_qty']>0): ?>
              &nbsp;·&nbsp; Done: <strong style="color:#2e7d32;"><?= $p['actual_qty'] ?></strong>
              <?php endif; ?>
              <?php if($p['assigned_name']): ?>
              &nbsp;·&nbsp; <i class="bi bi-person"></i> <?= htmlspecialchars($p['assigned_name']) ?>
              <?php endif; ?>
              <?php if($p['notes']): ?>
              <br><i class="bi bi-chat-text" style="color:#c9973a;"></i> <?= htmlspecialchars($p['notes']) ?>
              <?php endif; ?>
            </div>
          </div>
          <span class="prod-status ps-<?= $p['status'] ?>">
            <?= ucfirst(str_replace('_',' ',$p['status'])) ?>
          </span>
        </div>
        <?php endwhile; endif; ?>
      </div>

    </div>

    <div class="col-lg-4">



      <!-- LOW STOCK ITEMS -->
      <div class="section-card">
        <div class="section-title">
          <span><i class="bi bi-exclamation-triangle"></i> Low Stock Alert</span>
          <?php if($low_stock>0): ?>
          <span style="background:#ffebee;color:#c62828;font-size:11px;padding:3px 10px;border-radius:99px;font-weight:700;">
            <?= $low_stock ?> items
          </span>
          <?php endif; ?>
        </div>
        <?php if(mysqli_num_rows($low_items)===0): ?>
        <div class="empty-msg">
          <i class="bi bi-check-circle" style="color:#2e7d32;"></i>
          All stock levels OK!
        </div>
        <?php else: while($item=mysqli_fetch_assoc($low_items)): ?>
        <div class="low-item">
          <div>
            <div style="font-size:14px;font-weight:600;color:#1a0a0f;"><?= htmlspecialchars($item['ingredient_name']) ?></div>
            <div style="font-size:11px;color:#8a6a4a;">Min: <?= $item['min_level'] ?> <?= $item['unit'] ?></div>
          </div>
          <div class="low-qty"><?= $item['quantity'] ?> <?= $item['unit'] ?> left</div>
        </div>
        <?php endwhile; endif; ?>
        <div style="font-size:12px;color:#8a6a4a;margin-top:12px;padding-top:12px;border-top:1px solid #f0e4d0;">
          <i class="bi bi-info-circle me-1" style="color:#c9973a;"></i>
          Inform admin to reorder these items
        </div>
      </div>

      <!-- SHIFT SUMMARY -->
      <div class="section-card">
        <div class="section-title">
          <span><i class="bi bi-clock-history"></i> My Shift</span>
        </div>
        <div style="text-align:center;padding:10px 0;">
          <div style="font-size:40px;margin-bottom:8px;">⏰</div>
          <div style="font-size:28px;font-weight:700;color:#1a0a0f;" id="clock">--:--:--</div>
          <div style="font-size:13px;color:#8a6a4a;margin-top:4px;"><?= date('l, d F Y') ?></div>
          <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f0e4d0;">
            <div style="font-size:12px;color:#8a6a4a;margin-bottom:8px;">Today's Progress</div>
            <div style="display:flex;justify-content:space-around;">
              <div style="text-align:center;">
                <div style="font-size:20px;font-weight:700;color:#c9973a;"><?= $today_orders ?></div>
                <div style="font-size:11px;color:#8a6a4a;">Orders</div>
              </div>
              <div style="text-align:center;">
                <div style="font-size:20px;font-weight:700;color:#2e7d32;"><?= $ready_count ?></div>
                <div style="font-size:11px;color:#8a6a4a;">Ready</div>
              </div>
              <div style="text-align:center;">
                <div style="font-size:20px;font-weight:700;color:#1565c0;"><?= $today_orders - $pending_count ?></div>
                <div style="font-size:11px;color:#8a6a4a;">Done</div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
// Live clock
function updateClock(){
  var now=new Date();
  var h=String(now.getHours()).padStart(2,'0');
  var m=String(now.getMinutes()).padStart(2,'0');
  var s=String(now.getSeconds()).padStart(2,'0');
  document.getElementById('clock').textContent=h+':'+m+':'+s;
}
updateClock();
setInterval(updateClock,1000);

// Auto refresh every 60 seconds
setTimeout(function(){ location.reload(); }, 60000);
</script>
</body>
</html>
