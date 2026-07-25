<?php

namespace App\Http\Controllers;

use App\Models\SupportingUnit;
use App\Models\IssueCategory;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceManagementController extends Controller
{
    /**
     * Display the Rooms management page.
     */
    public function indexRooms()
    {
        return Inertia::render('ServiceManagement/Rooms', [
            'rooms' => Inertia::defer(fn() => Room::orderBy('name', 'asc')->get()),
        ]);
    }

    /**
     * Display the Damage Categories management page.
     */
    public function indexCategories()
    {
        return Inertia::render('ServiceManagement/Categories', [
            'categories' => Inertia::defer(fn() => IssueCategory::with(['supportingUnit'])->orderBy('id', 'desc')->get()),
            'supportingUnits' => Inertia::defer(fn() => SupportingUnit::orderBy('name', 'asc')->get()),
        ]);
    }

    /**
     * Display the Supporting Units (Layanan Penunjang) management page.
     */
    public function indexSupportingUnits()
    {
        return Inertia::render('ServiceManagement/SupportingUnits', [
            'supportingUnits' => Inertia::defer(fn() => SupportingUnit::orderBy('type', 'asc')->orderBy('name', 'asc')->get()),
        ]);
    }

    /**
     * Store a newly created room.
     */
    public function storeRoom(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'location_floor' => 'nullable|string|max:50',
        ]);

        Room::create($validated);

        return redirect()->back()->with('success', 'Ruangan berhasil ditambahkan.');
    }

    /**
     * Update the specified room.
     */
    public function updateRoom(Request $request, Room $room)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'location_floor' => 'nullable|string|max:50',
        ]);

        $room->update($validated);

        return redirect()->back()->with('success', 'Ruangan berhasil diperbarui.');
    }

    /**
     * Remove the specified room.
     */
    public function destroyRoom(Room $room)
    {
        $room->delete();
        return redirect()->back()->with('success', 'Ruangan berhasil dihapus.');
    }

    /**
     * Store a newly created category.
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'supporting_unit_id' => 'required|exists:supporting_units,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
        ]);

        IssueCategory::create($validated);

        return redirect()->back()->with('success', 'Kategori kerusakan berhasil ditambahkan.');
    }

    /**
     * Update the specified category.
     */
    public function updateCategory(Request $request, IssueCategory $category)
    {
        $validated = $request->validate([
            'supporting_unit_id' => 'required|exists:supporting_units,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        return redirect()->back()->with('success', 'Kategori kerusakan berhasil diperbarui.');
    }

    /**
     * Remove the specified category.
     */
    public function destroyCategory(IssueCategory $category)
    {
        $category->delete();
        return redirect()->back()->with('success', 'Kategori kerusakan berhasil dihapus.');
    }

    /**
     * Store a newly created supporting unit.
     */
    public function storeSupportingUnit(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:MEDIK,NON_MEDIK',
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:supporting_units,slug',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,IN_DEVELOPMENT,MAINTENANCE,INACTIVE',
        ]);

        SupportingUnit::create($validated);

        return redirect()->back()->with('success', 'Unit penunjang berhasil ditambahkan.');
    }

    /**
     * Update the specified supporting unit.
     */
    public function updateSupportingUnit(Request $request, SupportingUnit $unit)
    {
        $validated = $request->validate([
            'type' => 'required|in:MEDIK,NON_MEDIK',
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:supporting_units,slug,' . $unit->id,
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,IN_DEVELOPMENT,MAINTENANCE,INACTIVE',
        ]);

        $unit->update($validated);

        return redirect()->back()->with('success', 'Unit penunjang berhasil diperbarui.');
    }

    /**
     * Remove the specified supporting unit.
     */
    public function destroySupportingUnit(SupportingUnit $unit)
    {
        $unit->delete();
        return redirect()->back()->with('success', 'Unit penunjang berhasil dihapus.');
    }
}
