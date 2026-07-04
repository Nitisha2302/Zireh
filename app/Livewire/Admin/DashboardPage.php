<?php

namespace App\Livewire\Admin;

use App\Services\Admin\DashboardService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Dashboard'])]
class DashboardPage extends Component
{
    public function render(DashboardService $dashboardService)
    {
        $overview = $dashboardService->overview();

        return view('livewire.admin.dashboard-page', [
            'admin' => auth()->guard('admin')->user(),
            'stats' => $overview['stats'],
            'ordersByStatus' => $overview['orders_by_status'],
            'ordersByPlatform' => $overview['orders_by_platform'],
            'recentOrders' => $overview['recent_orders'],
            'recentCustomers' => $overview['recent_customers'],
            'recentWalletTransactions' => $overview['recent_wallet_transactions'],
        ])->title(__('admin.dashboard'));
    }
}
