<?php

namespace App\Models;

use Database\Factories\InvoiceNumberSequenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $year
 * @property int $last_number
 */
class InvoiceNumberSequence extends Model
{
    /** @use HasFactory<InvoiceNumberSequenceFactory> */
    use HasFactory;

    protected $primaryKey = 'year';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'year',
        'last_number',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
