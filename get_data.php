<?php
header('Content-Type: application/json');

// Datenbank-Verbindung (wie in deinem Original-Skript)
$servername = "localhost";
$username = "root"; // Dein DB-Benutzer
$password = "";     // Dein DB-Passwort
$dbname = "satellit"; // Dein DB-Name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Verbindung fehlgeschlagen']));
}

// 1. Hole den letzten GPS-Datenpunkt (oder einen bestimmten für den Loop)
// Für deinen "Loop"-Plan müsstest du hier einen Zeit-Parameter übergeben,
// z.B. get_data.php?time=...
// Fürs Erste holen wir den aktuellsten.
$gps_result = $conn->query("SELECT * FROM gps_data ORDER BY datetime DESC LIMIT 1");
if ($gps_result->num_rows == 0) {
    die(json_encode(['error' => 'Keine GPS-Daten gefunden']));
}

$gps_data = $gps_result->fetch_assoc();
$data_id = $gps_data['id'];

// 2. Hole alle Satelliten, die zu diesem Datenpunkt gehören
// --- HIER IST DIE ANPASSUNG ---
// Wir holen jetzt auch die 'system'-Spalte mit
$sat_result = $conn->query("SELECT system, sat_id, elevation, azimuth, snr FROM satellites WHERE data_id = $data_id");

$satellites = [];
if ($sat_result->num_rows > 0) {
    while($row = $sat_result->fetch_assoc()) {
        $satellites[] = $row; // Fügt den Satelliten (inkl. 'system') zum Array hinzu
    }
}

// 3. Kombiniere alles zu einer JSON-Antwort
$response = [
    'gps' => $gps_data,
    'satellites' => $satellites
];

echo json_encode($response);

$conn->close();
?>
