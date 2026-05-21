<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DJLN Marketing — WholesaleSeeder  (IDEMPOTENT VERSION)
 *
 * Safe to run multiple times. Uses:
 *   • Category::updateOrCreate(['slug' => ...], [...])
 *   • Product::updateOrCreate(['sku'  => ...], [...])
 *   • Order::firstOrCreate(['order_number' => ...], [...])
 *
 * Run with:
 *   php artisan db:seed --class=WholesaleSeeder
 */
class WholesaleSeeder extends Seeder
{
    public function run(): void
    {
        // ── Resolve the admin user ──────────────────────────────────────
        $admin = User::where('role', 'admin')->first()
            ?? User::first();

        if (! $admin) {
            $this->command->error('No users found. Run the main DatabaseSeeder first.');
            return;
        }

        DB::transaction(function () use ($admin) {

            // ══════════════════════════════════════════════════════════
            // 1. CATEGORIES  —  updateOrCreate keyed on unique `slug`
            // ══════════════════════════════════════════════════════════

            $categoryDefs = [
                [
                    'slug'        => 'candies-gummies',
                    'name'        => 'Candies & Gummies',
                    'icon'        => '🍬',
                    'description' => 'Assorted hard candies, gummy bears, sour worms, and chewy confections.',
                ],
                [
                    'slug'        => 'lollipops',
                    'name'        => 'Lollipops',
                    'icon'        => '🍭',
                    'description' => 'Flat pops, round pops, and novelty lollipops in bulk packaging.',
                ],
                [
                    'slug'        => 'chocolates',
                    'name'        => 'Chocolates',
                    'icon'        => '🍫',
                    'description' => 'Milk, dark, and white chocolate bars, coins, and coated treats.',
                ],
                [
                    'slug'        => 'balloons',
                    'name'        => 'Latex & Foil Balloons',
                    'icon'        => '🎈',
                    'description' => 'Latex standard balloons and premium metallic foil balloons.',
                ],
                [
                    'slug'        => 'party-needs',
                    'name'        => 'Party Needs',
                    'icon'        => '🎉',
                    'description' => 'Party hats, streamers, banners, candles, and celebration accessories.',
                ],
            ];

            $catModels = [];
            foreach ($categoryDefs as $def) {
                $slug = $def['slug'];
                // Key on `slug` (unique); update everything else
                $catModels[$slug] = Category::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name'        => $def['name'],
                        'icon'        => $def['icon'],
                        'description' => $def['description'],
                        'is_active'   => true,
                        'created_by'  => $admin->id,
                    ]
                );
            }

            // ══════════════════════════════════════════════════════════
            // 2. PRODUCTS  —  updateOrCreate keyed on unique `sku`
            // ══════════════════════════════════════════════════════════

            $productDefs = [

                // ── Candies & Gummies ─────────────────────────────────
                [
                    'category'        => 'candies-gummies',
                    'sku'             => 'GUM-BEAR-5KG',
                    'name'            => 'Gummy Bears — Bulk 5 kg',
                    'unit'            => 'bag',
                    'wholesale_price' => 280.00,
                    'retail_price'    => 350.00,
                    'stock_qty'       => 120,
                    'reorder_level'   => 20,
                    'stock_status'    => 'in_stock',
                    'description'     => '5 kg resealable bag of assorted fruit-flavored gummy bears.',
                ],
                [
                    'category'        => 'candies-gummies',
                    'sku'             => 'GUM-SWORM-1KG',
                    'name'            => 'Sour Worms — 1 kg Pack',
                    'unit'            => 'pack',
                    'wholesale_price' => 95.00,
                    'retail_price'    => 130.00,
                    'stock_qty'       => 85,
                    'reorder_level'   => 15,
                    'stock_status'    => 'in_stock',
                    'description'     => 'Tangy sour coated worm gummies, 1 kg pack.',
                ],
                [
                    'category'        => 'candies-gummies',
                    'sku'             => 'CND-JBEAN-500G',
                    'name'            => 'Jelly Beans — Assorted 500 g',
                    'unit'            => 'pack',
                    'wholesale_price' => 55.00,
                    'retail_price'    => 80.00,
                    'stock_qty'       => 200,
                    'reorder_level'   => 30,
                    'stock_status'    => 'in_stock',
                    'description'     => 'Assorted fruit-flavored jelly beans, 500 g resealable bag.',
                ],
                [
                    'category'        => 'candies-gummies',
                    'sku'             => 'CND-TAFFY-1KG',
                    'name'            => 'Taffy Chews — Mixed Flavors 1 kg',
                    'unit'            => 'pack',
                    'wholesale_price' => 70.00,
                    'retail_price'    => 100.00,
                    'stock_qty'       => 60,
                    'reorder_level'   => 10,
                    'stock_status'    => 'in_stock',
                    'description'     => 'Soft taffy chews in assorted fruit flavors, bulk 1 kg.',
                ],

                // ── Lollipops ─────────────────────────────────────────
                [
                    'category'        => 'lollipops',
                    'sku'             => 'LLP-ROUND-100',
                    'name'            => 'Classic Round Lollipops — 100 ct',
                    'unit'            => 'box',
                    'wholesale_price' => 150.00,
                    'retail_price'    => 200.00,
                    'stock_qty'       => 75,
                    'reorder_level'   => 10,
                    'stock_status'    => 'in_stock',
                    'description'     => 'Assorted fruit-flavored round lollipops, 100-count display box.',
                ],
                [
                    'category'        => 'lollipops',
                    'sku'             => 'LLP-FLAT-50',
                    'name'            => 'Flat Lollipop Sticks — 50 ct',
                    'unit'            => 'pack',
                    'wholesale_price' => 60.00,
                    'retail_price'    => 90.00,
                    'stock_qty'       => 40,
                    'reorder_level'   => 8,
                    'stock_status'    => 'in_stock',
                    'description'     => 'Strawberry, grape, and watermelon flat pops, 50-count pack.',
                ],

                // ── Chocolates ────────────────────────────────────────
                [
                    'category'        => 'chocolates',
                    'sku'             => 'CHC-COIN-500G',
                    'name'            => 'Milk Chocolate Coins — 500 g',
                    'unit'            => 'bag',
                    'wholesale_price' => 180.00,
                    'retail_price'    => 240.00,
                    'stock_qty'       => 50,
                    'reorder_level'   => 10,
                    'stock_status'    => 'in_stock',
                    'description'     => 'Foil-wrapped milk chocolate coins, 500 g bag.',
                ],

                // ── Balloons ──────────────────────────────────────────
                [
                    'category'        => 'balloons',
                    'sku'             => 'BAL-LATEX-100',
                    'name'            => 'Latex Balloons — 100 ct Assorted',
                    'unit'            => 'bag',
                    'wholesale_price' => 85.00,
                    'retail_price'    => 120.00,
                    'stock_qty'       => 150,
                    'reorder_level'   => 25,
                    'stock_status'    => 'in_stock',
                    'description'     => 'Standard 11-inch latex balloons, assorted colors, 100-count bag.',
                ],
                [
                    'category'        => 'balloons',
                    'sku'             => 'BAL-FOIL-STAR18',
                    'name'            => 'Foil Star Balloon — 18 in (each)',
                    'unit'            => 'piece',
                    'wholesale_price' => 25.00,
                    'retail_price'    => 45.00,
                    'stock_qty'       => 300,
                    'reorder_level'   => 40,
                    'stock_status'    => 'in_stock',
                    'description'     => '18-inch metallic foil star balloon, gold/silver/rose gold.',
                ],
                [
                    'category'        => 'balloons',
                    'sku'             => 'BAL-FOIL-NUMSET',
                    'name'            => 'Foil Number Balloon Set — 0–9',
                    'unit'            => 'set',
                    'wholesale_price' => 130.00,
                    'retail_price'    => 199.00,
                    'stock_qty'       => 8,
                    'reorder_level'   => 10,
                    'stock_status'    => 'low_stock',
                    'description'     => 'Complete set of 40-inch foil number balloons (0–9), gold.',
                ],

                // ── Party Needs ───────────────────────────────────────
                [
                    'category'        => 'party-needs',
                    'sku'             => 'PTY-HAT-12',
                    'name'            => 'Party Hats — Assorted 12 ct',
                    'unit'            => 'pack',
                    'wholesale_price' => 35.00,
                    'retail_price'    => 55.00,
                    'stock_qty'       => 180,
                    'reorder_level'   => 20,
                    'stock_status'    => 'in_stock',
                    'description'     => 'Colorful cone party hats with elastic, 12-count pack.',
                ],
                [
                    'category'        => 'party-needs',
                    'sku'             => 'PTY-STRM-3PK',
                    'name'            => 'Metallic Streamers — 3-Roll Pack',
                    'unit'            => 'pack',
                    'wholesale_price' => 28.00,
                    'retail_price'    => 45.00,
                    'stock_qty'       => 220,
                    'reorder_level'   => 30,
                    'stock_status'    => 'in_stock',
                    'description'     => 'Metallic crepe streamers in gold, silver, and rainbow, 3-roll pack.',
                ],
                [
                    'category'        => 'party-needs',
                    'sku'             => 'PTY-CNDL-10',
                    'name'            => 'Birthday Candle Set — 10 pcs',
                    'unit'            => 'set',
                    'wholesale_price' => 18.00,
                    'retail_price'    => 30.00,
                    'stock_qty'       => 350,
                    'reorder_level'   => 50,
                    'stock_status'    => 'in_stock',
                    'description'     => 'Classic birthday candles, assorted rainbow colors, 10-piece set.',
                ],
            ];

            $productModels = [];
            foreach ($productDefs as $def) {
                $catSlug = $def['category'];
                $sku     = $def['sku'];
                unset($def['category'], $def['sku']);

                // Key on `sku` (unique); update everything else
                $productModels[$sku] = Product::updateOrCreate(
                    ['sku' => $sku],
                    array_merge($def, [
                        'category_id' => $catModels[$catSlug]->id,
                        'is_active'   => true,
                        'created_by'  => $admin->id,
                    ])
                );
            }

            // ══════════════════════════════════════════════════════════
            // 3. SAMPLE ORDERS  —  firstOrCreate keyed on order_number
            //    Skips re-creating if the seeder already ran once.
            // ══════════════════════════════════════════════════════════

            $this->seedOrder(
                'DJLN-2026-00001',
                $admin,
                'wholesale',
                'delivered',
                'paid',
                50.00,
                'Bulk order — Party Events Supplier',
                now()->subDays(5),
                [
                    ['sku' => 'BAL-LATEX-100', 'qty' => 10, 'price' => 85.00],
                    ['sku' => 'GUM-BEAR-5KG',  'qty' => 5,  'price' => 280.00],
                ],
                $productModels
            );

            $this->seedOrder(
                'DJLN-2026-00002',
                $admin,
                'wholesale',
                'processing',
                'partial',
                0.00,
                'Standing order — weekly candy restocking',
                now()->subDays(1),
                [
                    ['sku' => 'GUM-SWORM-1KG', 'qty' => 20, 'price' => 95.00],
                    ['sku' => 'LLP-ROUND-100', 'qty' => 8,  'price' => 150.00],
                ],
                $productModels
            );

            $this->seedOrder(
                'DJLN-2026-00003',
                $admin,
                'retail',
                'delivered',
                'paid',
                0.00,
                'Walk-in customer — birthday party supplies',
                now()->subHours(3),
                [
                    ['sku' => 'PTY-HAT-12',      'qty' => 3, 'price' => 55.00],
                    ['sku' => 'BAL-FOIL-STAR18',  'qty' => 5, 'price' => 45.00],
                    ['sku' => 'PTY-CNDL-10',      'qty' => 2, 'price' => 30.00],
                ],
                $productModels
            );
        });

        // ── Success summary ─────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('✅ DJLN MARKETING — INVENTORY SEEDED (IDEMPOTENT)');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('📦 Categories : 5  (updateOrCreate on slug)');
        $this->command->info('🛒 Products   : 13 (updateOrCreate on sku)');
        $this->command->info('📋 Orders     : 3  (firstOrCreate on order_number)');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('');
    }

    // ──────────────────────────────────────────────────────────────────
    // PRIVATE HELPER — creates an order + items only if it doesn't exist
    // ──────────────────────────────────────────────────────────────────

    private function seedOrder(
        string $orderNumber,
        User   $admin,
        string $type,
        string $fulfillment,
        string $payment,
        float  $discount,
        string $notes,
        $orderedAt,
        array  $lines,
        array  $productModels
    ): void {
        $order = Order::firstOrCreate(
            ['order_number' => $orderNumber],
            [
                'customer_id'        => $admin->id,
                'processed_by'       => $admin->id,
                'order_type'         => $type,
                'fulfillment_status' => $fulfillment,
                'payment_status'     => $payment,
                'subtotal'           => 0,
                'discount_amount'    => $discount,
                'total_amount'       => 0,
                'notes'              => $notes,
                'ordered_at'         => $orderedAt,
            ]
        );

        // Only add items if this order was just created (wasRecentlyCreated)
        if ($order->wasRecentlyCreated) {
            foreach ($lines as $line) {
                $lineTotal = $line['qty'] * $line['price'];
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $productModels[$line['sku']]->id,
                    'quantity'   => $line['qty'],
                    'unit_price' => $line['price'],
                    'line_total' => $lineTotal,
                ]);
            }
            $order->recalculateTotals();
        }
    }
}
