<?php
session_start();
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Module Disabled</title>
  <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
  <div class="page-container">
    <div class="alert alert-warning">
      <strong>Payment module disabled.</strong>
      <span>This CLMS build does not implement payment processing.</span>
    </div>
    <a href="contractor-dashboard.php" class="btn btn-primary">Back to Dashboard</a>
  </div>
</body>
</html>

