# ExportFlow — Genişletilebilirlik Kılavuzu

Bu proje **plugin/modül mimarisi** ile tasarlanmıştır. Dil, para birimi, ödeme yöntemi ve yeni modüller admin panelden veya kod ile kolayca eklenebilir.

## 1. Yeni Modül Ekleme

### ZIP ile yükleme (önerilen)

1. **Ayarlar → Modül Yönetimi** sayfasına gidin
2. Modül ZIP dosyasını seçip **Yükle**'ye tıklayın
3. Listeden **Etkinleştir**'e basın

Örnek paketler: `storage/app/module-samples/` (insurance.zip, warehouse.zip, quality-check.zip)

### Manuel kurulum

```
modules/
  YourModule/
    module.json
    ModuleServiceProvider.php
    Http/Controllers/
    Resources/views/
    Routes/web.php
```

Sayfayı açtığınızda modüller otomatik taranır.

## 2. Hook Sistemi

```php
hook()->register('payment.before_create', fn ($data) => $data);
hook()->filter('payment.validation_rules', $rules, $method);
hook()->fire('shipment.created', $shipment);
```

## 3. Registry

```php
registry()->languages();
registry()->currencies();
registry()->lookup('incoterms');
payment_methods()->forPayment();
```

## 4. Ödeme Yöntemi Dinamik Alanları

Ayarlar → Ödeme Yöntemleri → JSON config_schema ile özel alanlar tanımlayın.

## 5. Örnek Modül

`modules/Insurance/` klasörünü inceleyin.
