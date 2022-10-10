<?php

namespace Rutatiina\DebitNote\Models;

use Illuminate\Database\Eloquent\Model;
use Rutatiina\Tenant\Scopes\TenantIdScope;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebitNoteItemTax extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static $logName = 'TxnItem';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_debit_note_item_taxes';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new TenantIdScope);
    }

    public function tax()
    {
        return $this->hasOne('Rutatiina\Tax\Models\Tax', 'code', 'tax_code');
    }

    public function debit_note()
    {
        return $this->belongsTo('Rutatiina\DebitNote\Models\DebitNote', 'debit_note_id', 'id');
    }

    public function debit_note_item()
    {
        return $this->belongsTo('Rutatiina\DebitNote\Models\DebitNoteItem', 'debit_note_item_id', 'id');
    }

}
