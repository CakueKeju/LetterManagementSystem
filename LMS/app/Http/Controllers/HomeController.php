<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\Division;
use App\Models\JenisSurat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $query = Surat::query();

        // Filter based on user access
        $user = Auth::user();
        
        // Show public surat from same division OR private surat that user has access to
        $query->where(function($q) use ($user) {
            // Public surat from same division
            $q->where(function($subQ) use ($user) {
                $subQ->where('is_private', false)
                     ->where('divisi_id', $user->divisi_id);
            });
            
            // OR private surat that user uploaded
            $q->orWhere('uploaded_by', $user->id);
            
            // OR private surat that user has access to
            $q->orWhereExists(function($existsQuery) use ($user) {
                $existsQuery->select(\DB::raw(1))
                           ->from('surat_access')
                           ->whereColumn('surat_access.surat_id', 'surat.id')
                           ->where('surat_access.user_id', $user->id);
            });
        });

        // Additional filtering
        if ($request->filled('divisi_id')) {
            $query->where('divisi_id', $request->divisi_id);
        }
        if ($request->filled('jenis_surat_id')) {
            $query->where('jenis_surat_id', $request->jenis_surat_id);
        }
        if ($request->filled('tanggal_surat')) {
            $query->where('tanggal_surat', $request->tanggal_surat);
        }
        if ($request->filled('is_private')) {
            $query->where('is_private', $request->is_private);
        }
        if ($request->filled('perihal')) {
            $query->where('perihal', 'like', '%' . $request->perihal . '%');
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $letters = $query->paginate(15);

        // Calculate next available nomor urut for the user's division and selected jenis surat
        $userDivisionId = $user->divisi_id;
        $jenisSuratId = $request->get('jenis_surat_id');
        $existingNumbers = collect();
        if ($jenisSuratId) {
        $existingNumbers = Surat::where('divisi_id', $userDivisionId)
                ->where('jenis_surat_id', $jenisSuratId)
            ->pluck('nomor_urut')
            ->sort()
            ->values();
        }
        $nextNomorUrut = 1;
        foreach ($existingNumbers as $existingNumber) {
            if ($existingNumber > $nextNomorUrut) {
                break;
            }
            $nextNomorUrut = $existingNumber + 1;
        }

        $jenisSurat = JenisSurat::where('divisi_id', $user->divisi_id)->active()->get();
        return view('home', [
            'letters' => $letters,
            'filters' => $request->only(['divisi_id', 'jenis_surat_id', 'tanggal_surat', 'is_private', 'sort']),
            'divisions' => Division::all(),
            'jenisSurat' => $jenisSurat,
            'available_nomor_urut' => $nextNomorUrut,
        ]);
    }
}
