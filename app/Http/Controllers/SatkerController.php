<?php

namespace App\Http\Controllers;

use App\Models\Satker;
use App\Models\User;
use Illuminate\Http\Request;

class SatkerController extends Controller
{
    public function index()
    {
        $satkers = Satker::with('user')->get();
        return view('satkers.index', compact('satkers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_satker' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $satker = Satker::create([
            'nama_satker' => $request->nama_satker,
        ]);

        User::create([
            'name' => $request->nama_satker,
            'email' => $request->email,
            'password' => $request->password, 
            'role' => 'satker',
            'satker_id' => $satker->id,
        ]);

        return redirect()->back()->with('success', "Satker \"{$satker->nama_satker}\" berhasil ditambahkan beserta akun login.");
    }

    public function destroy($id)
    {
        $satker = Satker::findOrFail($id);

        
        User::where('satker_id', $satker->id)->delete();

        $satker->delete();

        return redirect()->back()->with('success', 'Satker berhasil dihapus.');
    }
}