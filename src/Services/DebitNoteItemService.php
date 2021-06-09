<?php

namespace Rutatiina\DebitNote\Services;

use Rutatiina\DebitNote\Models\DebitNoteItem;
use Rutatiina\DebitNote\Models\DebitNoteItemTax;

class DebitNoteItemService
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
            $item['debit_note_id'] = $data['id'];

            $itemTaxes = (is_array($item['taxes'])) ? $item['taxes'] : [] ;
            unset($item['taxes']);

            $itemModel = DebitNoteItem::create($item);

            foreach ($itemTaxes as $tax)
            {
                //save the taxes attached to the item
                $itemTax = new DebitNoteItemTax;
                $itemTax->tenant_id = $item['tenant_id'];
                $itemTax->debit_note_id = $item['debit_note_id'];
                $itemTax->debit_note_item_id = $itemModel->id;
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
