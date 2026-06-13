<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

// Date ranges
$today      = date('Y-m-d');
$month_start= date('Y-m-01');
$year_start = date('Y-01-01');
$last_month_start = date('Y-m-01', strtotime('-1 month'));
$last_month_end   = date('Y-m-t', strtotime('-1 month'));

// ===== REVENUE STATS =====
$rev_today  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(b.total),0) as amt FROM bills b
     JOIN orders o ON b.order_id=o.order_id
     WHERE DATE(o.created_at)='$today'"))['amt'];

$rev_month  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(b.total),0) as amt FROM bills b
     JOIN orders o ON b.order_id=o.order_id
     WHERE DATE(o.created_at) BETWEEN '$month_start' AND '$today'"))['amt'];

$rev_year   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(b.total),0) as amt FROM bills b
     JOIN orders o ON b.order_id=o.order_id
     WHERE DATE(o.created_at) BETWEEN '$year_start' AND '$today'"))['amt'];

$rev_last_month = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(b.total),0) as amt FROM bills b
     JOIN orders o ON b.order_id=o.order_id
     WHERE DATE(o.created_at) BETWEEN '$last_month_start' AND '$last_month_end'"))['amt'];

// month growth %
$growth = $rev_last_month > 0
    ? round((($rev_month - $rev_last_month) / $rev_last_month) * 100, 1)
    : 100;

// ===== EXPENSE & PROFIT =====
$exp_month = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(amount),0) as amt FROM expenses
     WHERE expense_date BETWEEN '$month_start' AND '$today'"))['amt'];
$exp_year  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(amount),0) as amt FROM expenses
     WHERE expense_date BETWEEN '$year_start' AND '$today'"))['amt'];

$profit_month = (float)$rev_month - (float)$exp_month;
$profit_year  = (float)$rev_year  - (float)$exp_year;

// profit margin %
$margin_month = $rev_month > 0 ? round(($profit_month / $rev_month) * 100, 1) : 0;
$margin_year  = $rev_year  > 0 ? round(($profit_year  / $rev_year)  * 100, 1) : 0;

// ===== ORDER STATS =====
$orders_today = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM orders WHERE DATE(created_at)='$today'"))['c'];
$orders_month = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM orders
     WHERE DATE(created_at) BETWEEN '$month_start' AND '$today'"))['c'];
$avg_order_val= $orders_month > 0
    ? mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(AVG(b.total),0) as avg FROM bills b
         JOIN orders o ON b.order_id=o.order_id
         WHERE DATE(o.created_at) BETWEEN '$month_start' AND '$today'"))['avg']
    : 0;

// ===== CUSTOMER STATS =====
$total_customers = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM users WHERE role='customer'"))['c'];
$new_this_month  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM users WHERE role='customer'
     AND DATE(created_at) BETWEEN '$month_start' AND '$today'"))['c'];
$returning = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT user_id) as c FROM orders
     WHERE user_id IN (SELECT user_id FROM orders GROUP BY user_id HAVING COUNT(*)>1)
     AND DATE(created_at) BETWEEN '$month_start' AND '$today'"))['c'];

// ===== CHARTS DATA =====

// 12 months revenue + expense
$monthly_trend = mysqli_query($conn,
    "SELECT DATE_FORMAT(o.created_at,'%b %Y') as month,
            DATE_FORMAT(o.created_at,'%Y-%m') as month_key,
            COALESCE(SUM(b.total),0) as revenue,
            COUNT(o.order_id) as orders
     FROM orders o LEFT JOIN bills b ON o.order_id=b.order_id
     WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY month_key ORDER BY month_key ASC");
$trend_labels=[]; $trend_rev=[]; $trend_orders=[];
while($r=mysqli_fetch_assoc($monthly_trend)){
    $trend_labels[]=$r['month']; $trend_rev[]=(float)$r['revenue']; $trend_orders[]=(int)$r['orders'];
}

// monthly expenses
$monthly_exp = mysqli_query($conn,
    "SELECT DATE_FORMAT(expense_date,'%Y-%m') as month_key,
            COALESCE(SUM(amount),0) as expenses
     FROM expenses
     WHERE expense_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY month_key ORDER BY month_key ASC");
$exp_by_month = [];
while($r=mysqli_fetch_assoc($monthly_exp)) $exp_by_month[$r['month_key']]=(float)$r['expenses'];

// fill expenses for same months
$trend_exp = [];
foreach($trend_labels as $i => $label){
    $mk = date('Y-m', strtotime('01 '.$label));
    $trend_exp[] = $exp_by_month[$mk] ?? 0;
}

// hourly sales heatmap (avg by hour)
$hourly = mysqli_query($conn,
    "SELECT HOUR(created_at) as hr, COUNT(*) as cnt,
            COALESCE(SUM(total_amount),0) as rev
     FROM orders
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY hr ORDER BY hr ASC");
$hour_labels=[]; $hour_data=[];
for($h=0;$h<24;$h++){$hour_labels[]=$h.':00';$hour_data[]=0;}
while($r=mysqli_fetch_assoc($hourly)){$hour_data[(int)$r['hr']]=(int)$r['cnt'];}

// product performance
$prod_perf = mysqli_query($conn,
    "SELECT p.name, p.category,
            COUNT(oi.item_id) as order_count,
            SUM(oi.quantity) as total_qty,
            SUM(oi.quantity*oi.unit_price) as revenue
     FROM order_items oi
     JOIN products p ON oi.product_id=p.product_id
     JOIN orders o ON oi.order_id=o.order_id
     WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY oi.product_id ORDER BY revenue DESC LIMIT 10");

// order type distribution
$type_dist = mysqli_query($conn,
    "SELECT order_type, COUNT(*) as cnt,
            COALESCE(SUM(total_amount),0) as revenue
     FROM orders
     WHERE DATE(created_at) BETWEEN '$month_start' AND '$today'
     GROUP BY order_type");
$type_labels=[]; $type_data=[]; $type_rev=[];
while($r=mysqli_fetch_assoc($type_dist)){
    $type_labels[]=ucfirst($r['order_type']);
    $type_data[]=(int)$r['cnt'];
    $type_rev[]=(float)$r['revenue'];
}

// daily revenue last 30 days
$daily30 = mysqli_query($conn,
    "SELECT DATE(o.created_at) as day,
            COALESCE(SUM(b.total),0) as revenue
     FROM orders o LEFT JOIN bills b ON o.order_id=b.order_id
     WHERE DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(o.created_at) ORDER BY day ASC");
$d30_labels=[]; $d30_data=[];
while($r=mysqli_fetch_assoc($daily30)){
    $d30_labels[]=date('d M',strtotime($r['day']));
    $d30_data[]=(float)$r['revenue'];
}

// top customers
$top_customers = mysqli_query($conn,
    "SELECT u.name, u.phone,
            COUNT(o.order_id) as orders,
            COALESCE(SUM(b.total),0) as total_spent,
            u.loyalty_points
     FROM users u
     JOIN orders o ON u.user_id=o.user_id
     LEFT JOIN bills b ON o.order_id=b.order_id
     WHERE u.role='customer'
     GROUP BY u.user_id
     ORDER BY total_spent DESC LIMIT 8");

// expense breakdown
$exp_break = mysqli_query($conn,
    "SELECT category, SUM(amount) as total
     FROM expenses
     WHERE expense_date BETWEEN '$month_start' AND '$today'
     GROUP BY category ORDER BY total DESC");
$exp_labels=[]; $exp_data=[];
while($r=mysqli_fetch_assoc($exp_break)){
    $exp_labels[]=ucfirst($r['category']); $exp_data[]=(float)$r['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Analytics — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*{box-sizing:border-box;}
body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}
.page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}
.page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}

/* KPI CARDS */
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px;}
.kpi-card{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;position:relative;overflow:hidden;}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;}
.kpi-gold::before{background:#c9973a;}
.kpi-green::before{background:#2e7d32;}
.kpi-blue::before{background:#1565c0;}
.kpi-pink::before{background:#e8789a;}
.kpi-purple::before{background:#7b1fa2;}
.kpi-red::before{background:#c62828;}
.kpi-label{font-size:12px;color:#8a6a4a;font-weight:500;margin-bottom:6px;}
.kpi-value{font-size:26px;font-weight:700;color:#1a0a0f;line-height:1;margin-bottom:6px;}
.kpi-sub{font-size:12px;color:#8a6a4a;}
.kpi-badge{display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;padding:3px 8px;border-radius:99px;margin-top:4px;}
.kpi-up{background:#e8f5e9;color:#2e7d32;}
.kpi-down{background:#ffebee;color:#c62828;}

/* SECTION TITLE */
.section-title{font-size:14px;font-weight:600;color:#8a5a20;letter-spacing:.5px;text-transform:uppercase;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #c9973a;display:inline-block;}

/* CHART CARDS */
.chart-card{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;margin-bottom:20px;}
.chart-title{font-size:15px;font-weight:600;color:#1a0a0f;margin-bottom:4px;}
.chart-sub{font-size:12px;color:#8a6a4a;margin-bottom:16px;}

/* TABLE */
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

.rank-1{color:#c9973a;font-size:18px;}
.rank-2{color:#8a8a8a;font-size:18px;}
.rank-3{color:#c47820;font-size:18px;}

.insight-card{background:#1a0a0f;border-radius:14px;padding:20px;margin-bottom:20px;color:#f0e0c0;}
.insight-title{font-size:14px;font-weight:600;color:#e8c870;margin-bottom:12px;}
.insight-item{display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid #2a1018;font-size:13px;color:#c9b090;}
.insight-item:last-child{border-bottom:none;}
.insight-icon{font-size:16px;flex-shrink:0;margin-top:1px;}

@media print{
  .sidebar{display:none!important;}
  .main{margin-left:0;padding:10px;}
}
</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
    <div class="page-title"><i class="bi bi-graph-up me-2" style="color:#c9973a;"></i>Advanced Analytics</div>
    <button onclick="window.print()" style="background:#fff;border:1px solid #e8d8c0;border-radius:8px;padding:8px 16px;font-size:13px;cursor:pointer;color:#2a1a10;display:flex;align-items:center;gap:6px;">
      <i class="bi bi-printer"></i> Print
    </button>
  </div>
  <div class="page-sub">Business intelligence dashboard — <?= date('l, d F Y') ?></div>

  <!-- KPI CARDS -->
  <div class="kpi-grid">
    <div class="kpi-card kpi-gold">
      <div class="kpi-label">Revenue Today</div>
      <div class="kpi-value">Rs. <?= number_format($rev_today,0) ?></div>
      <div class="kpi-sub">Month: Rs. <?= number_format($rev_month,0) ?></div>
      <?php if ($growth >= 0): ?>
      <div class="kpi-badge kpi-up"><i class="bi bi-arrow-up"></i><?= $growth ?>% vs last month</div>
      <?php else: ?>
      <div class="kpi-badge kpi-down"><i class="bi bi-arrow-down"></i><?= abs($growth) ?>% vs last month</div>
      <?php endif; ?>
    </div>
    <div class="kpi-card kpi-green">
      <div class="kpi-label">Profit This Month</div>
      <div class="kpi-value" style="color:<?= $profit_month>=0?'#2e7d32':'#c62828' ?>;">
        Rs. <?= number_format(abs($profit_month),0) ?>
      </div>
      <div class="kpi-sub">Margin: <?= $margin_month ?>%</div>
      <div class="kpi-badge <?= $profit_month>=0?'kpi-up':'kpi-down' ?>">
        <?= $profit_month>=0?'Profitable':'Loss' ?> — Year: Rs. <?= number_format(abs($profit_year),0) ?>
      </div>
    </div>
    <div class="kpi-card kpi-blue">
      <div class="kpi-label">Orders Today</div>
      <div class="kpi-value"><?= $orders_today ?></div>
      <div class="kpi-sub">Month: <?= $orders_month ?> orders</div>
      <div class="kpi-badge kpi-up">Avg: Rs. <?= number_format($avg_order_val,0) ?></div>
    </div>
    <div class="kpi-card kpi-pink">
      <div class="kpi-label">Total Customers</div>
      <div class="kpi-value"><?= $total_customers ?></div>
      <div class="kpi-sub">New this month: <?= $new_this_month ?></div>
      <div class="kpi-badge kpi-up"><?= $returning ?> returning customers</div>
    </div>
    <div class="kpi-card kpi-red">
      <div class="kpi-label">Expenses This Month</div>
      <div class="kpi-value" style="color:#c62828;">Rs. <?= number_format($exp_month,0) ?></div>
      <div class="kpi-sub">Year total: Rs. <?= number_format($exp_year,0) ?></div>
      <div class="kpi-badge kpi-down">Year margin: <?= $margin_year ?>%</div>
    </div>
    <div class="kpi-card kpi-purple">
      <div class="kpi-label">Year Revenue</div>
      <div class="kpi-value">Rs. <?= number_format($rev_year,0) ?></div>
      <div class="kpi-sub">Net profit: Rs. <?= number_format(abs($profit_year),0) ?></div>
      <div class="kpi-badge <?= $profit_year>=0?'kpi-up':'kpi-down' ?>">
        <?= $margin_year ?>% margin
      </div>
    </div>
  </div>

  <!-- SMART INSIGHTS -->
  <div class="insight-card">
    <div class="insight-title"><i class="bi bi-lightbulb me-2" style="color:#e8c870;"></i>Smart Business Insights</div>
    <?php
    // Peak hour
    $peak_hr = array_search(max($hour_data), $hour_data);
    $best_prod = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT p.name, SUM(oi.quantity) as qty FROM order_items oi
         JOIN products p ON oi.product_id=p.product_id
         JOIN orders o ON oi.order_id=o.order_id
         WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY oi.product_id ORDER BY qty DESC LIMIT 1"));
    $low_stock_count = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as c FROM inventory WHERE quantity <= min_level"))['c'];
    ?>
    <div class="insight-item">
      <div class="insight-icon">🕐</div>
      <div>Peak sales hour is <strong style="color:#e8c870;"><?= $hour_labels[$peak_hr] ?></strong> — schedule more staff during this time for better service!</div>
    </div>
    <?php if ($best_prod): ?>
    <div class="insight-item">
      <div class="insight-icon">🏆</div>
      <div>Best selling product last 30 days: <strong style="color:#e8c870;"><?= htmlspecialchars($best_prod['name']) ?></strong> (<?= $best_prod['qty'] ?> units) — consider increasing production!</div>
    </div>
    <?php endif; ?>
    <?php if ($growth > 0): ?>
    <div class="insight-item">
      <div class="insight-icon">📈</div>
      <div>Revenue grew <strong style="color:#e8c870;"><?= $growth ?>%</strong> compared to last month — excellent performance!</div>
    </div>
    <?php elseif ($growth < 0): ?>
    <div class="insight-item">
      <div class="insight-icon">📉</div>
      <div>Revenue dropped <strong style="color:#f08090;"><?= abs($growth) ?>%</strong> vs last month — consider promotions or new products!</div>
    </div>
    <?php endif; ?>
    <?php if ($low_stock_count > 0): ?>
    <div class="insight-item">
      <div class="insight-icon">⚠️</div>
      <div><strong style="color:#f0a050;"><?= $low_stock_count ?> ingredient(s)</strong> running low — reorder soon to avoid production delays!</div>
    </div>
    <?php endif; ?>
    <?php if ($margin_month < 20): ?>
    <div class="insight-item">
      <div class="insight-icon">💡</div>
      <div>Profit margin is <strong style="color:#f0a050;"><?= $margin_month ?>%</strong> — review expenses to improve profitability!</div>
    </div>
    <?php else: ?>
    <div class="insight-item">
      <div class="insight-icon">✅</div>
      <div>Profit margin is <strong style="color:#e8c870;"><?= $margin_month ?>%</strong> — healthy margin! Keep up the good work!</div>
    </div>
    <?php endif; ?>
    <div class="insight-item">
      <div class="insight-icon">👥</div>
      <div>You have <strong style="color:#e8c870;"><?= $new_this_month ?> new customers</strong> this month and <strong style="color:#e8c870;"><?= $returning ?> returning customers</strong>!</div>
    </div>
  </div>

  <!-- REVENUE vs EXPENSE TREND -->
  <div class="chart-card">
    <div class="chart-title"><i class="bi bi-graph-up-arrow me-2" style="color:#c9973a;"></i>Revenue vs Expenses — 12 Month Trend</div>
    <div class="chart-sub">Monthly comparison of revenue and expenses over the past year</div>
    <canvas id="trendChart" height="80"></canvas>
  </div>

  <!-- DAILY 30 DAYS -->
  <div class="chart-card">
    <div class="chart-title"><i class="bi bi-bar-chart me-2" style="color:#c9973a;"></i>Daily Revenue — Last 30 Days</div>
    <div class="chart-sub">Day by day revenue performance</div>
    <canvas id="daily30Chart" height="70"></canvas>
  </div>

  <div class="row g-3 mb-4">
    <!-- ORDER TYPES -->
    <div class="col-md-4">
      <div class="chart-card" style="margin-bottom:0;height:100%;">
        <div class="chart-title"><i class="bi bi-pie-chart me-2" style="color:#c9973a;"></i>Order Types</div>
        <div class="chart-sub">This month breakdown</div>
        <canvas id="typeChart" height="200"></canvas>
        <div style="margin-top:14px;">
          <?php
          mysqli_data_seek($type_dist,0);
          $type_colors=['#c9973a','#e8789a','#5090d0','#50c090'];
          $ti=0;
          mysqli_data_seek($type_dist,0);
          $type_rows2=[];
          $td2=mysqli_query($conn,"SELECT order_type,COUNT(*) as cnt,COALESCE(SUM(total_amount),0) as revenue FROM orders WHERE DATE(created_at) BETWEEN '$month_start' AND '$today' GROUP BY order_type");
          while($r=mysqli_fetch_assoc($td2)):
          ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:5px 0;font-size:13px;border-bottom:1px solid #f5ede0;">
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:10px;height:10px;border-radius:50%;background:<?= $type_colors[$ti%4] ?>;flex-shrink:0;"></div>
              <span><?= ucfirst($r['order_type']) ?></span>
            </div>
            <div>
              <strong><?= $r['cnt'] ?></strong>
              <span style="color:#8a6a4a;margin-left:4px;">Rs. <?= number_format($r['revenue'],0) ?></span>
            </div>
          </div>
          <?php $ti++; endwhile; ?>
        </div>
      </div>
    </div>

    <!-- EXPENSE BREAKDOWN -->
    <div class="col-md-4">
      <div class="chart-card" style="margin-bottom:0;height:100%;">
        <div class="chart-title"><i class="bi bi-wallet2 me-2" style="color:#c9973a;"></i>Expense Breakdown</div>
        <div class="chart-sub">This month by category</div>
        <?php if (!empty($exp_data)): ?>
        <canvas id="expChart" height="200"></canvas>
        <?php else: ?>
        <div style="text-align:center;padding:40px;color:#8a6a4a;">No expenses this month</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- PEAK HOURS -->
    <div class="col-md-4">
      <div class="chart-card" style="margin-bottom:0;height:100%;">
        <div class="chart-title"><i class="bi bi-clock me-2" style="color:#c9973a;"></i>Peak Sales Hours</div>
        <div class="chart-sub">Orders by hour (last 30 days)</div>
        <canvas id="hourChart" height="220"></canvas>
      </div>
    </div>
  </div>

  <!-- TOP PRODUCTS -->
  <div class="chart-card">
    <div class="chart-title"><i class="bi bi-trophy me-2" style="color:#c9973a;"></i>Product Performance — Last 30 Days</div>
    <div class="chart-sub">Revenue and sales volume by product</div>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr><th>Rank</th><th>Product</th><th>Category</th><th>Orders</th><th>Qty Sold</th><th>Revenue</th><th>Performance</th></tr>
        </thead>
        <tbody>
          <?php
          $rank=1;
          $rows=[];
          while($r=mysqli_fetch_assoc($prod_perf)) $rows[]=$r;
          $max_rev=!empty($rows)?$rows[0]['revenue']:1;
          if($max_rev==0)$max_rev=1;
          foreach($rows as $r):
            $pct=round(($r['revenue']/$max_rev)*100);
          ?>
          <tr>
            <td>
              <?php if($rank==1):?><span class="rank-1">🥇</span>
              <?php elseif($rank==2):?><span class="rank-2">🥈</span>
              <?php elseif($rank==3):?><span class="rank-3">🥉</span>
              <?php else:?><span style="color:#8a6a4a;font-weight:600;">#<?= $rank ?></span>
              <?php endif;?>
            </td>
            <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
            <td><span class="cat-badge cat-<?= $r['category'] ?>"><?= ucfirst($r['category']) ?></span></td>
            <td><?= $r['order_count'] ?></td>
            <td><strong><?= $r['total_qty'] ?> pcs</strong></td>
            <td><strong style="color:#2e7d32;">Rs. <?= number_format($r['revenue'],2) ?></strong></td>
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

  <!-- TOP CUSTOMERS -->
  <div class="chart-card">
    <div class="chart-title"><i class="bi bi-people me-2" style="color:#c9973a;"></i>Top Customers</div>
    <div class="chart-sub">Highest spending customers of all time</div>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr><th>Rank</th><th>Customer</th><th>Phone</th><th>Total Orders</th><th>Total Spent</th><th>Loyalty Pts</th></tr>
        </thead>
        <tbody>
          <?php
          $rank=1;
          while($c=mysqli_fetch_assoc($top_customers)):
          ?>
          <tr>
            <td>
              <?php if($rank==1):?><span class="rank-1">🥇</span>
              <?php elseif($rank==2):?><span class="rank-2">🥈</span>
              <?php elseif($rank==3):?><span class="rank-3">🥉</span>
              <?php else:?><span style="color:#8a6a4a;font-weight:600;">#<?= $rank ?></span>
              <?php endif;?>
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:32px;height:32px;border-radius:50%;background:#fdf5ec;border:1px solid #c9973a;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#c9973a;">
                  <?= strtoupper(substr($c['name'],0,1)) ?>
                </div>
                <strong><?= htmlspecialchars($c['name']) ?></strong>
              </div>
            </td>
            <td style="color:#8a6a4a;"><?= $c['phone'] ?? '—' ?></td>
            <td><?= $c['orders'] ?> orders</td>
            <td><strong style="color:#2e7d32;">Rs. <?= number_format($c['total_spent'],2) ?></strong></td>
            <td>
              <span style="background:#fdf5ec;border:1px solid #e8d8c0;border-radius:99px;font-size:12px;color:#c9973a;font-weight:600;padding:3px 10px;">
                ⭐ <?= $c['loyalty_points'] ?> pts
              </span>
            </td>
          </tr>
          <?php $rank++; endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
// Revenue vs Expense Trend
new Chart(document.getElementById('trendChart').getContext('2d'),{
  type:'line',
  data:{
    labels:<?= json_encode($trend_labels) ?>,
    datasets:[
      {label:'Revenue',data:<?= json_encode($trend_rev) ?>,borderColor:'#c9973a',backgroundColor:'rgba(201,151,58,0.1)',borderWidth:2.5,pointRadius:4,tension:0.4,fill:true,yAxisID:'y'},
      {label:'Expenses',data:<?= json_encode($trend_exp) ?>,borderColor:'#ef5350',backgroundColor:'rgba(239,83,80,0.05)',borderWidth:2,pointRadius:3,tension:0.4,fill:false,yAxisID:'y'},
      {label:'Orders',data:<?= json_encode($trend_orders) ?>,borderColor:'#e8789a',borderWidth:1.5,pointRadius:3,tension:0.4,fill:false,yAxisID:'y1',borderDash:[5,5]}
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

// Daily 30 days
new Chart(document.getElementById('daily30Chart').getContext('2d'),{
  type:'bar',
  data:{labels:<?= json_encode($d30_labels) ?>,datasets:[{label:'Revenue',data:<?= json_encode($d30_data) ?>,backgroundColor:'rgba(201,151,58,0.7)',borderColor:'#c9973a',borderWidth:1,borderRadius:4}]},
  options:{plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#8a6a4a',maxRotation:45},grid:{color:'#f0e4d0'}},y:{ticks:{color:'#8a6a4a'},grid:{color:'#f0e4d0'}}}}
});

// Order types
<?php if (!empty($type_data)): ?>
new Chart(document.getElementById('typeChart').getContext('2d'),{
  type:'doughnut',
  data:{labels:<?= json_encode($type_labels) ?>,datasets:[{data:<?= json_encode($type_data) ?>,backgroundColor:['#c9973a','#e8789a','#5090d0','#50c090'],borderWidth:2,borderColor:'#fff'}]},
  options:{plugins:{legend:{position:'bottom',labels:{color:'#2a1a10',font:{size:11},padding:8}}}}
});
<?php endif; ?>

// Expense breakdown
<?php if (!empty($exp_data)): ?>
new Chart(document.getElementById('expChart').getContext('2d'),{
  type:'doughnut',
  data:{labels:<?= json_encode($exp_labels) ?>,datasets:[{data:<?= json_encode($exp_data) ?>,backgroundColor:['#ef5350','#e8789a','#c9973a','#5090d0','#50c090','#c070d0','#8a6a4a'],borderWidth:2,borderColor:'#fff'}]},
  options:{plugins:{legend:{position:'bottom',labels:{color:'#2a1a10',font:{size:11},padding:8}}}}
});
<?php endif; ?>

// Peak hours
new Chart(document.getElementById('hourChart').getContext('2d'),{
  type:'bar',
  data:{
    labels:['6am','7am','8am','9am','10am','11am','12pm','1pm','2pm','3pm','4pm','5pm','6pm','7pm','8pm','9pm','10pm'],
    datasets:[{
      label:'Orders',
      data:<?= json_encode(array_slice($hour_data,6,17)) ?>,
      backgroundColor:function(ctx){
        const val=ctx.dataset.data[ctx.dataIndex];
        const max=Math.max(...ctx.dataset.data);
        const alpha=max>0?0.3+(val/max)*0.7:0.3;
        return `rgba(201,151,58,${alpha})`;
      },
      borderRadius:4
    }]
  },
  options:{plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#8a6a4a',font:{size:10}},grid:{color:'#f0e4d0'}},y:{ticks:{color:'#8a6a4a'},grid:{color:'#f0e4d0'}}}}
});
</script>
</body>
</html>