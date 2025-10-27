<?php

namespace App\Http\Controllers;

use App\Services\Payments\MidtransServiceInterface;
use Illuminate\Http\Request;

class MidtransController extends Controller
{
    public function __construct(private readonly MidtransServiceInterface $midtrans) {}

    public function notification(Request $request)
    {
        $this->midtrans->handleNotification();
        return response()->json(['ok' => true]);
    }
}
