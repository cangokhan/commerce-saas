# Commerce SaaS - E-Ticaret SaaS API Backend

Laravel ile geliştirilmiş, modüler yapıda, multi-tenant e-ticaret SaaS API projesi.

## 🚀 Özellikler

- ✅ **Modüler Yapı**: Auth, Store, Product, Order, Import modülleri
- ✅ **Multi-Tenant**: Single-database tenant_id ile çalışır
- ✅ **3 Kullanıcı Tipi**: SuperAdmin, Vendor (Mağaza), Customer
- ✅ **Laravel Sanctum**: API authentication
- ✅ **Spatie Permission**: Role ve permission yönetimi
- ✅ **XML Import**: Dış servisten otomatik ürün çekme
- ✅ **Queue System**: Background job processing
- ✅ **Scheduled Tasks**: Periyodik görevler (cron)

## 📋 Kurulum Seçenekleri

### 1. Docker ile (Geliştirme/Production)
- [DOCKER_SETUP.md](DOCKER_SETUP.md) - Docker kurulum kılavuzu
- [PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md) - Production deployment
- [QUICK_DEPLOY.md](QUICK_DEPLOY.md) - Hızlı deployment

### 2. cPanel ile (SSH Olmadan)
- [CPANEL_DEPLOYMENT.md](CPANEL_DEPLOYMENT.md) - Detaylı cPanel kılavuzu
- [CPANEL_QUICK_START.md](CPANEL_QUICK_START.md) - Hızlı başlangıç
- [CPANEL_SETUP_STEPS.txt](CPANEL_SETUP_STEPS.txt) - Adım adım liste

## 🏗️ Proje Yapısı

```
app/Modules/
├── Auth/          # Authentication modülü
├── Store/         # Mağaza yönetimi
├── Product/       # Ürün yönetimi
├── Order/         # Sipariş yönetimi
└── Import/        # XML import sistemi
```

## 📚 Dokümantasyon

- [Adım 1: Docker Kurulumu](DOCKER_SETUP.md)
- [Adım 2: Modüler Yapı](STEP2_MODULAR_SETUP.md)
- [Adım 3: Auth Modülü](STEP3_AUTH_MODULE.md)
- [Adım 4: Store Modülü](STEP4_STORE_MODULE.md)
- [Adım 5: Product Modülü](STEP5_PRODUCT_MODULE.md)
- [Adım 6: Import Modülü](STEP6_IMPORT_MODULE.md)

## 🔧 Hızlı Başlangıç

### Docker ile:
```bash
docker-compose up -d --build
docker-compose exec app composer install
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

### cPanel ile:
1. Dosyaları `public_html/`'e yükle
2. `.env` dosyasını oluştur
3. Database oluştur
4. Composer install
5. `php artisan migrate`
6. Cron job'ları ayarla

## 🔐 Varsayılan Kullanıcı

**SuperAdmin:**
- Email: `admin@commerce-saas.com`
- Password: `password`

## 📡 API Endpoints

### Auth
- `POST /api/auth/register` - Kayıt
- `POST /api/auth/login` - Giriş
- `POST /api/auth/logout` - Çıkış
- `GET /api/auth/me` - Kullanıcı bilgileri

### Stores
- `GET /api/stores` - Store listesi
- `POST /api/stores` - Store oluştur
- `GET /api/stores/{id}` - Store detayı
- `PUT /api/stores/{id}` - Store güncelle
- `DELETE /api/stores/{id}` - Store sil

### Products
- `GET /api/products` - Product listesi
- `POST /api/products` - Product oluştur
- `GET /api/products/{id}` - Product detayı
- `PUT /api/products/{id}` - Product güncelle
- `DELETE /api/products/{id}` - Product sil

### Categories
- `GET /api/categories` - Category listesi
- `POST /api/categories` - Category oluştur

### Import
- `POST /api/import/xml` - XML import tetikle

## 🔄 XML Import

### Otomatik (Scheduled)
`.env` dosyasında `XML_IMPORT_URL` set edildiğinde, her saat başı otomatik çalışır.

### Manuel
```bash
php artisan import:products-xml --url=https://example.com/products.xml
```

## 📝 Notlar

- Production'da `APP_DEBUG=false` olmalı
- SSL sertifikası mutlaka kurulmalı
- Queue worker çalışmalı
- Cron job'lar ayarlanmalı

## 📞 Destek

Sorunlar için dokümantasyon dosyalarına bakın:
- Docker: `DOCKER_SETUP.md`
- cPanel: `CPANEL_DEPLOYMENT.md`
- Production: `PRODUCTION_DEPLOYMENT.md`
