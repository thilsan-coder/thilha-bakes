<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to   = isset($_GET['to'])   ? $_GET['to']   : date('Y-m-d');

$total_revenue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(b.total),0) as amt FROM bills b
     JOIN orders o ON b.order_id=o.order_id
     WHERE DATE(o.created_at) BETWEEN '$from' AND '$to'"))['amt'];
$total_orders  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM orders
     WHERE DATE(created_at) BETWEEN '$from' AND '$to'"))['cnt'];
$total_bills   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM bills b
     JOIN orders o ON b.order_id=o.order_id
     WHERE DATE(o.created_at) BETWEEN '$from' AND '$to'"))['cnt'];
$avg_order     = $total_orders > 0
    ? mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(AVG(b.total),0) as avg FROM bills b
         JOIN orders o ON b.order_id=o.order_id
         WHERE DATE(o.created_at) BETWEEN '$from' AND '$to'"))['avg']
    : 0;

// daily sales
$daily = mysqli_query($conn,
    "SELECT DATE(o.created_at) as day,
            COUNT(o.order_id) as orders,
            COALESCE(SUM(b.total),0) as revenue
     FROM orders o LEFT JOIN bills b ON o.order_id=b.order_id
     WHERE DATE(o.created_at) BETWEEN '$from' AND '$to'
     GROUP BY DATE(o.created_at) ORDER BY day ASC");
$daily_labels = []; $daily_revenue = []; $daily_orders = [];
while ($r = mysqli_fetch_assoc($daily)) {
    $daily_labels[]  = date('d M', strtotime($r['day']));
    $daily_revenue[] = (float)$r['revenue'];
    $daily_orders[]  = (int)$r['orders'];
}

// order type
$by_type = mysqli_query($conn,
    "SELECT order_type, COUNT(*) as cnt FROM orders
     WHERE DATE(created_at) BETWEEN '$from' AND '$to'
     GROUP BY order_type");
$type_labels = []; $type_data = [];
while ($r = mysqli_fetch_assoc($by_type)) {
    $type_labels[] = ucfirst($r['order_type']);
    $type_data[]   = (int)$r['cnt'];
}

// payment
$by_payment = mysqli_query($conn,
    "SELECT payment_method, COUNT(*) as cnt, COALESCE(SUM(b.total),0) as revenue
     FROM orders o LEFT JOIN bills b ON o.order_id=b.order_id
     WHERE DATE(o.created_at) BETWEEN '$from' AND '$to'
     GROUP BY payment_method");

// best sellers
$bestsellers = mysqli_query($conn,
    "SELECT p.name, p.category,
            SUM(oi.quantity) as total_qty,
            SUM(oi.quantity * oi.unit_price) as total_revenue
     FROM order_items oi
     JOIN products p ON oi.product_id=p.product_id
     JOIN orders o ON oi.order_id=o.order_id
     WHERE DATE(o.created_at) BETWEEN '$from' AND '$to'
     GROUP BY oi.product_id ORDER BY total_qty DESC LIMIT 8");

// monthly comparison
$months = mysqli_query($conn,
    "SELECT DATE_FORMAT(o.created_at, '%b %Y') as month_name,
            DATE_FORMAT(o.created_at, '%Y-%m') as month_key,
            COUNT(o.order_id) as total_orders,
            COALESCE(SUM(b.total),0) as revenue
     FROM orders o LEFT JOIN bills b ON o.order_id=b.order_id
     GROUP BY month_key ORDER BY month_key DESC LIMIT 6");
$month_rows = [];
while ($r = mysqli_fetch_assoc($months)) $month_rows[] = $r;
$month_rows    = array_reverse($month_rows);
$m_labels      = array_column($month_rows, 'month_name');
$m_revenue     = array_column($month_rows, 'revenue');
$m_orders      = array_column($month_rows, 'total_orders');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reports — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*{box-sizing:border-box;}
body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}
.page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}
.page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}
.filter-card{background:#fff;border:1px solid #e8d8c0;border-radius:12px;padding:16px 20px;margin-bottom:24px;}
.form-label{font-size:12px;color:#8a6a4a;font-weight:600;}
.form-control,.form-select{border:1px solid #e8d8c0;border-radius:8px;font-size:13px;color:#2a1a10;padding:8px 12px;}
.form-control:focus,.form-select:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);}
.btn-gold{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:600;padding:8px 20px;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;}
.btn-gold:hover{background:#a07828;color:#fff;}
.btn-outline{background:#fff;border:1px solid #c9973a;border-radius:8px;color:#c9973a;font-weight:600;padding:8px 16px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.btn-outline:hover{background:#fdf5ec;}
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
.stat-card{background:#fff;border:1px solid #e8d8c0;border-top:4px solid #c9973a;border-radius:12px;padding:18px 20px;}
.stat-num{font-size:24px;font-weight:700;color:#1a0a0f;margin-bottom:4px;}
.stat-label{font-size:12px;color:#8a6a4a;font-weight:500;}
.card-box{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;margin-bottom:24px;}
.card-title{font-size:15px;font-weight:600;color:#1a0a0f;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid #f0e4d0;display:flex;align-items:center;justify-content:space-between;}
.table{color:#2a1a10;margin-bottom:0;}
.table thead th{background:#fdf5ec;color:#8a5a20;font-size:12px;font-weight:600;border-color:#e8d8c0;padding:9px 14px;}
.table tbody td{border-color:#f5ede0;font-size:13px;padding:10px 14px;vertical-align:middle;}
.table tbody tr:hover{background:#fdf5ec;}
.cat-badge{font-size:11px;padding:3px 9px;border-radius:99px;font-weight:500;}
.cat-cakes{background:#fce4ec;color:#880e4f;}
.cat-brownies{background:#fff8e1;color:#f57f17;}
.cat-cupcakes{background:#f3e5f5;color:#6a1b9a;}
.cat-pastries{background:#e8f5e9;color:#1b5e20;}
.cat-breads{background:#e3f2fd;color:#0d47a1;}
.pay-badge{font-size:11px;padding:3px 9px;border-radius:99px;font-weight:500;}
.pay-cash{background:#fff8e1;color:#f57f17;}
.pay-card{background:#e3f2fd;color:#1565c0;}
.pay-online{background:#e8f5e9;color:#2e7d32;}
@media print{
  body{background:#fff;}
  .sidebar,.filter-card,.btn-gold,.btn-outline{display:none!important;}
  .main{margin-left:0;padding:10px;}
}
</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main">
  <div class="page-title"><i class="bi bi-bar-chart-fill me-2" style="color:#c9973a;"></i>Reports</div>
  <div class="page-sub">Sales analytics and business reports — <?= date('l, d F Y') ?></div>

  <!-- DATE FILTER -->
  <div class="filter-card">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label">From Date</label>
        <input type="date" name="from" class="form-control" value="<?= $from ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">To Date</label>
        <input type="date" name="to" class="form-control" value="<?= $to ?>">
      </div>
      <div class="col-md-6 d-flex gap-2 flex-wrap">
        <button type="submit" class="btn-gold"><i class="bi bi-funnel"></i> Filter</button>
        <a href="reports.php" class="btn-outline">This Month</a>
        <a href="reports.php?from=<?= date('Y-m-d',strtotime('-7 days')) ?>&to=<?= date('Y-m-d') ?>" class="btn-outline">Last 7 Days</a>
        <a href="reports.php?from=<?= date('Y-m-d',strtotime('-30 days')) ?>&to=<?= date('Y-m-d') ?>" class="btn-outline">Last 30 Days</a>
        <button type="button" onclick="window.print()" class="btn-outline"><i class="bi bi-printer"></i> Print</button>
      </div>
    </form>
  </div>

  <!-- SUMMARY -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-label">Total Revenue</div>
      <div class="stat-num">Rs. <?= number_format($total_revenue,0) ?></div>
    </div>
    <div class="stat-card" style="border-top-color:#e8789a;">
      <div class="stat-label">Total Orders</div>
      <div class="stat-num"><?= $total_orders ?></div>
    </div>
    <div class="stat-card" style="border-top-color:#66bb6a;">
      <div class="stat-label">Bills Generated</div>
      <div class="stat-num"><?= $total_bills ?></div>
    </div>
    <div class="stat-card" style="border-top-color:#42a5f5;">
      <div class="stat-label">Average Order</div>
      <div class="stat-num">Rs. <?= number_format($avg_order,0) ?></div>
    </div>
  </div>

  <!-- DAILY SALES CHART -->
  <div class="card-box">
    <div class="card-title">
      <span><i class="bi bi-graph-up-arrow me-2" style="color:#c9973a;"></i>Daily Sales</span>
      <span style="font-size:12px;color:#8a6a4a;"><?= date('d M Y',strtotime($from)) ?> — <?= date('d M Y',strtotime($to)) ?></span>
    </div>
    <canvas id="dailyChart" height="80"></canvas>
  </div>

  <!-- CHARTS ROW -->
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card-box" style="margin-bottom:0;">
        <div class="card-title"><span><i class="bi bi-pie-chart me-2" style="color:#c9973a;"></i>Order Types</span></div>
        <canvas id="typeChart" height="200"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card-box" style="margin-bottom:0;">
        <div class="card-title"><span><i class="bi bi-credit-card me-2" style="color:#c9973a;"></i>Payment Methods</span></div>
        <table class="table table-hover">
          <thead><tr><th>Method</th><th>Orders</th><th>Revenue</th></tr></thead>
          <tbody>
            <?php mysqli_data_seek($by_payment,0); while ($p = mysqli_fetch_assoc($by_payment)): ?>
            <tr>
              <td><span class="pay-badge pay-<?= $p['payment_method'] ?>"><?= ucfirst($p['payment_method']) ?></span></td>
              <td><?= $p['cnt'] ?> orders</td>
              <td><strong>Rs. <?= number_format($p['revenue'],2) ?></strong></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- MONTHLY COMPARISON -->
  <div class="card-box">
    <div class="card-title"><span><i class="bi bi-calendar-range me-2" style="color:#c9973a;"></i>Monthly Comparison</span></div>
    <canvas id="monthlyChart" height="80" style="margin-bottom:20px;"></canvas>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr><th>Month</th><th>Orders</th><th>Revenue</th><th>Avg Order</th><th>Performance</th></tr>
        </thead>
        <tbody>
          <?php
          $max_rev = !empty($month_rows) ? max(array_column($month_rows,'revenue')) : 1;
          if ($max_rev == 0) $max_rev = 1;
          foreach ($month_rows as $m):
            $avg = $m['total_orders'] > 0 ? $m['revenue']/$m['total_orders'] : 0;
            $pct = round(($m['revenue']/$max_rev)*100);
          ?>
          <tr>
            <td><strong><?= $m['month_name'] ?></strong></td>
            <td><?= $m['total_orders'] ?> orders</td>
            <td><strong style="color:#2e7d32;">Rs. <?= number_format($m['revenue'],2) ?></strong></td>
            <td>Rs. <?= number_format($avg,2) ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:120px;height:6px;background:#f0e4d0;border-radius:3px;overflow:hidden;">
                  <div style="width:<?= $pct ?>%;height:100%;background:#c9973a;border-radius:3px;"></div>
                </div>
                <span style="font-size:12px;color:#8a6a4a;"><?= $pct ?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- BEST SELLERS -->
  <div class="card-box">
    <div class="card-title"><span><i class="bi bi-trophy me-2" style="color:#c9973a;"></i>Best Selling Products</span></div>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr><th>#</th><th>Product</th><th>Category</th><th>Qty Sold</th><th>Revenue</th><th>Performance</th></tr>
        </thead>
        <tbody>
          <?php
          $rank = 1;
          $rows2 = [];
          while ($r = mysqli_fetch_assoc($bestsellers)) $rows2[] = $r;
          $max_qty = !empty($rows2) ? $rows2[0]['total_qty'] : 1;
          if ($max_qty == 0) $max_qty = 1;
          foreach ($rows2 as $r):
            $pct = round(($r['total_qty']/$max_qty)*100);
          ?>
          <tr>
            <td>
              <?php if ($rank==1): ?>🥇
              <?php elseif ($rank==2): ?>🥈
              <?php elseif ($rank==3): ?>🥉
              <?php else: ?><span style="color:#8a6a4a;font-weight:600;">#<?= $rank ?></span>
              <?php endif; ?>
            </td>
            <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
            <td><span class="cat-badge cat-<?= $r['category'] ?>"><?= ucfirst($r['category']) ?></span></td>
            <td><strong><?= $r['total_qty'] ?></strong> pcs</td>
            <td><strong style="color:#2e7d32;">Rs. <?= number_format($r['total_revenue'],2) ?></strong></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:100px;height:6px;background:#f0e4d0;border-radius:3px;overflow:hidden;">
                  <div style="width:<?= $pct ?>%;height:100%;background:#c9973a;border-radius:3px;"></div>
                </div>
                <span style="font-size:12px;color:#8a6a4a;"><?= $pct ?>%</span>
              </div>
            </td>
          </tr>
          <?php $rank++; endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('dailyChart').getContext('2d'),{
  type:'bar',
  data:{
    labels:<?= json_encode($daily_labels) ?>,
    datasets:[
      {label:'Revenue (Rs.)',data:<?= json_encode(array_map('floatval',$daily_revenue)) ?>,backgroundColor:'#c9973a',borderRadius:6,yAxisID:'y'},
      {label:'Orders',data:<?= json_encode(array_map('intval',$daily_orders)) ?>,type:'line',borderColor:'#e8789a',backgroundColor:'rgba(232,120,154,0.1)',borderWidth:2,pointBackgroundColor:'#e8789a',tension:0.4,yAxisID:'y1'}
    ]
  },
  options:{
    plugins:{legend:{labels:{color:'#2a1a10',font:{size:12}}}},
    scales:{
      x:{ticks:{color:'#8a6a4a'},grid:{color:'#f0e4d0'}},
      y:{ticks:{color:'#8a6a4a'},grid:{color:'#f0e4d0'},position:'left'},
      y1:{ticks:{color:'#e8789a'},grid:{drawOnChartArea:false},position:'right'}
    }
  }
});

new Chart(document.getElementById('typeChart').getContext('2d'),{
  type:'doughnut',
  data:{labels:<?= json_encode($type_labels) ?>,datasets:[{data:<?= json_encode($type_data) ?>,backgroundColor:['#c9973a','#e8789a','#5090d0','#50c090'],borderWidth:2,borderColor:'#fff'}]},
  options:{plugins:{legend:{position:'bottom',labels:{color:'#2a1a10',font:{size:12},padding:12}}}}
});

new Chart(document.getElementById('monthlyChart').getContext('2d'),{
  type:'line',
  data:{
    labels:<?= json_encode($m_labels) ?>,
    datasets:[
      {label:'Revenue (Rs.)',data:<?= json_encode(array_map('floatval',$m_revenue)) ?>,borderColor:'#c9973a',backgroundColor:'rgba(201,151,58,0.1)',borderWidth:2.5,pointBackgroundColor:'#c9973a',pointRadius:5,tension:0.4,fill:true,yAxisID:'y'},
      {label:'Orders',data:<?= json_encode(array_map('intval',$m_orders)) ?>,borderColor:'#e8789a',backgroundColor:'rgba(232,120,154,0.05)',borderWidth:2,pointBackgroundColor:'#e8789a',pointRadius:4,tension:0.4,fill:false,yAxisID:'y1'}
    ]
  },
  options:{
    plugins:{legend:{labels:{color:'#2a1a10',font:{size:12}}}},
    scales:{
      x:{ticks:{color:'#8a6a4a'},grid:{color:'#f0e4d0'}},
      y:{ticks:{color:'#8a6a4a'},grid:{color:'#f0e4d0'},position:'left'},
      y1:{ticks:{color:'#e8789a'},grid:{drawOnChartArea:false},position:'right'}
    }
  }
});
</script>
</body>
</html>
