<?php

namespace App\Services\Warehouse;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Services\Order\OrderAuditLogger;
use Illuminate\Validation\ValidationException;

class ParcelScannerService
{
    /**
     * Standard Code-128B patterns (values 0..106) for pure-PHP 1D SVG barcode generation.
     *
     * @var list<string>
     */
    private const CODE128_PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    public function __construct(
        private readonly WarehousePanelService $warehousePanelService,
        private readonly OrderAuditLogger $auditLogger,
    ) {}

    public function normalizePayload(string $rawPayload): string
    {
        $payload = trim($rawPayload);

        if ($payload === '') {
            return '';
        }

        if (str_starts_with(strtolower($payload), 'cargo-parcel:')) {
            $payload = substr($payload, strlen('cargo-parcel:'));
        } elseif (str_starts_with(strtolower($payload), 'cargo-pickup:')) {
            $payload = substr($payload, strlen('cargo-pickup:'));
        }

        if (preg_match('#/(?:orders|pickup|scanner)/([^/?\#]+)#i', $payload, $matches) === 1) {
            $payload = $matches[1];
        }

        return trim($payload);
    }

    /**
     * Find a customer order by parcel_code, parcel_tracking_id, elim_order_id, pickup_token, or #id.
     *
     * @return array{order: CustomerOrder, matched_by: string}
     */
    public function findOrderByScan(string $rawPayload, Admin $admin, string $panel): array
    {
        $code = $this->normalizePayload($rawPayload);

        if ($code === '') {
            throw ValidationException::withMessages([
                'scanInput' => [__('admin.parcel_scan_code_required')],
            ]);
        }

        $order = null;
        $matchedBy = 'parcel_code';
        $relations = ['user', 'items', 'orderStatus', 'warehouse', 'shippingMethod', 'shipment'];

        // Parcel codes are generated upper-case; scanners/keyboards may send lower-case.
        $order = CustomerOrder::query()
            ->with($relations)
            ->where('parcel_code', strtoupper($code))
            ->first();

        if (! $order) {
            $matchedBy = 'parcel_tracking_id';
            $order = CustomerOrder::query()
                ->with($relations)
                ->where('parcel_tracking_id', $code)
                ->first();
        }

        if (! $order) {
            $matchedBy = 'elim_order_id';
            $order = CustomerOrder::query()
                ->with($relations)
                ->where('elim_order_id', $code)
                ->first();
        }

        if (! $order) {
            $matchedBy = 'pickup_token';
            $order = CustomerOrder::query()
                ->with($relations)
                ->where('pickup_token', $code)
                ->first();
        }

        // Internal order IDs are only matched with an explicit "#" prefix (e.g. "#123"),
        // so an unknown numeric carrier barcode can never update an unrelated order.
        if (! $order && preg_match('/^#(\d+)$/', $code, $idMatch) === 1) {
            $matchedBy = 'order_id';
            $order = CustomerOrder::query()
                ->with($relations)
                ->find((int) $idMatch[1]);
        }

        if (! $order) {
            throw ValidationException::withMessages([
                'scanInput' => [__('admin.parcel_scan_order_not_found', ['code' => $code])],
            ]);
        }

        if ($order->status === OrderStatus::CODE_CANCELLED) {
            throw ValidationException::withMessages([
                'scanInput' => [__('admin.order_status_cancelled_locked')],
            ]);
        }

        if ($panel === 'china') {
            if (! $admin->canAccessChinaWarehousePanel() || ! $order->isVisibleInChinaWarehousePanel()) {
                throw ValidationException::withMessages([
                    'scanInput' => [__('admin.warehouse_order_not_available')],
                ]);
            }
        } elseif ($panel === 'tajikistan') {
            if (! $admin->canAccessTajikistanWarehousePanel()) {
                throw ValidationException::withMessages([
                    'scanInput' => [__('admin.warehouse_order_not_available')],
                ]);
            }

            if ($admin->isTajikistanWarehouseStaff() && (int) $order->warehouse_id !== (int) $admin->warehouse_id) {
                throw ValidationException::withMessages([
                    'scanInput' => [__('admin.warehouse_order_not_available')],
                ]);
            }
        }

        return [
            'order' => $order,
            'matched_by' => $matchedBy,
            'scanned_code' => $code,
        ];
    }

    /**
     * Scan a parcel barcode/QR and immediately update its warehouse status.
     *
     * @return array<string, mixed>
     */
    public function scanAndUpdateStatus(
        Admin $admin,
        string $rawPayload,
        string $targetStatusCode,
        string $panel,
    ): array {
        $resolved = $this->findOrderByScan($rawPayload, $admin, $panel);
        /** @var CustomerOrder $order */
        $order = $resolved['order'];
        $matchedBy = $resolved['matched_by'];
        $scannedCode = $resolved['scanned_code'];

        $previousStatus = $order->status;
        $previousStatusName = $order->orderStatus?->name ?? $order->status;

        $updatedOrder = $this->auditLogger->withContext(
            OrderStatusHistory::SOURCE_BARCODE_SCAN,
            $admin,
            sprintf('Scanned %s (%s) in %s warehouse', $scannedCode, $matchedBy, ucfirst($panel)),
            [
                'scanned_code' => $scannedCode,
                'matched_by' => $matchedBy,
                'panel' => $panel,
            ],
            fn (): CustomerOrder => $this->warehousePanelService
                ->updateOrderStatus($order, $targetStatusCode)
                ->load(['user', 'items', 'orderStatus', 'warehouse', 'shippingMethod', 'shipment'])
        );

        return [
            'success' => true,
            'order_id' => $updatedOrder->id,
            'parcel_code' => $updatedOrder->parcel_code,
            'elim_order_id' => $updatedOrder->elim_order_id,
            'parcel_tracking_id' => $updatedOrder->parcel_tracking_id,
            'customer_name' => $updatedOrder->user?->name ?? '—',
            'platform' => $updatedOrder->platform,
            'matched_by' => $matchedBy,
            'scanned_code' => $scannedCode,
            'previous_status' => $previousStatus,
            'previous_status_name' => $previousStatusName,
            'new_status' => $updatedOrder->status,
            'new_status_name' => $updatedOrder->orderStatus?->name ?? $updatedOrder->status,
            'new_status_badge' => $updatedOrder->orderStatus?->badgeClass() ?? 'bg-label-primary',
            'scanned_at' => now()->format('H:i:s'),
        ];
    }

    /**
     * Render a clean, self-contained Code-128B 1D barcode SVG for the given string.
     */
    public static function renderCode128Svg(string $text, int $height = 54, int $barWidth = 2): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // Start Code B = 104
        $codes = [104];
        $checksum = 104;
        $length = strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $ascii = ord($text[$i]);
            if ($ascii < 32 || $ascii > 126) {
                $ascii = 45; // fallback to '-'
            }
            $value = $ascii - 32;
            $codes[] = $value;
            $checksum += $value * ($i + 1);
        }

        $codes[] = $checksum % 103;
        $codes[] = 106; // Stop pattern

        $bars = '';
        foreach ($codes as $codeIndex) {
            $bars .= self::CODE128_PATTERNS[$codeIndex] ?? '';
        }

        $totalUnits = 0;
        $barsLength = strlen($bars);
        for ($i = 0; $i < $barsLength; $i++) {
            $totalUnits += (int) $bars[$i];
        }

        $quietZone = 10 * $barWidth;
        $svgWidth = ($totalUnits * $barWidth) + ($quietZone * 2);

        $rects = [];
        $x = $quietZone;

        for ($i = 0; $i < $barsLength; $i++) {
            $w = ((int) $bars[$i]) * $barWidth;
            if ($i % 2 === 0) {
                $rects[] = sprintf('<rect x="%d" y="0" width="%d" height="%d" fill="#111827" />', $x, $w, $height);
            }
            $x += $w;
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="100%%" height="%d" role="img" aria-label="%s" style="max-width:%dpx;background:#fff;"><rect width="100%%" height="100%%" fill="#ffffff"/>%s</svg>',
            $svgWidth,
            $height,
            $height,
            htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
            $svgWidth,
            implode('', $rects)
        );
    }
}
