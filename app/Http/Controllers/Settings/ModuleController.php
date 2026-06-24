<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\LookupType;
use App\Models\LookupValue;
use App\Models\SystemModule;
use App\Services\ModuleInstallService;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.view')->only(['index', 'lookups']);
        $this->middleware('permission:settings.edit')->only(['toggle', 'storeLookupType', 'storeLookupValue', 'destroyLookupValue', 'upload', 'destroy']);
    }

    public function index()
    {
        modules()->syncDiscovered();
        $modules = SystemModule::orderBy('name')->get();
        $discovered = modules()->discover();

        return view('settings.modules.index', compact('modules', 'discovered'));
    }

    public function toggle(SystemModule $module)
    {
        if ($module->is_enabled) {
            modules()->disable($module);
            return back()->with('success', __('extensions.module_disabled'));
        }

        modules()->enable($module);
        return back()->with('success', __('extensions.module_enabled'));
    }

    public function upload(Request $request, ModuleInstallService $installer)
    {
        $request->validate([
            'module_file' => 'required|file|mimes:zip|max:10240',
        ]);

        $module = $installer->installFromUpload($request->file('module_file'));

        return back()->with('success', __('extensions.module_installed', ['name' => $module->name]));
    }

    public function destroy(SystemModule $module, ModuleInstallService $installer)
    {
        $name = $module->name;
        $installer->remove($module);

        return back()->with('success', __('extensions.module_removed', ['name' => $name]));
    }

    public function lookups()
    {
        $types = LookupType::with('values')->orderBy('name')->get();
        return view('settings.lookups.index', compact('types'));
    }

    public function storeLookupType(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:100|unique:lookup_types,slug|alpha_dash',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        LookupType::create($validated);
        return back()->with('success', __('extensions.lookup_type_added'));
    }

    public function storeLookupValue(Request $request, LookupType $lookupType)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100',
            'label' => 'required|string|max:255',
        ]);

        $validated['sort_order'] = $lookupType->values()->max('sort_order') + 1;
        $lookupType->values()->create($validated);

        return back()->with('success', __('extensions.lookup_value_added'));
    }

    public function destroyLookupValue(LookupValue $lookupValue)
    {
        if ($lookupValue->type?->is_system) {
            return back()->withErrors(['lookup' => __('extensions.cannot_delete_system_lookup')]);
        }

        $lookupValue->delete();
        return back()->with('success', __('messages.deleted'));
    }
}
