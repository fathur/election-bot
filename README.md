# Election Bot

Dikarenakan Pemilu 2024 semakin dekat, kami membuat bot
untuk melakukan polling di Twitter untuk mendapatkan 
preferensi data lebih di kalangan wargenet Twitter.

## Kontribusi

Repositori ini dibuat menggunakan Laravel. Untuk 
berkontribusi silahkan `fork` repository ini, dan lakukan perubahan
di repositori hasil fork Anda. Buka __pull request__
ke repositori ini dan tunggu diskusi dan hasil __merge__-nya.

## Instalasi

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