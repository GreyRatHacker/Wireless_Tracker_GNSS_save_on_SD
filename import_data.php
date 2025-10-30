<?php
// Datenbank-Verbindung (wie in deinem Original-Skript)
$servername = "localhost";
$username = "root"; // Dein DB-Benutzer
$password = "";     // Dein DB-Passwort
$dbname = "satellit"; // Dein DB-Name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Den Inhalt der Log-Datei lesen (z.B. nach Upload)
// In einem echten Szenario würdest du hier die hochgeladene Datei verarbeiten
// Zum Testen kannst du die Datei 'gpslog.jsonl' in dasselbe Verzeichnis legen.
$logFile = 'gpslog.jsonl'; 
if (!file_exists($logFile)) {
    die("Log-Datei nicht gefunden. Bitte lade 'gpslog.jsonl' hoch oder platziere sie hier.");
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$conn->begin_transaction();

try {
    foreach ($lines as $line) {
        $data = json_decode($line, true);

        // 1. Haupt-Datenpunkt einfügen
        $stmt_gps = $conn->prepare("INSERT INTO gps_data (datetime, fix, lat, lon, sats_in_use, sats_in_view, hdop, alt_m, speed_kmph, ms) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_gps->bind_param("siddiiidddi", 
            $data['datetime'], $data['fix'], $data['lat'], $data['lon'], 
            $data['sats_in_use'], $data['sats_in_view'], $data['hdop'], 
            $data['alt_m'], $data['speed_kmph'], $data['ms']
        );
        $stmt_gps->execute();
        
        // Die ID des gerade eingefügten Datensatzes holen
        $last_data_id = $conn->insert_id;
        $stmt_gps->close();

        // 2. Alle Satelliten für diesen Datenpunkt einfügen
        if (isset($data['satellites']) && is_array($data['satellites'])) {
            
            // --- HIER IST DIE ANPASSUNG ---
            // Das SQL-Statement enthält jetzt die 'system'-Spalte
            $stmt_sat = $conn->prepare("INSERT INTO satellites (data_id, system, sat_id, elevation, azimuth, snr) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($data['satellites'] as $sat) {
                // Das 'bind_param' enthält jetzt 's' (für String) für das System
                $stmt_sat->bind_param("isiiii", 
                    $last_data_id, 
                    $sat['system'], // <-- NEUES DATENFELD
                    $sat['id'], 
                    $sat['elev'], 
                    $sat['azim'], 
                    $sat['snr']
                );
                $stmt_sat->execute();
            }
            $stmt_sat->close();
        }
    }
    
    $conn->commit();
    echo "Daten erfolgreich importiert!";

} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    echo "Fehler beim Importieren: " . $exception->getMessage();
}

$conn->close();
?>
