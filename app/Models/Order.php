<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_area',
        'customer_address',
        'customer_notes',
        'subtotal',
        'discount_total',
        'delivery_fee',
        'total',
        'currency',
        'order_status',
        'payment_method',
        'payment_status',
        'transaction_reference',
        'paid_amount',
        'paid_currency',
        'paid_at',
        'payment_notes',
        'payment_provider_response',
        'source',
        'whatsapp_message',
        'safe_ip_hash_or_masked_ip',
        'user_agent_hash_optional',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'payment_provider_response' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
