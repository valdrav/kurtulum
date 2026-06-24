<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\IncomeExpense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SystemModule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::role('super-admin')->first() ?? User::first();

        if (!$admin) {
            $this->command?->warn('Admin kullanıcı bulunamadı. Önce kurulumu tamamlayın.');

            return;
        }

        $this->seedCompanySettings();
        $departments = $this->seedDepartments();
        $this->seedUsers($admin, $departments);
        $customers = $this->seedCustomers($admin);
        $this->seedSuppliers($admin);
        $products = $this->seedProducts();
        $this->seedOrders($admin, $customers, $products);
        $this->seedEmployees($departments);
        $this->seedAccounts($customers);
        $this->seedFinance($admin);
        $this->syncModules($admin);
    }

    protected function seedCompanySettings(): void
    {
        $defaults = [
            'company_name' => 'ExportFlow İhracat A.Ş.',
            'company_email' => 'info@exportflow.demo',
            'company_phone' => '+90 212 555 0100',
            'company_address' => 'Maslak Mah. Büyükdere Cad. No:123, Sarıyer / İstanbul',
            'company_tax_number' => '1234567890',
            'company_tax_office' => 'Maslak VD',
            'company_website' => 'https://exportflow.demo',
            'default_currency' => 'USD',
            'timezone' => 'Europe/Istanbul',
        ];

        foreach ($defaults as $key => $value) {
            Setting::set($key, $value, 'company');
        }
    }

    protected function seedDepartments(): array
    {
        $data = [
            ['name' => 'İhracat Satış', 'code' => 'SALES', 'description' => 'Müşteri ilişkileri ve sipariş yönetimi'],
            ['name' => 'Lojistik', 'code' => 'LOG', 'description' => 'Sevkiyat ve nakliye operasyonları'],
            ['name' => 'Finans', 'code' => 'FIN', 'description' => 'Cari hesap ve ödeme takibi'],
            ['name' => 'Operasyon', 'code' => 'OPS', 'description' => 'Genel operasyon ve evrak'],
        ];

        $departments = [];
        foreach ($data as $row) {
            $departments[$row['code']] = Department::firstOrCreate(['code' => $row['code']], $row);
        }

        return $departments;
    }

    protected function seedUsers(User $admin, array $departments): void
    {
        $users = [
            ['name' => 'Ayşe Yönetici', 'email' => 'manager@exportflow.demo', 'role' => 'manager', 'dept' => 'SALES'],
            ['name' => 'Mehmet Operatör', 'email' => 'operator@exportflow.demo', 'role' => 'operator', 'dept' => 'LOG'],
            ['name' => 'Zeynep Görüntüleyici', 'email' => 'viewer@exportflow.demo', 'role' => 'viewer', 'dept' => 'FIN'],
        ];

        foreach ($users as $row) {
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make('demo1234'),
                    'department_id' => $departments[$row['dept']]->id ?? null,
                    'locale' => 'tr',
                    'theme' => 'light',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$row['role']]);
        }

        $admin->update(['department_id' => $departments['OPS']->id ?? null]);
    }

    protected function seedCustomers(User $admin): array
    {
        $rows = [
            ['company_name' => 'Berlin Handels GmbH', 'contact_person' => 'Hans Mueller', 'email' => 'hans@berlin-handels.de', 'country' => 'DEU', 'city' => 'Berlin', 'type' => 'buyer', 'currency' => 'EUR'],
            ['company_name' => 'Gulf Trading LLC', 'contact_person' => 'Ahmed Al-Rashid', 'email' => 'ahmed@gulftrading.ae', 'country' => 'ARE', 'city' => 'Dubai', 'type' => 'distributor', 'currency' => 'USD'],
            ['company_name' => 'Nordic Import AB', 'contact_person' => 'Erik Johansson', 'email' => 'erik@nordicimport.se', 'country' => 'SWE', 'city' => 'Stockholm', 'type' => 'buyer', 'currency' => 'EUR'],
            ['company_name' => 'Mediterranean Foods SRL', 'contact_person' => 'Marco Rossi', 'email' => 'marco@medfoods.it', 'country' => 'ITA', 'city' => 'Milano', 'type' => 'partner', 'currency' => 'EUR'],
        ];

        $customers = [];
        foreach ($rows as $row) {
            $customers[] = Customer::firstOrCreate(
                ['company_name' => $row['company_name']],
                array_merge($row, ['status' => 'active', 'credit_limit' => 50000, 'created_by' => $admin->id])
            );
        }

        return $customers;
    }

    protected function seedSuppliers(User $admin): void
    {
        $rows = [
            ['company_name' => 'Anadolu Tekstil San. Tic. A.Ş.', 'contact_person' => 'Fatma Kaya', 'type' => 'manufacturer', 'city' => 'Bursa', 'country' => 'TUR', 'currency' => 'TRY'],
            ['company_name' => 'Ege Gıda İhracat Ltd.', 'contact_person' => 'Can Demir', 'type' => 'trader', 'city' => 'İzmir', 'country' => 'TUR', 'currency' => 'TRY'],
            ['company_name' => 'Global Freight Logistics', 'contact_person' => 'John Smith', 'type' => 'logistics', 'city' => 'İstanbul', 'country' => 'TUR', 'currency' => 'USD'],
        ];

        foreach ($rows as $row) {
            Supplier::firstOrCreate(
                ['company_name' => $row['company_name']],
                array_merge($row, ['status' => 'active', 'email' => strtolower(str_replace(' ', '', $row['contact_person'])) . '@supplier.demo', 'created_by' => $admin->id])
            );
        }
    }

    protected function seedProducts(): array
    {
        $rows = [
            ['sku' => 'TXT-001', 'name' => 'Organik Pamuklu Havlu Seti', 'unit_price' => 12.50, 'currency' => 'USD', 'hs_code' => '630260'],
            ['sku' => 'FOD-101', 'name' => 'Kuru İncir (Premium)', 'unit_price' => 8.75, 'currency' => 'USD', 'hs_code' => '080212'],
            ['sku' => 'FOD-102', 'name' => 'Zeytinyağı 5L', 'unit_price' => 45.00, 'currency' => 'EUR', 'hs_code' => '150910'],
            ['sku' => 'MCH-201', 'name' => 'Endüstriyel Pompa Valfi', 'unit_price' => 320.00, 'currency' => 'USD', 'hs_code' => '848180'],
            ['sku' => 'TXT-002', 'name' => 'Bambu Ev Tekstili Seti', 'unit_price' => 28.90, 'currency' => 'USD', 'hs_code' => '630231'],
        ];

        $products = [];
        foreach ($rows as $row) {
            $products[] = Product::firstOrCreate(['sku' => $row['sku']], array_merge($row, ['unit' => 'pcs', 'is_active' => true]));
        }

        return $products;
    }

    protected function seedOrders(User $admin, array $customers, array $products): void
    {
        if (Order::exists()) {
            return;
        }

        $orders = [
            ['customer' => 0, 'status' => 'confirmed', 'currency' => 'EUR', 'incoterm' => 'FOB', 'items' => [[0, 500], [2, 200]]],
            ['customer' => 1, 'status' => 'production', 'currency' => 'USD', 'incoterm' => 'CIF', 'items' => [[1, 1000], [4, 300]]],
            ['customer' => 2, 'status' => 'shipped', 'currency' => 'EUR', 'incoterm' => 'EXW', 'items' => [[3, 50]]],
        ];

        $num = 1;
        foreach ($orders as $spec) {
            $customer = $customers[$spec['customer']];
            $order = Order::create([
                'order_number' => 'ORD-2026-' . str_pad((string) $num++, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'status' => $spec['status'],
                'order_date' => now()->subDays(rand(5, 30)),
                'delivery_date' => now()->addDays(rand(10, 45)),
                'currency' => $spec['currency'],
                'incoterm' => $spec['incoterm'],
                'assigned_user_id' => $admin->id,
            ]);

            $subtotal = 0;
            foreach ($spec['items'] as [$productIdx, $qty]) {
                $product = $products[$productIdx];
                $total = $qty * $product->unit_price;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $qty,
                    'unit' => $product->unit,
                    'unit_price' => $product->unit_price,
                    'total' => $total,
                ]);
                $subtotal += $total;
            }

            $order->update(['subtotal' => $subtotal, 'total_amount' => $subtotal]);
        }
    }

    protected function seedEmployees(array $departments): void
    {
        $rows = [
            ['employee_code' => 'EMP-001', 'first_name' => 'Elif', 'last_name' => 'Arslan', 'position' => 'İhracat Uzmanı', 'dept' => 'SALES', 'email' => 'elif@exportflow.demo'],
            ['employee_code' => 'EMP-002', 'first_name' => 'Burak', 'last_name' => 'Yıldız', 'position' => 'Lojistik Koordinatörü', 'dept' => 'LOG', 'email' => 'burak@exportflow.demo'],
            ['employee_code' => 'EMP-003', 'first_name' => 'Selin', 'last_name' => 'Koç', 'position' => 'Finans Muhasebe', 'dept' => 'FIN', 'email' => 'selin@exportflow.demo'],
        ];

        foreach ($rows as $row) {
            Employee::firstOrCreate(
                ['employee_code' => $row['employee_code']],
                [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'],
                    'position' => $row['position'],
                    'department_id' => $departments[$row['dept']]->id ?? null,
                    'hire_date' => now()->subMonths(rand(6, 36)),
                    'status' => 'active',
                ]
            );
        }
    }

    protected function seedAccounts(array $customers): void
    {
        foreach ($customers as $customer) {
            Account::firstOrCreate(
                ['customer_id' => $customer->id, 'type' => 'customer'],
                [
                    'code' => 'CAR-' . str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT),
                    'name' => $customer->company_name,
                    'currency' => $customer->currency,
                    'opening_balance' => 0,
                    'current_balance' => rand(5000, 50000),
                    'is_active' => true,
                ]
            );
        }
    }

    protected function seedFinance(User $admin): void
    {
        company_treasury()->ensureDefaults();

        $cash = company_treasury()->defaultAccount();
        $bank = company_treasury()->accounts()->firstWhere('type', 'bank');

        $cash->update(['opening_balance' => 75000, 'current_balance' => 75000]);
        if ($bank) {
            $bank->update(['opening_balance' => 120000, 'current_balance' => 120000]);
        }

        if (IncomeExpense::query()->exists()) {
            return;
        }

        $entries = [
            ['type' => 'income', 'category' => 'sales_export', 'item_name' => 'İhracat tahsilatı — Berlin Handels', 'amount' => 45000, 'days' => 2, 'account' => $bank],
            ['type' => 'income', 'category' => 'sales_domestic', 'item_name' => 'Yurtiçi satış geliri', 'amount' => 12500, 'days' => 5, 'account' => $cash],
            ['type' => 'expense', 'category' => 'logistics_freight', 'item_name' => 'Navlun ödemesi', 'amount' => 8200, 'days' => 7, 'account' => $bank],
            ['type' => 'expense', 'category' => 'food_meal', 'item_name' => 'Personel yemek', 'amount' => 680, 'days' => 3, 'account' => $cash],
            ['type' => 'expense', 'category' => 'utility_electric', 'item_name' => 'Elektrik faturası', 'amount' => 2340, 'days' => 10, 'account' => $bank],
            ['type' => 'expense', 'category' => 'rent_office', 'item_name' => 'Ofis kirası', 'amount' => 15000, 'days' => 12, 'account' => $bank],
            ['type' => 'income', 'category' => 'sales_export', 'item_name' => 'Gulf Trading tahsilat', 'amount' => 28000, 'days' => 18, 'account' => $bank],
            ['type' => 'expense', 'category' => 'office_supplies', 'item_name' => 'Kırtasiye alımı', 'amount' => 420, 'days' => 15, 'account' => $cash],
            ['type' => 'expense', 'category' => 'vehicle_fuel', 'item_name' => 'Mazot', 'amount' => 1850, 'days' => 20, 'account' => $cash],
            ['type' => 'income', 'category' => 'sales_service', 'item_name' => 'Lojistik hizmet geliri', 'amount' => 5600, 'days' => 25, 'account' => $cash],
            ['type' => 'expense', 'category' => 'salary', 'item_name' => 'Maaş ödemeleri', 'amount' => 32000, 'days' => 28, 'account' => $bank],
            ['type' => 'expense', 'category' => 'customs_duty', 'item_name' => 'Gümrük vergisi', 'amount' => 4100, 'days' => 35, 'account' => $bank],
            ['type' => 'income', 'category' => 'sales_export', 'item_name' => 'Nordic Import ödemesi', 'amount' => 19500, 'days' => 42, 'account' => $bank],
            ['type' => 'expense', 'category' => 'utility_internet', 'item_name' => 'İnternet faturası', 'amount' => 890, 'days' => 45, 'account' => $bank],
            ['type' => 'expense', 'category' => 'food_tea_coffee', 'item_name' => 'Ofis ikram', 'amount' => 260, 'days' => 48, 'account' => $cash],
        ];

        foreach ($entries as $row) {
            $account = $row['account'] ?? $cash;
            $date = now()->subDays($row['days'])->toDateString();
            $amount = (float) $row['amount'];
            $delta = $row['type'] === 'income' ? $amount : -$amount;

            $entry = IncomeExpense::create([
                'type' => $row['type'],
                'category' => $row['category'],
                'item_name' => $row['item_name'],
                'description' => $row['item_name'],
                'amount' => $amount,
                'currency' => 'TRY',
                'exchange_rate' => 1,
                'amount_base' => $amount,
                'account_id' => $account->id,
                'transaction_date' => $date,
                'user_id' => $admin->id,
            ]);

            $account->increment('current_balance', $delta);

            AccountTransaction::create([
                'account_id' => $account->id,
                'type' => $delta >= 0 ? 'credit' : 'debit',
                'amount' => abs($delta),
                'currency' => $account->currency,
                'exchange_rate' => 1,
                'reference_type' => IncomeExpense::class,
                'reference_id' => $entry->id,
                'description' => ($row['type'] === 'income' ? 'Gelir: ' : 'Gider: ') . $row['item_name'],
                'transaction_date' => $date,
                'user_id' => $admin->id,
            ]);
        }
    }

    protected function syncModules(User $admin): void
    {
        modules()->syncDiscovered();

        $insurance = SystemModule::where('slug', 'insurance')->first();
        if (!$insurance) {
            return;
        }

        modules()->syncPermissions($insurance);

        if (!$insurance->is_enabled) {
            modules()->enable($insurance);
        }

        $superAdmin = Role::findByName('super-admin');
        foreach ($insurance->manifest['permissions'] ?? [] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $superAdmin->givePermissionTo($perm);
        }
    }
}
