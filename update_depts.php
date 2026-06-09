<?php
include __DIR__ . '/include/config.php';

$depts = [
    "Directors Office", "Company Sectt. Department", "IQC & HSE", "HR & Training Section",
    "Strategy & New Projects", "Civil", "Infra Projects", "IR - Admin & CSR Section",
    "Ship Repair", "Mumbai SR Facility", "Materials Department", "Design Department",
    "Planning Department", "Ship Building", "IAC Department", "IAC-Project Management",
    "Information Systems Department", "Finance", "Vigilance Office", "ISR Facility",
    "P & A Department", "Director-Finance Office", "Director-Operations Office",
    "Director-Technical Office", "Canteen", "U & M", "Technical Services",
    "Safety & Fire Services", "IQC", "KMRL Project", "CKRSU", "Business Development",
    "Training Institute", "TEBMA", "HCSL", "NA"
];

$conn->query("TRUNCATE TABLE master_departments");
$stmt = $conn->prepare("INSERT INTO master_departments (dept_name, status) VALUES (?, 'active')");
foreach ($depts as $dept) {
    $stmt->bind_param("s", $dept);
    $stmt->execute();
}
echo "Departments updated successfully: " . count($depts);
?>
