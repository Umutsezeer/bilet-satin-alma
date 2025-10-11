<?php
// Bu script projenin en başında sadece bir kez çalıştırılacak.

// UUID v4 oluşturmak için bir fonksiyon
function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

echo "<pre style='font-family: monospace; line-height: 1.6;'>";

$db_file = __DIR__ . '/includes/bilet_sistemi.db';
if (file_exists($db_file)) {
    unlink($db_file);
    echo "Mevcut veritabanı dosyası silindi.\n";
}

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Yeni veritabanı dosyası oluşturuldu.\n\n";

    // --- TABLOLARI OLUŞTURMA (TAM LİSTE) ---
    
    $pdo->exec("CREATE TABLE Bus_Company (id TEXT PRIMARY KEY NOT NULL, name TEXT UNIQUE NOT NULL, logo_path TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL)");
    echo "1. 'Bus_Company' tablosu oluşturuldu.\n";

    $pdo->exec("CREATE TABLE Users (id TEXT PRIMARY KEY NOT NULL, full_name TEXT NOT NULL, email TEXT UNIQUE NOT NULL, role TEXT NOT NULL, password TEXT NOT NULL, company_id TEXT, balance REAL DEFAULT 1000.00 NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL, FOREIGN KEY (company_id) REFERENCES Bus_Company(id))");
    echo "2. 'Users' tablosu oluşturuldu.\n";

    $pdo->exec("CREATE TABLE Trips (id TEXT PRIMARY KEY NOT NULL, company_id TEXT NOT NULL, destination_city TEXT NOT NULL, arrival_time DATETIME NOT NULL, departure_time DATETIME NOT NULL, departure_city TEXT NOT NULL, price REAL NOT NULL, capacity INTEGER NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (company_id) REFERENCES Bus_Company(id))");
    echo "3. 'Trips' tablosu oluşturuldu.\n";
    
    $pdo->exec("CREATE TABLE Tickets (id TEXT PRIMARY KEY NOT NULL, trip_id TEXT NOT NULL, user_id TEXT NOT NULL, status TEXT DEFAULT 'active' NOT NULL, total_price REAL NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (trip_id) REFERENCES Trips(id), FOREIGN KEY (user_id) REFERENCES Users(id))");
    echo "4. 'Tickets' tablosu oluşturuldu.\n";

    $pdo->exec("CREATE TABLE Booked_Seats (id TEXT PRIMARY KEY NOT NULL, ticket_id TEXT NOT NULL, seat_number INTEGER NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (ticket_id) REFERENCES Tickets(id))");
    echo "5. 'Booked_Seats' tablosu oluşturuldu.\n";

    $pdo->exec("CREATE TABLE Coupons (id TEXT PRIMARY KEY NOT NULL, code TEXT UNIQUE NOT NULL, discount REAL NOT NULL, company_id TEXT, usage_limit INTEGER NOT NULL, expiring_date DATETIME NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (company_id) REFERENCES Bus_Company(id))");
    echo "6. 'Coupons' tablosu oluşturuldu.\n";
    
    $pdo->exec("CREATE TABLE User_Coupons (id TEXT PRIMARY KEY NOT NULL, coupon_id TEXT NOT NULL, user_id TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (coupon_id) REFERENCES Coupons(id), FOREIGN KEY (user_id) REFERENCES Users(id))");
    echo "7. 'User_Coupons' tablosu oluşturuldu.\n";
    
    echo "\n--- Test Verileri Ekleniyor ---\n";

    // Test Firmaları Oluştur
    $company1_id = generate_uuid();
    $pdo->prepare("INSERT INTO Bus_Company (id, name) VALUES (?, ?)")->execute([$company1_id, 'Siber Vatan Turizm']);
    echo "Test firması eklendi: Siber Vatan Turizm\n";
    
    $company2_id = generate_uuid();
    $pdo->prepare("INSERT INTO Bus_Company (id, name) VALUES (?, ?)")->execute([$company2_id, 'Anadolu Ulaşım']);
    echo "Test firması eklendi: Anadolu Ulaşım\n";

    $trip1_id = generate_uuid();
    $pdo->prepare("INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([$trip1_id, $company1_id, 'İstanbul', 'Ankara', '2025-11-20 10:00:00', '2025-11-20 18:00:00', 650, 40]);
    echo "Test seferi eklendi: İstanbul -> Ankara\n";


    // Test Kullanıcıları Oluştur
    $hashed_password = password_hash('123456', PASSWORD_DEFAULT);
    
    // Admin Kullanıcısı
    $admin_id = generate_uuid();
    $pdo->prepare("INSERT INTO Users (id, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)")->execute([$admin_id, 'Admin User', 'admin@test.com', $hashed_password, 'admin']);
    echo "Test kullanıcısı eklendi: admin@test.com (Rol: admin)\n";

    // Firma Admin Kullanıcısı (Siber Vatan Turizm'e bağlı)
    $company_admin_id = generate_uuid();
    $pdo->prepare("INSERT INTO Users (id, full_name, email, password, role, company_id) VALUES (?, ?, ?, ?, ?, ?)")->execute([$company_admin_id, 'Firma Admini', 'firma@test.com', $hashed_password, 'company_admin', $company1_id]);
    echo "Test kullanıcısı eklendi: firma@test.com (Rol: company_admin)\n";

    // Normal Yolcu Kullanıcısı
    $user_id = generate_uuid();
    $pdo->prepare("INSERT INTO Users (id, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)")->execute([$user_id, 'Yolcu User', 'user@test.com', $hashed_password, 'user']);
    echo "Test kullanıcısı eklendi: user@test.com (Rol: user)\n";

    echo "\n-------------------------------------\n";
    echo "VERİTABANI KURULUMU BAŞARIYLA TAMAMLANDI!\n";
    echo "-------------------------------------\n";

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

echo "</pre>";
?>