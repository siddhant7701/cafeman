<?php
// Enhanced db.php (Safe Upgrade)

function getPDO() {
    static $pdo = null;
    
    if ($pdo === null) {
        $host = 'localhost';
        $dbname = 'cafeman';
        $username = 'root';
        $password = '';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Database Connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Initialize PDO
$pdo = getPDO();

/*
|--------------------------------------------------------------------------
| Load Global Settings (NEW)
|--------------------------------------------------------------------------
| Makes API keys, GST, site name available everywhere
*/

try {
    $stmt = $pdo->prepare("SELECT * FROM settings WHERE id = 1 LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch();

    // If settings row doesn't exist, create one
    if (!$settings) {
        $pdo->exec("INSERT INTO settings (id) VALUES (1)");
        $stmt = $pdo->prepare("SELECT * FROM settings WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch();
    }

} catch (Exception $e) {
    // If settings table doesn't exist yet, prevent crash
    $settings = [];
}

?>
