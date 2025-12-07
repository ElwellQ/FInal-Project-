<?php
require_once __DIR__ . '/functions.php';

$zones = $pdo->query("SELECT * FROM zones ORDER BY id ASC")->fetchAll();

if (!$zones) {
    echo "<p>No zones found.</p>";
    exit;
}

$current_threshold = get_setting('global_db_threshold', 75);

echo "<table>
<thead>
<tr>
<th>Location</th>
<th>Noise (dB)</th>
<th>Status</th>
</tr>
</thead>
<tbody>";

foreach ($zones as $zone) {
    $noise = $zone['current_db'];
    $status = ($noise >= $current_threshold) ? "High" : (($noise >= ($current_threshold - 10)) ? "Moderate" : "Safe");

    $color = ($status === "High") ? "red" : (($status === "Moderate") ? "orange" : "green");

    echo "<tr>
        <td>" . htmlspecialchars($zone['location']) . "</td>
        <td>$noise</td>
        <td style='color:$color; font-weight:bold;'>$status</td>
    </tr>";
}

echo "</tbody></table>";
