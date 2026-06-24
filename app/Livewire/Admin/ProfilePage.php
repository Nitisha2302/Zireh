<?php

namespace App\Livewire\Admin;

use App\Models\LoginLog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Profile'])]
class ProfilePage extends Component
{
    public function render()
    {
        $admin = auth()->guard('admin')->user();

        return view('livewire.admin.profile-page', [
            'admin' => $admin,
            'currentSessions' => LoginLog::query()
                ->whereMorphedTo('authenticatable', $admin)
                ->where('guard', 'admin')
                ->where('successful', true)
                ->whereNull('logout_at')
                ->latest('last_seen_at')
                ->get(),
            'loginLogs' => LoginLog::query()
                ->whereMorphedTo('authenticatable', $admin)
                ->where('guard', 'admin')
                ->latest('login_at')
                ->limit(20)
                ->get(),
        ])->title(__('admin.profile'));
    }
}
