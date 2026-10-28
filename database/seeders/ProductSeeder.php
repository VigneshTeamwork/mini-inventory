<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * A fixed catalog so demos and screenshots are repeatable. Includes the
     * wireframe's items, a mix of tax rates, low-stock items, one item at
     * exactly the default threshold (10) and one out of stock.
     */
    public function run(): void
    {
        $catalog = [
            // code, name, price, tax %, stock
            ['COL-100', 'Colgate Toothpaste', '50.00', 8, 40],
            ['PAR-010', 'Parle-G Biscuit', '10.00', 8, 120],
            ['BRD-001', 'Bread', '45.00', 0, 4],
            ['MLK-001', 'Milk 1L', '62.00', 0, 9],
            ['EGG-012', 'Eggs (12)', '84.00', 0, 2],
            ['RIC-005', 'Basmati Rice 5kg', '650.00', 5, 25],
            ['ATA-005', 'Wheat Atta 5kg', '285.00', 5, 30],
            ['SUG-001', 'Sugar 1kg', '48.00', 5, 60],
            ['TEA-250', 'Tea Powder 250g', '140.00', 5, 30],
            ['OIL-001', 'Sunflower Oil 1L', '155.00', 5, 18],
            ['SOP-100', 'Bath Soap 100g', '38.00', 18, 75],
            ['SHM-180', 'Shampoo 180ml', '165.00', 18, 22],
            ['DET-001', 'Detergent Powder 1kg', '120.00', 18, 14],
            ['SAL-001', 'Iodised Salt 1kg', '28.00', 0, 50],
            ['CHO-050', 'Chocolate Bar 50g', '40.00', 18, 0],
            ['BUT-100', 'Butter 100g', '58.00', 12, 7],
            ['PAN-200', 'Paneer 200g', '90.00', 5, 11],
            ['NOO-070', 'Instant Noodles 70g', '14.00', 12, 200],
            ['JAM-500', 'Mixed Fruit Jam 500g', '175.00', 12, 10],
            ['COF-100', 'Instant Coffee 100g', '310.00', 18, 1],
        ];

        foreach ($catalog as [$code, $name, $price, $tax, $stock]) {
            $product = Product::create([
                'code' => $code,
                'name' => $name,
                'price' => $price,
                'tax_percent' => $tax,
                'stock_on_hand' => $stock,
            ]);

            StockMovement::create([
                'product_id' => $product->id,
                'change' => $stock,
                'balance_after' => $stock,
                'reason' => StockMovement::REASON_SEED,
            ]);
        }
    }
}
