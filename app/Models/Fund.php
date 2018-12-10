<?php

namespace App\Models;

use App\Services\BunqService\BunqService;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Class Fund
 * @property mixed $id
 * @property integer $organization_id
 * @property string $state
 * @property string $name
 * @property Organization $organization
 * @property float $budget_total
 * @property float $budget_validated
 * @property float $budget_used
 * @property float $budget_left
 * @property Media $logo
 * @property FundConfig $fund_config
 * @property Collection $metas
 * @property Collection $products
 * @property Collection $product_categories
 * @property Collection $criteria
 * @property Collection $vouchers
 * @property Collection $voucher_transactions
 * @property Collection $providers
 * @property Collection $validators
 * @property Collection $provider_organizations
 * @property Collection $provider_organizations_approved
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property float $notification_amount
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Fund extends Model
{
    use HasMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'state', 'name', 'start_date', 'end_date', 'notification_amount'
    ];

    protected $hidden = [
        'fund_config'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'start_date',
        'end_date'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function metas() {
        return $this->hasMany(FundMeta::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products() {
        return $this->belongsToMany(
            Product::class,
            'fund_products'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function product_categories() {
        return $this->belongsToMany(
            ProductCategory::class,
            'fund_product_categories'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function criteria() {
        return $this->hasMany(FundCriterion::class);
    }

    /**
     * Get fund logo
     * @return MorphOne
     */
    public function logo() {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'fund_logo'
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers() {
        return $this->hasMany(Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function voucher_transactions() {
        return $this->hasManyThrough(VoucherTransaction::class, Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function providers() {
        return $this->hasMany(FundProvider::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function provider_organizations() {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function top_ups() {
        return $this->hasMany(FundTopUp::class);
    }

    /**
     * @return float
     */
    public function getBudgetValidatedAttribute() {
        return 0;
    }

    /**
     * @return float
     */
    public function getBudgetTotalAttribute() {
        return round($this->top_ups()->where([
            'state' => 'confirmed'
        ])->sum('amount'), 2);
    }

    /**
     * @return float
     */
    public function getBudgetUsedAttribute() {
        return round($this->voucher_transactions->sum('amount'), 2);
    }

    /**
     * @return float
     */
    public function getBudgetLeftAttribute() {
        return round($this->budget_total - $this->budget_used, 2);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function provider_organizations_approved() {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        )->where('fund_providers.state', 'approved');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function validators() {
        return $this->hasManyThrough(
            Validator::class,
            Organization::class,
            'id',
            'organization_id',
            'organization_id',
            'id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function fund_config() {
        return $this->hasOne(FundConfig::class);
    }

    /**
     * @return array|null
     */
    public function getBunqKey() {
        if (!$this->fund_config) {
            return null;
        }

        return [
            "key" => $this->fund_config->bunq_key,
            "sandbox" => $this->fund_config->bunq_sandbox,
            "allowed_ip" => $this->fund_config->bunq_allowed_ip,
        ];
    }

    /**
     * @return array|null
     */
    public function getFundFormula() {
        if (!$this->fund_config) {
            return null;
        }

        return [
            "amount" => $this->fund_config->formula_amount,
            "multiplier" => $this->fund_config->formula_multiplier,
        ];
    }

    public static function getTrustedRecordOfType(
        Fund $fund,
        string $identity_address,
        string $recordType
    ) {
        $recordRepo = app()->make('forus.services.record');

        $trustedIdentities = $fund->validators->pluck(
            'identity_address'
        );

        /** @var FundCriterion $criterion */
        $recordsOfType = collect($recordRepo->recordsList(
            $identity_address, $recordType
        ));

        $validRecordsOfType = $recordsOfType->map(function($record) use (
            $trustedIdentities
        ) {
            $record['validations'] = collect($record['validations'])->whereIn(
                'identity_address', $trustedIdentities
            )->sortByDesc('created_at');

            return $record;
        })->filter(function($record) {
            return count($record['validations']) > 0;
        })->sortByDesc(function($record) {
            return $record['validations'][0]['created_at'];
        });

        return collect($validRecordsOfType)->first();
    }

    public static function amountForIdentity(Fund $fund, $identityAddress)
    {
        $fundFormula = $fund->getFundFormula();

        $record = self::getTrustedRecordOfType(
            $fund, $identityAddress, $fundFormula['multiplier']
        );

        return $fundFormula['amount'] * $record['value'];
    }

    /**
     * @return BunqService|string
     */
    public function getBunq() {
        $fundBunq = $this->getBunqKey();

        if (empty($fundBunq) || empty($fundBunq['key'])) {
            app('log')->alert('No bunq config for fund: ' . $this->id);
            return false;
        }

        $bunqService = BunqService::create(
            $this->id,
            $fundBunq['key'],
            $fundBunq['allowed_ip'],
            $fundBunq['sandbox']
        );

        return $bunqService;
    }

    public static function configuredFunds () {
        try {
            return static::query()->whereHas('fund_config')->get();
        } catch (\Exception $exception) {
            return collect();
        }
    }

    /**
     */
    public static function checkStateQueue() {
        $funds = self::query()
            ->whereHas('fund_config', function (Builder $query){
                return $query->where('is_configured', true);
            })
            ->whereDate('start_date', '>=', now()->startOfDay())
            ->get();

        if ($funds->count() == 0) {
            return null;
        }

        /** @var self $fund */
        foreach($funds as $fund) {

            if (($fund->start_date->isToday() && $fund->state != 'active') || ($fund->start_date->gt(now()) && $fund->state == 'waiting')) {
                $fund->update([
                    'state' => 'active'
                ]);
            }

            if ($fund->end_date->isToday() && $fund->state != 'closed') {
                $fund->update([
                    'state' => 'closed'
                ]);
            }
        }
    }

    /**
     * @return void
     */
    public static function checkConfigStateQueue()
    {
        $funds = self::query()
            ->whereHas('fund_config', function (Builder $query){
                return $query->where('is_configured', true);
            })
            ->where('state', 'waiting')
            ->whereDate('start_date', '<', now())
            ->get();

        if ($funds->count() == 0) {
            return null;
        }

        /** @var self $fund */
        foreach($funds as $fund) {
            $fund->update([
                'state' => 'paused'
            ]);

            $fund->criteria()->create([
                'record_type_key' => $fund->fund_config->key . '_eligible',
                'value' => "Ja",
                'operator' => '='
            ]);

            $fund->criteria()->create([
                'record_type_key' => 'children_nth',
                'value' => 1,
                'operator' => '>='
            ]);

            $organizations = Organization::query()->whereIn(
                'id', OrganizationProductCategory::query()->whereIn(
                'product_category_id',
                $fund->product_categories()->pluck('id')->all()
            )->pluck('organization_id')->toArray()
            )->get();

            /** @var Organization $organization */
            foreach ($organizations as $organization) {
                resolve('forus.services.mail_notification')->newFundApplicable(
                    $organization->emailServiceId(),
                    $fund->name,
                    config('forus.front_ends.panel-provider')
                );
            }
        }
    }

    /**
     * @return void
     */
    public static function calculateUsersQueue()
    {
        $funds = self::query()
            ->whereHas('fund_config', function (Builder $query){
                return $query->where('is_configured', true);
            })
            ->whereIn('state', ['active', 'paused'])
            ->get();

        if ($funds->count() == 0) {
            return null;
        }

        /** @var self $fund */
        foreach($funds as $fund) {

            $organization = $fund->organization;

            $sponsorCount = $organization->employees->count() + 1;

            $providers = $fund->providers()->where([
                'state' => 'approved'
            ])->get();

            $providerCount = $providers->map(function ($fundProvider){
                /** @var FundProvider $fundProvider */
                return $fundProvider->organization->employees->count() + 1;
            })->sum();

            if($fund->state == 'active'){
                $requesterCount = $fund->vouchers()->whereNull('parent_id')->count();
            }else{
                $requesterCount = 0;
            }

            resolve('forus.services.mail_notification')->calculateFundUsers(
                $fund->name,
                $organization->name,
                $sponsorCount,
                $providerCount,
                $requesterCount,
                ($sponsorCount + $providerCount + $requesterCount)
            );
        }
    }
}