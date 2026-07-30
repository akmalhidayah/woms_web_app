# Inventory Stock Service

`App\Services\Inventory\InventoryStockService` adalah satu-satunya pintu perubahan
`inventory_items.current_stock`. Service tidak bergantung pada HTTP request,
controller, autentikasi global, atau UI sehingga dapat dipakai oleh admin WOMS,
API Flutter, command, seeder, dan importer.

## Jenis transaksi

- `opening_balance`: menetapkan saldo awal barang yang masih memiliki stok nol dan
  belum mempunyai history.
- `stock_in`: menambah stok oleh admin WOMS.
- `stock_out`: mengurangi stok oleh admin WOMS atau user Inventory Flutter.
- `adjustment_in`: koreksi penambahan stok oleh admin WOMS.
- `adjustment_out`: koreksi pengurangan stok oleh admin WOMS.

Setiap operasi memperbarui stok dan membuat history dalam satu
`DB::transaction()`. Barang diambil ulang menggunakan `lockForUpdate()`. Dengan
demikian, request paralel pada database yang mendukung row lock tidak membaca
stok lama secara bersamaan. Stok keluar dan adjustment keluar tidak boleh
menghasilkan nilai negatif.

Test project memakai SQLite in-memory. Test tersebut memverifikasi rollback dan
keberadaan pemanggilan locking pada service, tetapi SQLite tidak dapat
mensimulasikan row-level concurrency MySQL secara penuh. Perlindungan request
paralel bergantung pada `lockForUpdate()` saat service dijalankan pada MySQL
production.

## Actor

- `App\Models\User` dengan role admin mengisi `woms_user_id`; sumber transaksi
  menjadi `woms_admin`.
- `App\Models\Inventory\InventoryUser` aktif mengisi `inventory_user_id`; sumber
  transaksi menjadi `flutter`.
- Opening balance tanpa actor dapat memakai sumber `system`, `seeder`, atau
  `import`.

Kedua kolom actor tidak pernah diisi bersamaan. Actor selalu diberikan langsung
ke service; service tidak memanggil `auth()`.

## Presisi stok

Nilai `decimal(15,3)` dihitung sebagai integer milli-unit. Sebagai contoh,
`1.250` diubah menjadi `1250`, ditambah `2.500` sebagai `2500`, lalu disimpan
kembali sebagai `3.750`. Input dengan lebih dari tiga angka desimal ditolak dan
hasil di atas `999999999999.999` ditolak.

## Contoh

Stock in oleh admin WOMS:

```php
$transaction = app(InventoryStockService::class)->stockIn(
    item: $item,
    quantity: '5.250',
    actor: $admin,
    context: ['reference_number' => 'GR-001'],
);
```

Stock out oleh user Flutter:

```php
$transaction = app(InventoryStockService::class)->stockOut(
    item: $item,
    quantity: '1.000',
    actor: $inventoryUser,
    context: [
        'inventory_request_type_id' => $requestType->id,
        'purpose' => 'Pemeliharaan harian',
    ],
);
```

Koreksi stok tidak dilakukan dengan mengedit history lama. Gunakan
`adjustmentIn()` atau `adjustmentOut()` dengan alasan yang wajib diisi. Service
tidak menyediakan update atau delete transaksi.

Tahap ini belum menyediakan controller, API, route, upload attachment, halaman
Blade, menu admin, maupun autentikasi Flutter.
