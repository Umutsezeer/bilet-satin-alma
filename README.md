## Projeyi Çalıştırma (Docker ile)
Bu proje, tüm bağımlılıklarıyla birlikte paketlenmiştir. Çalıştırmak için sadece **Docker Desktop** gereklidir.

**1. Veritabanını Kurun (İlk Seferlik)**
Projenin test verilerini (admin, firma, yolcu hesapları) oluşturması için bu script'i bir kez çalıştırmalısınız.
```bash
php setup_database.php
