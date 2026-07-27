<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SupportingUnit;

class QrGeneratorController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPageAccess('admin.wa-gateway.index') && (int) $user->role_id !== 1) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $units = SupportingUnit::select(['id', 'name', 'slug', 'type', 'status'])
            ->where('status', 'ACTIVE')
            ->get();

        return Inertia::render('Admin/QrGenerator/Index', [
            'baseUrl' => url('/'),
            'units' => $units,
        ]);
    }
}
