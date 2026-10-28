<?php

arch('no debugging leftovers')
    ->preset()->php();

arch('no insecure functions')
    ->preset()->security();

arch('controllers stay thin and never open transactions')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('pricing services are pure (no database or facades)')
    ->expect('App\Services')
    ->not->toUse(['Illuminate\Support\Facades', 'Illuminate\Database']);

arch('DTOs are immutable')
    ->expect('App\DTOs')
    ->toBeFinal()
    ->toBeReadonly();

arch('the confirmation job is queued')
    ->expect('App\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

arch('form requests are used for validation')
    ->expect('App\Http\Requests')
    ->toExtend('Illuminate\Foundation\Http\FormRequest');
