<?php

namespace App\Http\Controllers;

use App\Models\Satker;
use Illuminate\Http\Request;

class SatkerController extends Controller
{
    public function index()
    {
        $satkers = Satker::all();
        return view('satkers.index', compact('satkers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_satker' => 'required|string|max:255',
        ]);

        Satker::create($request->only('nama_satker'));

        return redirect()->back()->with('success', 'Satker berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        Satker::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Satker berhasil dihapus.');
    }
}