#!/bin/bash

# Lokal Geliştirme Sunucusu Başlatıcı
PORT=8000

echo "=========================================================="
echo "🚀 Lokal Test Sunucusu Başlatılıyor..."
echo "🌐 Ana Sayfa:        http://localhost:${PORT}"
echo "   Türkçe:           http://localhost:${PORT}/tr/"
echo "🔐 Yönetim Paneli:   http://localhost:${PORT}/admin/"
echo "   (İlk açılışta kurulum sihirbazı yönetici hesabını oluşturur)"
echo "=========================================================="
echo "Durdurmak için Ctrl+C tuşlarına basabilirsiniz."
echo ""

php -S localhost:${PORT} router.php
