<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\SupportingUnit;
use App\Models\UnitFeature;
use App\Models\FeatureCategory;
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
        $rooms = Room::orderBy('name', 'asc')->get();
        return Inertia::render('ServiceManagement/Rooms', [
            'rooms' => $rooms,
        ]);
    }

    /**
     * Display the Damage Categories management page.
     */
    public function indexCategories()
    {
        $categories = FeatureCategory::with(['unitFeature.supportingUnit'])->orderBy('id', 'desc')->get();
        $unitFeatures = UnitFeature::with(['supportingUnit'])->orderBy('name', 'asc')->get();

        return Inertia::render('ServiceManagement/Categories', [
            'categories' => $categories,
            'unitFeatures' => $unitFeatures,
        ]);
    }

    /**
     * Display the Supporting Units (Layanan Penunjang) management page.
     */
    public function indexSupportingUnits()
    {
        $divisions = Division::with(['supportingUnits'])->orderBy('name', 'asc')->get();
        return Inertia::render('ServiceManagement/SupportingUnits', [
            'divisions' => $divisions,
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
            'feature_id' => 'required|exists:unit_features,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
        ]);

        FeatureCategory::create($validated);

        return redirect()->back()->with('success', 'Kategori kerusakan berhasil ditambahkan.');
    }

    /**
     * Update the specified category.
     */
    public function updateCategory(Request $request, FeatureCategory $category)
    {
        $validated = $request->validate([
            'feature_id' => 'required|exists:unit_features,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        return redirect()->back()->with('success', 'Kategori kerusakan berhasil diperbarui.');
    }

    /**
     * Remove the specified category.
     */
    public function destroyCategory(FeatureCategory $category)
    {
        $category->delete();
        return redirect()->back()->with('success', 'Kategori kerusakan berhasil dihapus.');
    }

    /**
     * Store a newly created division.
     */
    public function storeDivision(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        Division::create($validated);

        return redirect()->back()->with('success', 'Divisi berhasil ditambahkan.');
    }

    /**
     * Update the specified division.
     */
    public function updateDivision(Request $request, Division $division)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $division->update($validated);

        return redirect()->back()->with('success', 'Divisi berhasil diperbarui.');
    }

    /**
     * Remove the specified division.
     */
    public function destroyDivision(Division $division)
    {
        $division->delete();
        return redirect()->back()->with('success', 'Divisi berhasil dihapus.');
    }

    /**
     * Store a newly created supporting unit.
     */
    public function storeSupportingUnit(Request $request)
    {
        $validated = $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,IN_DEVELOPMENT,INACTIVE',
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
            'division_id' => 'required|exists:divisions,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,IN_DEVELOPMENT,INACTIVE',
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
