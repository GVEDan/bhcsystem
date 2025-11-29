<?php
require_once 'includes/config.php';

echo "<h1>Database Maintenance</h1>";

// Check and add missing columns to contact_messages
$check_is_read = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='contact_messages' AND COLUMN_NAME='is_read' AND TABLE_SCHEMA='" . DB_NAME . "'";
$result = $conn->query($check_is_read);

echo "<h2>contact_messages Table Status:</h2>";

if ($result && $result->num_rows == 0) {
    echo "<p style='color: orange;'>Adding is_read column...</p>";
    $alter_sql = "ALTER TABLE contact_messages ADD COLUMN is_read BOOLEAN DEFAULT FALSE AFTER message";
    if ($conn->query($alter_sql)) {
        echo "<p style='color: green;'>✓ is_read column added</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding is_read: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ is_read column exists</p>";
}

$check_reply = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='contact_messages' AND COLUMN_NAME='reply' AND TABLE_SCHEMA='" . DB_NAME . "'";
$result = $conn->query($check_reply);

if ($result && $result->num_rows == 0) {
    echo "<p style='color: orange;'>Adding reply column...</p>";
    $alter_sql = "ALTER TABLE contact_messages ADD COLUMN reply TEXT AFTER is_read";
    if ($conn->query($alter_sql)) {
        echo "<p style='color: green;'>✓ reply column added</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding reply: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ reply column exists</p>";
}

$check_is_archived = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='contact_messages' AND COLUMN_NAME='is_archived' AND TABLE_SCHEMA='" . DB_NAME . "'";
$result = $conn->query($check_is_archived);

if ($result && $result->num_rows == 0) {
    echo "<p style='color: orange;'>Adding is_archived column...</p>";
    $alter_sql = "ALTER TABLE contact_messages ADD COLUMN is_archived BOOLEAN DEFAULT FALSE AFTER reply";
    if ($conn->query($alter_sql)) {
        echo "<p style='color: green;'>✓ is_archived column added</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding is_archived: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ is_archived column exists</p>";
}

// Show table structure
echo "<h2>Current Table Structure:</h2>";
$result = $conn->query("DESCRIBE contact_messages");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='admin/messages.php'>Go to Messages</a></p>";
?>
