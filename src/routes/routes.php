<?php

Route::group(['middleware' => ['web', 'auth', 'tenant', 'service.accounting']], function() {

	Route::prefix('debit-notes')->group(function () {

        //Route::get('summary', 'Rutatiina\DebitNote\Http\Controllers\DebitNoteController@summary');
        Route::post('export-to-excel', 'Rutatiina\DebitNote\Http\Controllers\DebitNoteController@exportToExcel');
        Route::post('{id}/approve', 'Rutatiina\DebitNote\Http\Controllers\DebitNoteController@approve');
        //Route::post('contact-estimates', 'Rutatiina\DebitNote\Http\Controllers\Sales\ReceiptController@estimates');
        Route::get('{id}/copy', 'Rutatiina\DebitNote\Http\Controllers\DebitNoteController@copy');

    });

    Route::resource('debit-notes/settings', 'Rutatiina\DebitNote\Http\Controllers\SettingsController');
    Route::resource('debit-notes', 'Rutatiina\DebitNote\Http\Controllers\DebitNoteController');

});
