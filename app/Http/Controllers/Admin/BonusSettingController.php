<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BonusSetting;


class BonusSettingController extends Controller
{
    public function json()
{
    return response()->json(BonusSetting::all());
}
    public function index()
    {

        return view('admin/bonus_settings.index');
    }

    public function show($id)
    {
        return response()->json(BonusSetting::findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'key' => 'required|string',
            'value' => 'required|string',
        ]);

        $bonus = BonusSetting::create($data);
        return response()->json($bonus);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'key' => 'required|string',
            'value' => 'required|string',
        ]);

        $bonus = BonusSetting::findOrFail($id);
        $bonus->update($data);
        return response()->json($bonus);
    }

    public function destroy($id)
    {
        $bonus = BonusSetting::findOrFail($id);
        $bonus->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
