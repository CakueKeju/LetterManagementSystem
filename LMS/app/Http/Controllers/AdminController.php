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
                    $extractionMethod = 'Word Parser';
                    $fullPath = storage_path('app/' . $filePath);
                    \Log::info('Admin extracting Word text from: ' . $fullPath);
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($fullPath);
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
            \Log::warning('Admin failed to extract text: ' . $e->getMessage(), [
                'file_path' => $filePath,
                'full_path' => storage_path('app/' . $filePath),
                'exists' => file_exists(storage_path('app/' . $filePath))
            ]);
        }
        // Prefill data kosong, admin pilih di konfirmasi
        $prefilledData = [
            'nomor_urut' => null,
            'divisi_id' => null,
            'jenis_surat_id' => null,
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
            'jenisSurat' => JenisSurat::active()->get(),
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
        
        // Fill PDF dengan nomor surat
        $filePath = $request->input('file_path');
        $storagePath = storage_path('app/' . $filePath);
        $fileExtension = strtolower(pathinfo($storagePath, PATHINFO_EXTENSION));
        
        if ($fileExtension === 'pdf') {
            $filledPdfPath = $this->fillPdfWithNomorSurat($storagePath, $nomorSurat);
            if ($filledPdfPath) {
                $filePath = 'letters/filled_' . uniqid() . '.pdf';
                \Illuminate\Support\Facades\Storage::put($filePath, file_get_contents($filledPdfPath));
                unlink($filledPdfPath); // Hapus file temporary
                $fileSize = \Illuminate\Support\Facades\Storage::size($filePath);
                $mimeType = 'application/pdf';
            }
        } elseif (in_array($fileExtension, ['doc', 'docx'])) {
            $filledWordPath = $this->fillWordWithNomorSurat($storagePath, $nomorSurat);
            if ($filledWordPath) {
                $filePath = 'letters/filled_' . uniqid() . '.' . $fileExtension;
                \Illuminate\Support\Facades\Storage::put($filePath, file_get_contents($filledWordPath));
                unlink($filledWordPath); // Hapus file temporary
                $fileSize = \Illuminate\Support\Facades\Storage::size($filePath);
                $mimeType = $request->input('mime_type');
            }
        } else {
            // Untuk file lain, simpan asli
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
     * Fill PDF with nomor surat by detecting and replacing placeholder text
     */
    private function fillPdfWithNomorSurat($pdfPath, $nomorSurat)
    {
        try {
            // Parse PDF untuk detect text dan koordinat
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfPath);
            
            // Cari text yang mengandung placeholder - lebih fleksibel
            $placeholderPatterns = [
                // Pattern dengan "Nomor:"
                'Nomor:\s*\.\.\.\.\./\.\.\.\.\./\.\.\.\.\./\.\.\.\.\./\.\.\.\.\./\.\.\.\.\.',
                'Nomor:\s*\.\.\.\.\./\.\.\.\.\./\.\.\.\.\./\.\.\.\.\./\.\.\.\.\.',
                'Nomor:\s*\.\.\.\.\./\.\.\.\.\./\.\.\.\.\./\.\.\.\.\.',
                'Nomor:\s*\.\.\.\.\./\.\.\.\.\./\.\.\.\.\.',
                'Nomor:\s*\.\.\.\.\./\.\.\.\.\.',
                'Nomor:\s*\.\.\.\.\.',
                // Pattern tanpa "Nomor:"
                '\.\.\.\.\./\.\.\.\.\./\.\.\.\.\./\.\.\.\.\./\.\.\.\.\./\.\.\.\.\.',
                '\.\.\.\.\./\.\.\.\.\./\.\.\.\.\./\.\.\.\.\./\.\.\.\.\.',
                '\.\.\.\.\./\.\.\.\.\./\.\.\.\.\./\.\.\.\.\.',
                '\.\.\.\.\./\.\.\.\.\./\.\.\.\.\.',
                '\.\.\.\.\./\.\.\.\.\.',
                '\.\.\.\.\.',
                // Pattern dengan variasi dots
                '\.\.\.\./\.\.\.\./\.\.\.\./\.\.\.\./\.\.\.\./\.\.\.\.',
                '\.\.\./\.\.\./\.\.\./\.\.\./\.\.\./\.\.\.',
                '\.\./\.\./\.\./\.\./\.\./\.\.',
                '\./\./\./\./\./\.',
                // Pattern dengan underscore atau dash
                '_____/_____/_____/_____/_____/_____',
                '-----/-----/-----/-----/-----/-----',
                '____/____/____/____/____/____',
                '----/----/----/----/----/----',
                // Pattern dengan spasi
                '\.\.\.\.\. /\.\.\.\.\. /\.\.\.\.\. /\.\.\.\.\. /\.\.\.\.\. /\.\.\.\.\.',
                '\.\.\.\. /\.\.\.\. /\.\.\.\. /\.\.\.\. /\.\.\.\. /\.\.\.\.',
            ];
            
            $foundPlaceholder = false;
            $replacementData = [];
            
            foreach ($pdf->getPages() as $pageIndex => $page) {
                $text = $page->getText();
                
                foreach ($placeholderPatterns as $pattern) {
                    if (preg_match('/' . $pattern . '/i', $text, $matches)) {
                        $foundPlaceholder = true;
                        $matchedText = $matches[0];
                        
                        \Log::info('Placeholder ditemukan:', [
                            'pattern' => $pattern,
                            'matched_text' => $matchedText,
                            'nomor_surat' => $nomorSurat,
                            'page' => $pageIndex
                        ]);
                        
                        // Extract font info dan koordinat dari page
                        $fontInfo = $this->extractFontInfo($page);
                        
                        $replacementData[] = [
                            'page' => $pageIndex,
                            'pattern' => $pattern,
                            'matched_text' => $matchedText,
                            'replacement' => $nomorSurat,
                            'font_info' => $fontInfo,
                            'position' => [
                                'x' => 50, // Default position
                                'y' => 50,
                                'width' => strlen($nomorSurat) * 6, // Approximate width
                                'height' => 12
                            ]
                        ];
                        
                        break 2; // Hanya replace yang pertama ditemukan
                    }
                }
            }
            
            if (!$foundPlaceholder) {
                \Log::warning('Placeholder text tidak ditemukan di PDF: ' . $pdfPath);
                \Log::info('Text yang ada di PDF:', ['text' => substr($text, 0, 500)]);
                return null;
            }
            
            // Buat PDF baru dengan replacement
            return $this->createFilledPdf($pdfPath, $replacementData);
            
        } catch (\Exception $e) {
            \Log::error('Error filling PDF: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Extract font information from PDF page
     */
    private function extractFontInfo($page)
    {
        try {
            // Coba extract font info dari page content
            $content = $page->getText();
            
            // Default font info jika tidak bisa detect
            return [
                'font_name' => 'Helvetica',
                'font_size' => 12,
                'font_color' => [0, 0, 0] // Black
            ];
        } catch (\Exception $e) {
            \Log::warning('Tidak bisa extract font info: ' . $e->getMessage());
            return [
                'font_name' => 'Helvetica',
                'font_size' => 12,
                'font_color' => [0, 0, 0]
            ];
        }
    }
    
    /**
     * Create filled PDF with replaced text
     */
    private function createFilledPdf($originalPath, $replacementData)
    {
        try {
            \Log::info('Creating filled PDF:', [
                'original_path' => $originalPath,
                'replacement_count' => count($replacementData)
            ]);
            
            // Buat temporary file untuk hasil
            $tempPath = storage_path('app/temp_filled_pdf_' . uniqid() . '.pdf');
            
            if (empty($replacementData)) {
                // Jika tidak ada replacement data, copy file asli
                if (copy($originalPath, $tempPath)) {
                    \Log::info('PDF copied to temp location (no replacements): ' . $tempPath);
                    return $tempPath;
                } else {
                    \Log::error('Failed to copy PDF to temp location');
                    return null;
                }
            }
            
            // Implementasi pengisian PDF menggunakan FPDI - Approach yang lebih sederhana
            try {
                // Import FPDI dan FPDF
                $pdf = new \setasign\Fpdi\Fpdi();
                
                // Set document properties
                $pdf->SetCreator('LMS System');
                $pdf->SetAuthor('LMS System');
                $pdf->SetTitle('Filled Document');
                
                // Get page count from original PDF
                $pageCount = $pdf->setSourceFile($originalPath);
                \Log::info('Original PDF has ' . $pageCount . ' pages');
                
                // Process each page
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    // Import page
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                    
                    // Apply replacements for this page
                    foreach ($replacementData as $replacement) {
                        if ($replacement['page'] == ($pageNo - 1)) { // PDF pages are 0-indexed
                            \Log::info('Applying replacement on page ' . $pageNo . ': ' . $replacement['replacement']);
                            
                            // Set font properties
                            $pdf->SetFont('Arial', '', 12);
                            $pdf->SetTextColor(0, 0, 0);
                            
                            // Use position from replacement data if available
                            $x = $replacement['position']['x'] ?? 50;
                            $y = $replacement['position']['y'] ?? 50;
                            
                            // Add text overlay
                            $pdf->SetXY($x, $y);
                            $pdf->Write(0, $replacement['replacement']);
                            
                            \Log::info('Text added at position: x=' . $x . ', y=' . $y . ', text: ' . $replacement['replacement']);
                        }
                    }
                }
                
                // Save the filled PDF
                $pdf->Output($tempPath, 'F');
                
                if (file_exists($tempPath)) {
                    \Log::info('Filled PDF created successfully: ' . $tempPath);
                    // Set proper permissions
                    chmod($tempPath, 0644);
                    return $tempPath;
                } else {
                    \Log::error('Failed to create filled PDF');
                    return null;
                }
                
            } catch (\Exception $e) {
                \Log::error('Error with FPDI: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Fallback: create simple PDF with text overlay
                try {
                    \Log::info('Trying fallback approach: create simple PDF with text');
                    $pdf = new \setasign\Fpdi\Fpdi();
                    $pdf->SetCreator('LMS System');
                    $pdf->SetAuthor('LMS System');
                    $pdf->SetTitle('Filled Document');
                    
                    // Add a page
                    $pdf->AddPage();
                    
                    // Add text
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->SetXY(50, 50);
                    
                    foreach ($replacementData as $replacement) {
                        $pdf->Write(0, 'Nomor Surat: ' . $replacement['replacement']);
                        break; // Only use first replacement
                    }
                    
                    $pdf->Output($tempPath, 'F');
                    
                    if (file_exists($tempPath)) {
                        \Log::info('Fallback PDF created successfully: ' . $tempPath);
                        // Set proper permissions
                        chmod($tempPath, 0644);
                        return $tempPath;
                    }
                } catch (\Exception $fallbackError) {
                    \Log::error('Fallback also failed: ' . $fallbackError->getMessage());
                }
                
                // Final fallback: copy original file
                if (copy($originalPath, $tempPath)) {
                    \Log::info('Final fallback: PDF copied to temp location: ' . $tempPath);
                    return $tempPath;
                } else {
                    \Log::error('Final fallback failed: Could not copy PDF');
                    return null;
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Error creating filled PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Fill Word document with nomor surat by detecting and replacing placeholder text
     */
    private function fillWordWithNomorSurat($wordPath, $nomorSurat)
    {
        try {
            // Load Word document
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($wordPath);
            
            // Cari text yang mengandung placeholder - lebih fleksibel
            $placeholderPatterns = [
                // Pattern dengan "Nomor:"
                '/Nomor:\s*\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\./i',
                '/Nomor:\s*\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\./i',
                '/Nomor:\s*\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\./i',
                '/Nomor:\s*\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\./i',
                '/Nomor:\s*\.\.\.\.\.\/\.\.\.\.\./i',
                '/Nomor:\s*\.\.\.\.\./i',
                // Pattern tanpa "Nomor:"
                '/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\./i',
                '/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\./i',
                '/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\./i',
                '/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\./i',
                '/\.\.\.\.\.\/\.\.\.\.\./i',
                '/\.\.\.\.\./i',
                // Pattern dengan variasi dots
                '/\.\.\.\.\/\.\.\.\.\/\.\.\.\.\/\.\.\.\.\/\.\.\.\.\/\.\.\.\./i',
                '/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\./i',
                '/\.\.\/\.\.\/\.\.\/\.\.\/\.\.\/\.\./i',
                '/\.\/\.\/\.\/\.\/\.\/\./i',
                // Pattern dengan underscore atau dash
                '/_____\/_____\/_____\/_____\/_____\/_____/i',
                '/-----\/-----\/-----\/-----\/-----\/-----/i',
                '/____\/____\/____\/____\/____\/____/i',
                '/----\/----\/----\/----\/----\/----/i',
                // Pattern dengan spasi
                '/\.\.\.\.\. \/\.\.\.\.\. \/\.\.\.\.\. \/\.\.\.\.\. \/\.\.\.\.\. \/\.\.\.\.\./i',
                '/\.\.\.\. \/\.\.\.\. \/\.\.\.\. \/\.\.\.\. \/\.\.\.\. \/\.\.\.\./i',
            ];
            
            $foundPlaceholder = false;
            $replacementData = [];
            
            // Iterate through all sections
            foreach ($phpWord->getSections() as $section) {
                // Iterate through all elements in the section
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text = $element->getText();
                        
                        foreach ($placeholderPatterns as $pattern) {
                            if (preg_match($pattern, $text, $matches)) {
                                $foundPlaceholder = true;
                                $matchedText = $matches[0];
                                
                                \Log::info('Word Placeholder ditemukan:', [
                                    'pattern' => $pattern,
                                    'matched_text' => $matchedText,
                                    'nomor_surat' => $nomorSurat
                                ]);
                                
                                // Replace text in the element
                                $newText = preg_replace($pattern, $nomorSurat, $text);
                                $element->setText($newText);
                                
                                $replacementData[] = [
                                    'original_text' => $matchedText,
                                    'replacement' => $nomorSurat,
                                    'new_text' => $newText
                                ];
                                
                                break 2; // Hanya replace yang pertama ditemukan
                            }
                        }
                    }
                }
            }
            
            if (!$foundPlaceholder) {
                \Log::warning('Placeholder text tidak ditemukan di Word document: ' . $wordPath);
                return null;
            }
            
            // Save the modified Word document
            $tempPath = storage_path('app/temp_filled_word_' . uniqid() . '.docx');
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempPath);
            
            \Log::info('Word document filled: ' . $tempPath);
            return $tempPath;
            
        } catch (\Exception $e) {
            \Log::error('Error filling Word document: ' . $e->getMessage());
            return null;
        }
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