<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the file that defines the bx_srv function
require_once '/home/gfunnelc/public_html/inc/header.inc.php'; // Adjust this path if necessary

// Database connection parameters
$host = 'localhost';
$username = 'gfunnelc_admin';
$password = 'Abundance358$';
$database = 'gfunnelc_members';

// Connect to the MySQL database
$connection = new mysqli($host, $username, $password, $database);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Set the character set to UTF-8
if (!$connection->set_charset("utf8")) {
    echo "Error loading character set utf8: " . $connection->error;
} else {
    echo "Successfully set character set to utf8<br>";
}

echo "Connected to the database successfully.<br>";

// Step 1: Retrieve all profile IDs from the sys_accounts table
$result = $connection->query("SELECT profile_id FROM sys_accounts");

if ($result->num_rows > 0) {
    // Step 2: Loop through each profile ID
    while ($row = $result->fetch_assoc()) {
        $iProfileId = $row['profile_id'];
        echo "Processing profile ID: $iProfileId<br>";
        
        // Get the referral code
        $referralCodeUrl = bx_srv('aqb_affiliate', 'get_referral_code', [$iProfileId]);
        echo "Referral Code URL for profile ID $iProfileId: $referralCodeUrl<br>";
        
        // Extract the member ID from the URL
        $urlComponents = parse_url($referralCodeUrl);
        parse_str($urlComponents['query'], $queryParams);
        $referralCode = $queryParams['member'];
        echo "Extracted Referral Code for profile ID $iProfileId: $referralCode<br>";

        // Update the table with the referral code
        $sql = "UPDATE sys_accounts SET MemberID='$referralCode' WHERE profile_id = $iProfileId";
        echo "Executing SQL: $sql<br>";

        if ($connection->query($sql) === TRUE) {
            echo "Record updated successfully for profile ID: $iProfileId<br>";
        } else {
            echo "Error updating record for profile ID: $iProfileId - " . $connection->error . "<br>";
        }
    }
} else {
    echo "No profile IDs found";
}

// Close the database connection
$connection->close();
?>
