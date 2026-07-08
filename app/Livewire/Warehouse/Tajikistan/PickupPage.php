<?php

namespace App\Livewire\Warehouse\Tajikistan;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Services\Order\OrderPickupService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::tajikistan-warehouse', ['title' => 'Order Pickup'])]
class PickupPage extends Component
{
    public string $pickupToken = '';

    public ?CustomerOrder $order = null;

    public function mount(?string $token = null): void
    {
        if ($token) {
            $this->pickupToken = $this->normalizePickupPayload($token);
            $this->lookupOrder(app(OrderPickupService::class));
        }
    }

    public function scanPickupQr(string $payload, OrderPickupService $pickupService): void
    {
        $this->pickupToken = $this->normalizePickupPayload($payload);
        $this->lookupOrder($pickupService);
        $this->dispatch('pickup-qr-scanned');
    }

    public function lookupOrder(OrderPickupService $pickupService): void
    {
        $this->pickupToken = $this->normalizePickupPayload($this->pickupToken);

        $this->validate([
            'pickupToken' => ['required', 'string', 'max:100'],
        ]);

        try {
            /** @var Admin $admin */
            $admin = Auth::guard('admin')->user();
            $this->order = $pickupService->findByPickupToken($this->pickupToken, $admin);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->order = null;
            $this->setErrorBag($exception->validator->getMessageBag());
        }
    }

    public function markPaymentReceived(OrderPickupService $pickupService): void
    {
        if (! $this->order) {
            return;
        }

        try {
            /** @var Admin $admin */
            $admin = Auth::guard('admin')->user();
            $this->order = $pickupService->markPaymentReceived($this->order, $admin);
            flash()->success(__('admin.pickup_payment_marked_paid'));
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
        }
    }

    public function deliverOrder(OrderPickupService $pickupService): void
    {
        if (! $this->order) {
            return;
        }

        try {
            /** @var Admin $admin */
            $admin = Auth::guard('admin')->user();
            $this->order = $pickupService->deliver($this->order, $admin);
            flash()->success(__('admin.pickup_delivered'));
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
        }
    }

    public function render()
    {
        return view('livewire.warehouse.tajikistan.pickup-page')
            ->title(__('admin.pickup_qr_scan'));
    }

    protected function normalizePickupPayload(string $payload): string
    {
        $payload = trim($payload);

        if ($payload === '') {
            return '';
        }

        if (str_starts_with($payload, 'cargo-pickup:')) {
            return substr($payload, strlen('cargo-pickup:'));
        }

        if (preg_match('#/tajikistan/pickup/([^/?]+)#', $payload, $matches) === 1) {
            return $matches[1];
        }

        return $payload;
    }
}
