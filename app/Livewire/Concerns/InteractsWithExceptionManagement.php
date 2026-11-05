<?php

namespace App\Livewire\Concerns;

use App\Models\Admin;
use App\Models\OrderException;
use App\Services\Warehouse\OrderExceptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\WithPagination;

trait InteractsWithExceptionManagement
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    public string $newType = OrderException::TYPE_UNIDENTIFIED_SHIPMENT;

    public string $newOrderIdentifier = '';

    public string $newScannedCode = '';

    public string $newSummary = '';

    public string $newDetails = '';

    public ?int $editingExceptionId = null;

    public string $editStatus = OrderException::STATUS_IN_REVIEW;

    public string $editResolutionNotes = '';

    public string $editLinkOrderIdentifier = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function createException(OrderExceptionService $exceptionService): void
    {
        $this->validate([
            'newType' => ['required', 'string', 'in:'.implode(',', OrderException::TYPES)],
            'newSummary' => ['required', 'string', 'max:255'],
            'newOrderIdentifier' => ['nullable', 'string', 'max:120'],
            'newScannedCode' => ['nullable', 'string', 'max:120'],
            'newDetails' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $exceptionService->reportException([
            'type' => $this->newType,
            'origin_panel' => $this->exceptionPanelName(),
            'order_identifier' => $this->newOrderIdentifier,
            'scanned_code' => $this->newScannedCode,
            'summary' => $this->newSummary,
            'details' => $this->newDetails,
            'warehouse_id' => $admin->isTajikistanWarehouseStaff() ? $admin->warehouse_id : null,
        ], $admin);

        $this->newOrderIdentifier = '';
        $this->newScannedCode = '';
        $this->newSummary = '';
        $this->newDetails = '';

        flash()->success(__('admin.exception_created_success'));
    }

    public function startReview(int $exceptionId, OrderExceptionService $exceptionService): void
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $exception = $exceptionService->findForPanel($admin, $this->exceptionPanelName(), $exceptionId);

        $this->editingExceptionId = $exception->id;
        $this->editStatus = $exception->status === OrderException::STATUS_OPEN
            ? OrderException::STATUS_RESOLVED
            : $exception->status;
        $this->editResolutionNotes = (string) ($exception->resolution_notes ?? '');
        $this->editLinkOrderIdentifier = (string) ($exception->order?->parcel_code ?? $exception->order?->elim_order_id ?? '');
    }

    public function cancelReview(): void
    {
        $this->editingExceptionId = null;
        $this->editResolutionNotes = '';
        $this->editLinkOrderIdentifier = '';
        $this->resetErrorBag();
    }

    public function saveReview(OrderExceptionService $exceptionService): void
    {
        if (! $this->editingExceptionId) {
            return;
        }

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $exception = $exceptionService->findForPanel($admin, $this->exceptionPanelName(), $this->editingExceptionId);

        try {
            $exceptionService->updateStatus(
                $exception,
                $this->editStatus,
                $this->editResolutionNotes,
                $admin,
                $this->editLinkOrderIdentifier
            );
        } catch (ValidationException $validationException) {
            $this->setErrorBag($validationException->validator->getMessageBag());

            return;
        }

        $this->editingExceptionId = null;
        $this->editResolutionNotes = '';
        $this->editLinkOrderIdentifier = '';

        flash()->success(__('admin.exception_updated_success'));
    }

    abstract protected function exceptionPanelName(): string;
}
