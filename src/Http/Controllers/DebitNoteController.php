<?php

namespace Rutatiina\DebitNote\Http\Controllers;

use Rutatiina\DebitNote\Services\DebitNoteService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Rutatiina\DebitNote\Models\DebitNote;
use Rutatiina\FinancialAccounting\Traits\FinancialAccountingTrait;
use Rutatiina\Contact\Traits\ContactTrait;

class DebitNoteController extends Controller
{
    use FinancialAccountingTrait;
    use ContactTrait;

    // >> get the item attributes template << !!important

    public function __construct()
    {
        $this->middleware('permission:debit-notes.view');
        $this->middleware('permission:debit-notes.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:debit-notes.update', ['only' => ['edit', 'update']]);
        $this->middleware('permission:debit-notes.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $query = DebitNote::query();

        if ($request->contact)
        {
            $query->where(function ($q) use ($request)
            {
                $q->where('contact_id', $request->contact);
            });
        }

        $txns = $query->latest()->paginate($request->input('per_page', 20));

        return [
            'tableData' => $txns
        ];
    }

    public function create()
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $tenant = Auth::user()->tenant;

        $txnAttributes = (new DebitNote())->rgGetAttributes();

        $txnAttributes['number'] = DebitNoteService::nextNumber();
        $txnAttributes['status'] = 'approved';
        $txnAttributes['contact_id'] = '';
        $txnAttributes['contact'] = json_decode('{"currencies":[]}'); #required
        $txnAttributes['date'] = date('Y-m-d');
        $txnAttributes['base_currency'] = $tenant->base_currency;
        $txnAttributes['quote_currency'] = $tenant->base_currency;
        $txnAttributes['taxes'] = json_decode('{}');
        $txnAttributes['contact_notes'] = null;
        $txnAttributes['terms_and_conditions'] = null;
        $txnAttributes['items'] = [[
            'selectedTaxes' => [], #required
            'selectedItem' => json_decode('{}'), #required
            'displayTotal' => 0,
            'name' => '',
            'description' => '',
            'rate' => 0,
            'quantity' => 1,
            'total' => 0,
            'taxes' => [],

            'item_id' => '',
            'contact_id' => '',
            'units' => '',
            'batch' => '',
            'expiry' => ''
        ]];

        return [
            'pageTitle' => 'Create Debit Note', #required
            'pageAction' => 'Create', #required
            'txnUrlStore' => '/debit-notes', #required
            'txnAttributes' => $txnAttributes, #required
        ];
    }

    public function store(Request $request)
    {
        $storeService = DebitNoteService::store($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => DebitNoteService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Debit Note saved'],
            'number' => 0,
            'callback' => route('debit-notes.show', [$storeService->id], false)
        ];
    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $txn = DebitNote::findOrFail($id);
        $txn->load('contact', 'items.taxes');
        $txn->setAppends([
            'taxes',
            'number_string',
            'total_in_words',
        ]);

        return $txn->toArray();
    }

    public function edit($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $txnAttributes = DebitNoteService::edit($id);

        return [
            'pageTitle' => 'Edit Debit note', #required
            'pageAction' => 'Edit', #required
            'txnUrlStore' => '/debit-notes/' . $id, #required
            'txnAttributes' => $txnAttributes, #required
        ];
    }

    public function update(Request $request)
    {
        //print_r($request->all()); exit;

        $storeService = DebitNoteService::update($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => DebitNoteService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Debit note updated'],
            'number' => 0,
            'callback' => route('debit-notes.show', [$storeService->id], false)
        ];
    }

    public function destroy($id)
    {
        $destroy = DebitNoteService::destroy($id);

        if ($destroy)
        {
            return [
                'status' => true,
                'messages' => ['Debit note deleted'],
                'callback' => route('debit-notes.index', [], false)
            ];
        }
        else
        {
            return [
                'status' => false,
                'messages' => DebitNoteService::$errors
            ];
        }
    }

    #-----------------------------------------------------------------------------------

    public function approve($id)
    {
        $approve = DebitNoteService::approve($id);

        if ($approve == false)
        {
            return [
                'status' => false,
                'messages' => DebitNoteService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Debit Note Approved'],
        ];
    }

    public function copy($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $txnAttributes = DebitNoteService::copy($id);

        return [
            'pageTitle' => 'Copy Debit note', #required
            'pageAction' => 'Copy', #required
            'txnUrlStore' => '/debit-notes', #required
            'txnAttributes' => $txnAttributes, #required
        ];
    }

    public function exportToExcel(Request $request)
    {
        $txns = collect([]);

        $txns->push([
            'DATE',
            'DOCUMENT #',
            'REFERENCE',
            'SUPPLIER / VENDOR',
            'TOTAL',
            ' ', //Currency
        ]);

        foreach (array_reverse($request->ids) as $id)
        {
            $txn = Transaction::transaction($id);

            $txns->push([
                $txn->date,
                $txn->number,
                $txn->reference,
                $txn->contact_name,
                $txn->total,
                $txn->base_currency,
            ]);
        }

        $export = $txns->downloadExcel(
            'maccounts-debit-notes-export-' . date('Y-m-d-H-m-s') . '.xlsx',
            null,
            false
        );

        //$books->load('author', 'publisher'); //of no use

        return $export;
    }

}
