<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\Division;
use App\Models\JenisSurat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use App\Models\NomorUrutLock;
use PhpOffice\PhpWord\TemplateProcessor;
use setasign\Fpdi\Fpdi;

class SuratController extends Controller
{
    // Show the upload form
    public function showUploadForm()
    {
        $user = Auth::user();
        $jenisSurat = JenisSurat::where('divisi_id', $user->divisi_id)->get();
        
        return view('surat.upload', compact('jenisSurat'));
    }

    // Handle file upload and text extraction
    public function handleUpload(Request $request)
    {
        try {
        $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
                'jenis_surat_id' => 'required|exists:jenis_surat,id',
        ]);

        $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
            $jenisSuratId = $request->input('jenis_surat_id');
            
            \Log::info('File upload started:', [
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'jenis_surat_id' => $jenisSuratId
            ]);

            // Ensure directory exists - PERBAIKAN DIREKTORI
            $storageDir = storage_path('app/letters');
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
                \Log::info('Created directory: ' . $storageDir);
            }

            // Generate descriptive filename
            $timestamp = date('Y-m-d_H-i-s');
            $user = Auth::user();
            $jenisSurat = JenisSurat::find($jenisSuratId);
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $descriptiveName = sprintf(
                'surat_%s_%s_%s.%s',
                $user->division->kode_divisi,
                $jenisSurat->kode_jenis,
                $timestamp,
                $fileExtension
            );

            // Store file dengan nama yang deskriptif
            $filePath = $file->storeAs('letters', $descriptiveName);
            
            \Log::info('File uploaded successfully:', [
                'original_name' => $originalName,
                'descriptive_name' => $descriptiveName,
                'file_path' => $filePath,
                'full_path' => storage_path('app/' . $filePath),
                'storage_dir' => $storageDir,
                'exists' => Storage::exists($filePath),
                'file_size' => Storage::size($filePath)
            ]);

            // Check for duplicate nomor urut
            $user = Auth::user();
            $nextNomorUrut = $this->getNextNomorUrut($user->divisi_id, $jenisSuratId);
            
            \Log::info('Nomor urut check:', [
                'next_nomor_urut' => $nextNomorUrut,
                'divisi_id' => $user->divisi_id,
                'jenis_surat_id' => $jenisSuratId
            ]);
            
            if ($this->checkDuplicate($user->divisi_id, $jenisSuratId, $nextNomorUrut)) {
                \Log::warning('Duplicate nomor urut detected, showing warning');
                
                // Delete the uploaded file since it's a duplicate
                Storage::delete($filePath);
                
                return view('surat.duplicate_warning', [
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'nomor_urut' => $nextNomorUrut,
                    'divisi_id' => $user->divisi_id,
                    'jenis_surat_id' => $jenisSuratId
                ]);
            }

            // Extract text from file untuk detect nomor surat yang sudah ada
            $extractedText = '';
            $extractionMethod = '';
        $ocrError = null;
        
            if ($fileExtension === 'pdf') {
        try {
                    $extractionMethod = 'PDF Parser';
                    $parser = new Parser();
                    $fullPath = storage_path('app/' . $filePath);
                    \Log::info('Extracting PDF text from: ' . $fullPath);
                    $pdf = $parser->parseFile($fullPath);
                    foreach ($pdf->getPages() as $page) {
                        $extractedText .= $page->getText() . ' ';
                    }
                    \Log::info('PDF text extracted successfully, length: ' . strlen($extractedText));
                } catch (\Exception $e) {
                    $ocrError = 'Error ekstraksi teks (PDF Parser): ' . $e->getMessage();
                    \Log::warning('Failed to extract text from PDF: ' . $e->getMessage(), [
                        'file_path' => $filePath,
                        'full_path' => storage_path('app/' . $filePath),
                        'exists' => file_exists(storage_path('app/' . $filePath))
                    ]);
                }
            } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                try {
                    $extractionMethod = 'Word Parser';
                    $fullPath = storage_path('app/' . $filePath);
                    \Log::info('Extracting Word text from: ' . $fullPath);
                    $phpWord = IOFactory::load($fullPath);
                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $extractedText .= $element->getText() . ' ';
                            }
                        }
                    }
                    \Log::info('Word text extracted successfully, length: ' . strlen($extractedText));
                } catch (\Exception $e) {
                    $ocrError = 'Error ekstraksi teks (Word Parser): ' . $e->getMessage();
                    \Log::warning('Failed to extract text from Word document: ' . $e->getMessage(), [
                        'file_path' => $filePath,
                        'full_path' => storage_path('app/' . $filePath),
                        'exists' => file_exists(storage_path('app/' . $filePath))
                    ]);
                }
            }

            // Check if file already contains a valid nomor surat
            $hasValidNomor = false;
            if (!empty($extractedText)) {
                // Pattern untuk detect nomor surat yang sudah valid
                $nomorPattern = '/\d{3}\/[A-Z]+\/[A-Z]+\/INTENS\/\d{2}\/\d{4}/';
                if (preg_match($nomorPattern, $extractedText, $matches)) {
                    $hasValidNomor = true;
                    \Log::info('Valid nomor surat found in file: ' . $matches[0]);
                }
            }

            // Generate nomor surat untuk preview
            $jenisSurat = JenisSurat::find($jenisSuratId);
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%02d/%04d',
                $nextNomorUrut,
                $user->division->kode_divisi,
                $jenisSurat->kode_jenis,
                date('m'),
                date('Y')
            );

            \Log::info('Generated nomor surat:', [
                'nomor_urut' => $nextNomorUrut,
                'nomor_surat' => $nomorSurat
            ]);

            // Prefill data untuk form konfirmasi
            $prefilledData = [
                'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
                'nomor_urut' => $nextNomorUrut,
                'divisi_id' => $user->divisi_id,
                'jenis_surat_id' => $jenisSuratId,
                'has_valid_nomor' => $hasValidNomor,
                'jenisSurat' => JenisSurat::where('divisi_id', $user->divisi_id)->get(),
                'nomor_surat' => $nomorSurat,
                'extracted_text' => $extractedText,
            'extraction_method' => $extractionMethod,
                'ocr_error' => $ocrError
            ];

            \Log::info('Prefilled data prepared:', $prefilledData);

            // Jika file sudah berisi nomor surat valid, langsung ke preview
            if ($hasValidNomor) {
                return view('surat.preview_before_confirm', $prefilledData);
            }

            // Lock nomor urut untuk user ini
            $this->lockNomorUrut($user->divisi_id, $jenisSuratId, $nextNomorUrut, $user->id);

            return view('surat.confirm', $prefilledData);

        } catch (\Exception $e) {
            \Log::error('Error in handleUpload: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['file' => 'Terjadi kesalahan saat upload file: ' . $e->getMessage()]);
        }
    }

    // Show confirmation form for user to verify/correct code
    public function showConfirmForm(Request $request)
    {
        $jenisSurat = JenisSurat::where('divisi_id', Auth::user()->divisi_id)->active()->get();
        $divisiId = Auth::user()->divisi_id;
        $jenisSuratId = $request->input('jenis_surat_id');
        $nomorUrut = $request->input('nomor_urut');
        
        // Hitung nomor surat jika tidak ada di request
        $nomorSurat = $request->input('nomor_surat');
        if (!$nomorSurat && $divisiId && $jenisSuratId && $nomorUrut) {
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%02d/%04d',
                $nomorUrut,
                Division::find($divisiId)->kode_divisi,
                JenisSurat::find($jenisSuratId)->kode_jenis,
                date('m'),
                date('Y')
            );
        }
        
        if ($divisiId && $jenisSuratId && $nomorUrut) {
            \App\Models\NomorUrutLock::updateOrCreate([
                'divisi_id' => $divisiId,
                'jenis_surat_id' => $jenisSuratId,
                'nomor_urut' => $nomorUrut,
            ], [
                'user_id' => \Auth::id(),
                'locked_until' => now()->addMinutes(10),
            ]);
        }
        
        return view('surat.confirm', [
            'file_path' => $request->input('file_path'),
            'file_size' => $request->input('file_size'),
            'mime_type' => $request->input('mime_type'),
            'nomor_surat' => $nomorSurat,
            'nomor_urut' => $nomorUrut,
            'jenis_surat_id' => $jenisSuratId,
            'extracted_text' => $request->input('extracted_text'),
            'input' => $request->all(),
            'divisions' => Division::all(),
            'jenisSurat' => $jenisSurat,
        ]);
    }

    // Store the confirmed/corrected data
    public function store(Request $request)
    {
        try {
        $request->validate([
                'file_path' => 'required',
                'file_size' => 'required|integer',
                'mime_type' => 'required',
            'nomor_urut' => 'required|integer',
            'divisi_id' => 'required|exists:divisions,id',
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
                'perihal' => 'required|string|max:255',
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date',
                'is_private' => 'boolean',
                'selected_users' => 'array',
                'selected_users.*' => 'exists:users,id'
            ]);

            // Generate nomor surat untuk preview
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%02d/%04d',
                $request->nomor_urut,
                Division::find($request->divisi_id)->kode_divisi,
                JenisSurat::find($request->jenis_surat_id)->kode_jenis,
                date('m', strtotime($request->tanggal_surat)),
                date('Y', strtotime($request->tanggal_surat))
            );

            // Redirect ke final preview dengan data yang sudah dikonfirmasi
            return view('surat.final_preview', [
                'file_path' => $request->file_path,
                'file_size' => $request->file_size,
                'mime_type' => $request->mime_type,
                'nomor_surat' => $nomorSurat,
                'input' => [
                    'nomor_urut' => $request->nomor_urut,
                    'divisi_id' => $request->divisi_id,
                    'jenis_surat_id' => $request->jenis_surat_id,
                    'perihal' => $request->perihal,
                    'tanggal_surat' => $request->tanggal_surat,
                    'tanggal_diterima' => $request->tanggal_diterima,
                    'is_private' => $request->has('is_private'),
                    'selected_users' => $request->selected_users ?? []
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in store method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    // Final store after preview confirmation
    public function finalStore(Request $request)
    {
        try {
        $request->validate([
            'file_path' => 'required',
            'file_size' => 'required|integer',
                'mime_type' => 'required',
            'nomor_urut' => 'required|integer',
            'divisi_id' => 'required|exists:divisions,id',
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
                'perihal' => 'required|string|max:255',
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date',
                'is_private' => 'boolean',
                'selected_users' => 'array',
                'selected_users.*' => 'exists:users,id'
            ]);

            // Hapus lock nomor urut user ini (jika ada)
            NomorUrutLock::where('divisi_id', $request->divisi_id)
                ->where('jenis_surat_id', $request->jenis_surat_id)
                ->where('nomor_urut', $request->nomor_urut)
                ->where('user_id', Auth::id())
                ->delete();

        // Cek duplikasi nomor urut
            if (Surat::where('nomor_urut', $request->nomor_urut)
                ->where('divisi_id', $request->divisi_id)
                ->where('jenis_surat_id', $request->jenis_surat_id)
                ->exists()) {
                return back()->withErrors(['nomor_urut' => 'Nomor urut sudah ada untuk jenis surat ini di divisi ini. Silakan pilih nomor lain.'])->withInput();
            }

            // Generate nomor surat untuk database
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%02d/%04d',
                $request->nomor_urut,
                Division::find($request->divisi_id)->kode_divisi,
                JenisSurat::find($request->jenis_surat_id)->kode_jenis,
                date('m', strtotime($request->tanggal_surat)),
                date('Y', strtotime($request->tanggal_surat))
            );

            // Fill PDF dengan nomor surat
            $filePath = $request->input('file_path');
            // Ensure proper path handling with forward slashes
            $storagePath = storage_path('app/' . str_replace('\\', '/', $filePath));
            $fileExtension = strtolower(pathinfo($storagePath, PATHINFO_EXTENSION));
            
            \Log::info('Final store process:', [
                'file_path' => $filePath,
                'storage_path' => $storagePath,
                'file_extension' => $fileExtension,
                'file_exists' => file_exists($storagePath),
                'file_readable' => is_readable($storagePath),
                'file_size' => file_exists($storagePath) ? filesize($storagePath) : 0,
                'file_permissions' => file_exists($storagePath) ? substr(sprintf('%o', fileperms($storagePath)), -4) : 'N/A'
            ]);
            
            $filledFilePath = null;
            if ($fileExtension === 'pdf') {
                $filledFilePath = $this->fillPdfWithNomorSurat($storagePath, $nomorSurat);
            } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                $filledFilePath = $this->fillWordWithNomorSurat($storagePath, $nomorSurat);
            }

            // Jika berhasil fill, gunakan file yang sudah diisi
            if ($filledFilePath && file_exists($filledFilePath)) {
                // Convert absolute path back to relative path for storage
                $relativePath = str_replace(storage_path('app/'), '', $filledFilePath);
                $filePath = str_replace('\\', '/', $relativePath);
                \Log::info('Using filled file: ' . $filePath);
            }

            // Create surat record
        $surat = Surat::create([
                'nomor_urut' => $request->nomor_urut,
                'nomor_surat' => $nomorSurat,
                'divisi_id' => $request->divisi_id,
                'jenis_surat_id' => $request->jenis_surat_id,
                'perihal' => $request->perihal,
                'tanggal_surat' => $request->tanggal_surat,
                'tanggal_diterima' => $request->tanggal_diterima,
                'file_path' => $filePath,
                'file_size' => $request->file_size,
                'mime_type' => $request->mime_type,
                'is_private' => $request->has('is_private'),
            'uploaded_by' => Auth::id(),
        ]);

            // Handle private access
            if ($request->has('is_private') && $request->has('selected_users')) {
                foreach ($request->selected_users as $userId) {
                    \App\Models\SuratAccess::create([
                        'surat_id' => $surat->id,
                        'user_id' => $userId,
                    ]);
                }
            }

            return redirect()->route('home')->with('success', 'Surat berhasil disimpan dengan nomor: ' . $nomorSurat);

        } catch (\Exception $e) {
            \Log::error('Error in finalStore method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Fill PDF with nomor surat by detecting and replacing placeholder text
     */
    private function fillPdfWithNomorSurat($pdfPath, $nomorSurat)
    {
        try {
            \Log::info('Starting PDF fill process:', [
                'pdf_path' => $pdfPath,
                'nomor_surat' => $nomorSurat,
                'file_exists' => file_exists($pdfPath)
            ]);
            
            // Parse PDF untuk detect text dan koordinat
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            
            \Log::info('PDF parsed successfully, pages count: ' . count($pdf->getPages()));
            
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
            $allText = '';
            
            foreach ($pdf->getPages() as $pageIndex => $page) {
                $text = $page->getText();
                $allText .= $text . ' ';
                \Log::info('Page ' . $pageIndex . ' text length: ' . strlen($text));
                
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
            
            // Buat PDF baru dengan replacement (atau copy asli jika tidak ada placeholder)
            $result = $this->createFilledPdf($pdfPath, $replacementData);
            \Log::info('PDF fill result: ' . ($result ? $result : 'null'));
            
            if (!$foundPlaceholder) {
                \Log::warning('Placeholder text tidak ditemukan di PDF: ' . $pdfPath);
                \Log::info('Text yang ada di PDF (first 500 chars):', ['text' => substr($allText, 0, 500)]);
                // Return file yang sudah di-copy meskipun tidak ada placeholder
                return $result;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            \Log::error('Error filling PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
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
     * Create filled PDF with nomor surat replacement
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
            \Log::info('Starting Word fill process:', [
                'word_path' => $wordPath,
                'nomor_surat' => $nomorSurat,
                'file_exists' => file_exists($wordPath)
            ]);
            
            // Load Word document
            $phpWord = IOFactory::load($wordPath);
            
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
            
            // Save the modified Word document
            $tempPath = storage_path('app/temp_filled_word_' . uniqid() . '.docx');
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempPath);
            
            \Log::info('Word document processed: ' . $tempPath);
            
            if (!$foundPlaceholder) {
                \Log::warning('Placeholder text tidak ditemukan di Word document: ' . $wordPath);
                // Return file yang sudah di-save meskipun tidak ada placeholder
                return $tempPath;
            }
            
            return $tempPath;
            
        } catch (\Exception $e) {
            \Log::error('Error filling Word document: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    // Get users for access selection
    public function getUsersForAccess(Request $request)
    {
        $search = $request->get('search', '');
        // Only non-admin users, no limit
        $users = \App\Models\User::where('id', '!=', Auth::id())
                    ->where('is_admin', false)
                    ->where(function($query) use ($search) {
                        $query->where('full_name', 'like', "%{$search}%")
                              ->orWhere('username', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orderBy('full_name')
                    ->get(['id', 'full_name', 'username', 'email', 'divisi_id']);

        return response()->json($users);
    }

    // List all letters with filters
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

        // Sorting
        $sort = $request->get('sort', 'newest');
        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $letters = $query->paginate(15);

        return view('surat.index', [
            'letters' => $letters,
            'filters' => $request->only(['divisi_id', 'jenis_surat_id', 'tanggal_surat', 'is_private', 'sort']),
            'divisions' => Division::all(),
            'jenisSurat' => JenisSurat::active()->get(),
        ]);
    }

    // Extract form data from file or OCR text
    private function extractFormData($file, $extractedText)
    {
        $data = [
            'nomor_urut' => null,
            'divisi_id' => null,
            'jenis_surat_id' => null,
            'perihal' => '', // was 'deskripsi'
            'tanggal_surat' => date('Y-m-d'),
            'tanggal_diterima' => date('Y-m-d'), // Default to today
            'is_private' => false,
            'has_valid_nomor' => false, // Flag untuk detect apakah file sudah berisi nomor valid
        ];

        if ($extractedText) {
            \Log::info('Extracted text for surat:', ['text' => $extractedText]);
        }
        
        // Try to extract nomor_urut and other data from extracted text
        if ($extractedText) {
            // Normalize text: remove double slashes, extra spaces
            $normalizedText = preg_replace('/\/\/+/', '/', $extractedText);
            $normalizedText = preg_replace('/\s+/', ' ', $normalizedText);

            // Check if file already has valid nomor surat format
            if (preg_match('/Nomor:\s*(\d+)\/([^\/\n]+)\/([^\/\n]+)\/INTENS\/?\/?(\d{4})/i', $normalizedText, $matches)) {
                $data['nomor_urut'] = trim($matches[1]);
                $divisiId = $this->findDivisiByKode(trim($matches[2]));
                $jenisId = $this->findJenisSuratByKode(trim($matches[3]));
                $data['divisi_id'] = $divisiId ?: null;
                $data['jenis_surat_id'] = $jenisId ?: null;
                $data['tanggal_surat'] = $this->extractDateFromText($normalizedText);
                $data['has_valid_nomor'] = true; // File sudah berisi nomor valid
            }
            // Alternative pattern: "123/ABC/DEF/INTENS/2023" (without "Nomor:")
            elseif (preg_match('/(\d+)\/([^\/\n]+)\/([^\/\n]+)\/INTENS\/?\/?(\d{4})/i', $normalizedText, $matches)) {
                $data['nomor_urut'] = trim($matches[1]);
                $divisiId = $this->findDivisiByKode(trim($matches[2]));
                $jenisId = $this->findJenisSuratByKode(trim($matches[3]));
                $data['divisi_id'] = $divisiId ?: null;
                $data['jenis_surat_id'] = $jenisId ?: null;
                $data['tanggal_surat'] = $this->extractDateFromText($normalizedText);
                $data['has_valid_nomor'] = true; // File sudah berisi nomor valid
            }
            // Check for placeholder patterns (file belum diisi)
            elseif (preg_match('/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\.\/\.\.\.\.\./i', $normalizedText) ||
                    preg_match('/_____\/_____\/_____\/_____\/_____\/_____/i', $normalizedText) ||
                    preg_match('/-----\/-----\/-----\/-----\/-----\/-----/i', $normalizedText)) {
                $data['has_valid_nomor'] = false; // File masih berisi placeholder
            }
            // Simple number pattern if no structured format found
            if (!$data['nomor_urut'] && preg_match('/(\d+)/', $normalizedText, $matches)) {
                $data['nomor_urut'] = $matches[1];
            }
        }

        // Try to extract nomor_urut from filename if not found in text
        if (!$data['nomor_urut']) {
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            if (preg_match('/(\d+)/', $filename, $matches)) {
                $data['nomor_urut'] = $matches[1];
            }
        }

        // Try to extract dates from text if not already found
        if ($data['tanggal_surat'] === date('Y-m-d')) {
            $data['tanggal_surat'] = $this->extractDateFromText($extractedText);
        }

        // Try to match divisi and jenis_surat from text if not already found
        if (!$data['divisi_id'] && $extractedText) {
            $divisiId = $this->findDivisiFromText($extractedText);
            $data['divisi_id'] = $divisiId ?: null;
        }

        if (!$data['jenis_surat_id'] && $extractedText) {
            $jenisId = $this->findJenisSuratFromText($extractedText);
            $data['jenis_surat_id'] = $jenisId ?: null;
        }

        return $data;
    }

    // Helper method to find divisi by kode
    private function findDivisiByKode($kode)
    {
        $division = Division::where('kode_divisi', $kode)->first();
        return $division ? $division->id : null;
    }

    // Helper method to find jenis surat by kode
    private function findJenisSuratByKode($kode)
    {
        $jenisSurat = JenisSurat::where('kode_jenis', $kode)->first();
        return $jenisSurat ? $jenisSurat->id : null;
    }

    // Helper method to extract date from text
    private function extractDateFromText($text)
    {
        // Look for various date patterns
        $patterns = [
            '/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})/', // DD/MM/YYYY or DD-MM-YYYY
            '/(\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})/', // YYYY/MM/DD or YYYY-MM-DD
            '/(\d{1,2}\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4})/i', // DD Month YYYY
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $dateStr = $matches[1];
                
                // Try different date formats
                $formats = ['d/m/Y', 'd-m-Y', 'Y/m/d', 'Y-m-d'];
                foreach ($formats as $format) {
                    $date = \DateTime::createFromFormat($format, $dateStr);
                    if ($date) {
                        return $date->format('Y-m-d');
                    }
                }
                
                // Handle Indonesian month names
                if (preg_match('/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})/i', $dateStr, $monthMatches)) {
                    $monthMap = [
                        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
                        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
                        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
                    ];
                    $month = $monthMap[strtolower($monthMatches[2])];
                    return sprintf('%04d-%02d-%02d', $monthMatches[3], $month, $monthMatches[1]);
                }
            }
        }
        
        return date('Y-m-d'); // Default to today if no date found
    }

    // Helper method to find divisi from text
    private function findDivisiFromText($text)
    {
        $divisions = Division::all();
        foreach ($divisions as $division) {
            // Match as whole word only
            if (preg_match('/\\b' . preg_quote($division->nama_divisi, '/') . '\\b/i', $text) ||
                preg_match('/\\b' . preg_quote($division->kode_divisi, '/') . '\\b/i', $text)) {
                return $division->id;
            }
        }
        return null;
    }

    // Helper method to find jenis surat from text
    private function findJenisSuratFromText($text)
    {
        $jenisSurat = JenisSurat::active()->get();
        foreach ($jenisSurat as $jenis) {
            // Match as whole word only
            if (preg_match('/\\b' . preg_quote($jenis->nama_jenis, '/') . '\\b/i', $text)) {
                \Log::info('Jenis surat match by nama_jenis', ['match' => $jenis->nama_jenis]);
                return $jenis->id;
            }
            if (preg_match('/\\b' . preg_quote($jenis->kode_jenis, '/') . '\\b/i', $text)) {
                \Log::info('Jenis surat match by kode_jenis', ['match' => $jenis->kode_jenis]);
                return $jenis->id;
            }
        }
        \Log::info('No jenis surat match found in text.');
        return null;
    }

    // Helper: Check for duplicate nomor_urut and get available numbers (per divisi & jenis surat)
    private function checkDuplicate($divisiId, $jenisSuratId, $nomorUrut)
    {
        $isDuplicate = Surat::where('nomor_urut', $nomorUrut)
                           ->where('divisi_id', $divisiId)
            ->where('jenis_surat_id', $jenisSuratId)
                           ->exists();

        if ($isDuplicate) {
            \Log::warning('Duplicate nomor urut detected:', [
                'nomor_urut' => $nomorUrut,
                'divisi_id' => $divisiId,
                'jenis_surat_id' => $jenisSuratId
            ]);
        }
        
        return $isDuplicate;
    }

    // Helper: Get next available nomor urut (skip locked by others)
    public function getNextNomorUrut($divisiId, $jenisSuratId)
    {
        \Log::info('Getting next nomor urut:', [
            'divisi_id' => $divisiId,
            'jenis_surat_id' => $jenisSuratId
        ]);
        
        $usedNumbers = Surat::where('divisi_id', $divisiId)
            ->where('jenis_surat_id', $jenisSuratId)
                               ->pluck('nomor_urut')
            ->toArray();
            
        $lockedNumbers = NomorUrutLock::where('divisi_id', $divisiId)
            ->where('jenis_surat_id', $jenisSuratId)
            ->where(function($q) {
                $q->whereNull('locked_until')->orWhere('locked_until', '>', now());
            })
                               ->pluck('nomor_urut')
            ->toArray();
            
        $allUsed = array_unique(array_merge($usedNumbers, $lockedNumbers));
        
        \Log::info('Nomor urut status:', [
            'used_numbers' => $usedNumbers,
            'locked_numbers' => $lockedNumbers,
            'all_used' => $allUsed
        ]);
        
        for ($i = 1; $i <= 999; $i++) {
            if (!in_array($i, $allUsed)) {
                \Log::info('Next available nomor urut: ' . $i);
                return $i;
            }
        }
        
        \Log::warning('No available nomor urut found');
        return null;
    }

    // Helper: Lock nomor urut for a specific user
    private function lockNomorUrut($divisiId, $jenisSuratId, $nomorUrut, $userId)
    {
        try {
            \Log::info('Locking nomor urut:', [
                'divisi_id' => $divisiId,
                'jenis_surat_id' => $jenisSuratId,
                'nomor_urut' => $nomorUrut,
                'user_id' => $userId
            ]);
            
            NomorUrutLock::updateOrCreate([
                'divisi_id' => $divisiId,
                'jenis_surat_id' => $jenisSuratId,
                'nomor_urut' => $nomorUrut,
            ], [
                'user_id' => $userId,
                'locked_until' => now()->addMinutes(10),
            ]);
            
            \Log::info('Nomor urut locked successfully');
            
        } catch (\Exception $e) {
            \Log::error('Error locking nomor urut: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function preview(Request $request)
    {
        try {
            $request->validate([
                'file_path' => 'required',
                'nomor_urut' => 'required|integer',
                'divisi_id' => 'required|exists:divisions,id',
                'jenis_surat_id' => 'required|exists:jenis_surat,id',
                'tanggal_surat' => 'required|date',
            ]);
            
            $filePath = $request->input('file_path');
            
            // PERBAIKAN PATH HANDLING - Pastikan path benar
            $correctPath = storage_path('app/' . $filePath);
            
            \Log::info('Preview request:', [
                'file_path' => $filePath,
                'correct_path' => $correctPath,
                'exists' => file_exists($correctPath),
                'is_readable' => is_readable($correctPath),
                'is_writable' => is_writable($correctPath),
                'file_size' => file_exists($correctPath) ? filesize($correctPath) : 0,
                'file_permissions' => file_exists($correctPath) ? substr(sprintf('%o', fileperms($correctPath)), -4) : 'N/A',
                'storage_path' => storage_path('app'),
                'storage_exists' => is_dir(storage_path('app')),
                'storage_readable' => is_readable(storage_path('app'))
            ]);
            
            if (!file_exists($correctPath)) {
                \Log::error('File tidak ditemukan untuk preview: ' . $correctPath);
                return response('File tidak ditemukan. Path: ' . $filePath, 404);
            }
            
            if (!is_readable($correctPath)) {
                \Log::error('File tidak dapat dibaca: ' . $correctPath);
                return response('File tidak dapat dibaca: ' . $correctPath, 403);
            }
            
            // Generate nomor surat untuk preview
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%02d/%04d',
                $request->nomor_urut,
                Division::find($request->divisi_id)->kode_divisi,
                JenisSurat::find($request->jenis_surat_id)->kode_jenis,
                date('m', strtotime($request->tanggal_surat)),
                date('Y', strtotime($request->tanggal_surat))
            );
            
            \Log::info('Generated nomor surat for preview: ' . $nomorSurat);
            
            $fileExtension = strtolower(pathinfo($correctPath, PATHINFO_EXTENSION));
            
            \Log::info('File extension: ' . $fileExtension);
            
            // Try to fill the document first
            $filledFilePath = null;
            $fillingSuccess = false;
            
            if ($fileExtension === 'pdf') {
                // Fill PDF dengan nomor surat untuk preview
                \Log::info('Attempting to fill PDF...');
                $filledFilePath = $this->fillPdfWithNomorSurat($correctPath, $nomorSurat);
                
                if ($filledFilePath && file_exists($filledFilePath)) {
                    \Log::info('PDF filled successfully: ' . $filledFilePath);
                    $fillingSuccess = true;
                } else {
                    \Log::warning('PDF filling failed, will show original file');
                }
            } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                // Fill Word document dengan nomor surat untuk preview
                \Log::info('Attempting to fill Word document...');
                $filledFilePath = $this->fillWordWithNomorSurat($correctPath, $nomorSurat);
                
                if ($filledFilePath && file_exists($filledFilePath)) {
                    \Log::info('Word document filled successfully: ' . $filledFilePath);
                    $fillingSuccess = true;
                } else {
                    \Log::warning('Word filling failed, will show original file');
                }
            }
            
            // Return the appropriate file
            if ($fillingSuccess && $filledFilePath) {
                if ($fileExtension === 'pdf') {
                    return response()->file($filledFilePath, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="preview_surat.pdf"',
                    ])->deleteFileAfterSend(true);
                } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                    $mimeType = $fileExtension === 'docx' ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : 'application/msword';
                    return response()->download($filledFilePath, 'preview_surat.' . $fileExtension, [
                        'Content-Type' => $mimeType,
                    ])->deleteFileAfterSend(true);
                }
            }
            
            // Fallback: return file asli jika tidak bisa fill
            \Log::info('Returning original file as fallback');
            if ($fileExtension === 'pdf') {
                return response()->file($correctPath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="preview_surat.pdf"',
                ]);
            } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                $mimeType = $fileExtension === 'docx' ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : 'application/msword';
                return response()->download($correctPath, 'preview_surat.' . $fileExtension, [
                    'Content-Type' => $mimeType,
                ]);
            } else {
                return response()->download($correctPath);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error in preview method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response('Error generating preview: ' . $e->getMessage(), 500);
        }
    }

    // Store from preview (when file already has valid nomor surat)
    public function storeFromPreview(Request $request)
    {
        $request->validate([
            'file_path' => 'required',
            'file_size' => 'required|integer',
            'mime_type' => 'required|string',
            'nomor_urut' => 'required|integer',
            'divisi_id' => 'required|exists:divisions,id',
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
            'perihal' => 'required|string',
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date',
        ]);

        // Generate nomor surat untuk database
        $nomorSurat = sprintf('%03d/%s/%s/INTENS/%02d/%04d',
            $request->nomor_urut,
            Division::find($request->divisi_id)->kode_divisi,
            JenisSurat::find($request->jenis_surat_id)->kode_jenis,
            date('m', strtotime($request->tanggal_surat)),
            date('Y', strtotime($request->tanggal_surat))
        );

        // Cek duplikasi nomor urut
        if (Surat::where('nomor_urut', $request->nomor_urut)
            ->where('divisi_id', $request->divisi_id)
            ->where('jenis_surat_id', $request->jenis_surat_id)
            ->exists()) {
            return back()->withErrors(['nomor_urut' => 'Nomor urut sudah ada untuk jenis surat ini di divisi ini.'])->withInput();
        }

        // Create surat record (file sudah berisi nomor, tidak perlu fill lagi)
        $surat = Surat::create([
            'nomor_urut' => $request->nomor_urut,
            'nomor_surat' => $nomorSurat,
            'divisi_id' => $request->divisi_id,
            'jenis_surat_id' => $request->jenis_surat_id,
            'perihal' => $request->perihal,
            'tanggal_surat' => $request->tanggal_surat,
            'tanggal_diterima' => $request->tanggal_diterima,
            'file_path' => $request->file_path,
            'file_size' => $request->file_size,
            'mime_type' => $request->mime_type,
            'is_private' => $request->has('is_private'),
            'uploaded_by' => Auth::id(),
        ]);

        return redirect()->route('home')->with('success', 'Surat berhasil disimpan dengan nomor: ' . $nomorSurat);
    }
} 