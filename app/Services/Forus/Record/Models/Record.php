<?php

namespace App\Services\Forus\Record\Models;

use App\Models\Traits\EloquentModel;
use Barryvdh\LaravelIdeHelper\Eloquent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Record
 * @mixin Eloquent
 * @property mixed $id
 * @property string $identity_address
 * @property integer $record_type_id
 * @property integer $record_category_id
 * @property string $value
 * @property integer $order
 * @property RecordType $record_type
 * @property RecordCategory $record_category
 * @property Collection $validations
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Record extends Model
{
    use EloquentModel;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'record_type_id', 'record_category_id',
        'value', 'order'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function record_type() {
        return $this->belongsTo(RecordType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function record_category() {
        return $this->belongsTo(RecordCategory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function validations() {
        return $this->hasMany(RecordValidation::class);
    }
}
