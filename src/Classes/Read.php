<?php

namespace Rutatiina\DebitNote\Classes;

use Rutatiina\DebitNote\Models\DebitNote;

use Rutatiina\DebitNote\Traits\Init as TxnTraitsInit;

class Read
{
    use TxnTraitsInit;

    public function __construct()
    {
    }

    public function run($id)
    {
        $Txn = DebitNote::find($id);

        if ($Txn)
        {
            //txn has been found so continue normally
        }
        else
        {
            $this->errors[] = 'Transaction not found';
            return false;
        }

        $Txn->load('contact', 'debit_account', 'credit_account', 'items');
        $Txn->setAppends(['taxes']);

        foreach ($Txn->items as &$item)
        {

            if (empty($item->name))
            {
                $txnDescription[] = $item->description;
            }
            else
            {
                $txnDescription[] = (empty($item->description)) ? $item->name : $item->name . ': ' . $item->description;
            }

        }

        $Txn->description = implode(',', $txnDescription);

        return $Txn->toArray();

    }

}
