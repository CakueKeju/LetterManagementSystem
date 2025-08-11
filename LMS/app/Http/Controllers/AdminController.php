<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\Division;
use App\Models\JenisSurat;
use App\Models\User;
use App\Models\SuratAccess;
use App\Traits\DocumentProcessor;
use App\Traits\RomanNumeralConverter;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    use DocumentProcessor;
    use RomanNumeralConverter;

    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    // ==========================================================================================
    // Dashboard
    public function dashboard(): View
    {
        // statistik dashboard
        $stats = [
            'total_surat' => Surat::count(),
            'total_users' => User::count(),
            'total_divisions' => Division::count(),
            'total_jenis_surat' => JenisSurat::count(),
            'private_surat' => Surat::where('is_private', true)->count(),
            'public_surat' => Surat::where('is_private', false)->count(),
        ];

        // surat terbaru
        $recentSurat = Surat::with(['uploader', 'division', 'jenisSurat'])
            ->latest()
            ->limit(10)
            ->get();

        // user terbaru
        $recentUsers = User::with('division')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentSurat', 'recentUsers'));
    }

    // daftar semua surat dengan kontrol admin
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
        if ($request->filled('tanggal_surat')) {
            $query->whereDate('tanggal_surat', $request->tanggal_surat);
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

    // form edit surat
    public function suratEdit($id)
    {
        $surat = Surat::with(['uploader', 'division', 'jenisSurat'])->findOrFail($id);
        $divisions = Division::all();
        $jenisSurat = JenisSurat::all();
        $users = User::where('is_admin', false)
                    ->where('is_active', true)
                    ->where('id', '!=', Auth::id()) // Exclude current user (admin yang sedang edit)
                    ->get();

        return view('admin.surat.edit', compact('surat', 'divisions', 'jenisSurat', 'users'));
    }

    // update surat
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
        $nomorSurat = sprintf('%03d/%s/%s/INTENS/%s/%04d',
            $request->nomor_urut, 
            $division->kode_divisi, 
            $jenisSurat->kode_jenis, 
            $this->monthToRoman(date('n', strtotime($request->tanggal_surat))),
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

        // handle akses privat
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

    // hapus surat
    public function suratDestroy($id)
    {
        $surat = Surat::findOrFail($id);
        
        // hapus file
        if (Storage::exists($surat->file_path)) {
            Storage::delete($surat->file_path);
        }
        
        // hapus access records
        SuratAccess::where('surat_id', $surat->id)->delete();
        
        // hapus surat
        $surat->delete();

        return redirect()->route('admin.surat.index')->with('success', 'Surat berhasil dihapus!');
    }

    /**
     * Show upload form for admin (admin bisa pilih divisi)
     */
    public function showUploadForm()
    {
        $divisions = Division::all();
        return view('admin.surat.upload', compact('divisions'));
    }

    /**
     * Handle file upload and text extraction (step 1: upload -> konfirmasi)
     */
    public function handleUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx',
            'divisi_id' => 'required|exists:divisions,id',
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
        ]);
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
        $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        \Log::info('Admin file upload started:', [
            'original_name' => $originalName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'file_extension' => $fileExtension
        ]);

        // Ensure directory exists - PERBAIKAN DIREKTORI
        $storageDir = storage_path('app/letters');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
            \Log::info('Created directory: ' . $storageDir);
        }

        // Generate descriptive filename
        $timestamp = date('Y-m-d_H-i-s');
        $descriptiveName = sprintf(
            'admin_surat_%s_%s.%s',
            $timestamp,
            substr(md5($originalName), 0, 8),
            $fileExtension
        );

        // Store file dengan nama yang deskriptif
        $filePath = $file->storeAs('letters', $descriptiveName);
        
        \Log::info('Admin file uploaded successfully:', [
            'original_name' => $originalName,
            'descriptive_name' => $descriptiveName,
            'file_path' => $filePath,
            'full_path' => storage_path('app/' . $filePath),
            'exists' => Storage::exists($filePath),
            'file_size' => Storage::size($filePath)
        ]);
        
        // Convert DOCX to PDF immediately after upload using LibreOffice
        if (in_array($fileExtension, ['doc', 'docx'])) {
            \Log::info('Converting Word document to PDF using LibreOffice');
            $fullPath = storage_path('app/' . $filePath);
            
            // Convert to PDF using LibreOffice
            $convertedPdfPath = $this->convertWordToPdfWithLibreOffice($fullPath);
            
            if ($convertedPdfPath && file_exists($convertedPdfPath)) {
                // Replace the original file with PDF version
                $pdfDescriptiveName = str_replace('.' . $fileExtension, '.pdf', $descriptiveName);
                $newFilePath = 'letters/' . $pdfDescriptiveName;
                
                // Store the converted PDF
                Storage::put($newFilePath, file_get_contents($convertedPdfPath));
                
                // hapus file temporary
                unlink($convertedPdfPath);
                
                // hapus file Word asli
                Storage::delete($filePath);
                
                // update variabel untuk PDF
                $filePath = $newFilePath;
                $fileExtension = 'pdf';
                $mimeType = 'application/pdf';
                $fileSize = Storage::size($filePath);
                
                \Log::info('Word document successfully converted to PDF using LibreOffice:', [
                    'original_extension' => pathinfo($originalName, PATHINFO_EXTENSION),
                    'new_file_path' => $filePath,
                    'new_file_size' => $fileSize
                ]);
            } else {
                \Log::error('Failed to convert Word document to PDF using LibreOffice, keeping original file');
                // Keep the original DOCX file - we'll handle it differently in preview
            }
        }
        
        // Extract text (copy dari SuratController)
        $extracted = '';
        $ocrError = null;
        $extractionMethod = '';
        try {
            switch ($fileExtension) {
                case 'pdf':
                    $extractionMethod = 'PDF Parser';
                    $parser = new \Smalot\PdfParser\Parser();
                    $fullPath = storage_path('app/' . $filePath);
                    \Log::info('Admin extracting PDF text from: ' . $fullPath);
                    $pdf = $parser->parseFile($fullPath);
                    $extracted = $pdf->getText();
                    break;
                case 'doc':
                case 'docx':
                    // This should not happen anymore since we convert to PDF above
                    $extractionMethod = 'Word Parser (fallback)';
                    $extracted = 'Word document processing - should have been converted to PDF.';
                    break;
                default:
                    $ocrError = 'Format file tidak didukung untuk ekstraksi teks.';
            }
        } catch (\Exception $e) {
            $ocrError = 'Error ekstraksi teks (' . $extractionMethod . '): ' . $e->getMessage();
            \Log::warning('Admin failed to extract text: ' . $e->getMessage(), [
                'file_path' => $filePath,
                'full_path' => storage_path('app/' . $filePath),
                'exists' => file_exists(storage_path('app/' . $filePath))
            ]);
        }
        // Prefill data dengan divisi dan jenis surat yang dipilih
        $prefilledData = [
            'nomor_urut' => null,
            'divisi_id' => $request->divisi_id,
            'jenis_surat_id' => $request->jenis_surat_id,
            'perihal' => '',
            'tanggal_surat' => date('Y-m-d'),
            'tanggal_diterima' => date('Y-m-d'),
            'is_private' => false,
        ];
        return view('admin.surat.confirm', [
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'extracted_text' => $extracted,
            'input' => $prefilledData,
            'divisions' => Division::all(),
            'jenisSurat' => JenisSurat::where('divisi_id', $request->divisi_id)->active()->get(),
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
        $nomorSurat = sprintf('%03d/%s/%s/INTENS/%s/%04d',
            $request->nomor_urut,
            $division->kode_divisi,
            $jenisSurat->kode_jenis,
            $this->monthToRoman(date('n', strtotime($request->tanggal_surat))),
            date('Y', strtotime($request->tanggal_surat))
        );
        
        // Fill PDF dengan nomor surat
        $filePath = $request->input('file_path');
        $storagePath = storage_path('app/' . $filePath);
        $fileExtension = strtolower(pathinfo($storagePath, PATHINFO_EXTENSION));
        
        // Since we now convert all Word documents to PDF at upload time,
        // we only need to handle PDF files here
        if ($fileExtension === 'pdf') {
            $filledPdfPath = $this->fillPdfWithNomorSurat($storagePath, $nomorSurat);
            if ($filledPdfPath) {
                $filePath = 'letters/filled_' . uniqid() . '.pdf';
                \Illuminate\Support\Facades\Storage::put($filePath, file_get_contents($filledPdfPath));
                unlink($filledPdfPath); // Hapus file temporary
                $fileSize = \Illuminate\Support\Facades\Storage::size($filePath);
                $mimeType = 'application/pdf';
            } else {
                // Use original file if filling failed
                $fileSize = $request->input('file_size');
                $mimeType = $request->input('mime_type');
            }
        } else {
            // For other file types, keep original
            $fileSize = $request->input('file_size');
            $mimeType = $request->input('mime_type');
        }
        
        $surat = \App\Models\Surat::create([
            'nomor_urut' => $request->input('nomor_urut'),
            'nomor_surat' => $nomorSurat,
            'divisi_id' => $request->input('divisi_id'),
            'jenis_surat_id' => $request->input('jenis_surat_id'),
            'perihal' => $request->input('perihal'),
            'tanggal_surat' => $request->input('tanggal_surat'),
            'tanggal_diterima' => $request->input('tanggal_diterima'),
            'file_path' => $filePath,
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

        // update anggota divisi
        if ($request->has('division_users') && is_array($request->input('division_users'))) {
            // First, remove all users from this division
            User::where('divisi_id', $division->id)->update(['divisi_id' => null]);
            
            // Then assign selected users to this division
            User::whereIn('id', $request->input('division_users'))->update(['divisi_id' => $division->id]);
        } else {
            // If no users selected, remove all users from this division
            User::where('divisi_id', $division->id)->update(['divisi_id' => null]);
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