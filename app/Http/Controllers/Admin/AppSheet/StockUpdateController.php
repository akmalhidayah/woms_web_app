<?php

namespace App\Http\Controllers\Admin\AppSheet;

use App\Exceptions\AppSheet\GoogleSheetsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppSheet\UpdateStockRequest;
use App\Services\AppSheet\GoogleSheetsWriter;
use App\Support\AppSheet\StockData;
use App\Support\AppSheet\StockSheetMap;
use Illuminate\Http\RedirectResponse;

class StockUpdateController extends Controller
{
    public function __invoke(
        UpdateStockRequest $request,
        string $stockKind,
        GoogleSheetsWriter $writer,
    ): RedirectResponse {
        $definition = StockSheetMap::definition($stockKind);
        $validated = $request->validated();
        $submittedValues = [];
        $originalValues = [];

        foreach ($definition['editable_fields'] as $requestField => $field) {
            $submittedValues[$requestField] = $validated[$requestField];
            $originalValues[$requestField] = $validated[$field['original']];
        }

        $actor = trim((string) ($request->user()->name ?: $request->user()->email));

        try {
            $result = $writer->updateStock(
                $stockKind,
                $validated['identifier'],
                $submittedValues,
                $originalValues,
                $actor,
            );
        } catch (GoogleSheetsException $exception) {
            return back()->with(
                $exception->requiresReconnect ? 'appsheet_google_error' : 'appsheet_stock_error',
                $exception->getMessage(),
            );
        }

        if (! $result['changed']) {
            return back()->with('appsheet_stock_success', 'Tidak ada perubahan stock yang perlu disimpan.');
        }

        if ($stockKind === StockSheetMap::CONSUMABLE_BMS) {
            $change = $result['changes']['spare_stock'];

            return back()->with('appsheet_stock_success', sprintf(
                'Stock %s berhasil diperbarui dari %s menjadi %s.',
                $result['item_name'],
                StockData::displayQuantity($change['old']),
                StockData::displayQuantity($change['new']),
            ));
        }

        return back()->with('appsheet_stock_success', sprintf(
            'Stock %s berhasil diperbarui.',
            $result['item_name'],
        ));
    }
}
