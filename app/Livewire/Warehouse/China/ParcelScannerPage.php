<?php

namespace App\Livewire\Warehouse\China;

use App\Models\Admin;
use App\Models\OrderException;
use App\Models\OrderStatus;
use App\Services\Order\OrderStatusService;
use App\Services\Warehouse\OrderExceptionService;
use App\Services\Warehouse\ParcelScannerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::china-warehouse', ['title' => 'China Parcel Scanner'])]
class ParcelScannerPage extends Component
{
    public string $targetStatusCode = OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE;

    public string $scanInput = '';

    /** @var list<array<string, mixed>> */
    public array $scanHistory = [];

    public ?array $lastScanResult = null;

    public ?string $lastUnidentifiedCode = null;

    public function scanFromInput(ParcelScannerService $scannerService): void
    {
        $code = trim($this->scanInput);
        $this->scanInput = '';

        if ($code === '') {
            return;
        }

        $this->processScan($code, $scannerService);
        $this->dispatch('scanner-refocus');
    }

    public function scanFromCamera(string $decodedText, ParcelScannerService $scannerService): void
    {
        $code = trim($decodedText);
        if ($code === '') {
            return;
        }

        $this->processScan($code, $scannerService);
    }

    public function flagUnidentifiedAsException(
        string $type,
        OrderExceptionService $exceptionService,
    ): void {
        if (! $this->lastUnidentifiedCode) {
            return;
        }

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $exception = $exceptionService->reportException([
            'type' => $type,
            'origin_panel' => 'china',
            'scanned_code' => $this->lastUnidentifiedCode,
            'summary' => __('admin.exception_type_'.$type).' ('.$this->lastUnidentifiedCode.')',
        ], $admin);

        $this->lastUnidentifiedCode = null;

        flash()->warning(__('admin.exception_logged_success', ['id' => $exception->id]));
        $this->dispatch('scanner-refocus');
    }

    public function clearHistory(): void
    {
        $this->scanHistory = [];
        $this->lastScanResult = null;
        $this->lastUnidentifiedCode = null;
        $this->resetErrorBag();
        $this->dispatch('scanner-refocus');
    }

    protected function processScan(string $rawPayload, ParcelScannerService $scannerService): void
    {
        $this->resetErrorBag();

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        try {
            $result = $scannerService->scanAndUpdateStatus(
                $admin,
                $rawPayload,
                $this->targetStatusCode,
                'china'
            );

            $this->lastScanResult = $result;
            $this->lastUnidentifiedCode = null;
            array_unshift($this->scanHistory, $result);
            $this->scanHistory = array_slice($this->scanHistory, 0, 50);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?? $exception->getMessage();
            $normalized = $scannerService->normalizePayload($rawPayload);

            $errorEntry = [
                'success' => false,
                'scanned_code' => $normalized ?: $rawPayload,
                'error_message' => $message,
                'scanned_at' => now()->format('H:i:s'),
            ];

            $this->lastScanResult = $errorEntry;
            $this->lastUnidentifiedCode = $normalized ?: $rawPayload;
            array_unshift($this->scanHistory, $errorEntry);
            $this->scanHistory = array_slice($this->scanHistory, 0, 50);

            $this->setErrorBag($exception->validator->getMessageBag());
        }
    }

    public function render(OrderStatusService $orderStatusService)
    {
        return view('livewire.warehouse.china.parcel-scanner-page', [
            'statusOptions' => $orderStatusService->listActiveForWarehouseUpdate(),
            'panel' => 'china',
            'orderShowRoute' => 'china.orders.show',
            'exceptionsRoute' => 'china.exceptions.index',
            'exceptionTypes' => [
                OrderException::TYPE_UNIDENTIFIED_SHIPMENT,
                OrderException::TYPE_MISSING_BARCODE,
                OrderException::TYPE_DAMAGED_PARCEL,
            ],
        ])->title(__('admin.parcel_barcode_scanner'));
    }
}
