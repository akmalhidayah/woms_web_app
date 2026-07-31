# Inventory Admin dan Stok Integer

Seluruh stok operasional Inventory menggunakan bilangan bulat. Request API menerima `quantity`
berupa integer JSON atau string digit seperti `"10"`; nilai decimal, notasi ilmiah, nol, dan
nilai negatif ditolak.

Data legacy dengan unit KG dikonversi menjadi GRAM tanpa float (`942.400 KG` menjadi
`942400 GRAM`). Nilai mentah sumber tetap dipertahankan di `legacy_payload`.

Perubahan stok hanya dilakukan melalui `InventoryStockService` dengan database transaction dan
`lockForUpdate()`. CRUD barang tidak menerima perubahan `current_stock`; opening stock, stock in,
stock out, dan adjustment selalu menghasilkan transaksi audit.

Attachment permintaan Flutter tetap private. User Flutter hanya dapat melihat attachment miliknya,
sedangkan admin Inventory menggunakan route admin terotorisasi.

## TODO

Flow peminjaman dan pengembalian equipment belum diimplementasikan. Permintaan equipment tetap
dicatat sebagai `stock_out` sampai modul loan terpisah tersedia.
