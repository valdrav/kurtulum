<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'shipment_number',
        'order_id',
        'customer_id',
        'transport_mode',
        'status',
        'status_location',
        'status_updated_at',
        'origin_port_id',
        'destination_port_id',
        'etd',
        'eta',
        'atd',
        'ata',
        'bl_number',
        'awb_number',
        'incoterm',
        'origin',
        'destination',
        'vessel_id',
        'voyage_number',
        'cmr_number',
        'flight_number',
        'airline',
        'vehicle_id',
        'driver_id',
        'carrier',
        'forwarder',
        'currency',
        'total_weight_kg',
        'total_volume_cbm',
        'package_count',
        'cargo_description',
        'total_cost',
        'notes',
        'assigned_user_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'etd' => 'date',
            'eta' => 'date',
            'atd' => 'date',
            'ata' => 'date',
            'status_updated_at' => 'datetime',
        ];
    }

    public function statusDisplay(): string
    {
        return shipment_status_display($this);
    }

    public function displayLabel(): string
    {
        $parts = [$this->shipment_number];

        if ($this->cargo_description) {
            $parts[] = $this->cargo_description;
        } elseif ($this->package_count) {
            $parts[] = $this->package_count . ' box';
        }

        return implode(' · ', $parts);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function originPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'origin_port_id');
    }

    public function destinationPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'destination_port_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function legs(): HasMany
    {
        return $this->hasMany(ShipmentLeg::class);
    }

    public function containers(): BelongsToMany
    {
        return $this->belongsToMany(Container::class, 'shipment_container');
    }

    public function costs(): HasMany
    {
        return $this->hasMany(ShipmentCost::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ShipmentMilestone::class);
    }

    public function customsDeclarations(): HasMany
    {
        return $this->hasMany(CustomsDeclaration::class);
    }

    public function lettersOfCredit(): HasMany
    {
        return $this->hasMany(LetterOfCredit::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function emails(): MorphMany
    {
        return $this->morphMany(Email::class, 'emailable');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }
}
