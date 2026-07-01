<?php

namespace App\Support\Elim;

use App\Helpers\SettingHelper;
use Illuminate\Validation\ValidationException;

class ElimWarehouseAddress
{
    public const SETTING_KEY = 'elim_receiver_address';

    public static function get(): array
    {
        $raw = SettingHelper::get(self::SETTING_KEY);

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_array($raw)) {
            return $raw;
        }

        throw ValidationException::withMessages([
            'receiver_address' => [__('api.elim_warehouse_address_missing')],
        ]);
    }

    public static function requiredFields(): array
    {
        return ['name', 'phone', 'mobile', 'address', 'province', 'city', 'area'];
    }

    public static function isConfigured(): bool
    {
        try {
            $address = self::get();

            foreach (self::requiredFields() as $field) {
                if (empty($address[$field])) {
                    return false;
                }
            }

            return true;
        } catch (ValidationException) {
            return false;
        }
    }
}
