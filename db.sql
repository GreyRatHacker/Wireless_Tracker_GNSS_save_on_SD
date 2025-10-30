ALTER TABLE satellites
ADD COLUMN system VARCHAR(3) AFTER data_id;



-- Löscht die Tabellen, falls sie schon existieren (für einen sauberen Start)
DROP TABLE IF EXISTS satellites;
DROP TABLE IF EXISTS gps_data;

-- Erstellt die Haupt-Datentabelle
CREATE TABLE gps_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    datetime DATETIME,
    fix BOOLEAN,
    lat DECIMAL(10, 8),
    lon DECIMAL(11, 8),
    sats_in_use INT,
    sats_in_view INT,
    hdop DECIMAL(4, 2),
    alt_m DECIMAL(7, 2),
    speed_kmph DECIMAL(6, 2),
    ms BIGINT
);

-- Erstellt die Satelliten-Tabelle
CREATE TABLE satellites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_id INT,               -- Dies verknüpft den Satelliten mit einem gps_data-Eintrag
    
    -- DAS IST DIE NEUE SPALTE --
    system VARCHAR(3),         -- Speichert "GP", "GL", "GA", "BD", "QZ"
    
    sat_id INT,                -- Die ID-Nummer des Satelliten (z.B. 14)
    elevation INT,
    azimuth INT,
    snr INT,
    FOREIGN KEY (data_id) REFERENCES gps_data(id) ON DELETE CASCADE
);
