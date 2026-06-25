<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Crm\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Logistics\PortController;
use App\Http\Controllers\Logistics\ShipmentController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicMediaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Settings\CurrencyController;
use App\Http\Controllers\Settings\DepartmentController;
use App\Http\Controllers\Settings\LanguageController;
use App\Http\Controllers\Settings\ModuleController;
use App\Http\Controllers\Settings\PaymentMethodController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Supplier\SupplierController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return filter_var(config('ticari.installed'), FILTER_VALIDATE_BOOLEAN)
        ? redirect()->route('login')
        : redirect()->route('install.welcome');
})->name('home');

// Installer (no auth required)
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'welcome'])->name('welcome');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database', [InstallController::class, 'databaseStore'])->name('database.store');
    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/admin', [InstallController::class, 'adminStore'])->name('admin.store');
    Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
});

// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/manifest.webmanifest', ManifestController::class)->name('manifest');
Route::get('/media/{path}', [PublicMediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.show');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
    Route::get('/theme/{theme}', [ThemeController::class, 'switch'])->name('theme.switch');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // CRM
    Route::resource('customers', CustomerController::class);

    // Suppliers
    Route::resource('suppliers', SupplierController::class);

    // Orders
    Route::resource('orders', OrderController::class);

    // Logistics
    Route::get('/shipments/tracking', [ShipmentController::class, 'tracking'])->name('shipments.tracking');
    Route::post('/shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])->name('shipments.status');
    Route::resource('shipments', ShipmentController::class);
    Route::post('/api/ports', [PortController::class, 'store'])->name('ports.store');

    Route::prefix('vessels')->name('vessels.')->group(function () {
        Route::get('/track', [\App\Http\Controllers\Logistics\VesselTrackingController::class, 'index'])->name('track.index');
        Route::get('/track/search', [\App\Http\Controllers\Logistics\VesselTrackingController::class, 'search'])->name('track.search');
        Route::delete('/{vessel}/track', [\App\Http\Controllers\Logistics\VesselTrackingController::class, 'destroy'])->name('track.destroy');
        Route::get('/{vessel}/track', [\App\Http\Controllers\Logistics\VesselTrackingController::class, 'show'])->name('track.show');
    });

    Route::post('/exchange-rates/sync', [\App\Http\Controllers\ExchangeRateController::class, 'sync'])->name('exchange-rates.sync');
    Route::get('/api/exchange-rates', [\App\Http\Controllers\ExchangeRateController::class, 'rates'])->name('exchange-rates.api');
    Route::post('/api/exchange-rates/convert', [\App\Http\Controllers\ExchangeRateController::class, 'convert'])->name('exchange-rates.convert');

    // Finance
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::get('/treasury', [FinanceController::class, 'treasury'])->name('treasury');
        Route::post('/treasury/accounts', [FinanceController::class, 'storeTreasuryAccount'])->name('treasury.accounts.store');
        Route::get('/accounts', [FinanceController::class, 'accounts'])->name('accounts');
        Route::get('/accounts/create', [FinanceController::class, 'createAccount'])->name('accounts.create');
        Route::post('/accounts', [FinanceController::class, 'storeAccount'])->name('accounts.store');
        Route::get('/accounts/{account}/edit', [FinanceController::class, 'editAccount'])->name('accounts.edit');
        Route::put('/accounts/{account}', [FinanceController::class, 'updateAccount'])->name('accounts.update');
        Route::get('/accounts/{account}', [FinanceController::class, 'showAccount'])->name('accounts.show');
        Route::get('/payments', [FinanceController::class, 'payments'])->name('payments');
        Route::post('/payments', [FinanceController::class, 'storePayment'])->name('payments.store');
        Route::get('/payments/{payment}', [FinanceController::class, 'showPayment'])->name('payments.show');
        Route::get('/payments/{payment}/edit', [FinanceController::class, 'editPayment'])->name('payments.edit');
        Route::put('/payments/{payment}', [FinanceController::class, 'updatePayment'])->name('payments.update');
        Route::delete('/payments/{payment}', [FinanceController::class, 'destroyPayment'])->name('payments.destroy');
        Route::get('/collections', [FinanceController::class, 'collections'])->name('collections');
        Route::post('/collections', [FinanceController::class, 'storeCollection'])->name('collections.store');
        Route::get('/collections/{collection}', [FinanceController::class, 'showCollection'])->name('collections.show');
        Route::get('/collections/{collection}/edit', [FinanceController::class, 'editCollection'])->name('collections.edit');
        Route::put('/collections/{collection}', [FinanceController::class, 'updateCollection'])->name('collections.update');
        Route::delete('/collections/{collection}', [FinanceController::class, 'destroyCollection'])->name('collections.destroy');
        Route::get('/income-expenses', [FinanceController::class, 'incomeExpenses'])->name('income-expenses');
        Route::post('/income-expenses', [FinanceController::class, 'storeIncomeExpense'])->name('income-expenses.store');
        Route::get('/income-expenses/{incomeExpense}/edit', [FinanceController::class, 'editIncomeExpense'])->name('income-expenses.edit');
        Route::put('/income-expenses/{incomeExpense}', [FinanceController::class, 'updateIncomeExpense'])->name('income-expenses.update');
        Route::delete('/income-expenses/{incomeExpense}', [FinanceController::class, 'destroyIncomeExpense'])->name('income-expenses.destroy');
        Route::get('/profit-loss', [FinanceController::class, 'profitLoss'])->name('profit-loss');
    });

    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('/documents/backup', [DocumentController::class, 'backup'])->name('documents.backup');

    // Tasks & Calendar
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::get('/calendar', [TaskController::class, 'calendar'])->name('calendar.index');
    Route::post('/calendar/events', [TaskController::class, 'storeEvent'])->name('calendar.events.store');

    // Employees
    Route::resource('employees', EmployeeController::class);

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/logistics', [ReportController::class, 'logistics'])->name('logistics');
        Route::get('/finance', [ReportController::class, 'finance'])->name('finance');
        Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
    });

    // Emails
    Route::prefix('emails')->name('emails.')->group(function () {
        Route::get('/', [EmailController::class, 'index'])->name('index');
        Route::get('/accounts', [EmailController::class, 'accounts'])->name('accounts');
        Route::post('/accounts', [EmailController::class, 'storeAccount'])->name('accounts.store');
        Route::get('/accounts/{account}/edit', [EmailController::class, 'editAccount'])->name('accounts.edit');
        Route::put('/accounts/{account}', [EmailController::class, 'updateAccount'])->name('accounts.update');
        Route::delete('/accounts/{account}', [EmailController::class, 'destroyAccount'])->name('accounts.destroy');
        Route::post('/sync', [EmailController::class, 'sync'])->name('sync');
        Route::get('/compose', [EmailController::class, 'compose'])->name('compose');
        Route::post('/send', [EmailController::class, 'send'])->name('send');
        Route::get('/{email}', [EmailController::class, 'show'])->name('show');
    });

    // AI
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/', [AiController::class, 'index'])->name('index');
        Route::post('/email', [AiController::class, 'generateEmail'])->name('email');
        Route::post('/summarize', [AiController::class, 'summarizeReport'])->name('summarize');
        Route::post('/operations', [AiController::class, 'operationSuggestions'])->name('operations');
        Route::post('/finance', [AiController::class, 'financialAnalysis'])->name('finance');
        Route::post('/translate', [AiController::class, 'translate'])->name('translate');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->middleware('permission:settings.view')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/company', [SettingsController::class, 'company'])->name('company');
        Route::put('/company', [SettingsController::class, 'update'])->middleware('permission:settings.edit')->name('company.update');
        Route::get('/branding', [SettingsController::class, 'branding'])->name('branding');
        Route::put('/branding', [SettingsController::class, 'updateBranding'])->middleware('permission:settings.edit')->name('branding.update');
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::put('/security', [SettingsController::class, 'updateSecurity'])->middleware('permission:settings.edit')->name('security.update');
        Route::get('/mail', [SettingsController::class, 'mail'])->name('mail');
        Route::put('/mail', [SettingsController::class, 'updateMail'])->middleware('permission:settings.edit')->name('mail.update');
        Route::get('/ai', [SettingsController::class, 'ai'])->name('ai');
        Route::put('/ai', [SettingsController::class, 'updateAi'])->middleware('permission:settings.edit')->name('ai.update');
        Route::get('/marinetraffic', [SettingsController::class, 'marinetraffic'])->name('marinetraffic');
        Route::put('/marinetraffic', [SettingsController::class, 'updateMarinetraffic'])->middleware('permission:settings.edit')->name('marinetraffic.update');
        Route::get('/audit-log', [SettingsController::class, 'auditLog'])->name('audit-log');
        Route::get('/updates', [SettingsController::class, 'updates'])->name('updates');
        Route::post('/updates', [SettingsController::class, 'applyUpdate'])->middleware('permission:settings.edit')->name('updates.apply');

        Route::middleware('permission:users.view')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.edit')->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');
            Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->middleware('permission:users.edit')->name('users.toggle-active');
        });

        Route::middleware('permission:settings.edit')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
            Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
            Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
            Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
        });

        Route::get('/languages', [LanguageController::class, 'index'])->name('languages.index');
        Route::get('/currencies', [CurrencyController::class, 'index'])->name('currencies.index');
        Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::get('/payment-methods/create', [PaymentMethodController::class, 'create'])->middleware('permission:settings.edit')->name('payment-methods.create');
        Route::get('/payment-methods/{paymentMethod}/edit', [PaymentMethodController::class, 'edit'])->middleware('permission:settings.edit')->name('payment-methods.edit');
        Route::get('/payment-methods/{paymentMethod}/fields', [PaymentMethodController::class, 'fields'])->name('payment-methods.fields');
        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::get('/lookups', [ModuleController::class, 'lookups'])->name('lookups.index');

        Route::middleware('permission:settings.edit')->group(function () {
            Route::post('/languages', [LanguageController::class, 'store'])->name('languages.store');
            Route::put('/languages/{language}', [LanguageController::class, 'update'])->name('languages.update');
            Route::delete('/languages/{language}', [LanguageController::class, 'destroy'])->name('languages.destroy');
            Route::post('/currencies', [CurrencyController::class, 'store'])->name('currencies.store');
            Route::put('/currencies/{currency}', [CurrencyController::class, 'update'])->name('currencies.update');
            Route::delete('/currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');
            Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
            Route::put('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
            Route::delete('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
            Route::post('/modules/upload', [ModuleController::class, 'upload'])->name('modules.upload');
            Route::post('/modules/{module}/toggle', [ModuleController::class, 'toggle'])->name('modules.toggle');
            Route::delete('/modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');
            Route::post('/lookups/types', [ModuleController::class, 'storeLookupType'])->name('lookups.types.store');
            Route::post('/lookups/{lookupType}/values', [ModuleController::class, 'storeLookupValue'])->name('lookups.values.store');
            Route::delete('/lookups/values/{lookupValue}', [ModuleController::class, 'destroyLookupValue'])->name('lookups.values.destroy');
        });
    });
});
