<?php
require __DIR__ . '/../include/config.php';
require __DIR__ . '/../include/payment_flow.php';

// Force payment gateway provider to razorpay
clms_set_payment_setting($conn, 'payment_gateway_provider', 'razorpay', 1);

// Just to be sure, check if it was set
$provider = clms_payment_setting($conn, 'payment_gateway_provider');
echo "Provider is now: " . $provider;
