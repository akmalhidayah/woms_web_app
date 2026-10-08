<?php

namespace App\Http\Controllers\Admin\AppSheet;

use App\Exceptions\AppSheet\GoogleSheetsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppSheet\StoreConsumableTransactionRequest;
use App\Services\AppSheet\GoogleSheetsWriter;
use App\Support\AppSheet\StockData;
use Illuminate\Http\RedirectResponse;

class ConsumableTransactionController extends Controller
{
    public function store(
        StoreConsumableTransactionRequest $request,
        GoogleSheetsWriter $writer,
    ): RedirectResponse {
        $validated = $request->validated();
        $actor = trim((string) ($request->user()->name ?: $request->user()->email));

        try {
            $result = $writer->createConsumableTransaction(
                $validated['uid'],
                $validated['input_type'],
                $validated['quantity'],
                $validated['usage_purpose'] ?? null,
                $validated['request_type'] ?? null,
                $actor,
                $validated['transaction_token'],
            );
        } catch (GoogleSheetsException $exception) {
            return back()->with(
                $exception->requiresReconnect ? 'appsheet_google_error' : 'appsheet_transaction_error',
                $exception->getMessage(),
            );
        }

        return back()->with('appsheet_transaction_success', sprintf(
            '%s %s sebanyak %s %s berhasil ditambahkan. Stok sekarang %s %s.',
            $result['input_type'],
            $result['item_name'],
            StockData::displayQuantity($result['quantity']),
            $result['unit'],
            StockData::displayQuantity($result['stock_after']),
            $result['unit'],
        ));
    }
}
