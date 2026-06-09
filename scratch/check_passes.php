<?php
$conn = mysqli_connect("localhost", "root", "", "new_clms");
if (!$conn) {
    die("Connection failed\n");
}
$res = $conn->query("SELECT id, name, role, email FROM users");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "User: ID={$row['id']}, Name={$row['name']}, Role={$row['role']}, Email={$row['email']}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
mysqli_close($conn);
?>
