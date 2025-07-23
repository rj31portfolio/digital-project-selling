 <?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $functions->redirect('../login.php');
}

// Get current settings
$settings = [
    'gst_percent' => $functions->getSetting('gst_percent'),
    'razorpay_key_id' => $functions->getSetting('razorpay_key_id'),
    'razorpay_key_secret' => $functions->getSetting('razorpay_key_secret'),
    'razorpay_mode' => $functions->getSetting('razorpay_mode'),
    'timezone' => $functions->getSetting('timezone'),
    'company_name' => $functions->getSetting('company_name'),
    'company_address' => $functions->getSetting('company_address')
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update settings
    $gstPercent = floatval($_POST['gst_percent']);
    $razorpayKeyId = $functions->sanitize($_POST['razorpay_key_id']);
    $razorpayKeySecret = $functions->sanitize($_POST['razorpay_key_secret']);
    $razorpayMode = $functions->sanitize($_POST['razorpay_mode']);
    $timezone = $functions->sanitize($_POST['timezone']);
    $companyName = $functions->sanitize($_POST['company_name']);
    $companyAddress = $functions->sanitize($_POST['company_address']);
    
    $newSettings = [
        ['key' => 'gst_percent', 'value' => $gstPercent],
        ['key' => 'razorpay_key_id', 'value' => $razorpayKeyId],
        ['key' => 'razorpay_key_secret', 'value' => $razorpayKeySecret],
        ['key' => 'razorpay_mode', 'value' => $razorpayMode],
        ['key' => 'timezone', 'value' => $timezone],
        ['key' => 'company_name', 'value' => $companyName],
        ['key' => 'company_address', 'value' => $companyAddress]
    ];
    
    $success = true;
    foreach ($newSettings as $setting) {
        $updated = $functions->db->query("UPDATE settings SET value = ? WHERE key = ?", [$setting['value'], $setting['key']]);
        if (!$updated) {
            $success = false;
        }
    }
    
    if ($success) {
        $_SESSION['success_message'] = 'Settings updated successfully!';
        $functions->redirect('settings.php');
    } else {
        $error = 'Failed to update some settings. Please try again.';
    }
}

$pageTitle = 'System Settings';
require_once '../includes/header.php';

if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}

if (isset($error)) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
}
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">System Settings</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <h5 class="mb-3">Payment Settings</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="gst_percent" class="form-label">GST Percentage</label>
                            <input type="number" class="form-control" id="gst_percent" name="gst_percent" step="0.01" min="0" max="30" value="<?php echo $settings['gst_percent']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="razorpay_mode" class="form-label">Razorpay Mode</label>
                            <select class="form-select" id="razorpay_mode" name="razorpay_mode" required>
                                <option value="test" <?php echo $settings['razorpay_mode'] === 'test' ? 'selected' : ''; ?>>Test Mode</option>
                                <option value="live" <?php echo $settings['razorpay_mode'] === 'live' ? 'selected' : ''; ?>>Live Mode</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="razorpay_key_id" class="form-label">Razorpay Key ID</label>
                            <input type="text" class="form-control" id="razorpay_key_id" name="razorpay_key_id" value="<?php echo $settings['razorpay_key_id']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="razorpay_key_secret" class="form-label">Razorpay Key Secret</label>
                            <input type="text" class="form-control" id="razorpay_key_secret" name="razorpay_key_secret" value="<?php echo $settings['razorpay_key_secret']; ?>" required>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">General Settings</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select class="form-select" id="timezone" name="timezone" required>
                                <?php
                                $timezones = DateTimeZone::listIdentifiers();
                                foreach ($timezones as $tz) {
                                    echo '<option value="' . $tz . '" ' . ($settings['timezone'] === $tz ? 'selected' : '') . '>' . $tz . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo $settings['company_name']; ?>" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="company_address" class="form-label">Company Address</label>
                            <textarea class="form-control" id="company_address" name="company_address" rows="3" required><?php echo $settings['company_address']; ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
