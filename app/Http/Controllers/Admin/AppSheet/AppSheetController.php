<?php

namespace App\Http\Controllers\Admin\AppSheet;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AppSheetController extends Controller
{
    public function historyConsumable(): View
    {
        return view('admin.appsheet.history-consumable');
    }

    public function stockConsumable(): View
    {
        return view('admin.appsheet.stock-consumable');
    }
}
