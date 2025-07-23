<?php
require_once 'includes/config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql)) {
    echo "Database created successfully<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select database
$conn->select_db(DB_NAME);

// Create tables with corrected schema
$sql = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    bio TEXT,
    address TEXT,
    image VARCHAR(255),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    category_id INT,
    price DECIMAL(10,2) NOT NULL,
    gst_percent DECIMAL(5,2) DEFAULT 0,
    preview_link VARCHAR(255),
    zip_file VARCHAR(255),
    screenshots TEXT,
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_percent DECIMAL(5,2) NOT NULL,
    expiry_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    razorpay_payment_id VARCHAR(255),
    coupon_id INT,
    gst_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (coupon_id) REFERENCES coupons(id)
);

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT
);
";

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Tables created successfully<br>";
} else {
    die("Error creating tables: " . $conn->error);
}

// Insert default settings - NOTE THE BACKTICKS AROUND 'key'
$sql = "
INSERT INTO settings (`key`, value) VALUES 
('gst_percent', '18'),
('razorpay_key_id', 'YOUR_TEST_KEY_ID'),
('razorpay_key_secret', 'YOUR_TEST_KEY_SECRET'),
('razorpay_mode', 'test'),
('company_name', 'Digital Project Store'),
('company_address', '123 Tech Street, Bangalore, India'),
('timezone', 'Asia/Kolkata')
ON DUPLICATE KEY UPDATE value = VALUES(value);
";

if ($conn->query($sql)) {
    echo "Default settings inserted<br>";
} else {
    die("Error inserting settings: " . $conn->error);
}

// Create admin user
$password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "
INSERT INTO users (name, email, password, role) 
VALUES ('Admin', 'admin@example.com', '$password', 'admin')
ON DUPLICATE KEY UPDATE password = '$password', role = 'admin';
";

if ($conn->query($sql)) {
    echo "Admin user created (email: admin@example.com, password: admin123)<br>";
} else {
    die("Error creating admin user: " . $conn->error);
}

$conn->close();

echo "<h3>Installation completed successfully!</h3>";
echo "<p>You can now <a href='login.php'>login to the admin panel</a>.</p>";
echo "<p><strong>Important:</strong> Delete this install.php file for security reasons.</p>";
?>