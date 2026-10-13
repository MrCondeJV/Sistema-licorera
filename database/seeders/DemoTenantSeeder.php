<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\StockMovement;
use App\Services\TenantContext;
use App\Services\TenantService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Superadmin platform user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@licorerapos.com'],
            [
                'name' => 'Super Administrador',
                'phone' => '3001112233',
                'pin_hash' => Hash::make('9999'),
                'password' => Hash::make('Admin1234*'),
                'is_superadmin' => true,
                'is_active' => true,
            ]
        );

        // 2. Register main demo liquor store via TenantService
        $tenantService = new TenantService();
        $result = $tenantService->registerBusiness([
            'business_name' => 'Licorera Central - Sede Principal',
            'admin_name' => 'Carlos Administrador',
            'email' => 'admin@licorerapos.com',
            'phone' => '3104567890',
            'tax_id' => '901.458.789-3',
            'address' => 'Carrera 37A # 8A - 20, Medellín',
            'pin' => '1234',
            'password' => 'Admin1234*',
        ]);

        $tenant = $result['tenant'];
        $branch = $result['branch'];
        $adminUser = $result['user'];

        $context = app(TenantContext::class);
        $context->setTenant($tenant);
        $context->setBranch($branch);

        // 3. Create Cashier user (PIN: 2222)
        $cashierUser = User::create([
            'name' => 'Valentina Cajera',
            'email' => 'cajero@licorerapos.com',
            'phone' => '3123456781',
            'pin_hash' => Hash::make('2222'),
            'password' => Hash::make('Cajero1234*'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $cashierUser->id,
            'role' => 'cashier',
            'is_active' => true,
        ]);

        // 4. Create Waiter user (PIN: 3333)
        $waiterUser = User::create([
            'name' => 'Mateo Mesero',
            'email' => 'mesero@licorerapos.com',
            'phone' => '3145678902',
            'pin_hash' => Hash::make('3333'),
            'password' => Hash::make('Mesero1234*'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $waiterUser->id,
            'role' => 'waiter',
            'is_active' => true,
        ]);

        // 5. Create Suppliers
        $suppliers = [
            ['name' => 'Bavaria & Cía S.C.A.', 'tax_id' => '860.005.224-6', 'phone' => '018000526555', 'email' => 'pedidos@bavaria.co'],
            ['name' => 'Fábrica de Licores de Antioquia (FLA)', 'tax_id' => '890.900.286-0', 'phone' => '018000940094', 'email' => 'ventas@fla.com.co'],
            ['name' => 'Diageo Colombia S.A.', 'tax_id' => '830.004.912-1', 'phone' => '6015551234', 'email' => 'colombia@diageo.com'],
            ['name' => 'Industria Licorera de Caldas', 'tax_id' => '890.801.377-5', 'phone' => '6068875500', 'email' => 'contacto@ilc.com.co'],
        ];

        foreach ($suppliers as $s) {
            Supplier::create([
                'tenant_id' => $tenant->id,
                'name' => $s['name'],
                'tax_id' => $s['tax_id'],
                'phone' => $s['phone'],
                'email' => $s['email'],
                'is_active' => true,
            ]);
        }

        // 6. Categories map
        $catMap = Category::where('tenant_id', $tenant->id)->pluck('id', 'slug')->toArray();

        // 7. Products catalog: Beers, Liquors, Mixers, Snacks
        $productsData = [
            // BEERS (With individual, six-pack, crate 24 presentations)
            [
                'name' => 'Cerveza Águila Original 330ml',
                'sku' => 'CER-AGU-001',
                'category_id' => $catMap['cervezas'],
                'price_buy' => 2400,
                'price_sell' => 4500,
                'stock' => 144, // 6 cajas equivalentes
                'stock_min' => 24,
                'base_unit' => 'unidad',
                'is_beer' => true,
                'is_liquor' => false,
                'is_favorite' => true,
                'presentations' => [
                    ['name' => 'Six-Pack (6 Uds)', 'conversion_factor' => 6, 'sale_price' => 24000],
                    ['name' => 'Caja (24 Uds)', 'conversion_factor' => 24, 'sale_price' => 90000],
                ]
            ],
            [
                'name' => 'Cerveza Corona Extra 355ml',
                'sku' => 'CER-COR-002',
                'category_id' => $catMap['cervezas'],
                'price_buy' => 4500,
                'price_sell' => 8000,
                'stock' => 96,
                'stock_min' => 18,
                'base_unit' => 'unidad',
                'is_beer' => true,
                'is_liquor' => false,
                'is_favorite' => true,
                'presentations' => [
                    ['name' => 'Six-Pack (6 Uds)', 'conversion_factor' => 6, 'sale_price' => 45000],
                    ['name' => 'Caja (24 Uds)', 'conversion_factor' => 24, 'sale_price' => 170000],
                ]
            ],
            [
                'name' => 'Cerveza Poker 330ml',
                'sku' => 'CER-POK-003',
                'category_id' => $catMap['cervezas'],
                'price_buy' => 2400,
                'price_sell' => 4500,
                'stock' => 120,
                'stock_min' => 24,
                'base_unit' => 'unidad',
                'is_beer' => true,
                'is_liquor' => false,
                'is_favorite' => true,
                'presentations' => [
                    ['name' => 'Six-Pack (6 Uds)', 'conversion_factor' => 6, 'sale_price' => 24000],
                    ['name' => 'Caja (24 Uds)', 'conversion_factor' => 24, 'sale_price' => 90000],
                ]
            ],
            [
                'name' => 'Cerveza Club Colombia Dorada 330ml',
                'sku' => 'CER-CLU-004',
                'category_id' => $catMap['cervezas'],
                'price_buy' => 3100,
                'price_sell' => 5500,
                'stock' => 72,
                'stock_min' => 18,
                'base_unit' => 'unidad',
                'is_beer' => true,
                'is_liquor' => false,
                'is_favorite' => true,
                'presentations' => [
                    ['name' => 'Six-Pack (6 Uds)', 'conversion_factor' => 6, 'sale_price' => 30000],
                    ['name' => 'Caja (24 Uds)', 'conversion_factor' => 24, 'sale_price' => 115000],
                ]
            ],
            [
                'name' => 'Cerveza Heineken 330ml',
                'sku' => 'CER-HEI-005',
                'category_id' => $catMap['cervezas'],
                'price_buy' => 4200,
                'price_sell' => 7500,
                'stock' => 60,
                'stock_min' => 12,
                'base_unit' => 'unidad',
                'is_beer' => true,
                'is_liquor' => false,
                'is_favorite' => false,
                'presentations' => [
                    ['name' => 'Six-Pack (6 Uds)', 'conversion_factor' => 6, 'sale_price' => 42000],
                ]
            ],

            // LIQUORS (Strictly full bottle only, is_liquor = true)
            [
                'name' => 'Aguardiente Antioqueño Azul Sin Azúcar 750ml',
                'sku' => 'AGU-ANT-AZU-750',
                'category_id' => $catMap['aguardientes'],
                'price_buy' => 38000,
                'price_sell' => 65000,
                'stock' => 25,
                'stock_min' => 5,
                'base_unit' => 'botella',
                'is_beer' => false,
                'is_liquor' => true,
                'is_favorite' => true,
                'presentations' => []
            ],
            [
                'name' => 'Aguardiente Antioqueño Rojo Tradicional 750ml',
                'sku' => 'AGU-ANT-ROJ-750',
                'category_id' => $catMap['aguardientes'],
                'price_buy' => 38000,
                'price_sell' => 65000,
                'stock' => 18,
                'stock_min' => 5,
                'base_unit' => 'botella',
                'is_beer' => false,
                'is_liquor' => true,
                'is_favorite' => true,
                'presentations' => []
            ],
            [
                'name' => 'Aguardiente Amarillo de Manzanares 750ml',
                'sku' => 'AGU-AMA-MAN-750',
                'category_id' => $catMap['aguardientes'],
                'price_buy' => 42000,
                'price_sell' => 75000,
                'stock' => 20,
                'stock_min' => 4,
                'base_unit' => 'botella',
                'is_beer' => false,
                'is_liquor' => true,
                'is_favorite' => true,
                'presentations' => []
            ],
            [
                'name' => 'Ron Medellín Añejo 3 Años 750ml',
                'sku' => 'RON-MED-3A-750',
                'category_id' => $catMap['rones'],
                'price_buy' => 42000,
                'price_sell' => 70000,
                'stock' => 15,
                'stock_min' => 4,
                'base_unit' => 'botella',
                'is_beer' => false,
                'is_liquor' => true,
                'is_favorite' => true,
                'presentations' => []
            ],
            [
                'name' => 'Ron Viejo de Caldas Juan de la Cruz 5 Años 750ml',
                'sku' => 'RON-CAL-5A-750',
                'category_id' => $catMap['rones'],
                'price_buy' => 46000,
                'price_sell' => 78000,
                'stock' => 14,
                'stock_min' => 3,
                'base_unit' => 'botella',
                'is_beer' => false,
                'is_liquor' => true,
                'is_favorite' => true,
                'presentations' => []
            ],
            [
                'name' => "Whisky Buchanan's Deluxe 12 Años 750ml",
                'sku' => 'WHI-BUC-12A-750',
                'category_id' => $catMap['whiskies'],
                'price_buy' => 125000,
                'price_sell' => 185000,
                'stock' => 12,
                'stock_min' => 2,
                'base_unit' => 'botella',
                'is_beer' => false,
                'is_liquor' => true,
                'is_favorite' => true,
                'presentations' => []
            ],
            [
                'name' => 'Whisky Old Parr 12 Años 750ml',
                'sku' => 'WHI-OLD-12A-750',
                'category_id' => $catMap['whiskies'],
                'price_buy' => 120000,
                'price_sell' => 180000,
                'stock' => 10,
                'stock_min' => 2,
                'base_unit' => 'botella',
                'is_beer' => false,
                'is_liquor' => true,
                'is_favorite' => true,
                'presentations' => []
            ],
            [
                'name' => 'Whisky Johnnie Walker Black Label 750ml',
                'sku' => 'WHI-JON-BLA-750',
                'category_id' => $catMap['whiskies'],
                'price_buy' => 130000,
                'price_sell' => 195000,
                'stock' => 8,
                'stock_min' => 2,
                'base_unit' => 'botella',
                'is_beer' => false,
                'is_liquor' => true,
                'is_favorite' => false,
                'presentations' => []
            ],
            [
                'name' => 'Vodka Smirnoff Red 750ml',
                'sku' => 'VOD-SMI-RED-750',
                'category_id' => $catMap['vodkas'],
                'price_buy' => 45000,
                'price_sell' => 75000,
                'stock' => 9,
                'stock_min' => 2,
                'base_unit' => 'botella',
                'is_beer' => false,
                'is_liquor' => true,
                'is_favorite' => false,
                'presentations' => []
            ],
            [
                'name' => 'Tequila Don Julio Blanco 700ml',
                'sku' => 'TEQ-DON-BLA-700',
                'category_id' => $catMap['tequilas'],
                'price_buy' => 180000,
                'price_sell' => 260000,
                'stock' => 6,
                'stock_min' => 1,
                'base_unit' => 'botella',
                'is_beer' => false,
                'is_liquor' => true,
                'is_favorite' => false,
                'presentations' => []
            ],
            [
                'name' => 'Ginebra Bombay Sapphire 750ml',
                'sku' => 'GIN-BOM-SAP-750',
                'category_id' => $catMap['ginebras'],
                'price_buy' => 95000,
                'price_sell' => 150000,
                'stock' => 7,
                'stock_min' => 2,
                'base_unit' => 'botella',
                'is_beer' => false,
                'is_liquor' => true,
                'is_favorite' => false,
                'presentations' => []
            ],

            // SOFT DRINKS, MIXERS & WATER
            [
                'name' => 'Coca-Cola Original 400ml',
                'sku' => 'GAS-COC-400',
                'category_id' => $catMap['gaseosas'],
                'price_buy' => 2000,
                'price_sell' => 4000,
                'stock' => 50,
                'stock_min' => 12,
                'base_unit' => 'unidad',
                'is_beer' => false,
                'is_liquor' => false,
                'is_favorite' => true,
                'presentations' => []
            ],
            [
                'name' => 'Ginger Ale Canada Dry 300ml',
                'sku' => 'MEZ-GIN-300',
                'category_id' => $catMap['mezcladores'],
                'price_buy' => 2200,
                'price_sell' => 4500,
                'stock' => 35,
                'stock_min' => 10,
                'base_unit' => 'unidad',
                'is_beer' => false,
                'is_liquor' => false,
                'is_favorite' => false,
                'presentations' => []
            ],
            [
                'name' => 'Agua Cristal Sin Gas 500ml',
                'sku' => 'AGU-CRI-500',
                'category_id' => $catMap['agua'],
                'price_buy' => 1200,
                'price_sell' => 3000,
                'stock' => 40,
                'stock_min' => 10,
                'base_unit' => 'unidad',
                'is_beer' => false,
                'is_liquor' => false,
                'is_favorite' => true,
                'presentations' => []
            ],
            [
                'name' => 'Red Bull Energy Drink 250ml',
                'sku' => 'ENE-RED-250',
                'category_id' => $catMap['energizantes'],
                'price_buy' => 5800,
                'price_sell' => 10000,
                'stock' => 30,
                'stock_min' => 8,
                'base_unit' => 'unidad',
                'is_beer' => false,
                'is_liquor' => false,
                'is_favorite' => true,
                'presentations' => []
            ],
            [
                'name' => 'Papas Margarita Limón 110g',
                'sku' => 'SNK-MAR-LIM-110',
                'category_id' => $catMap['snacks'],
                'price_buy' => 3200,
                'price_sell' => 5500,
                'stock' => 25,
                'stock_min' => 6,
                'base_unit' => 'unidad',
                'is_beer' => false,
                'is_liquor' => false,
                'is_favorite' => false,
                'presentations' => []
            ],
            [
                'name' => 'Maní La Especial Salado 150g',
                'sku' => 'SNK-ESP-SAL-150',
                'category_id' => $catMap['snacks'],
                'price_buy' => 3500,
                'price_sell' => 6000,
                'stock' => 20,
                'stock_min' => 5,
                'base_unit' => 'unidad',
                'is_beer' => false,
                'is_liquor' => false,
                'is_favorite' => false,
                'presentations' => []
            ],
        ];

        foreach ($productsData as $pData) {
            $presentations = $pData['presentations'] ?? [];
            unset($pData['presentations']);

            $pData['tenant_id'] = $tenant->id;
            $product = Product::create($pData);

            foreach ($presentations as $pres) {
                ProductPresentation::create([
                    'tenant_id' => $tenant->id,
                    'product_id' => $product->id,
                    'name' => $pres['name'],
                    'conversion_factor' => $pres['conversion_factor'],
                    'sale_price' => $pres['sale_price'],
                    'is_active' => true,
                ]);
            }
        }

        // 8. Expense Categories
        $expCategories = [
            'Arriendo del Local',
            'Servicios Públicos (Energía y Agua)',
            'Insumos y Aseo',
            'Mantenimiento de Neveras y Luces',
            'Gastos Operativos Varios',
        ];

        foreach ($expCategories as $ecName) {
            ExpenseCategory::create([
                'tenant_id' => $tenant->id,
                'name' => $ecName,
            ]);
        }

        // 9. Customers
        $customers = [
            ['name' => 'Juan David Pérez', 'phone' => '3009876543', 'tax_id' => '1020304050', 'credit_limit' => 200000],
            ['name' => 'Camila Restrepo', 'phone' => '3118765432', 'tax_id' => '1030405060', 'credit_limit' => 150000],
            ['name' => 'Andrés Gómez', 'phone' => '3157654321', 'tax_id' => '1040506070', 'credit_limit' => 300000],
        ];

        foreach ($customers as $c) {
            Customer::create([
                'tenant_id' => $tenant->id,
                'name' => $c['name'],
                'phone' => $c['phone'],
                'tax_id' => $c['tax_id'],
                'credit_limit' => $c['credit_limit'],
                'is_active' => true,
            ]);
        }
    }
}
