<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\Division;
use App\Models\JenisSurat;
use App\Models\User;
use App\Models\SuratAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Show admin dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_surat' => Surat::count(),
            'total_users' => User::count(),
            'total_divisions' => Division::count(),
            'total_jenis_surat' => JenisSurat::count(),
            'private_surat' => Surat::where('is_private', true)->count(),
            'public_surat' => Surat::where('is_private', false)->count(),
        ];

        $recent_surat = Surat::with(['uploader', 'division', 'jenisSurat'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recent_users = User::with('division')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_surat', 'recent_users'));
    }

    /**
     * Show all surat with admin controls
     */
    public function suratIndex(Request $request)
    {
        $query = Surat::with(['uploader', 'division', 'jenisSurat']);

        // Filtering
        if ($request->filled('divisi_id')) {
            $query->where('divisi_id', $request->divisi_id);
        }
        if ($request->filled('jenis_surat_id')) {
            $query->where('jenis_surat_id', $request->jenis_surat_id);
        }
        if ($request->filled('is_private')) {
            $query->where('is_private', $request->is_private);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                  ->orWhere('perihal', 'like', "%{$search}%")
                  ->orWhereHas('uploader', function($userQuery) use ($search) {
                      $userQuery->where('full_name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $surat = $query->paginate(20);
        $divisions = Division::all();
        $jenisSurat = JenisSurat::all();

        return view('admin.surat.index', compact('surat', 'divisions', 'jenisSurat'));
    }

    /**
     * Show surat edit form
     */
    public function suratEdit($id)
    {
        $surat = Surat::with(['uploader', 'division', 'jenisSurat'])->findOrFail($id);
        $divisions = Division::all();
        $jenisSurat = JenisSurat::all();
        $users = User::all();

        return view('admin.surat.edit', compact('surat', 'divisions', 'jenisSurat', 'users'));
    }

    /**
     * Update surat
     */
    public function suratUpdate(Request $request, $id)
    {
        $surat = Surat::findOrFail($id);

        $request->validate([
            'nomor_urut' => 'required|integer',
            'divisi_id' => 'required|exists:divisions,id',
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
            'perihal' => 'required|string',
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date',
            'is_private' => 'boolean',
        ]);

        // Check unique nomor_urut per divisi & jenis surat (excluding current surat)
        if (Surat::where('nomor_urut', $request->nomor_urut)
                ->where('divisi_id', $request->divisi_id)
                ->where('jenis_surat_id', $request->jenis_surat_id)
                ->where('id', '!=', $id)
                ->exists()) {
            return back()->withErrors(['nomor_urut' => 'Nomor urut sudah ada untuk jenis surat ini di divisi ini.'])->withInput();
        }

        // Generate new nomor_surat
        $division = Division::find($request->divisi_id);
        $jenisSurat = JenisSurat::find($request->jenis_surat_id);
        $nomorSurat = sprintf('%03d/%s/%s/INTENS/%02d/%04d',
            $request->nomor_urut,
            $division->kode_divisi,
            $jenisSurat->kode_jenis,
            date('m', strtotime($request->tanggal_surat)),
            date('Y', strtotime($request->tanggal_surat))
        );

        $surat->update([
            'nomor_urut' => $request->nomor_urut,
            'nomor_surat' => $nomorSurat,
            'divisi_id' => $request->divisi_id,
            'jenis_surat_id' => $request->jenis_surat_id,
            'perihal' => $request->perihal,
            'tanggal_surat' => $request->tanggal_surat,
            'tanggal_diterima' => $request->tanggal_diterima,
            'is_private' => $request->has('is_private'),
        ]);

        // Handle private access
        if ($request->has('is_private') && $request->has('selected_users')) {
            // Remove existing access
            SuratAccess::where('surat_id', $surat->id)->delete();
            // Add new access
            $selectedUsers = $request->selected_users;
            foreach ($selectedUsers as $userId) {
                SuratAccess::create([
                    'surat_id' => $surat->id,
                    'user_id' => $userId,
                    'granted_at' => now(),
                ]);
            }
        } elseif (!$request->has('is_private')) {
            // Remove all access if surat is public
            SuratAccess::where('surat_id', $surat->id)->delete();
        }

        return redirect()->route('admin.surat.index')->with('success', 'Surat berhasil diperbarui!');
    }

    /**
     * Delete surat
     */
    public function suratDestroy($id)
    {
        $surat = Surat::findOrFail($id);
        
        // Delete file
        if (Storage::exists($surat->file_path)) {
            Storage::delete($surat->file_path);
        }
        
        // Delete access records
        SuratAccess::where('surat_id', $surat->id)->delete();
        
        // Delete surat
        $surat->delete();

        return redirect()->route('admin.surat.index')->with('success', 'Surat berhasil dihapus!');
    }

    /**
     * Show upload form for admin (admin bisa pilih divisi)
     */
    public function showUploadForm()
    {
        $divisions = Division::all();
        $jenisSurat = JenisSurat::all();
        return view('admin.surat.upload', compact('divisions', 'jenisSurat'));
    }

    /**
     * Handle file upload and text extraction (step 1: upload -> konfirmasi)
     */
    public function handleUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx',
        ]);
        $file = $request->file('file');
        $lettersDir = storage_path('app/private/letters');
        if (!is_dir($lettersDir)) {
            mkdir($lettersDir, 0777, true);
        }
        $path = $file->store('private/letters');
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
        // Extract text (copy dari SuratController)
        $extracted = '';
        $ocrError = null;
        $extractionMethod = '';
        $fileExtension = strtolower($file->getClientOriginalExtension());
        try {
            switch ($fileExtension) {
                case 'pdf':
                    $extractionMethod = 'PDF Parser';
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($file->getRealPath());
                    $extracted = $pdf->getText();
                    break;
                case 'doc':
                case 'docx':
                    $extractionMethod = 'Word Parser';
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($file->getRealPath());
                    $extracted = '';
                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $extracted .= $element->getText() . "\n";
                            }
                        }
                    }
                    break;
                default:
                    $ocrError = 'Format file tidak didukung untuk ekstraksi teks.';
            }
        } catch (\Exception $e) {
            $ocrError = 'Error ekstraksi teks (' . $extractionMethod . '): ' . $e->getMessage();
        }
        // Prefill data kosong, admin pilih di konfirmasi
        $prefilledData = [
            'divisi_id' => null,
            'jenis_surat_id' => null,
            'perihal' => '',
            'tanggal_surat' => date('Y-m-d'),
            'tanggal_diterima' => date('Y-m-d'),
            'is_private' => false,
        ];
        // Tidak perlu lock nomor urut di sini, baru di konfirmasi
        return view('admin.surat.confirm', [
            'file_path' => $path,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'nomor_surat' => '',
            'extracted_text' => $extracted,
            'input' => $prefilledData,
            'divisions' => Division::all(),
            'jenisSurat' => JenisSurat::all(),
            'ocr_error' => $ocrError,
            'extraction_method' => $extractionMethod,
        ]);
    }

    /**
     * Konfirmasi dan simpan surat (step 2)
     */
    public function store(Request $request)
    {
        $request->merge([
            'nomor_urut' => (int) ltrim($request->input('nomor_urut'), '0')
        ]);
        $request->validate([
            'nomor_urut' => 'required|integer',
            'divisi_id' => 'required|exists:divisions,id',
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
            'perihal' => 'required|string',
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date',
            'file_path' => 'required',
            'file_size' => 'required|integer',
            'mime_type' => 'required|string',
        ]);
        \App\Models\NomorUrutLock::where('divisi_id', $request->divisi_id)
            ->where('jenis_surat_id', $request->jenis_surat_id)
            ->where('nomor_urut', $request->nomor_urut)
            ->where('user_id', \Auth::id())
            ->delete();
        if (\App\Models\Surat::where('nomor_urut', $request->nomor_urut)
            ->where('divisi_id', $request->divisi_id)
            ->where('jenis_surat_id', $request->jenis_surat_id)
            ->exists()) {
            return back()->withErrors(['nomor_urut' => 'Nomor urut sudah ada untuk jenis surat ini di divisi ini. Silakan pilih nomor lain.'])->withInput();
        }
        $division = Division::find($request->divisi_id);
        $jenisSurat = JenisSurat::find($request->jenis_surat_id);
        $nomorSurat = sprintf('%03d/%s/%s/INTENS/%02d/%04d',
            $request->nomor_urut,
            $division->kode_divisi,
            $jenisSurat->kode_jenis,
            date('m', strtotime($request->tanggal_surat)),
            date('Y', strtotime($request->tanggal_surat))
        );
        $filePath = $request->input('file_path');
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $finalPdfPath = 'private/letters/final_' . uniqid() . '.pdf';
        $outputPdfPath = storage_path('app/' . $finalPdfPath);
        $pythonScript = base_path('python/fill_nomor_surat.py');
        $cmd = escapeshellcmd("python3 $pythonScript '$filePath' '$outputPdfPath' '$nomorSurat'");
        $output = [];
        $return_var = 0;
        try {
            exec($cmd, $output, $return_var);
        } catch (\Throwable $e) {
            return back()->withErrors(['file_path' => 'Server tidak mengizinkan eksekusi script Python. Hubungi admin.'])->withInput();
        }
        if ($return_var !== 0 || !file_exists($outputPdfPath)) {
            return back()->withErrors(['file_path' => 'Gagal generate file PDF akhir.'])->withInput();
        }
        $mimeType = 'application/pdf';
        $fileSize = filesize($outputPdfPath);
        $surat = \App\Models\Surat::create([
            'nomor_urut' => $request->input('nomor_urut'),
            'nomor_surat' => $nomorSurat,
            'divisi_id' => $request->input('divisi_id'),
            'jenis_surat_id' => $request->input('jenis_surat_id'),
            'perihal' => $request->input('perihal'),
            'tanggal_surat' => $request->input('tanggal_surat'),
            'tanggal_diterima' => $request->input('tanggal_diterima'),
            'file_path' => $finalPdfPath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'is_private' => $request->input('is_private', false),
            'uploaded_by' => \Auth::id(),
        ]);
        if ($request->input('is_private') && $request->has('selected_users')) {
            $selectedUsers = $request->input('selected_users', []);
            foreach ($selectedUsers as $userId) {
                \App\Models\SuratAccess::grantAccess($surat->id, $userId);
            }
        }
        return redirect()->route('admin.surat.index')->with('success', 'Surat berhasil diupload oleh admin!');
    }

    /**
     * Show all users
     */
    public function usersIndex(Request $request)
    {
        $query = User::with('division');

        if ($request->filled('divisi_id')) {
            $query->where('divisi_id', $request->divisi_id);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20);
        $divisions = Division::all();

        return view('admin.users.index', compact('users', 'divisions'));
    }

    /**
     * Show user create form
     */
    public function usersCreate()
    {
        $divisions = Division::all();
        return view('admin.users.create', compact('divisions'));
    }

    /**
     * Store new user
     */
    public function usersStore(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50|unique:users',
            'full_name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users',
            'divisi_id' => 'required|exists:divisions,id',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'boolean',
        ]);

        User::create([
            'username' => $request->username,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'divisi_id' => $request->divisi_id,
            'password' => Hash::make($request->password),
            'is_admin' => $request->has('is_admin'),
            'is_active' => true,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan!');
    }

    /**
     * Show user edit form
     */
    public function usersEdit($id)
    {
        $user = User::findOrFail($id);
        $divisions = Division::all();
        return view('admin.users.edit', compact('user', 'divisions'));
    }

    /**
     * Update user
     */
    public function usersUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username' => 'required|string|max:50|unique:users,username,' . $id,
            'full_name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users,email,' . $id,
            'divisi_id' => 'required|exists:divisions,id',
            'password' => 'nullable|string|min:8|confirmed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $data = [
            'username' => $request->username,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'divisi_id' => $request->divisi_id,
            'is_admin' => $request->has('is_admin'),
            'is_active' => $request->has('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui!');
    }

    /**
     * Delete user
     */
    public function usersDestroy($id)
    {
        $user = User::findOrFail($id);
        
        // Don't allow admin to delete themselves
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri!');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus!');
    }

    /**
     * Show all divisions
     */
    public function divisionsIndex()
    {
        $divisions = Division::withCount('users')->with('users')->paginate(20);
        return view('admin.divisions.index', compact('divisions'));
    }

    /**
     * Show division create form
     */
    public function divisionsCreate()
    {
        return view('admin.divisions.create');
    }

    /**
     * Store new division
     */
    public function divisionsStore(Request $request)
    {
        $request->validate([
            'nama_divisi' => 'required|string|max:100|unique:divisions',
            'kode_divisi' => 'required|string|max:10|unique:divisions',
            'deskripsi' => 'nullable|string',
        ]);

        Division::create($request->all());

        return redirect()->route('admin.divisions.index')->with('success', 'Divisi berhasil ditambahkan!');
    }

    /**
     * Show division edit form
     */
    public function divisionsEdit($id)
    {
        $division = Division::withCount('users', 'surat')->with('users')->findOrFail($id);
        $allUsers = \App\Models\User::all();
        return view('admin.divisions.edit', compact('division', 'allUsers'));
    }

    /**
     * Update division
     */
    public function divisionsUpdate(Request $request, $id)
    {
        $division = Division::findOrFail($id);

        $request->validate([
            'nama_divisi' => 'required|string|max:100|unique:divisions,nama_divisi,' . $id,
            'kode_divisi' => 'required|string|max:10|unique:divisions,kode_divisi,' . $id,
            'deskripsi' => 'nullable|string',
        ]);

        $division->update($request->all());

        // Sync division members
        if ($request->has('division_users')) {
            $division->users()->sync($request->input('division_users'));
        } else {
            $division->users()->sync([]);
        }

        return redirect()->route('admin.divisions.index')->with('success', 'Divisi berhasil diperbarui!');
    }

    /**
     * Delete division
     */
    public function divisionsDestroy($id)
    {
        $division = Division::findOrFail($id);
        
        // Check if division has users
        if ($division->users()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus divisi yang masih memiliki user!');
        }

        // Check if division has surat
        if ($division->surat()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus divisi yang masih memiliki surat!');
        }

        $division->delete();

        return redirect()->route('admin.divisions.index')->with('success', 'Divisi berhasil dihapus!');
    }

    /**
     * Show all jenis surat
     */
    public function jenisSuratIndex()
    {
        $jenisSurat = JenisSurat::withCount('surat')->paginate(20);
        return view('admin.jenis-surat.index', compact('jenisSurat'));
    }

    /**
     * Show jenis surat create form
     */
    public function jenisSuratCreate()
    {
        return view('admin.jenis-surat.create');
    }

    /**
     * Store new jenis surat
     */
    public function jenisSuratStore(Request $request)
    {
        $request->validate([
            'nama_jenis' => 'required|string|max:100|unique:jenis_surat',
            'kode_jenis' => 'required|string|max:10|unique:jenis_surat',
            'deskripsi' => 'nullable|string',
        ]);

        JenisSurat::create($request->all());

        return redirect()->route('admin.jenis-surat.index')->with('success', 'Jenis surat berhasil ditambahkan!');
    }

    /**
     * Show jenis surat edit form
     */
    public function jenisSuratEdit($id)
    {
        $jenisSurat = JenisSurat::findOrFail($id);
        return view('admin.jenis-surat.edit', compact('jenisSurat'));
    }

    /**
     * Update jenis surat
     */
    public function jenisSuratUpdate(Request $request, $id)
    {
        $jenisSurat = JenisSurat::findOrFail($id);

        $request->validate([
            'nama_jenis' => 'required|string|max:100|unique:jenis_surat,nama_jenis,' . $id,
            'kode_jenis' => 'required|string|max:10|unique:jenis_surat,kode_jenis,' . $id,
            'deskripsi' => 'nullable|string',
        ]);

        $jenisSurat->update($request->all());

        return redirect()->route('admin.jenis-surat.index')->with('success', 'Jenis surat berhasil diperbarui!');
    }

    /**
     * Delete jenis surat
     */
    public function jenisSuratDestroy($id)
    {
        $jenisSurat = JenisSurat::findOrFail($id);
        
        // Check if jenis surat has surat
        if ($jenisSurat->surat()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus jenis surat yang masih digunakan!');
        }

        $jenisSurat->delete();

        return redirect()->route('admin.jenis-surat.index')->with('success', 'Jenis surat berhasil dihapus!');
    }
} 