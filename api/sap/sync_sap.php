<?php
// api/sap/sync_sap.php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_compat.php'; // DB abstraction layer

header('Content-Type: application/json; charset=utf-8');

// 1. SQL Server credentials override check
if (!defined('SAP_DB_SERVER') && file_exists(__DIR__ . '/../../include/config_credentials.php')) {
    require_once __DIR__ . '/../../include/config_credentials.php';
}

if (!defined('SAP_DB_SERVER')) {
    // Fallback default definitions if not defined in config_credentials.php
    define('SAP_DB_DRIVER', 'sqlsrv');
    define('SAP_DB_SERVER', '127.0.0.1'); 
    define('SAP_DB_USER', 'sa');               
    define('SAP_DB_PASS', ''); 
    define('SAP_DB_NAME', 'commondb');         
}

$sap_conn = clms_db_connect(SAP_DB_DRIVER, SAP_DB_SERVER, SAP_DB_USER, SAP_DB_PASS, SAP_DB_NAME);

if ($sap_conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'SAP Connection Failed: ' . $sap_conn->connect_error]);
    exit;
}

$log = [];

try {
    // ==========================================
    // 2. SYNC CUSTOMER MASTER
    // ==========================================
    $sql_cust = "SELECT customer_code, customer_name, customer_mob1, customer_mob2, active_ind, email_address, address, pin, created_at FROM sap_customer_master";
    $stmt_cust = $sap_conn->prepare($sql_cust);
    $stmt_cust->execute();
    $res_cust = $stmt_cust->get_result();
    
    $cust_synced = 0;
    while ($row = $res_cust->fetch_assoc()) {
        // MySQL database in CLMS
        $stmt_my = $conn->prepare("
            INSERT INTO sap_customer_master 
                (customer_code, customer_name, customer_mob1, customer_mob2, active_ind, email_address, address, pin, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                customer_name = VALUES(customer_name),
                customer_mob1 = VALUES(customer_mob1),
                customer_mob2 = VALUES(customer_mob2),
                active_ind = VALUES(active_ind),
                email_address = VALUES(email_address),
                address = VALUES(address),
                pin = VALUES(pin)
        ");
        
        $stmt_my->bind_param('sssssssss', 
            $row['customer_code'], 
            $row['customer_name'], 
            $row['customer_mob1'], 
            $row['customer_mob2'], 
            $row['active_ind'], 
            $row['email_address'], 
            $row['address'], 
            $row['pin'], 
            $row['created_at']
        );
        $stmt_my->execute();
        $stmt_my->close();
        $cust_synced++;
    }
    $log[] = "Customers Synced: $cust_synced";

    // ==========================================
    // 3. SYNC SALE ORDER MASTER
    // ==========================================
    $sql_so = "SELECT sale_order_no, customer_code, amount, currency, doc_date, sales_organization, created_at FROM sap_sale_order_master";
    $stmt_so = $sap_conn->prepare($sql_so);
    $stmt_so->execute();
    $res_so = $stmt_so->get_result();
    
    $so_synced = 0;
    while ($row = $res_so->fetch_assoc()) {
        // MySQL database in CLMS
        $stmt_my = $conn->prepare("
            INSERT INTO sap_sale_order_master 
                (sale_order_no, customer_code, amount, currency, doc_date, sales_organization, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                customer_code = VALUES(customer_code),
                amount = VALUES(amount),
                currency = VALUES(currency),
                doc_date = VALUES(doc_date),
                sales_organization = VALUES(sales_organization)
        ");
        
        $stmt_my->bind_param('ssdsdss', 
            $row['sale_order_no'], 
            $row['customer_code'], 
            $row['amount'], 
            $row['currency'], 
            $row['doc_date'], 
            $row['sales_organization'], 
            $row['created_at']
        );
        $stmt_my->execute();
        $stmt_my->close();
        $so_synced++;
    }
    $log[] = "Sale Orders Synced: $so_synced";

    echo json_encode(['success' => true, 'logs' => $log]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
