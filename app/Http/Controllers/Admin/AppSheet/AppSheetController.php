<?php

namespace App\Http\Controllers\Admin\AppSheet;

use App\Exceptions\AppSheet\GoogleOAuthException;
use App\Http\Controllers\Controller;
use App\Services\AppSheet\GoogleOAuthService;
use Illuminate\View\View;

class AppSheetController extends Controller
{
    public function historyConsumable(GoogleOAuthService $google): View
    {
        return view('admin.appsheet.history-consumable', $this->connectionStatus($google));
    }

    public function stockConsumable(GoogleOAuthService $google): View
    {
        return view('admin.appsheet.stock-consumable', $this->connectionStatus($google));
    }

    private function connectionStatus(GoogleOAuthService $google): array
    {
        try {
            return ['googleConnected' => $google->isConnected(), 'googleConnectionError' => null];
        } catch (GoogleOAuthException $exception) {
            return ['googleConnected' => false, 'googleConnectionError' => $exception->getMessage()];
        }
    }
}
