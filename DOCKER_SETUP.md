# Docker Kurulum ve Kullanım Kılavuzu

## Adım 1: Proje Başlatma

### Gereksinimler
- Docker Desktop (Windows/Mac) veya Docker + Docker Compose (Linux)
- Git

### Docker Container'ları Başlatma

```bash
# Proje kök dizininde (docker-compose.yml'in olduğu yerde)
docker-compose up -d --build
```

Bu komut şunları yapacak:
- PHP-FPM container'ını oluşturur ve başlatır
- Nginx container'ını başlatır (port 8080)
- MySQL container'ını başlatır (port 3306)
- Redis container'ını başlatır (port 6379)

### Container'ların Durumunu Kontrol Etme

```bash
docker-compose ps
```

### Container Loglarını Görüntüleme

```bash
# Tüm container'ların logları
docker-compose logs -f

# Sadece app (PHP-FPM) logları
docker-compose logs -f app

# Sadece nginx logları
docker-compose logs -f nginx

# Sadece mysql logları
docker-compose logs -f mysql
```

## Adım 2: Laravel Bağımlılıklarını Yükleme

```bash
# Composer bağımlılıklarını yükle
docker-compose exec app composer install

# Veya eğer composer.json değiştiyse
docker-compose exec app composer update
```

## Adım 3: .env Dosyasını Kontrol Etme

`.env` dosyası Docker için yapılandırılmış durumda:
- `DB_HOST=mysql` (Docker container adı)
- `DB_DATABASE=commerce_saas`
- `DB_USERNAME=commerce_saas`
- `DB_PASSWORD=root`
- `REDIS_HOST=redis` (Docker container adı)
- `APP_URL=http://localhost:8080`

## Adım 4: Application Key Oluşturma

```bash
docker-compose exec app php artisan key:generate
```

## Adım 5: Veritabanı Migration'ları Çalıştırma

```bash
# Migration'ları çalıştır
docker-compose exec app php artisan migrate

# Veya fresh migration (veritabanını sıfırlayıp yeniden oluşturur)
docker-compose exec app php artisan migrate:fresh

# Migration ile birlikte seeder'ları çalıştır
docker-compose exec app php artisan migrate:fresh --seed
```

## Adım 6: Storage Link Oluşturma

```bash
docker-compose exec app php artisan storage:link
```

## Adım 7: Cache Temizleme

```bash
# Config cache temizle
docker-compose exec app php artisan config:clear

# Route cache temizle
docker-compose exec app php artisan route:clear

# View cache temizle
docker-compose exec app php artisan view:clear

# Tüm cache'leri temizle
docker-compose exec app php artisan cache:clear
```

## Kullanışlı Komutlar

### Container İçine Giriş Yapma

```bash
# PHP-FPM container'ına giriş
docker-compose exec app bash

# MySQL container'ına giriş
docker-compose exec mysql bash

# MySQL'e bağlanma
docker-compose exec mysql mysql -u commerce_saas -proot commerce_saas
```

### Artisan Komutlarını Çalıştırma

```bash
# Herhangi bir artisan komutu
docker-compose exec app php artisan [komut]

# Örnek: Route listesi
docker-compose exec app php artisan route:list

# Örnek: Tinker
docker-compose exec app php artisan tinker
```

### Container'ları Durdurma

```bash
# Container'ları durdur (veriler korunur)
docker-compose stop

# Container'ları durdur ve sil (veriler korunur - volume'lar silinmez)
docker-compose down

# Container'ları durdur, sil ve volume'ları da sil (DİKKAT: Veritabanı verileri silinir!)
docker-compose down -v
```

### Container'ları Yeniden Başlatma

```bash
docker-compose restart
```

## Olası Hatalar ve Çözümleri

### 1. Port Zaten Kullanımda Hatası

**Hata:** `Bind for 0.0.0.0:8080 failed: port is already allocated`

**Çözüm:** 
- Port 8080'i kullanan başka bir uygulama var. `docker-compose.yml` dosyasındaki port numarasını değiştirin:
  ```yaml
  ports:
    - "8081:80"  # 8080 yerine 8081 kullan
  ```

### 2. Permission Denied Hatası

**Hata:** `Permission denied` veya dosya yazma hatası

**Çözüm:**
```bash
# Storage ve bootstrap/cache klasörlerine yazma izni ver
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### 3. MySQL Bağlantı Hatası

**Hata:** `SQLSTATE[HY000] [2002] Connection refused`

**Çözüm:**
- MySQL container'ının başladığından emin olun: `docker-compose ps`
- Birkaç saniye bekleyin (MySQL başlaması zaman alabilir)
- `.env` dosyasında `DB_HOST=mysql` olduğundan emin olun (localhost değil!)

### 4. Composer Install Hatası

**Hata:** `Composer dependencies installation failed`

**Çözüm:**
```bash
# Composer cache'i temizle
docker-compose exec app composer clear-cache
docker-compose exec app composer install --no-cache
```

### 5. APP_KEY Eksik Hatası

**Hata:** `No application encryption key has been specified`

**Çözüm:**
```bash
docker-compose exec app php artisan key:generate
```

### 6. Redis Bağlantı Hatası

**Hata:** `Connection to Redis failed`

**Çözüm:**
- Redis container'ının çalıştığından emin olun: `docker-compose ps`
- `.env` dosyasında `REDIS_HOST=redis` olduğundan emin olun

### 7. Nginx 502 Bad Gateway

**Hata:** `502 Bad Gateway` hatası alıyorsunuz

**Çözüm:**
- PHP-FPM container'ının çalıştığından emin olun: `docker-compose ps`
- Container'ları yeniden başlatın: `docker-compose restart`
- Logları kontrol edin: `docker-compose logs nginx` ve `docker-compose logs app`

## Veritabanı Yedekleme ve Geri Yükleme

### Yedekleme

```bash
# Veritabanını yedekle
docker-compose exec mysql mysqldump -u commerce_saas -proot commerce_saas > backup.sql
```

### Geri Yükleme

```bash
# Yedekten geri yükle
docker-compose exec -T mysql mysql -u commerce_saas -proot commerce_saas < backup.sql
```

## Geliştirme İpuçları

1. **Hot Reload:** Laravel dosyalarınız otomatik olarak container içinde güncellenir (volume mount sayesinde)

2. **Log Görüntüleme:** 
   ```bash
   docker-compose exec app tail -f storage/logs/laravel.log
   ```

3. **Xdebug:** Gerekirse `docker/php/php.ini` dosyasına Xdebug ayarları eklenebilir

4. **Performance:** Production ortamında cache'leri aktif edin:
   ```bash
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```

## Sonraki Adımlar

1. ✅ Docker yapılandırması tamamlandı
2. ⏭️ Modüler yapı kurulumu (app/Modules)
3. ⏭️ Laravel Sanctum kurulumu
4. ⏭️ Spatie Permission kurulumu
5. ⏭️ Multi-tenant yapı (tenant_id)
6. ⏭️ Auth, Store, Product, Order, Import modülleri

