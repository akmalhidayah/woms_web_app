# Data Import Inventory

Folder ini disiapkan untuk file hasil ekspor Google Spreadsheet/AppSheet yang akan diimpor ke modul Inventory pada tahap berikutnya.

Belum ada importer yang dijalankan pada tahap fondasi database ini. Jangan menyimpan credential, link privat, atau file produksi yang mengandung data sensitif ke repository.

## Format sumber yang direncanakan

File dapat berupa CSV. XLSX dapat digunakan setelah project mempunyai pembaca Excel yang telah disetujui. Tidak ada dependency Composer baru yang ditambahkan pada tahap ini.

### Master barang

| Kolom sumber | Target |
|---|---|
| `UID` | `inventory_items.uid` |
| `LOC ID` | `inventory_locations.code` |
| `IMG` | `inventory_items.legacy_image_path` |
| `TYPE CATEGORY` | `inventory_items.type_category` |
| `DESC.` | `inventory_items.name` |
| `SIZE` | `inventory_items.size` |
| `SPARE STOCK` | `inventory_items.current_stock` |
| `STN` | `inventory_items.unit` |
| `CATEGORY` | `inventory_categories.name` |
| `SUB CATEGORY` | `inventory_subcategories.name` |
| `LOC` | `inventory_locations.name` |
| `INPUT BY` | metadata legacy saat import |
| `INPUT DATE` | metadata legacy saat import |

### History transaksi

| Kolom sumber | Target |
|---|---|
| `UID` | pencarian `inventory_items.uid` |
| `DESC.` | `inventory_transactions.item_name_snapshot` |
| `CATEGORY` | `legacy_payload.category` |
| `INPUT TYPE` | `inventory_transactions.transaction_type` |
| `QTY` | `inventory_transactions.quantity` |
| `TUJUAN PENGGUNAAN` | `inventory_transactions.purpose` |
| `JENIS PERMINTAAN` | `inventory_request_types` atau `legacy_payload` |
| `POTO ALAT YANG RUSAK` | attachment `damaged_item_photo` |
| `POTO ALAT YANG BARU` | attachment `new_item_photo` |
| `INPUT DATE` | `inventory_transactions.transaction_at` |
| `INPUT BY` | `inventory_user_id` atau `legacy_payload` |
| Kolom peminjaman lainnya | `inventory_transactions.legacy_payload` |

## Aturan importer berikutnya

- Gunakan `updateOrCreate` atau `upsert` berdasarkan `UID` untuk master barang.
- Gunakan `legacy_id` yang stabil untuk history agar import aman dijalankan ulang.
- Simpan kolom AppSheet yang belum dimodelkan ke `legacy_payload`.
- Jalankan import dalam batch/chunk dan database transaction.
- Jangan mengubah atau menghapus history yang sudah ada.
- Jangan membuat flow peminjaman sebelum aturan bisnisnya ditetapkan.
