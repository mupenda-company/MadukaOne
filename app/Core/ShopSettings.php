<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class ShopSettings
{
    public const DEFAULTS = [
        'sales_credit_enabled' => '1',
        'partial_payments_enabled' => '1',
        'discounts_enabled' => '1',
        'taxes_enabled' => '0',
        'expiration_dates_enabled' => '1',
        'variants_enabled' => '0',
        'tables_enabled' => '0',
        'reservations_enabled' => '0',
        'multi_warehouse_enabled' => '0',
        'multi_shop_enabled' => '0',
    ];

    public function allForShop(int $shopId): array
    {
        $settings = self::DEFAULTS;

        if ($shopId < 1) {
            return $settings;
        }

        try {
            $statement = Database::connection()->prepare(
                'SELECT setting_key, setting_value
                 FROM shop_business_settings
                 WHERE shop_id = :shop_id'
            );
            $statement->execute(['shop_id' => $shopId]);

            foreach ($statement->fetchAll() as $row) {
                $key = (string) ($row['setting_key'] ?? '');
                if ($key !== '') {
                    $settings[$key] = (string) ($row['setting_value'] ?? '');
                }
            }
        } catch (Throwable) {
        }

        return $settings;
    }

    public function saveForShop(int $shopId, array $settings): void
    {
        if ($shopId < 1) {
            throw new InvalidArgumentException('Boutique invalide.');
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO shop_business_settings (shop_id, setting_key, setting_value, value_type)
             VALUES (:shop_id, :setting_key, :setting_value, "boolean")
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), value_type = VALUES(value_type)'
        );

        foreach (array_keys(self::DEFAULTS) as $key) {
            $statement->execute([
                'shop_id' => $shopId,
                'setting_key' => $key,
                'setting_value' => isset($settings[$key]) ? '1' : '0',
            ]);
        }
    }

    public function enabled(array $settings, string $key): bool
    {
        return (string) ($settings[$key] ?? self::DEFAULTS[$key] ?? '0') === '1';
    }
}
