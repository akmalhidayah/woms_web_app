<?php

namespace App\Services\Orders;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Models\Order;

class OrderDocumentOverviewService
{
    /**
     * Build the document overview for an order.
     *
     * @return array<int, array<string, mixed>>
     */
    public function build(Order $order): array
    {
        $documentsByType = $order->documents
            ->keyBy(fn ($document) => $document->jenis_dokumen->value);

        $overview = [];

        foreach (OrderDocumentType::required() as $type) {
            $document = $documentsByType->get($type->value);

            $overview[] = [
                'value' => $type->value,
                'label' => $type->label(),
                'is_uploaded' => (bool) $document,
                'document' => $document,
            ];
        }

        return $overview;
    }
}
