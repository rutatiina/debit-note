<?php

namespace Rutatiina\DebitNote\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Rutatiina\DebitNote\Models\DebitNote;
use Rutatiina\FinancialAccounting\Services\AccountBalanceUpdateService;
use Rutatiina\FinancialAccounting\Services\ContactBalanceUpdateService;
use Rutatiina\DebitNote\Models\DebitNoteSetting;
use Rutatiina\Tax\Models\Tax;

class DebitNoteService
{
    public static $errors = [];

    public function __construct()
    {
        //
    }

    public static function nextNumber()
    {
        $count = DebitNote::count();
        $settings = DebitNoteSetting::first();

        return $settings->number_prefix . (str_pad(($count + 1), $settings->minimum_number_length, "0", STR_PAD_LEFT)) . $settings->number_postfix;
    }

    public static function edit($id)
    {
        $taxes = Tax::all()->keyBy('code');

        $txn = DebitNote::findOrFail($id);
        $txn->load('contact', 'items.taxes');
        $txn->setAppends(['taxes']);

        $attributes = $txn->toArray();

        //print_r($attributes); exit;

        $attributes['_method'] = 'PATCH';

        $attributes['contact']['currency'] = $txn->contact->currency_and_exchange_rate;
        $attributes['contact']['currencies'] = $txn->contact->currencies_and_exchange_rates;

        $attributes['taxes'] = json_decode('{}');

        foreach ($attributes['items'] as &$item)
        {
            $selectedItem = [
                'id' => $item['item_id'],
                'name' => $item['name'],
                'description' => $item['description'],
                'rate' => $item['rate'],
                'tax_method' => 'inclusive',
                'account_type' => null,
            ];

            $item['selectedItem'] = $selectedItem; #required
            $item['selectedTaxes'] = []; #required
            $item['displayTotal'] = 0; #required

            foreach ($item['taxes'] as $itemTax)
            {
                $item['selectedTaxes'][] = $taxes[$itemTax['tax_code']];
            }

            $item['rate'] = floatval($item['rate']);
            $item['quantity'] = floatval($item['quantity']);
            $item['total'] = floatval($item['total']);
            $item['displayTotal'] = $item['total']; #required
        };

        return $attributes;
    }

    public static function store($requestInstance)
    {
        $data = DebitNoteValidateService::run($requestInstance);
        //print_r($data); exit;
        if ($data === false)
        {
            self::$errors = DebitNoteValidateService::$errors;
            return false;
        }

        //start database transaction
        DB::connection('tenant')->beginTransaction();

        try
        {
            $Txn = new DebitNote;
            $Txn->tenant_id = $data['tenant_id'];
            $Txn->created_by = Auth::id();
            $Txn->document_name = $data['document_name'];
            $Txn->number = $data['number'];
            $Txn->date = $data['date'];
            $Txn->debit_financial_account_code = $data['debit_financial_account_code'];
            $Txn->contact_id = $data['contact_id'];
            $Txn->contact_name = $data['contact_name'];
            $Txn->contact_address = $data['contact_address'];
            $Txn->reference = $data['reference'];
            $Txn->base_currency = $data['base_currency'];
            $Txn->quote_currency = $data['quote_currency'];
            $Txn->exchange_rate = $data['exchange_rate'];
            $Txn->taxable_amount = $data['taxable_amount'];
            $Txn->total = $data['total'];
            $Txn->branch_id = $data['branch_id'];
            $Txn->store_id = $data['store_id'];
            $Txn->contact_notes = $data['contact_notes'];
            $Txn->terms_and_conditions = $data['terms_and_conditions'];
            $Txn->status = $data['status'];

            $Txn->save();

            $data['id'] = $Txn->id;

            //print_r($data['items']); exit;

            //Save the items >> $data['items']
            DebitNoteItemService::store($data);

            //Save the ledgers >> $data['ledgers']; and update the balances
            $Txn->ledgers()->createMany($data['ledgers']);

            //$Txn->refresh(); //make the ledgers relationship infor available

            //update financial account and contact balances accordingly
            DebitNoteApprovalService::run($Txn);

            DB::connection('tenant')->commit();

            return $Txn;

        }
        catch (\Throwable $e)
        {
            DB::connection('tenant')->rollBack();

            Log::critical('Fatal Internal Error: Failed to save debit note to database');
            Log::critical($e);

            //print_r($e); exit;
            if (App::environment('local'))
            {
                self::$errors[] = 'Error: Failed to save debit note to database.';
                self::$errors[] = 'File: ' . $e->getFile();
                self::$errors[] = 'Line: ' . $e->getLine();
                self::$errors[] = 'Message: ' . $e->getMessage();
            }
            else
            {
                self::$errors[] = 'Fatal Internal Error: Failed to save debit note to database. Please contact Admin';
            }

            return false;
        }
        //*/

    }

    public static function update($requestInstance)
    {
        $data = DebitNoteValidateService::run($requestInstance);
        //print_r($data); exit;
        if ($data === false)
        {
            self::$errors = DebitNoteValidateService::$errors;
            return false;
        }

        //start database transaction
        DB::connection('tenant')->beginTransaction();

        try
        {
            $Txn = DebitNote::with('items', 'ledgers')->findOrFail($data['id']);

            if ($Txn->status == 'approved')
            {
                self::$errors[] = 'Approved debit note cannot be not be edited';
                return false;
            }

            //reverse the account balances
            AccountBalanceUpdateService::doubleEntry($Txn->toArray(), true);

            //reverse the contact balances
            ContactBalanceUpdateService::doubleEntry($Txn->toArray(), true);

            //Delete affected relations
            $Txn->ledgers()->delete();
            $Txn->items()->delete();
            $Txn->item_taxes()->delete();
            $Txn->comments()->delete();
            $Txn->delete();

            $txnStore = self::store($requestInstance);

            DB::connection('tenant')->commit();

            return $txnStore;
        }
        catch (\Throwable $e)
        {
            DB::connection('tenant')->rollBack();

            Log::critical('Fatal Internal Error: Failed to update debit note in database');
            Log::critical($e);

            //print_r($e); exit;
            if (App::environment('local'))
            {
                self::$errors[] = 'Error: Failed to update debit note in database.';
                self::$errors[] = 'File: ' . $e->getFile();
                self::$errors[] = 'Line: ' . $e->getLine();
                self::$errors[] = 'Message: ' . $e->getMessage();
            }
            else
            {
                self::$errors[] = 'Fatal Internal Error: Failed to update debit note in database. Please contact Admin';
            }

            return false;
        }

    }

    public static function destroy($id)
    {
        //start database transaction
        DB::connection('tenant')->beginTransaction();

        try
        {
            $Txn = DebitNote::with('items', 'ledgers')->findOrFail($id);

            if ($Txn->status == 'approved')
            {
                self::$errors[] = 'Approved Debit note(s) cannot be not be deleted';
                return false;
            }

            //reverse the account balances
            AccountBalanceUpdateService::doubleEntry($Txn, true);

            //reverse the contact balances
            ContactBalanceUpdateService::doubleEntry($Txn, true);

            //Delete affected relations
            $Txn->ledgers()->delete();
            $Txn->items()->delete();
            $Txn->item_taxes()->delete();
            $Txn->comments()->delete();
            $Txn->delete();

            DB::connection('tenant')->commit();

            return true;

        }
        catch (\Throwable $e)
        {
            DB::connection('tenant')->rollBack();

            Log::critical('Fatal Internal Error: Failed to delete debit note from database');
            Log::critical($e);

            //print_r($e); exit;
            if (App::environment('local'))
            {
                self::$errors[] = 'Error: Failed to delete debit note from database.';
                self::$errors[] = 'File: ' . $e->getFile();
                self::$errors[] = 'Line: ' . $e->getLine();
                self::$errors[] = 'Message: ' . $e->getMessage();
            }
            else
            {
                self::$errors[] = 'Fatal Internal Error: Failed to delete debit note from database. Please contact Admin';
            }

            return false;
        }
    }

    public static function copy($id)
    {
        $taxes = Tax::all()->keyBy('code');

        $txn = DebitNote::findOrFail($id);
        $txn->load('contact', 'items.taxes');
        $txn->setAppends(['taxes']);

        $attributes = $txn->toArray();

        #reset some values
        $attributes['number'] = self::nextNumber();
        $attributes['date'] = date('Y-m-d');
        $attributes['due_date'] = '';
        $attributes['expiry_date'] = '';
        #reset some values

        $attributes['contact']['currency'] = $txn->contact->currency_and_exchange_rate;
        $attributes['contact']['currencies'] = $txn->contact->currencies_and_exchange_rates;
        $attributes['taxes'] = json_decode('{}');

        foreach ($attributes['items'] as &$item)
        {
            $selectedItem = [
                'id' => $item['item_id'],
                'name' => $item['name'],
                'description' => $item['description'],
                'rate' => $item['rate'],
                'tax_method' => 'inclusive',
                'account_type' => null,
            ];

            $item['selectedItem'] = $selectedItem; #required
            $item['selectedTaxes'] = []; #required
            $item['displayTotal'] = 0; #required
            $item['rate'] = floatval($item['rate']);
            $item['quantity'] = floatval($item['quantity']);
            $item['total'] = floatval($item['total']);
            $item['displayTotal'] = $item['total']; #required

            foreach ($item['taxes'] as $itemTax)
            {
                $item['selectedTaxes'][] = $taxes[$itemTax['tax_code']];
            }
        };

        return $attributes;
    }

    public static function approve($id)
    {
        $Txn = DebitNote::with(['ledgers'])->findOrFail($id);

        if (strtolower($Txn->status) != 'draft')
        {
            self::$errors[] = $Txn->status . ' debit note cannot be approved';
            return false;
        }

        //start database transaction
        DB::connection('tenant')->beginTransaction();

        try
        {
            $Txn->status = 'approved';
            DebitNoteApprovalService::run($Txn);

            DB::connection('tenant')->commit();

            return true;

        }
        catch (\Exception $e)
        {
            DB::connection('tenant')->rollBack();
            //print_r($e); exit;
            if (App::environment('local'))
            {
                self::$errors[] = 'DB Error: Failed to approve debit note.';
                self::$errors[] = 'File: ' . $e->getFile();
                self::$errors[] = 'Line: ' . $e->getLine();
                self::$errors[] = 'Message: ' . $e->getMessage();
            }
            else
            {
                self::$errors[] = 'Fatal Internal Error: Failed to approve debit note. Please contact Admin';
            }

            return false;
        }
    }

}
