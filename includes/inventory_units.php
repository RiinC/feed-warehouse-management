<?php

function inventory_unit_options(): array
{
    return ['Kg', 'Tấn', 'Bao', 'Pallet', 'm³'];
}

function inventory_kg_per_unit(string $unit, array $item): float
{
    switch ($unit) {
        case 'Kg':
            return 1.0;
        case 'Tấn':
            return 1000.0;
        case 'Bao':
            $factor = (float)($item['kgMoiBao'] ?? 0);
            break;
        case 'Pallet':
            $factor = (float)($item['kgMoiBao'] ?? 0) * (float)($item['baoMoiPallet'] ?? 0);
            break;
        case 'm³':
            $factor = (float)($item['khoiLuongRieng'] ?? 0);
            break;
        default:
            throw new DomainException('Đơn vị tính không được hỗ trợ.');
    }

    if ($factor <= 0) {
        throw new DomainException($unit === 'm³'
            ? 'Mặt hàng chưa được cấu hình khối lượng riêng để quy đổi m³ sang Kg.'
            : 'Mặt hàng chưa được cấu hình hệ số quy đổi đơn vị.');
    }

    return $factor;
}

function inventory_quantity_to_kg(float $quantity, string $unit, array $item): float
{
    return round($quantity * inventory_kg_per_unit($unit, $item), 3);
}

function inventory_kg_to_capacity(float $quantityKg, string $capacityUnit, array $item): float
{
    return $quantityKg / inventory_kg_per_unit($capacityUnit, $item);
}

function inventory_location_capacity_usage(string $locationCode, string $capacityUnit, string $database): ?float
{
    $stocks = db_all(
        "SELECT t.soLuongTon, h.kgMoiBao, h.baoMoiPallet, h.khoiLuongRieng
         FROM TonKho t
         JOIN LoHang l ON l.maLoHang = t.maLoHang
         JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
         WHERE t.maViTri = ? AND t.trangThai = 'Đã nhập kho'",
        's',
        [$locationCode],
        $database
    );

    $usage = 0.0;
    foreach ($stocks as $stock) {
        try {
            $usage += inventory_kg_to_capacity((float)$stock['soLuongTon'], $capacityUnit, $stock);
        } catch (DomainException $ex) {
            if ($capacityUnit === 'm³') {
                return null;
            }
            throw $ex;
        }
    }

    return $usage;
}
