<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SupportingUnit;

class QrGeneratorController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        if (!$user->hasPageAccess('admin.qr-code.index') && !$user->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $units = SupportingUnit::select(['id', 'name', 'slug', 'type', 'status'])
            ->where('status', 'ACTIVE')
            ->get();

        return Inertia::render('QrGenerator/Index', [
            'baseUrl' => url('/'),
            'units' => $units,
        ]);
    }
}
