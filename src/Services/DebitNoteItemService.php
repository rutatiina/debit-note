<?php

namespace Rutatiina\CreditNote\Services;

use Rutatiina\CreditNote\Models\CreditNoteItem;
use Rutatiina\CreditNote\Models\CreditNoteItemTax;

class CreditNoteItemService
{
    public static $errors = [];

    public function __construct()
    {
        //
    }

    public static function store($data)
    {
        //print_r($data['items']); exit;

        //Save the items >> $data['items']
        foreach ($data['items'] as &$item)
        {
            $item['credit_note_id'] = $data['id'];

            $itemTaxes = (is_array($item['taxes'])) ? $item['taxes'] : [] ;
            unset($item['taxes']);

            $itemModel = CreditNoteItem::create($item);

            foreach ($itemTaxes as $tax)
            {
                //save the taxes attached to the item
                $itemTax = new CreditNoteItemTax;
                $itemTax->tenant_id = $item['tenant_id'];
                $itemTax->credit_note_id = $item['credit_note_id'];
                $itemTax->credit_note_item_id = $itemModel->id;
                $itemTax->tax_code = $tax['code'];
                $itemTax->amount = $tax['total'];
                $itemTax->inclusive = $tax['inclusive'];
                $itemTax->exclusive = $tax['exclusive'];
                $itemTax->save();
            }
            unset($tax);
        }
        unset($item);

    }

}
