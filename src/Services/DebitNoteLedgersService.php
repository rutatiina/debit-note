<?php

namespace Rutatiina\CreditNote\Services;

use Rutatiina\CreditNote\Models\CreditNoteLedger;

class CreditNoteLedgersService
{
    public static $errors = [];

    public function __construct()
    {
        //
    }

    public static function store($data)
    {
        foreach ($data['ledgers'] as &$ledger)
        {
            $ledger['credit_note_id'] = $data['id'];
            CreditNoteLedger::create($ledger);
        }
        unset($ledger);

    }

}
