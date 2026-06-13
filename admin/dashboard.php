<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$total_orders   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as cnt FROM orders"))['cnt'];
$today_orders   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at)=CURDATE()"))['cnt'];
$total_revenue  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(total),0) as amt FROM bills"))['amt'];
$today_revenue  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(b.total),0) as amt FROM bills b JOIN orders o ON b.order_id=o.order_id WHERE DATE(o.created_at)=CURDATE()"))['amt'];
$total_customers= mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as cnt FROM users WHERE role='customer'"))['cnt'];
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as cnt FROM orders WHERE status='pending'"))['cnt'];
$low_stock      = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as cnt FROM inventory WHERE quantity<=min_level"))['cnt'];

$today_list       = mysqli_query($conn,"SELECT o.*,u.name as customer FROM orders o LEFT JOIN users u ON o.user_id=u.user_id WHERE DATE(o.created_at)=CURDATE() ORDER BY o.created_at DESC");
$today_list_count = mysqli_num_rows($today_list);

$recent = mysqli_query($conn,"SELECT o.*,u.name as customer FROM orders o LEFT JOIN users u ON o.user_id=u.user_id ORDER BY o.created_at DESC LIMIT 8");

$weekly=mysqli_query($conn,"SELECT DATE(o.created_at) as day,COALESCE(SUM(b.total),0) as total FROM orders o LEFT JOIN bills b ON o.order_id=b.order_id WHERE o.created_at>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(o.created_at) ORDER BY day ASC");
$chart_labels=[]; $chart_data=[];
while($r=mysqli_fetch_assoc($weekly)){$chart_labels[]=date('D',strtotime($r['day']));$chart_data[]=(float)$r['total'];}

$bestsellers=mysqli_query($conn,"SELECT p.name,SUM(oi.quantity) as total_qty FROM order_items oi JOIN products p ON oi.product_id=p.product_id GROUP BY oi.product_id ORDER BY total_qty DESC LIMIT 5");
$bs_labels=[]; $bs_data=[];
while($r=mysqli_fetch_assoc($bestsellers)){$bs_labels[]=$r['name'];$bs_data[]=(int)$r['total_qty'];}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*{box-sizing:border-box;}
body{background:#f0ebe4;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}

/* PAGE HEADER */
.page-header{
  background:linear-gradient(135deg,#1a0a0f 0%,#2a1018 50%,#1a0a0f 100%);
  margin:-28px -28px 28px -28px;
  padding:28px 28px 24px;
  border-bottom:3px solid #c9973a;
  position:relative;overflow:hidden;
}
.page-header::before{
  content:'';position:absolute;inset:0;
  background-image:radial-gradient(circle,rgba(201,151,58,0.06) 1px,transparent 1px);
  background-size:30px 30px;
}
.page-header::after{
  content:'';position:absolute;
  width:300px;height:300px;border-radius:50%;
  background:radial-gradient(circle,rgba(201,151,58,0.08),transparent 70%);
  top:-80px;right:-60px;
}
.page-title{
  font-size:22px;font-weight:700;color:#e8c870;
  position:relative;z-index:2;margin-bottom:3px;
}
.page-sub{
  font-size:13px;color:#8a6a4a;
  position:relative;z-index:2;
}

/* STAT CARDS */
.stat-card{
  background:#fff;
  border:1px solid #e4d8c8;
  border-radius:16px;
  padding:20px;
  display:flex;align-items:center;gap:16px;
  border-top:4px solid #c9973a;
  transition:all .2s;
  box-shadow:0 2px 8px rgba(0,0,0,0.04);
}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,0.08);}
.stat-icon{
  width:54px;height:54px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  font-size:24px;flex-shrink:0;
}
.stat-num{font-size:26px;font-weight:700;color:#1a0a0f;line-height:1;}
.stat-label{font-size:12px;color:#8a6a4a;margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
.stat-sub{font-size:11px;color:#c9973a;margin-top:4px;}

/* CHART CARDS */
.chart-card{
  background:#fff;border:1px solid #e4d8c8;
  border-radius:16px;padding:20px;
  box-shadow:0 2px 8px rgba(0,0,0,0.04);
}
.chart-title{
  font-size:14px;font-weight:700;color:#1a0a0f;
  margin-bottom:16px;padding-bottom:10px;
  border-bottom:1px solid #f0e4d0;
  display:flex;align-items:center;justify-content:space-between;
}

/* ORDERS CARD */
.orders-card{
  background:#fff;border:1px solid #e4d8c8;
  border-radius:16px;padding:20px;margin-top:22px;
  box-shadow:0 2px 8px rgba(0,0,0,0.04);
}

/* TABLE */
.table{color:#2a1a10;margin-bottom:0;}
.table thead th{
  background:#fdf5ec;color:#8a5a20;
  font-size:11px;font-weight:700;
  border-color:#e4d8c8;padding:10px 14px;
  letter-spacing:.5px;text-transform:uppercase;
}
.table tbody td{
  border-color:#f5ede0;font-size:13px;
  padding:12px 14px;vertical-align:middle;
}
.table tbody tr{transition:background .15s;}
.table tbody tr:hover{background:#fdf5ec;}

/* STATUS BADGES */
.status-badge{font-size:11px;padding:4px 12px;border-radius:99px;font-weight:700;}
.badge-pending{background:#fff3e0;color:#e65c00;border:1px solid #ffcc80;}
.badge-confirmed{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;}
.badge-baking{background:#f3e5f5;color:#6a1b9a;border:1px solid #ce93d8;}
.badge-ready{background:#e3f2fd;color:#1565c0;border:1px solid #90caf9;}
.badge-delivered{background:#e8f5e9;color:#1b5e20;border:1px solid #81c784;}
.badge-cancelled{background:#ffebee;color:#c62828;border:1px solid #ffcdd2;}

/* VIEW BUTTON */
.btn-view-action{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 16px;
  background:linear-gradient(135deg,#c9973a,#a07828);
  color:#fff;font-size:12px;font-weight:700;
  border-radius:99px;text-decoration:none;
  transition:all .2s;
  box-shadow:0 2px 8px rgba(201,151,58,0.3);
}
.btn-view-action:hover{
  background:linear-gradient(135deg,#a07828,#c9973a);
  color:#fff;transform:translateY(-1px);
  box-shadow:0 4px 14px rgba(201,151,58,0.45);
}

/* ALERT */
.low-stock-alert{
  background:linear-gradient(135deg,#fff8e8,#fdf5ec);
  border:1px solid #f0d080;border-left:4px solid #c9973a;
  border-radius:12px;padding:14px 20px;
  margin-bottom:20px;
  display:flex;align-items:center;justify-content:space-between;
  box-shadow:0 2px 8px rgba(201,151,58,0.1);
}
</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="page-title">
      Good <?= (date('H')<12)?'Morning ☀️':((date('H')<17)?'Afternoon 🌤️':'Evening 🌙') ?>,
      <?= htmlspecialchars($_SESSION['name']) ?>!
    </div>
    <div class="page-sub">
      <i class="bi bi-calendar3 me-1"></i><?= date('l, d F Y') ?>
      &nbsp;·&nbsp; Admin Dashboard
    </div>
  </div>

  <!-- LOW STOCK ALERT -->
  <?php if ($low_stock > 0): ?>
  <div class="low-stock-alert">
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="width:38px;height:38px;background:#f5c842;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;">📦</div>
      <div>
        <div style="font-size:14px;font-weight:700;color:#7a5000;"><?= $low_stock ?> item(s) running low on stock!</div>
        <div style="font-size:12px;color:#b08020;">Check inventory and reorder soon to avoid disruptions</div>
      </div>
    </div>
    <a href="inventory.php" style="background:#c9973a;color:#fff;text-decoration:none;font-size:13px;font-weight:700;padding:8px 18px;border-radius:8px;white-space:nowrap;display:flex;align-items:center;gap:6px;">
      <i class="bi bi-eye"></i> Check Now
    </a>
  </div>
  <?php endif; ?>

  <!-- STAT CARDS -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#fff8e1,#ffecc0);">💰</div>
        <div>
          <div class="stat-num">Rs. <?= number_format($today_revenue,0) ?></div>
          <div class="stat-label">Today's Revenue</div>
          <div class="stat-sub">Total: Rs. <?= number_format($total_revenue,0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="stat-card" style="border-top-color:#e8789a;">
        <div class="stat-icon" style="background:linear-gradient(135deg,#fce4ec,#f8bbd0);">📦</div>
        <div>
          <div class="stat-num"><?= $today_orders ?></div>
          <div class="stat-label">Today's Orders</div>
          <div class="stat-sub">Total: <?= $total_orders ?> orders</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="stat-card" style="border-top-color:#66bb6a;">
        <div class="stat-icon" style="background:linear-gradient(135deg,#e8f5e9,#c8e6c9);">👥</div>
        <div>
          <div class="stat-num"><?= $total_customers ?></div>
          <div class="stat-label">Total Customers</div>
          <div class="stat-sub">Registered users</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="stat-card" style="border-top-color:#ef5350;">
        <div class="stat-icon" style="background:linear-gradient(135deg,#ffebee,#ffcdd2);">⏳</div>
        <div>
          <div class="stat-num" style="color:#ef5350;"><?= $pending_orders ?></div>
          <div class="stat-label">Pending Orders</div>
          <div class="stat-sub"><a href="orders.php?status=pending" style="color:#c9973a;text-decoration:none;font-weight:700;">View all →</a></div>
        </div>
      </div>
    </div>
  </div>

  <!-- CHARTS -->
  <div class="row g-3 mb-3">
    <div class="col-md-8">
      <div class="chart-card">
        <div class="chart-title">
          <span><i class="bi bi-graph-up-arrow me-2" style="color:#c9973a;"></i>Weekly Sales (Rs.)</span>
        </div>
        <canvas id="salesChart" height="95"></canvas>
      </div>
    </div>
    <div class="col-md-4">
      <div class="chart-card">
        <div class="chart-title">
          <span><i class="bi bi-star-fill me-2" style="color:#c9973a;"></i>Best Sellers</span>
        </div>
        <canvas id="bsChart" height="190"></canvas>
      </div>
    </div>
  </div>

  <!-- TODAY'S ORDERS -->
  <?php if ($today_list_count > 0): ?>
  <div class="orders-card" style="margin-top:0;margin-bottom:20px;">
    <div class="chart-title">
      <span><i class="bi bi-calendar-check me-2" style="color:#c9973a;"></i>Today's Orders</span>
      <span style="font-size:12px;background:#e8f5e9;color:#1b5e20;padding:3px 12px;border-radius:99px;font-weight:700;">
        <?= $today_list_count ?> today
      </span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>Order #</th><th>Customer</th><th>Type</th><th>Amount</th><th>Status</th><th>Time</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php while($t=mysqli_fetch_assoc($today_list)): ?>
          <tr>
            <td><strong>#<?= $t['order_id'] ?></strong></td>
            <td><?= htmlspecialchars($t['customer']??'Walk-in') ?></td>
            <td><span style="font-size:12px;color:#8a6a4a;font-weight:600;"><?= ucfirst($t['order_type']) ?></span></td>
            <td><strong>Rs. <?= number_format($t['total_amount'],2) ?></strong></td>
            <td><span class="status-badge badge-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
            <td style="font-size:12px;color:#8a6a4a;"><?= date('h:i A',strtotime($t['created_at'])) ?></td>
            <td><a href="order_view.php?id=<?= $t['order_id'] ?>" class="btn-view-action"><i class="bi bi-eye"></i> View</a></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- RECENT ORDERS -->
  <div class="orders-card">
    <div class="chart-title">
      <span><i class="bi bi-clock-history me-2" style="color:#c9973a;"></i>Recent Orders</span>
      <a href="orders.php" style="font-size:13px;color:#c9973a;font-weight:700;text-decoration:none;">View all →</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>Order #</th><th>Customer</th><th>Type</th><th>Amount</th><th>Payment</th><th>Status</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php while($o=mysqli_fetch_assoc($recent)): ?>
          <tr>
            <td><strong>#<?= $o['order_id'] ?></strong></td>
            <td><?= htmlspecialchars($o['customer']??'Walk-in') ?></td>
            <td><span style="font-size:12px;color:#8a6a4a;font-weight:600;"><?= ucfirst($o['order_type']) ?></span></td>
            <td><strong>Rs. <?= number_format($o['total_amount'],2) ?></strong></td>
            <td><span style="font-size:12px;color:#5a8a5a;font-weight:600;"><?= ucfirst($o['payment_method']) ?></span></td>
            <td><span class="status-badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
            <td style="font-size:12px;color:#8a6a4a;"><?= date('d M, h:i A',strtotime($o['created_at'])) ?></td>
            <td><a href="order_view.php?id=<?= $o['order_id'] ?>" class="btn-view-action"><i class="bi bi-eye"></i> View</a></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('salesChart').getContext('2d'),{
  type:'bar',
  data:{labels:<?= json_encode($chart_labels) ?>,datasets:[{label:'Sales (Rs.)',data:<?= json_encode($chart_data) ?>,backgroundColor:'rgba(201,151,58,0.8)',borderColor:'#c9973a',borderWidth:1,borderRadius:8,borderSkipped:false}]},
  options:{plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#8a6a4a'},grid:{color:'rgba(0,0,0,0.04)'}},y:{ticks:{color:'#8a6a4a'},grid:{color:'rgba(0,0,0,0.04)'}}}}
});
new Chart(document.getElementById('bsChart').getContext('2d'),{
  type:'doughnut',
  data:{labels:<?= json_encode($bs_labels) ?>,datasets:[{data:<?= json_encode($bs_data) ?>,backgroundColor:['#c9973a','#e8789a','#5090d0','#50c090','#c070d0'],borderWidth:3,borderColor:'#fff'}]},
  options:{plugins:{legend:{position:'bottom',labels:{color:'#2a1a10',font:{size:11},padding:8}}}}
});
</script>
</body>
</html>
