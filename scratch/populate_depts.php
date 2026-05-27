<?php
include 'd:/Xampp/htdocs/clms/include/config.php';

$depts = [
    [1, 'Directors Office'],
    [2, 'Company Sectt. Department'],
    [3, 'IQC & HSE'],
    [4, 'HR & Training Section'],
    [5, 'Strategy & New Projects'],
    [6, 'Civil'],
    [7, 'Infra Projects'],
    [8, 'IR - Admin & CSR Section'],
    [9, 'Ship Repair'],
    [10, 'Mumbai SR Facility'],
    [11, 'Materials Department'],
    [12, 'Design Department'],
    [13, 'Planning Department'],
    [14, 'Ship Building'],
    [15, 'IAC Department'],
    [16, 'IAC-Project Management'],
    [17, 'Information Systems Department'],
    [18, 'Finance'],
    [19, 'Vigilance Office'],
    [20, 'ISR Facility'],
    [21, 'P & A Department'],
    [22, 'Director-Finance Office'],
    [23, 'Director-Operations Office'],
    [24, 'Director-Technical Office'],
    [25, 'Canteen'],
    [26, 'U & M'],
    [27, 'Technical Services'],
    [28, 'Safety & Fire Services'],
    [29, 'IQC'],
    [30, 'KMRL Project'],
    [31, 'CKRSU'],
    [32, 'Business Development'],
    [33, 'Training Institute'],
    [34, 'TEBMA'],
    [35, 'HCSL'],
    [36, 'NA']
];

mysqli_query($conn, "TRUNCATE TABLE master_departments");

foreach ($depts as $d) {
    $code = $d[0];
    $name = mysqli_real_escape_string($conn, $d[1]);
    mysqli_query($conn, "INSERT INTO master_departments (dept_code, dept_name, status) VALUES ('$code', '$name', 'active')");
}

echo "SUCCESS: master_departments populated with " . count($depts) . " records.\n";
?>
