<?php

namespace App\Services\Forus\Identity\Models;

use Illuminate\Database\Eloquent\Model;

use App\Services\Forus\EthereumWallet\Traits\HasEthereumWallet;

/**
 * App\Services\Forus\Identity\Models\Identity
 *
 * @property int $id
 * @property string $pin_code
 * @property Collection $types
 * @property Collection $proxies
 * @property string $address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 * @property string $public_key
 * @property string $private_key
 * @property string|null $passphrase
 * @property string $address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Identity\Models\IdentityProxy[] $proxies
 * @property-read int|null $proxies_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity wherePassphrase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity wherePinCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity wherePrivateKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Identity extends Model
{
    use HasEthereumWallet;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pin_code', 'address', 'passphrase', 'private_key', 'public_key'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function proxies() {
        return $this->hasMany(IdentityProxy::class, 'identity_address', 'address');
    }

    /**
     * @param string $address
     * @return self
     */
    public function findByAddress(string $address) {
        return self::where(compact('address'))->first();
    }
}
