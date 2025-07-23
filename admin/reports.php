 <?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $functions->redirect('../login.php');
}

// Get report data
$dateRange = isset($_GET['date_range']) ? $functions->sanitize($_GET['date_range']) : 'this_month';
$startDate = '';
$endDate = date('Y-m-d');

switch ($dateRange) {
    case 'today':
        $startDate = date('Y-m-d');
        break;
    case 'yesterday':
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = $startDate;
        break;
    case 'this_week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        break;
    case 'last_week':
        $startDate = date('Y-m-d', strtotime('monday last week'));
        $endDate = date('Y-m-d', strtotime('sunday last week'));
        break;
    case 'this_month':
        $startDate = date('Y-m-01');
        break;
    case 'last_month':
        $startDate = date('Y-m-01', strtotime('last month'));
        $endDate = date('Y-m-t', strtotime('last month'));
        break;
    case 'this_year':
        $startDate = date('Y-01-01');
        break;
    case 'custom':
        $startDate = isset($_GET['start_date']) ? $functions->sanitize($_GET['start_date']) : date('Y-m-d');
        $endDate = isset($_GET['end_date']) ? $functions->sanitize($_GET['end_date']) : date('Y-m-d');
        break;
}

// Get sales data
$salesData = $functions->db->fetchAll("
    SELECT 
        DATE(o.created_at) as date,
        COUNT(*) as total_orders,
        SUM(o.total_amount) as total_sales
    FROM orders o
    WHERE o.status = 'completed' 
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY DATE(o.created_at)
    ORDER BY DATE(o.created_at)
", [$startDate, $endDate]);

// Get total sales and orders
$totals = $functions->db->fetchOne("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales
    FROM orders
    WHERE status = 'completed'
    AND DATE(created_at) BETWEEN ? AND ?
", [$startDate, $endDate]);

// Get top selling projects
$topProjects = $functions->db->fetchAll("
    SELECT 
        p.title,
        COUNT(o.id) as total_orders,
        SUM(o.total_amount) as total_sales
    FROM orders o
    JOIN projects p ON o.project_id = p.id
    WHERE o.status = 'completed'
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.title
    ORDER BY total_sales DESC
    LIMIT 5
", [$startDate, $endDate]);

$pageTitle = 'Sales Reports';
require_once '../includes/header.php';
?>

<div class="card mb-4">
    <div class="card-header">
        <h4 class="mb-0">Sales Reports</h4>
    </div>
    <div class="card-body">
        <form method="get" class="mb-4">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <select name="date_range" class="form-select" id="dateRangeSelect">
                        <option value="today" <?php echo $dateRange === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="yesterday" <?php echo $dateRange === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                        <option value="this_week" <?php echo $dateRange === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="last_week" <?php echo $dateRange === 'last_week' ? 'selected' : ''; ?>>Last Week</option>
                        <option value="this_month" <?php echo $dateRange === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="last_month" <?php echo $dateRange === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                        <option value="this_year" <?php echo $dateRange === 'this_year' ? 'selected' : ''; ?>>This Year</option>
                        <option value="custom" <?php echo $dateRange === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2" id="startDateContainer" style="<?php echo $dateRange !== 'custom' ? 'display:none;' : ''; ?>">
                    <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                </div>
                <div class="col-md-3 mb-2" id="endDateContainer" style="<?php echo $dateRange !== 'custom' ? 'display:none;' : ''; ?>">
                    <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <button type="submit" class="btn btn-primary w-100">Generate</button>
                </div>
            </div>
        </form>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <h2 class="mb-0"><?php echo $totals['total_orders'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Sales</h5>
                        <h2 class="mb-0">₹<?php echo number_format($totals['total_sales'] ?? 0, 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Daily Sales</h5>
            </div>
            <div class="card-body">
                <?php if (empty($salesData)): ?>
                    <div class="alert alert-info">No sales data found for the selected period.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salesData as $data): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($data['date'])); ?></td>
                                        <td><?php echo $data['total_orders']; ?></td>
                                        <td>₹<?php echo number_format($data['total_sales'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Top Selling Projects</h5>
            </div>
            <div class="card-body">
                <?php if (empty($topProjects)): ?>
                    <div class="alert alert-info">No project sales data found for the selected period.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Orders</th>
                                    <th>Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProjects as $project): ?>
                                    <tr>
                                        <td><?php echo $project['title']; ?></td>
                                        <td><?php echo $project['total_orders']; ?></td>
                                        <td>₹<?php echo number_format($project['total_sales'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('dateRangeSelect').addEventListener('change', function() {
    const isCustom = this.value === 'custom';
    document.getElementById('startDateContainer').style.display = isCustom ? 'block' : 'none';
    document.getElementById('endDateContainer').style.display = isCustom ? 'block' : 'none';
});
</script>

<?php require_once '../includes/footer.php'; ?>
