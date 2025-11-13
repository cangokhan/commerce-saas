#!/bin/bash

# Production Deployment Script
# Kullanım: ./deploy.sh

set -e

echo "🚀 Starting production deployment..."

# 1. Git pull (eğer git kullanıyorsanız)
# git pull origin main

# 2. Docker compose ile container'ları yeniden build et
echo "📦 Building Docker containers..."
docker-compose -f docker-compose.production.yml build --no-cache

# 3. Container'ları durdur
echo "🛑 Stopping containers..."
docker-compose -f docker-compose.production.yml down

# 4. Container'ları başlat
echo "▶️ Starting containers..."
docker-compose -f docker-compose.production.yml up -d

# 5. Composer install (production)
echo "📥 Installing dependencies..."
docker-compose -f docker-compose.production.yml exec -T app composer install --optimize-autoloader --no-dev

# 6. Migration'ları çalıştır
echo "🗄️ Running migrations..."
docker-compose -f docker-compose.production.yml exec -T app php artisan migrate --force

# 7. Cache'leri temizle
echo "🧹 Clearing caches..."
docker-compose -f docker-compose.production.yml exec -T app php artisan config:clear
docker-compose -f docker-compose.production.yml exec -T app php artisan route:clear
docker-compose -f docker-compose.production.yml exec -T app php artisan view:clear

# 8. Cache'leri yeniden oluştur
echo "⚡ Optimizing application..."
docker-compose -f docker-compose.production.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.production.yml exec -T app php artisan route:cache
docker-compose -f docker-compose.production.yml exec -T app php artisan view:cache
docker-compose -f docker-compose.production.yml exec -T app php artisan event:cache

# 9. Storage link
echo "🔗 Creating storage link..."
docker-compose -f docker-compose.production.yml exec -T app php artisan storage:link || true

# 10. Permissions
echo "🔐 Setting permissions..."
docker-compose -f docker-compose.production.yml exec -T app chmod -R 775 storage bootstrap/cache
docker-compose -f docker-compose.production.yml exec -T app chown -R www-data:www-data storage bootstrap/cache

# 11. Queue worker'ı restart et
echo "🔄 Restarting queue worker..."
docker-compose -f docker-compose.production.yml restart queue

# 12. Scheduler'ı restart et
echo "⏰ Restarting scheduler..."
docker-compose -f docker-compose.production.yml restart scheduler

echo "✅ Deployment completed successfully!"
echo ""
echo "📊 Container status:"
docker-compose -f docker-compose.production.yml ps

echo ""
echo "🔍 Check logs with:"
echo "docker-compose -f docker-compose.production.yml logs -f"

