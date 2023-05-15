# Election Bot

Dikarenakan Pemilu 2024 semakin dekat, kami membuat bot
untuk melakukan polling di Twitter untuk mendapatkan 
preferensi data lebih di kalangan wargenet Twitter.

## Kontribusi

Repositori ini dibuat menggunakan Laravel.

```
composer install
php artisan migrate
```

Untuk menjalankan poll ketikkan perintah berikut

```
# Menjalankan poll di official akun para kandidat
php artisan poll --target=candidate

# Menjalankan poll di media mainstream
php artisan poll --target=media
```