<?php

namespace Rutatiina\DebitNote\Services;

use Rutatiina\DebitNote\Models\DebitNoteLedger;

class DebitNoteLedgersService
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
            $ledger['debit_note_id'] = $data['id'];
            DebitNoteLedger::create($ledger);
        }
        unset($ledger);

    }

}
