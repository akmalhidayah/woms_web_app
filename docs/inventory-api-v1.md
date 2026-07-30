# Inventory API v1

Base path:

```text
/api/v1/inventory
```

Endpoint selain login menggunakan Bearer token milik `InventoryUser` dengan
ability `inventory-mobile`.

```http
Accept: application/json
Authorization: Bearer {token}
```

Contoh base URL di bawah menggunakan placeholder
`https://example.com/api/v1/inventory`.

## Authentication

### Login

```bash
curl -X POST https://example.com/api/v1/inventory/auth/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.test","password":"secret","device_name":"Android"}'
```

Login mengembalikan plaintext token satu kali. Login pada nama perangkat yang
sama mengganti token perangkat lama. Maksimal lima kegagalan login per kombinasi
email dan alamat IP dalam 60 detik.

### Profil dan password

```text
GET  /auth/me
POST /auth/change-password
```

Payload perubahan password:

```json
{
  "current_password": "password-lama",
  "password": "password-baru",
  "password_confirmation": "password-baru"
}
```

User dengan `must_change_password=true` hanya dapat mengakses profil, perubahan
password, dan logout. Setelah password berubah, token perangkat lain dihapus dan
token saat ini tetap aktif.

### Logout

```text
POST /auth/logout
POST /auth/logout-all
```

Logout menghapus token perangkat saat ini. Logout all menghapus seluruh token
user.

## Dashboard

```text
GET /dashboard
```

Dashboard berisi jumlah jenis barang tersedia, ringkasan permintaan bulan
berjalan milik user, lima transaksi terakhir, maksimal lima stok rendah, dan
waktu server.

## Catalog

```text
GET /catalogs/categories
GET /catalogs/subcategories?category_id=1
GET /catalogs/locations
GET /catalogs/request-types
```

Hanya master aktif yang dikembalikan.

## Barang

```text
GET /items
GET /items/{id}
GET /items/{id}/image
```

Filter daftar barang:

- `search`
- `item_type=consumable|equipment`
- `category_id`
- `subcategory_id`
- `location_id`
- `stock_status=available|low|out`
- `sort=name|uid|current_stock|newest`
- `page`
- `per_page` dengan nilai maksimal 50

`image_url` menunjuk endpoint terautentikasi. Path storage dan
`legacy_image_path` tidak dikirim ke client.

## Permintaan barang

```bash
curl -X POST https://example.com/api/v1/inventory/requests \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer {token}' \
  -F 'client_request_id=11111111-2222-3333-4444-555555555555' \
  -F 'inventory_item_id=1' \
  -F 'inventory_request_type_id=1' \
  -F 'quantity=1.000' \
  -F 'purpose=Keperluan operasional' \
  -F 'damaged_item_photo=@/path/photo.jpg'
```

File yang diterima adalah JPG, JPEG, PNG, atau WebP maksimal 5 MB per file.
`supporting_photos` maksimal tiga file. File disimpan pada disk privat.

`client_request_id` wajib berupa UUID. Server menyimpan
`MOBILE:{client_request_id}` dan unique index per user. Retry dengan payload sama
mengembalikan transaksi lama tanpa mengurangi stok kembali. UUID sama dengan
item, quantity, jenis permintaan, atau purpose berbeda menghasilkan HTTP 409.

Response permintaan baru memakai HTTP 201:

```json
{
  "success": true,
  "message": "Permintaan berhasil dicatat.",
  "data": {
    "transaction": {},
    "remaining_stock": "2.000",
    "idempotent_replay": false
  }
}
```

## History dan attachment

```text
GET /my-history
GET /my-history/{id}
GET /attachments/{id}
```

Filter history:

- `search`
- `item_type`
- `request_type_id`
- `date_from=YYYY-MM-DD`
- `date_to=YYYY-MM-DD`
- `page`
- `per_page`, maksimal 50

User hanya dapat melihat transaksi dan attachment miliknya. Resource milik user
lain dikembalikan sebagai 404. JSON tidak mengekspos actor ID, legacy payload,
atau path storage.

## Format response

Sukses:

```json
{
  "success": true,
  "message": "Data berhasil diambil.",
  "data": {}
}
```

Validasi:

```json
{
  "success": false,
  "message": "Data yang diberikan tidak valid.",
  "errors": {
    "field": ["Pesan validasi."]
  }
}
```

Stok tidak cukup:

```json
{
  "success": false,
  "message": "Stok tidak mencukupi. Stok tersedia hanya 2.000 EA.",
  "errors": {
    "quantity": ["Jumlah yang diminta melebihi stok tersedia."]
  }
}
```

Status utama:

- `200`: berhasil atau idempotent replay
- `201`: permintaan baru berhasil
- `401`: token/login tidak valid
- `403`: akses ditolak atau password awal belum diganti
- `404`: resource tidak ditemukan/tidak dimiliki user
- `409`: stok tidak cukup atau konflik idempotency
- `422`: validasi gagal
- `429`: rate limit
- `500`: kegagalan server tanpa detail internal

Token dan password tidak boleh disimpan di log. Production wajib memakai HTTPS.
API ini tidak menyediakan stock in, adjustment, halaman admin, atau UI Flutter.
