<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class CounterController extends Controller
{
    /**
     * The "New Order" counter screen. Data is loaded from the JSON API.
     */
    public function __invoke(): View
    {
        return view('counter');
    }
}
