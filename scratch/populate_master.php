<?php
include 'd:/Xampp/htdocs/clms/include/config.php';

$data = [
    ['53585', 'ALFA ENGG WORKS', '', '', 'A', '', 'KOCHUPALLY ROAD THOPPUMPADY', ''],
    ['54557', 'GAMA MARINE AND INDUSTRIAL', '', '', 'A', '', 'II/179L, MENACHERRY BUILDING, NEAR S COCHIN', ''],
    ['55065', 'Morning Star Technologies', '8848113724', '', 'A', 'morningstarfirm@gmail.com', 'Ernakulam', ''],
    ['55066', 'PARAS DEFENCE & SPACE TECHNOLOGIES', '', '', 'A', '', 'NERUL, NAVI MUMBAI', ''],
    ['55089', 'Starflex Bellows', '8153054857', '', 'A', 'starflexbellows@gmail.com', '', ''],
    ['55090', 'NISAN Scientific Process', '022-27601201', '+91 9833844128', 'A', 'marketing@nisanprocess.com', 'Navi Mumbai', ''],
    ['55091', 'Global Transportation', '', '', 'A', 'abeygeorge@aramex.com', 'Ernakulam', ''],
    ['55092', 'M Trans Corporation , Kochi', '2364436', '9847067896', 'A', 'mtranskerala@gmail.com', "39 Jacob's DD mall, Shenoy's Jn", ''],
    ['55093', 'SNOW COOL SYSTEMS INDIA PVT LTD', '9167015123', '', 'A', 'projects@snowcoolsystems.com', 'SB168, 2ND FLOOR', ''],
    ['55094', 'Dolphin Rubber Industries', '0891-2565095', '9866774339', 'A', '', 'Visakhapatnam', ''],
    ['55095', 'KELVION INDIA PRIVATE LIMITED', '2135619500', '', 'A', 'yogesh.bhave@kelvion.com', 'MIDC, CHAKAN, TAL-KHED', ''],
    ['55096', 'Siddhi Engineers', '2809879', '9447131947', 'A', 'siddhiengineerspvtltd@gmail.com', 'Vennala.P.O', ''],
    ['55097', 'CTC India', '9497165033', '9349165033', 'A', 'vijoy.cv@gmail.com', '', ''],
    ['55098', 'NAV BHARATH ENTERPRISES', '', '', 'A', 'info@aaronlogistics.in', 'Ernakulam', ''],
    ['55099', 'Integrated Enterprise Solutions', '9443445000', '', 'A', 'info@integrate.net.in', '', ''],
    ['55100', 'Island Shipping Agencies', '', '', 'A', 'docs@cb-isa.com', 'XXII 1582, MERCANTILE MARINE Ernakulam', ''],
    ['55101', 'P H Value Shipping Pvt Ltd', '', '', 'A', 'admin@phvalueshipping.com', 'XXIV/1672B,', ''],
    ['55102', 'V & S Seair Logistics Pvt Ltd', '', '', 'A', 'cscochin@vands.in', 'Ernakulam', ''],
    ['55104', 'Global Agencies', '', '', 'A', 'globage@hotmail.com', 'Ernakulam', '']
];

foreach ($data as $row) {
    // Check if exists
    $check = mysqli_query($conn, "SELECT id FROM sap_customer_master WHERE customer_code = '$row[0]'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE sap_customer_master SET 
            customer_name = '" . mysqli_real_escape_string($conn, $row[1]) . "',
            Customer_MOB1 = '$row[2]',
            customer_MOB2 = '$row[3]',
            ACTIVE_IND = '$row[4]',
            EMAIL_ADDRESS = '$row[5]',
            Address = '" . mysqli_real_escape_string($conn, $row[6]) . "',
            PIN = '$row[7]'
            WHERE customer_code = '$row[0]'");
    } else {
        mysqli_query($conn, "INSERT INTO sap_customer_master (customer_code, customer_name, Customer_MOB1, customer_MOB2, ACTIVE_IND, EMAIL_ADDRESS, Address, PIN, status) 
            VALUES ('$row[0]', '" . mysqli_real_escape_string($conn, $row[1]) . "', '$row[2]', '$row[3]', '$row[4]', '$row[5]', '" . mysqli_real_escape_string($conn, $row[6]) . "', '$row[7]', 'ACTIVE')");
    }
}

echo "SUCCESS: sap_customer_master populated with " . count($data) . " records.\n";
?>
