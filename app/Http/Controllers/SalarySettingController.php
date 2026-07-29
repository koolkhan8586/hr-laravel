<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Salary;
use App\Models\SalaryColumn;
use App\Models\TaxSlab;
use Illuminate\Http\Request;

class SalarySettingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Custom salary sheet columns
    |--------------------------------------------------------------------------
    */
    public function columns()
    {
        $columns = SalaryColumn::orderBy('sort_order')->orderBy('id')->get();
        $orgName = AppSetting::get('org_name', 'The University of Lahore (City Campus)');

        return view('salary.columns', compact('columns', 'orgName'));
    }

    /**
     * Organisation name printed at the top of the salary / bank sheets.
     */
    public function updateHeader(Request $request)
    {
        $request->validate([
            'org_name' => 'required|string|max:150',
        ]);

        AppSetting::put('org_name', $request->org_name);

        return back()->with('success', 'Sheet header updated.');
    }

    public function storeColumn(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:60',
            'type'       => 'required|in:earning,deduction',
            'applies_to' => 'required|in:both,teacher,staff',
            'sort_order' => 'nullable|integer',
        ]);

        SalaryColumn::create([
            'name'       => $request->name,
            'type'       => $request->type,
            'applies_to' => $request->applies_to,
            'sort_order' => $request->sort_order ?? 0,
            'is_active'  => true,
        ]);

        return back()->with('success', 'Column added. It now appears on the salary sheet.');
    }

    public function updateColumn(Request $request, $id)
    {
        $column = SalaryColumn::findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:60',
            'type'       => 'required|in:earning,deduction',
            'applies_to' => 'required|in:both,teacher,staff',
            'sort_order' => 'nullable|integer',
        ]);

        $column->update([
            'name'       => $request->name,
            'type'       => $request->type,
            'applies_to' => $request->applies_to,
            'sort_order' => $request->sort_order ?? 0,
            'is_active'  => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Column updated.');
    }

    public function destroyColumn($id)
    {
        $column = SalaryColumn::findOrFail($id);

        // Figures already recorded against this column would silently vanish
        // from posted sheets, so retire it instead of deleting outright.
        $inUse = Salary::whereNotNull('custom_values')
            ->get()
            ->contains(fn ($s) => array_key_exists($column->id, $s->custom_values ?? []));

        if ($inUse) {
            $column->update(['is_active' => false]);

            return back()->with('success',
                'This column already has amounts saved against it, so it has been hidden from new sheets instead of deleted.');
        }

        $column->delete();

        return back()->with('success', 'Column deleted.');
    }

    /*
    |--------------------------------------------------------------------------
    | Tax rules
    |--------------------------------------------------------------------------
    */
    public function tax()
    {
        $slabs   = TaxSlab::orderBy('from_amount')->get();
        $basis   = TaxSlab::basis();
        $divisor = AppSetting::get('tax_medical_divisor', 1.1);

        return view('salary.tax', compact('slabs', 'basis', 'divisor'));
    }

    /**
     * Divisor the tax sheet uses to strip the exempt medical allowance.
     */
    public function updateMedicalDivisor(Request $request)
    {
        $request->validate([
            'divisor' => 'required|numeric|min:1|max:5',
        ]);

        AppSetting::put('tax_medical_divisor', $request->divisor);

        return back()->with('success',
            'Tax sheet will now divide salary by '.$request->divisor.' to remove the medical allowance.');
    }

    public function storeTaxSlab(Request $request)
    {
        $request->validate([
            'from_amount'  => 'required|numeric|min:0',
            'to_amount'    => 'nullable|numeric|gt:from_amount',
            'fixed_amount' => 'required|numeric|min:0',
            'percentage'   => 'required|numeric|min:0|max:100',
        ], [
            'to_amount.gt' => 'The upper limit must be greater than the lower limit.',
        ]);

        TaxSlab::create([
            'from_amount'  => $request->from_amount,
            'to_amount'    => $request->to_amount,
            'fixed_amount' => $request->fixed_amount,
            'percentage'   => $request->percentage,
            'is_active'    => true,
        ]);

        return back()->with('success', 'Tax slab added.');
    }

    public function updateTaxSlab(Request $request, $id)
    {
        $slab = TaxSlab::findOrFail($id);

        $request->validate([
            'from_amount'  => 'required|numeric|min:0',
            'to_amount'    => 'nullable|numeric|gt:from_amount',
            'fixed_amount' => 'required|numeric|min:0',
            'percentage'   => 'required|numeric|min:0|max:100',
        ], [
            'to_amount.gt' => 'The upper limit must be greater than the lower limit.',
        ]);

        $slab->update([
            'from_amount'  => $request->from_amount,
            'to_amount'    => $request->to_amount,
            'fixed_amount' => $request->fixed_amount,
            'percentage'   => $request->percentage,
            'is_active'    => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Slab updated.');
    }

    public function destroyTaxSlab($id)
    {
        TaxSlab::findOrFail($id)->delete();

        return back()->with('success', 'Tax slab removed.');
    }

    /**
     * Replace everything with the published FBR slabs.
     */
    public function loadTaxPreset()
    {
        $count = TaxSlab::loadPreset(TaxSlab::FBR_2026_27);

        AppSetting::put('tax_basis', 'annual');

        return back()->with('success',
            "Loaded the {$count} FBR 2026-27 slabs as yearly income. Edit any of them below if you need to.");
    }

    public function updateTaxBasis(Request $request)
    {
        $request->validate([
            'basis' => 'required|in:annual,monthly',
        ]);

        AppSetting::put('tax_basis', $request->basis);

        return back()->with('success',
            'Slabs are now read as '.$request->basis.' income.');
    }
}
