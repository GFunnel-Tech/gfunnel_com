<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the file that defines the bx_srv function
require_once '/home/gfunnelc/public_html/inc/header.inc.php'; // Ensure this path is correct

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

// Step 1: Retrieve all profile IDs from the sys_accounts table where ReferredBy is empty and force_update is set
$result = $connection->query("SELECT profile_id FROM sys_accounts WHERE force_update = 1");

if ($result->num_rows > 0) {
    // Step 2: Loop through each profile ID
    while ($row = $result->fetch_assoc()) {
        $iProfileId = $row['profile_id'];
        echo "Processing profile ID: $iProfileId<br>";

        // Get the referrer ID
        $referrerId = bx_srv('aqb_affiliate', 'get_referrer_id', [$iProfileId]);
        if (empty($referrerId)) {
            echo "No referrer found for profile ID $iProfileId, using default value.<br>";
            $referrerId = 'QVdaMEVEWXp1YXpDaUxibGlmb3VoZz09'; // Default value if referrer ID is not found
        }
        echo "Referrer ID for profile ID $iProfileId: $referrerId<br>";

        // Update the table with the referrer ID
        $sql = "UPDATE sys_accounts SET ReferredBy='$referrerId', force_update = 0 WHERE profile_id = $iProfileId";
        echo "Executing SQL: $sql<br>";

        if ($connection->query($sql) === TRUE) {
            echo "Record updated successfully for profile ID: $iProfileId<br>";
        } else {
            echo "Error updating record for profile ID: $iProfileId - " . $connection->error . "<br>";
        }
    }
} else {
    echo "No profile IDs flagged for update found";
}

// Close the database connection
$connection->close();
?>
