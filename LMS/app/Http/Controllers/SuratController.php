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
use App\Traits\DocumentProcessor;
use App\Traits\RomanNumeralConverter;

class SuratController extends Controller
{
    use DocumentProcessor;
    use RomanNumeralConverter;

    // ================================= FORM UPLOAD =================================
    
    // tampilkan form upload
    public function showUploadForm()
    {
        $user = Auth::user();
        $jenisSurat = JenisSurat::where('divisi_id', $user->divisi_id)->get();
        
        return view('surat.automatic.form', compact('jenisSurat'));
    }

    // ================================= HANDLE UPLOAD =================================
    
    // proses upload file dan ekstrak teks
    public function handleUpload(Request $request)
    {
        try {
        $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // maks 10MB
                'jenis_surat_id' => 'required|exists:jenis_surat,id',
        ]);

        $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
            $jenisSuratId = $request->input('jenis_surat_id');
            
            \Log::info('File upload dimulai:', [
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'jenis_surat_id' => $jenisSuratId
            ]);

            // pastikan direktori ada
            $storageDir = storage_path('app/letters');
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
                \Log::info('Direktori dibuat: ' . $storageDir);
            }

            // buat nama file yang deskriptif
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

            // simpan file
            $filePath = $file->storeAs('letters', $descriptiveName);
            
            \Log::info('File berhasil diupload:', [
                'original_name' => $originalName,
                'descriptive_name' => $descriptiveName,
                'file_path' => $filePath,
                'full_path' => storage_path('app/' . $filePath),
                'storage_dir' => $storageDir,
                'exists' => Storage::exists($filePath),
                'file_size' => Storage::size($filePath)
            ]);

            // konversi DOCX ke PDF pakai LibreOffice
            if (in_array($fileExtension, ['doc', 'docx'])) {
                \Log::info('Konversi Word ke PDF pakai LibreOffice');
                $fullPath = storage_path('app/' . $filePath);
                
                // konversi ke PDF
                $convertedPdfPath = $this->convertWordToPdfWithLibreOffice($fullPath);
                
                if ($convertedPdfPath && file_exists($convertedPdfPath)) {
                    // ganti file asli dengan versi PDF
                    $pdfDescriptiveName = str_replace('.' . $fileExtension, '.pdf', $descriptiveName);
                    $newFilePath = 'letters/' . $pdfDescriptiveName;
                    
                    // simpan PDF yang udah dikonversi
                    Storage::put($newFilePath, file_get_contents($convertedPdfPath));
                    
                    // hapus file PDF sementara
                    unlink($convertedPdfPath);
                    
                    // hapus file Word asli
                    Storage::delete($filePath);
                    
                    // update variabel buat pake versi PDF
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
                    \Log::error('Gagal konversi Word ke PDF pake LibreOffice, tetep pake file asli');
                    // tetep pake file DOCX asli - nanti dihandle beda di preview
                }
            }

            // cek duplicate nomor urut
            $user = Auth::user();
            $nextNomorUrut = $this->getNextNomorUrut($user->divisi_id, $jenisSuratId);
            
            \Log::info('Nomor urut check:', [
                'next_nomor_urut' => $nextNomorUrut,
                'divisi_id' => $user->divisi_id,
                'jenis_surat_id' => $jenisSuratId
            ]);
            
            if ($this->checkDuplicate($user->divisi_id, $jenisSuratId, $nextNomorUrut)) {
                \Log::warning('Duplicate nomor urut detected, showing warning');
                
                // hapus file yang diupload karena duplicate
                Storage::delete($filePath);
                
                return view('surat.automatic.duplicate_warning', [
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'nomor_urut' => $nextNomorUrut,
                    'divisi_id' => $user->divisi_id,
                    'jenis_surat_id' => $jenisSuratId
                ]);
            }

            // ekstrak teks dari file buat detect nomor surat yang udah ada
            $extractedText = '';
            $extractionMethod = '';
        $ocrError = null;
        
        // karena sekarang semua Word udah dikonversi ke PDF, cuma perlu handle PDF
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
                $extractionMethod = 'Word Parser (fallback)';
                $extractedText = 'Word document processing - should have been converted to PDF.';
            }

            // cek apakah file udah ada nomor surat yang valid
            $hasValidNomor = false;
            if (!empty($extractedText)) {
                $nomorPattern = '/\d{3}\/[A-Z]+\/[A-Z]+\/INTENS\/\d{2}\/\d{4}/';
                if (preg_match($nomorPattern, $extractedText, $matches)) {
                    $hasValidNomor = true;
                    \Log::info('Nomor surat valid ditemukan di file: ' . $matches[0]);
                }
            }

            // generate nomor surat buat preview
            $jenisSurat = JenisSurat::find($jenisSuratId);
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%s/%04d',
                $nextNomorUrut,
                $user->division->kode_divisi,
                $jenisSurat->kode_jenis,
                $this->monthToRoman(date('n')),
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
                return view('surat.automatic.preview', $prefilledData);
            }

            // Don't lock here - let the JavaScript handle initial lock to avoid duplicates
            return view('surat.automatic.confirm', $prefilledData);

        } catch (\Exception $e) {
            \Log::error('Error in handleUpload: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['file' => 'Terjadi kesalahan saat upload file: ' . $e->getMessage()]);
        }
    }

    // tampilkan form konfirmasi buat user verify/koreksi kode
    public function showConfirmForm(Request $request)
    {
        $jenisSurat = JenisSurat::where('divisi_id', Auth::user()->divisi_id)->active()->get();
        $divisiId = Auth::user()->divisi_id;
        $jenisSuratId = $request->input('jenis_surat_id');
        $nomorUrut = $request->input('nomor_urut');
        
        // Hitung nomor surat jika tidak ada di request
        $nomorSurat = $request->input('nomor_surat');
        if (!$nomorSurat && $divisiId && $jenisSuratId && $nomorUrut) {
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%s/%04d',
                $nomorUrut,
                Division::find($divisiId)->kode_divisi,
                JenisSurat::find($jenisSuratId)->kode_jenis,
                $this->monthToRoman(date('n')),
                date('Y')
            );
        }
        
        // Don't create locks here - let JavaScript handle it to avoid race conditions
        
        return view('surat.automatic.confirm', [
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
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%s/%04d',
                $request->nomor_urut,
                Division::find($request->divisi_id)->kode_divisi,
                JenisSurat::find($request->jenis_surat_id)->kode_jenis,
                $this->monthToRoman(date('n', strtotime($request->tanggal_surat))),
                date('Y', strtotime($request->tanggal_surat))
            );

            // Redirect ke final preview dengan data yang sudah dikonfirmasi
            return view('surat.automatic.final_preview', [
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
            \Log::info('finalStore method called with data:', [
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
                'user_authenticated' => Auth::check(),
                'ip' => $request->ip()
            ]);
            
            if (!Auth::check()) {
                \Log::error('User not authenticated in finalStore');
                return redirect()->route('login')->with('error', 'Session expired. Please login again.');
            }
            
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
            
            \Log::info('finalStore validation passed, proceeding with data:', [
                'nomor_urut' => $request->nomor_urut,
                'divisi_id' => $request->divisi_id,
                'jenis_surat_id' => $request->jenis_surat_id,
                'perihal' => $request->perihal,
                'tanggal_surat' => $request->tanggal_surat,
                'file_path' => $request->file_path
            ]);

            // Hapus lock nomor urut user ini (jika ada) - cleanup for all months
            NomorUrutLock::where('divisi_id', $request->divisi_id)
                ->where('jenis_surat_id', $request->jenis_surat_id)
                ->where('nomor_urut', $request->nomor_urut)
                ->where('user_id', Auth::id())
                ->delete();
                
            // Trigger immediate cleanup of expired locks
            $expiredCleaned = NomorUrutLock::cleanupExpiredLocks();
            if ($expiredCleaned > 0) {
                \Log::info("Cleaned up {$expiredCleaned} expired locks during finalStore");
            }

        // Cek duplikasi nomor urut untuk bulan yang sama
            $monthYear = \Carbon\Carbon::parse($request->tanggal_surat)->format('Y-m');
            
            \Log::info('Checking for duplicate nomor urut with month specificity:', [
                'nomor_urut' => $request->nomor_urut,
                'divisi_id' => $request->divisi_id,
                'jenis_surat_id' => $request->jenis_surat_id,
                'month_year' => $monthYear,
                'tanggal_surat' => $request->tanggal_surat
            ]);
            
            $existingSurat = Surat::where('nomor_urut', $request->nomor_urut)
                ->where('divisi_id', $request->divisi_id)
                ->where('jenis_surat_id', $request->jenis_surat_id)
                ->whereYear('tanggal_surat', substr($monthYear, 0, 4))
                ->whereMonth('tanggal_surat', substr($monthYear, 5, 2))
                ->first();
                
            if ($existingSurat) {
                \Log::warning('Duplicate nomor urut detected in finalStore for the same month:', [
                    'nomor_urut' => $request->nomor_urut,
                    'divisi_id' => $request->divisi_id,
                    'jenis_surat_id' => $request->jenis_surat_id,
                    'month_year' => $monthYear,
                    'existing_surat_id' => $existingSurat->id,
                    'existing_surat_nomor' => $existingSurat->nomor_surat,
                    'existing_surat_tanggal' => $existingSurat->tanggal_surat,
                    'existing_surat_uploaded_by' => $existingSurat->uploaded_by,
                    'current_user_id' => Auth::id()
                ]);
                return back()->withErrors(['nomor_urut' => 'Nomor urut sudah ada untuk bulan ini pada jenis surat ini di divisi ini. Silakan pilih nomor lain.'])->withInput();
            }
            
            \Log::info('No duplicate nomor urut found, proceeding with store');

            // Generate nomor surat untuk database
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%s/%04d',
                $request->nomor_urut,
                Division::find($request->divisi_id)->kode_divisi,
                JenisSurat::find($request->jenis_surat_id)->kode_jenis,
                $this->monthToRoman(date('n', strtotime($request->tanggal_surat))),
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
            $finalMimeType = $request->mime_type;
            $finalFileSize = $request->file_size;
            
            if ($fileExtension === 'pdf') {
                $filledFilePath = $this->fillPdfWithNomorSurat($storagePath, $nomorSurat);
            } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                // Convert Word to PDF
                $filledFilePath = $this->fillWordWithNomorSuratAndConvertToPdf($storagePath, $nomorSurat);
                if ($filledFilePath) {
                    $finalMimeType = 'application/pdf'; // Update mime type to PDF
                    $finalFileSize = filesize($filledFilePath); // Update file size
                }
            }

            // Jika berhasil fill, gunakan file yang sudah diisi
            if ($filledFilePath && file_exists($filledFilePath)) {
                // Store the filled file in Laravel storage
                $filledStoragePath = 'letters/filled_' . uniqid() . '.pdf';
                \Illuminate\Support\Facades\Storage::put($filledStoragePath, file_get_contents($filledFilePath));
                
                // Clean up temporary file
                unlink($filledFilePath);
                
                // Update file path and size
                $filePath = $filledStoragePath;
                $finalFileSize = \Illuminate\Support\Facades\Storage::size($filePath);
                
                \Log::info('Using filled and converted file: ' . $filePath);
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
                'file_size' => $finalFileSize,
                'mime_type' => $finalMimeType,
                'is_private' => $request->has('is_private'),
            'uploaded_by' => Auth::id(),
        ]);

            // NOW increment the counter in JenisSurat since letter is actually stored
            $this->incrementNomorUrut($request->jenis_surat_id, $request->tanggal_surat);
            
            \Log::info('Letter successfully stored and counter incremented', [
                'surat_id' => $surat->id,
                'nomor_surat' => $nomorSurat,
                'jenis_surat_id' => $request->jenis_surat_id
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

            \Log::info('finalStore about to redirect to home with success message', [
                'surat_id' => $surat->id,
                'nomor_surat' => $nomorSurat
            ]);

            return redirect()->route('home')->with('success', 'Surat berhasil disimpan dengan nomor: ' . $nomorSurat);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in finalStore:', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'validator_messages' => $e->validator->messages()->toArray()
            ]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error in finalStore method: ' . $e->getMessage(), [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
        }
    }

    // Store directly from preview (when file already has valid nomor surat)
    public function storeFromPreview(Request $request)
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
                'is_private' => 'boolean'
            ]);

            // Check for duplicate nomor urut for the same month
            $monthYear = \Carbon\Carbon::parse($request->tanggal_surat)->format('Y-m');
            
            if (Surat::where('nomor_urut', $request->nomor_urut)
                ->where('divisi_id', $request->divisi_id)
                ->where('jenis_surat_id', $request->jenis_surat_id)
                ->whereYear('tanggal_surat', substr($monthYear, 0, 4))
                ->whereMonth('tanggal_surat', substr($monthYear, 5, 2))
                ->exists()) {
                return back()->withErrors(['nomor_urut' => 'Nomor urut sudah ada untuk bulan ini pada jenis surat ini di divisi ini.'])->withInput();
            }

            // Generate nomor surat
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%s/%04d',
                $request->nomor_urut,
                Division::find($request->divisi_id)->kode_divisi,
                JenisSurat::find($request->jenis_surat_id)->kode_jenis,
                $this->monthToRoman(date('n', strtotime($request->tanggal_surat))),
                date('Y', strtotime($request->tanggal_surat))
            );

            // Create surat record
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
            
            // NOW increment the counter in JenisSurat since letter is actually stored
            $this->incrementNomorUrut($request->jenis_surat_id, $request->tanggal_surat);
            
            \Log::info('Letter successfully stored and counter incremented', [
                'surat_id' => $surat->id,
                'nomor_surat' => $nomorSurat,
                'jenis_surat_id' => $request->jenis_surat_id
            ]);
            
            // Cleanup expired locks and any locks for this combination - all months
            NomorUrutLock::where('divisi_id', $request->divisi_id)
                ->where('jenis_surat_id', $request->jenis_surat_id)
                ->where('nomor_urut', $request->nomor_urut)
                ->delete();
                
            $expiredCleaned = NomorUrutLock::cleanupExpiredLocks();
            if ($expiredCleaned > 0) {
                \Log::info("Cleaned up {$expiredCleaned} expired locks during storeFromPreview");
            }

            return redirect()->route('home')->with('success', 'Surat berhasil disimpan dengan nomor: ' . $nomorSurat);

        } catch (\Exception $e) {
            \Log::error('Error in storeFromPreview method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
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
        if (!$text) {
            return date('Y-m-d');
        }

        // First, try to extract date patterns starting with "Pada tanggal"
        $padaTanggalPattern = '/Pada\s+tanggal\s+([^,\.!?]+?)(?:\s+(?:kami|dengan|telah|adalah|yang))?\s*(?:[,\.!?]|$)/i';
        if (preg_match($padaTanggalPattern, $text, $matches)) {
            $dateText = trim($matches[1]);
            \Log::info('Found "Pada tanggal" pattern: ' . $dateText);
            
            // Try to parse this specific date text
            $parsedDate = $this->parseIndonesianDate($dateText);
            if ($parsedDate) {
                return $parsedDate;
            }
        }

        // Look for various date patterns - ordered from most specific to least specific
        $patterns = [
            '/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})/', // DD/MM/YYYY or DD-MM-YYYY
            '/(\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})/', // YYYY/MM/DD or YYYY-MM-DD
            '/(\d{1,2}\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4})/i', // DD Month YYYY
            
            // Most specific: Complete Indonesian written dates (Day + Month + Year)
            '/((?:satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan|sepuluh|sebelas|dua\s+belas|tiga\s+belas|empat\s+belas|lima\s+belas|enam\s+belas|tujuh\s+belas|delapan\s+belas|sembilan\s+belas|dua\s+puluh|dua\s+puluh\s+satu|dua\s+puluh\s+dua|dua\s+puluh\s+tiga|dua\s+puluh\s+empat|dua\s+puluh\s+lima|dua\s+puluh\s+enam|dua\s+puluh\s+tujuh|dua\s+puluh\s+delapan|dua\s+puluh\s+sembilan|tiga\s+puluh|tiga\s+puluh\s+satu|\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(dua\s+ribu[^,\.!?]*?)(?:\s+(?:dengan|kami|yang|telah))?[,\.!?\s])/i', // Indonesian written dates
            
            // Less specific: Month + Year only 
            '/(?:bulan\s+)?(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(dua\s+ribu(?:\s+(?!kami|dengan|yang|telah)[a-z]+)*|\d{4})/i', // Month + Year only
            
            // Least specific: Year only (written)
            '/(?:tahun\s+)?(dua\s+ribu\s+(?:dua\s+puluh\s+)?(?:satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan))/i', // Year only (written)
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $dateStr = $matches[1];
                \Log::info('Found date pattern: ' . $dateStr);
                
                // Check if this is an Indonesian written date (complete or partial)
                if (preg_match('/((?:satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan|sepuluh|sebelas|dua\s+belas|tiga\s+belas|empat\s+belas|lima\s+belas|enam\s+belas|tujuh\s+belas|delapan\s+belas|sembilan\s+belas|dua\s+puluh|dua\s+puluh\s+satu|dua\s+puluh\s+dua|dua\s+puluh\s+tiga|dua\s+puluh\s+empat|dua\s+puluh\s+lima|dua\s+puluh\s+enam|dua\s+puluh\s+tujuh|dua\s+puluh\s+delapan|dua\s+puluh\s+sembilan|tiga\s+puluh|tiga\s+puluh\s+satu|\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+((?:satu\s+ribu\s+sembilan\s+ratus|dua\s+ribu|dua\s+ribu\s+[a-z\s]+|\d{4})))/i', $dateStr)) {
                    $parsedDate = $this->parseIndonesianDate($dateStr);
                    if ($parsedDate) {
                        return $parsedDate;
                    }
                }
                
                // Check for month + year only patterns
                if (preg_match('/(?:bulan\s+)?(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(dua\s+ribu\s+[a-z\s]+|\d{4})/i', $dateStr, $monthYearMatches)) {
                    $monthMap = [
                        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
                        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
                        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
                    ];
                    $month = $monthMap[strtolower($monthYearMatches[1])];
                    $yearText = $monthYearMatches[2];
                    
                    // Parse year
                    $year = null;
                    if (is_numeric($yearText)) {
                        $year = (int)$yearText;
                    } else {
                        $year = $this->parseIndonesianYear($yearText);
                    }
                    
                    if ($year && $month) {
                        \Log::info("Parsed month+year: $month/$year");
                        return sprintf('%04d-%02d-01', $year, $month); // Default to 1st of month
                    }
                }
                
                // Check for year only patterns
                if (preg_match('/(?:tahun\s+)?(dua\s+ribu\s+(?:dua\s+puluh\s+)?(?:satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan))/i', $dateStr, $yearMatches)) {
                    $year = $this->parseIndonesianYear($yearMatches[1]);
                    if ($year) {
                        \Log::info("Parsed year only: $year");
                        return sprintf('%04d-01-01', $year); // Default to January 1st
                    }
                }
                
                // Try different date formats for numeric dates
                $formats = ['d/m/Y', 'd-m-Y', 'Y/m/d', 'Y-m-d'];
                foreach ($formats as $format) {
                    $date = \DateTime::createFromFormat($format, $dateStr);
                    if ($date) {
                        return $date->format('Y-m-d');
                    }
                }
                
                // Handle Indonesian month names with numeric day and year
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

    private function parseIndonesianDate($dateText)
    {
        $dateText = strtolower(trim($dateText));
        \Log::info('Parsing Indonesian date: ' . $dateText);
        
        // Clean up extra words that might interfere
        $cleanText = preg_replace('/\b(kami|mengundang|dengan|ini|yang|lalu|telah|dilaksanakan|adalah|hari|penting|dalam|teks|senin|selasa|rabu|kamis|jumat|sabtu|minggu)\b/i', '', $dateText);
        $cleanText = trim(preg_replace('/\s+/', ' ', $cleanText));
        \Log::info('Cleaned text: ' . $cleanText);
        
        // Indonesian number to digit mapping
        $numberMap = [
            'satu' => 1, 'dua' => 2, 'tiga' => 3, 'empat' => 4, 'lima' => 5,
            'enam' => 6, 'tujuh' => 7, 'delapan' => 8, 'sembilan' => 9, 'sepuluh' => 10,
            'sebelas' => 11, 'dua belas' => 12, 'tiga belas' => 13, 'empat belas' => 14,
            'lima belas' => 15, 'enam belas' => 16, 'tujuh belas' => 17, 'delapan belas' => 18,
            'sembilan belas' => 19, 'dua puluh' => 20, 'dua puluh satu' => 21, 'dua puluh dua' => 22,
            'dua puluh tiga' => 23, 'dua puluh empat' => 24, 'dua puluh lima' => 25,
            'dua puluh enam' => 26, 'dua puluh tujuh' => 27, 'dua puluh delapan' => 28,
            'dua puluh sembilan' => 29, 'tiga puluh' => 30, 'tiga puluh satu' => 31
        ];

        $monthMap = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
        ];

        $day = null;
        $month = null;
        $year = null;

        // Split into words for analysis
        $words = explode(' ', $cleanText);
        
        // First, find the month (most reliable anchor)
        $monthIndex = -1;
        foreach ($words as $i => $word) {
            if (isset($monthMap[$word])) {
                $month = $monthMap[$word];
                $monthIndex = $i;
                \Log::info("Found month: $word = $month at position $i");
                break;
            }
        }
        
        // If we have a month, look for day before it and year after it
        if ($month !== null && $monthIndex >= 0) {
            // Look for day before month (1-4 words before)
            for ($i = max(0, $monthIndex - 4); $i < $monthIndex; $i++) {
                $dayCandidate = $this->parseIndonesianDayFromWords($words, $i);
                if ($dayCandidate && $dayCandidate >= 1 && $dayCandidate <= 31) {
                    $day = $dayCandidate;
                    \Log::info("Found day: $day");
                    break;
                }
            }
            
            // Look for year after month
            for ($i = $monthIndex + 1; $i < count($words); $i++) {
                $yearCandidate = $this->parseIndonesianYearFromWords($words, $i);
                if ($yearCandidate && $yearCandidate >= 1900 && $yearCandidate <= 2100) {
                    $year = $yearCandidate;
                    \Log::info("Found year: $year");
                    break;
                }
            }
        }
        
        // If no month found, try to find just a year
        if ($month === null) {
            foreach ($words as $i => $word) {
                $yearCandidate = $this->parseIndonesianYearFromWords($words, $i);
                if ($yearCandidate && $yearCandidate >= 1900 && $yearCandidate <= 2100) {
                    $year = $yearCandidate;
                    \Log::info("Found standalone year: $year");
                    break;
                }
            }
        }
        
        \Log::info("Final parsed components - Day: $day, Month: $month, Year: $year");
        
        // Return date in flexible format - use defaults for missing parts
        if ($year) {
            $finalDay = $day ?: 1; // Default to 1st if no day
            $finalMonth = $month ?: 1; // Default to January if no month
            
            // Validate ranges
            if ($finalDay >= 1 && $finalDay <= 31 && $finalMonth >= 1 && $finalMonth <= 12) {
                $result = sprintf('%04d-%02d-%02d', $year, $finalMonth, $finalDay);
                \Log::info("Returning flexible date: $result");
                return $result;
            }
        }
        
        return null;
    }

    private function parseIndonesianDayFromWords($words, $startIndex)
    {
        if (!isset($words[$startIndex])) return null;
        
        $numberMap = [
            'satu' => 1, 'dua' => 2, 'tiga' => 3, 'empat' => 4, 'lima' => 5,
            'enam' => 6, 'tujuh' => 7, 'delapan' => 8, 'sembilan' => 9, 'sepuluh' => 10,
            'sebelas' => 11, 'dua belas' => 12, 'tiga belas' => 13, 'empat belas' => 14,
            'lima belas' => 15, 'enam belas' => 16, 'tujuh belas' => 17, 'delapan belas' => 18,
            'sembilan belas' => 19, 'dua puluh' => 20, 'dua puluh satu' => 21, 'dua puluh dua' => 22,
            'dua puluh tiga' => 23, 'dua puluh empat' => 24, 'dua puluh lima' => 25,
            'dua puluh enam' => 26, 'dua puluh tujuh' => 27, 'dua puluh delapan' => 28,
            'dua puluh sembilan' => 29, 'tiga puluh' => 30, 'tiga puluh satu' => 31
        ];
        
        // Check if it's a simple numeric day
        if (is_numeric($words[$startIndex])) {
            $day = (int)$words[$startIndex];
            return ($day >= 1 && $day <= 31) ? $day : null;
        }
        
        // Try compound phrases like "dua puluh tiga"
        for ($len = 3; $len >= 1; $len--) {
            if ($startIndex + $len - 1 < count($words)) {
                $phrase = implode(' ', array_slice($words, $startIndex, $len));
                if (isset($numberMap[$phrase])) {
                    return $numberMap[$phrase];
                }
            }
        }
        
        return null;
    }
    
    private function parseIndonesianYearFromWords($words, $startIndex)
    {
        if (!isset($words[$startIndex])) return null;
        
        // Check if it's a simple numeric year
        if (is_numeric($words[$startIndex])) {
            $year = (int)$words[$startIndex];
            return ($year >= 1900 && $year <= 2100) ? $year : null;
        }
        
        // Try to parse written year like "dua ribu dua puluh empat"
        $yearText = '';
        $maxWords = min(6, count($words) - $startIndex); // Look ahead up to 6 words
        
        for ($i = 0; $i < $maxWords; $i++) {
            $word = $words[$startIndex + $i];
            // Stop if we hit a word that's not part of a year
            if (!in_array($word, ['dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'satu', 'ribu', 'ratus', 'puluh', 'belas', 'sepuluh', 'sebelas'])) {
                break;
            }
            $yearText .= $word . ' ';
        }
        
        $yearText = trim($yearText);
        if ($yearText) {
            return $this->parseIndonesianYear($yearText);
        }
        
        return null;
    }

    private function parseIndonesianYear($yearText)
    {
        $yearText = strtolower(trim($yearText));
        
        // Clean up punctuation and extra words
        $yearText = preg_replace('/[,\.!?]/', '', $yearText);
        $yearText = preg_replace('/\b(dengan|kami|yang|telah|ini|adalah|lalu|hingga)\b/', '', $yearText);
        $yearText = trim(preg_replace('/\s+/', ' ', $yearText));
        
        \Log::info('Parsing Indonesian year (cleaned): ' . $yearText);
        
        // Common year patterns - ordered from most specific to least specific
        $yearPatterns = [
            // 2020s - most specific first
            'dua ribu dua puluh sembilan' => 2029,
            'dua ribu dua puluh delapan' => 2028,
            'dua ribu dua puluh tujuh' => 2027,
            'dua ribu dua puluh enam' => 2026,
            'dua ribu dua puluh lima' => 2025,
            'dua ribu dua puluh empat' => 2024,
            'dua ribu dua puluh tiga' => 2023,
            'dua ribu dua puluh dua' => 2022,
            'dua ribu dua puluh satu' => 2021,
            'dua ribu dua puluh' => 2020,
            
            // 2010s
            'dua ribu sembilan belas' => 2019,
            'dua ribu delapan belas' => 2018,
            'dua ribu tujuh belas' => 2017,
            'dua ribu enam belas' => 2016,
            'dua ribu lima belas' => 2015,
            'dua ribu empat belas' => 2014,
            'dua ribu tiga belas' => 2013,
            'dua ribu dua belas' => 2012,
            'dua ribu sebelas' => 2011,
            'dua ribu sepuluh' => 2010,
            
            // 2000s
            'dua ribu sembilan' => 2009,
            'dua ribu delapan' => 2008,
            'dua ribu tujuh' => 2007,
            'dua ribu enam' => 2006,
            'dua ribu lima' => 2005,
            'dua ribu empat' => 2004,
            'dua ribu tiga' => 2003,
            'dua ribu dua' => 2002,
            'dua ribu satu' => 2001,
            'dua ribu' => 2000,
        ];
        
        // Use exact matching first for better accuracy
        if (isset($yearPatterns[$yearText])) {
            \Log::info('Found exact year match: ' . $yearText . ' = ' . $yearPatterns[$yearText]);
            return $yearPatterns[$yearText];
        }
        
        // Then try partial matching for cases where there might be extra words
        foreach ($yearPatterns as $pattern => $year) {
            if (strpos($yearText, $pattern) !== false) {
                \Log::info('Found partial year pattern: ' . $pattern . ' = ' . $year);
                return $year;
            }
        }
        
        // Try to parse more complex patterns
        if (preg_match('/dua\s+ribu\s+(.*)/i', $yearText, $matches)) {
            $remainder = trim($matches[1]);
            $baseYear = 2000;
            
            // Parse the remainder (e.g., "dua puluh dua" = 22)
            $numberMap = [
                'satu' => 1, 'dua' => 2, 'tiga' => 3, 'empat' => 4, 'lima' => 5,
                'enam' => 6, 'tujuh' => 7, 'delapan' => 8, 'sembilan' => 9, 'sepuluh' => 10,
                'sebelas' => 11, 'dua belas' => 12, 'tiga belas' => 13, 'empat belas' => 14,
                'lima belas' => 15, 'enam belas' => 16, 'tujuh belas' => 17, 'delapan belas' => 18,
                'sembilan belas' => 19
            ];
            
            if (isset($numberMap[$remainder])) {
                return $baseYear + $numberMap[$remainder];
            }
            
            // Handle "dua puluh X" patterns
            if (preg_match('/dua\s+puluh\s*(.*)/i', $remainder, $puluhMatches)) {
                $extra = trim($puluhMatches[1]);
                $result = $baseYear + 20;
                if ($extra && isset($numberMap[$extra])) {
                    $result += $numberMap[$extra];
                }
                return $result;
            }
            
            // Handle "tiga puluh X" patterns  
            if (preg_match('/tiga\s+puluh\s*(.*)/i', $remainder, $puluhMatches)) {
                $extra = trim($puluhMatches[1]);
                $result = $baseYear + 30;
                if ($extra && isset($numberMap[$extra])) {
                    $result += $numberMap[$extra];
                }
                return $result;
            }
        }
        
        return null;
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

    // Helper: Check for duplicate nomor_urut and get available numbers (per divisi & jenis surat & month)
    private function checkDuplicate($divisiId, $jenisSuratId, $nomorUrut, $tanggalSurat = null)
    {
        $query = Surat::where('nomor_urut', $nomorUrut)
                     ->where('divisi_id', $divisiId)
                     ->where('jenis_surat_id', $jenisSuratId);
        
        // If tanggal_surat is provided, check for the same month only
        if ($tanggalSurat) {
            $monthYear = \Carbon\Carbon::parse($tanggalSurat)->format('Y-m');
            $query->whereYear('tanggal_surat', substr($monthYear, 0, 4))
                  ->whereMonth('tanggal_surat', substr($monthYear, 5, 2));
        }
        
        $isDuplicate = $query->exists();

        if ($isDuplicate) {
            \Log::warning('Duplicate nomor urut detected:', [
                'nomor_urut' => $nomorUrut,
                'divisi_id' => $divisiId,
                'jenis_surat_id' => $jenisSuratId,
                'tanggal_surat' => $tanggalSurat,
                'month_specific' => $tanggalSurat ? true : false
            ]);
        }
        
        return $isDuplicate;
    }

    // Helper: Get next available nomor urut using counter system
    public function getNextNomorUrut($divisiId, $jenisSuratId, $tanggalSurat = null)
    {
        // Extract month-year from tanggal_surat, default to current month
        $monthYear = $tanggalSurat ? 
            \Carbon\Carbon::parse($tanggalSurat)->format('Y-m') : 
            \Carbon\Carbon::now()->format('Y-m');
            
        \Log::info('Getting next nomor urut (preview only):', [
            'divisi_id' => $divisiId,
            'jenis_surat_id' => $jenisSuratId,
            'tanggal_surat' => $tanggalSurat,
            'month_year' => $monthYear
        ]);
        
        // Get jenis surat with counter
        $jenisSurat = JenisSurat::find($jenisSuratId);
        if (!$jenisSurat) {
            \Log::error('Jenis surat not found: ' . $jenisSuratId);
            return null;
        }
        
        // Peek next counter WITHOUT incrementing (for preview/lock purposes)
        $nextCounter = $jenisSurat->peekNextCounter($monthYear);
        
        \Log::info('Next counter preview from jenis surat:', [
            'jenis_surat_id' => $jenisSuratId,
            'next_counter_preview' => $nextCounter,
            'target_month' => $monthYear
        ]);
        
        // Hapus lock lama user ini di divisi yang sama setelah update real-time
        if (\Auth::check()) {
            $deletedLocks = NomorUrutLock::where('user_id', \Auth::id())
                ->where('divisi_id', $divisiId)
                ->delete();
                
            if ($deletedLocks > 0) {
                \Log::info('Cleaned up old locks after real-time update:', [
                    'user_id' => \Auth::id(),
                    'divisi_id' => $divisiId,
                    'deleted_locks' => $deletedLocks
                ]);
            }
        }
        
        return $nextCounter;
    }

    /**
     * Actually increment the counter when finalizing the letter
     */
    public function incrementNomorUrut($jenisSuratId, $tanggalSurat = null)
    {
        // Extract month-year from tanggal_surat, default to current month
        $monthYear = $tanggalSurat ? 
            \Carbon\Carbon::parse($tanggalSurat)->format('Y-m') : 
            \Carbon\Carbon::now()->format('Y-m');
            
        \Log::info('Incrementing nomor urut (final submit):', [
            'jenis_surat_id' => $jenisSuratId,
            'tanggal_surat' => $tanggalSurat,
            'month_year' => $monthYear
        ]);
        
        $jenisSurat = JenisSurat::find($jenisSuratId);
        if (!$jenisSurat) {
            \Log::error('Jenis surat not found for increment: ' . $jenisSuratId);
            return null;
        }
        
        // Actually increment the counter for the specific month
        $finalCounter = $jenisSurat->incrementCounter($monthYear);
        
        \Log::info('Final counter incremented:', [
            'jenis_surat_id' => $jenisSuratId,
            'month_year' => $monthYear,
            'final_counter' => $finalCounter
        ]);
        
        return $finalCounter;
    }

    // Helper: Lock nomor urut for a specific user
    private function lockNomorUrut($divisiId, $jenisSuratId, $nomorUrut, $userId, $tanggalSurat = null)
    {
        try {
            // Get month-year for lock specificity
            $monthYear = $tanggalSurat ? 
                \Carbon\Carbon::parse($tanggalSurat)->format('Y-m') : 
                \Carbon\Carbon::now()->format('Y-m');
                
            \Log::info('Locking nomor urut:', [
                'divisi_id' => $divisiId,
                'jenis_surat_id' => $jenisSuratId,
                'nomor_urut' => $nomorUrut,
                'user_id' => $userId,
                'month_year' => $monthYear
            ]);
            
            // Use the new static method that handles month_year
            NomorUrutLock::createOrExtendLock($divisiId, $jenisSuratId, $nomorUrut, $userId, $monthYear);
            
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
            \Log::info('Preview method called with request data:', $request->all());
            
            $request->validate([
                'file_path' => 'required',
                'nomor_urut' => 'required|integer',
                'divisi_id' => 'required|exists:divisions,id',
                'jenis_surat_id' => 'required|exists:jenis_surat,id',
                'tanggal_surat' => 'required|date',
            ]);
            
            $filePath = $request->input('file_path');
            
            \Log::info('File path from request: ' . $filePath);
            
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
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%s/%04d',
                $request->nomor_urut,
                Division::find($request->divisi_id)->kode_divisi,
                JenisSurat::find($request->jenis_surat_id)->kode_jenis,
                $this->monthToRoman(date('n', strtotime($request->tanggal_surat))),
                date('Y', strtotime($request->tanggal_surat))
            );
            
            \Log::info('Generated nomor surat for preview: ' . $nomorSurat);
            
            $fileExtension = strtolower(pathinfo($correctPath, PATHINFO_EXTENSION));
            
            \Log::info('File extension: ' . $fileExtension);
            
            // Handle both PDF files and DOCX files (if conversion failed)
            if ($fileExtension === 'pdf') {
                // Try to fill the PDF document with nomor surat
                $filledFilePath = null;
                $fillingSuccess = false;
                
                \Log::info('Attempting to fill PDF with nomor surat...');
                $filledFilePath = $this->fillPdfWithNomorSurat($correctPath, $nomorSurat);
                
                if ($filledFilePath && file_exists($filledFilePath)) {
                    \Log::info('PDF filled successfully: ' . $filledFilePath);
                    $fillingSuccess = true;
                    
                    // Return the filled PDF for inline preview
                    return response()->file($filledFilePath, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="preview_surat.pdf"',
                    ])->deleteFileAfterSend(true);
                } else {
                    \Log::warning('PDF filling failed, showing original file');
                    
                    // Fallback: return original PDF file
                    return response()->file($correctPath, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="preview_surat.pdf"',
                    ]);
                }
            } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                // Handle DOCX files that couldn't be converted at upload time
                \Log::info('Handling DOCX file for preview - attempting conversion now');
                
                // Try to convert to PDF for preview
                $convertedPdfPath = $this->convertWordToPdfWithLibreOffice($correctPath);
                
                if ($convertedPdfPath && file_exists($convertedPdfPath)) {
                    \Log::info('DOCX converted to PDF for preview: ' . $convertedPdfPath);
                    
                    // Return the converted PDF for inline preview
                    return response()->file($convertedPdfPath, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="preview_surat.pdf"',
                    ])->deleteFileAfterSend(true);
                } else {
                    \Log::error('Failed to convert DOCX to PDF for preview, forcing download');
                    
                    // Fallback: force download of DOCX file
                    $mimeType = $fileExtension === 'docx' ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : 'application/msword';
                    return response()->download($correctPath, 'preview_surat.' . $fileExtension, [
                        'Content-Type' => $mimeType,
                    ]);
                }
            } else {
                \Log::error('Unexpected file type in preview: ' . $fileExtension);
                return response('File type not supported for preview: ' . $fileExtension, 400);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in preview method: ' . $e->getMessage(), [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response('Validation error: ' . json_encode($e->errors()), 422);
        } catch (\Exception $e) {
            \Log::error('Error in preview method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response('Error generating preview: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Serve file surat dengan permission check
     */
    public function serveFile($id)
    {
        try {
            $surat = Surat::with(['division', 'jenisSurat', 'uploader', 'accesses'])->findOrFail($id);
            $user = Auth::user();
            
            // Check permission untuk akses file
            if ($surat->is_private) {
                // Jika surat private, cek apakah user bisa akses
                $hasAccess = $user->is_admin || 
                           $surat->uploaded_by === $user->id || 
                           $surat->accesses->contains('user_id', $user->id);
                           
                if (!$hasAccess) {
                    abort(403, 'Anda tidak memiliki akses untuk melihat surat ini.');
                }
            }
            
            // Get file path
            $filePath = storage_path('app/' . $surat->file_path);
            
            if (!file_exists($filePath)) {
                abort(404, 'File tidak ditemukan.');
            }
            
            // Determine MIME type
            $mimeType = $surat->mime_type ?? 'application/pdf';
            
            // Return file response
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($surat->file_path) . '"'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error serving file: ' . $e->getMessage(), [
                'surat_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Error loading file.');
        }
    }

    /**
     * Download file surat dengan permission check
     */
    public function downloadFile($id)
    {
        try {
            $surat = Surat::with(['division', 'jenisSurat', 'uploader', 'accesses'])->findOrFail($id);
            $user = Auth::user();
            
            // Check permission untuk download file (sama dengan serveFile)
            if ($surat->is_private) {
                $hasAccess = $user->is_admin || 
                           $surat->uploaded_by === $user->id || 
                           $surat->accesses->contains('user_id', $user->id);
                           
                if (!$hasAccess) {
                    abort(403, 'Anda tidak memiliki akses untuk mendownload surat ini.');
                }
            }
            
            // Get file path
            $filePath = storage_path('app/' . $surat->file_path);
            
            if (!file_exists($filePath)) {
                abort(404, 'File tidak ditemukan.');
            }
            
            // Generate descriptive filename for download
            $originalFileName = pathinfo($surat->file_path, PATHINFO_FILENAME);
            $extension = pathinfo($surat->file_path, PATHINFO_EXTENSION);
            $downloadName = "{$surat->nomor_surat} - {$surat->perihal}.{$extension}";
            
            // Clean filename (remove invalid characters)
            $downloadName = preg_replace('/[^\w\s\-\.()]/u', '_', $downloadName);
            
            // Return download response
            return response()->download($filePath, $downloadName, [
                'Content-Type' => $surat->mime_type ?? 'application/pdf'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error downloading file: ' . $e->getMessage(), [
                'surat_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Error downloading file.');
        }
    }

    // ================= MANUAL MODE METHODS =================

    /**
     * Show mode selection page
     */
    public function showModeSelection()
    {
        return view('surat.mode_selection');
    }

    /**
     * Show manual form to generate nomor surat
     */
    public function showManualForm()
    {
        $user = Auth::user();
        $jenisSurat = JenisSurat::where('divisi_id', $user->divisi_id)->active()->get();
        
        return view('surat.manual.form', [
            'jenisSurat' => $jenisSurat
        ]);
    }

    /**
     * Generate nomor surat for manual mode
     */
    public function manualGenerate(Request $request)
    {
        try {
            $request->validate([
                'jenis_surat_id' => 'required|exists:jenis_surat,id',
                'perihal' => 'required|string|max:255',
                'tanggal_surat' => 'required|date',
                'tanggal_diterima' => 'nullable|date',
                'is_private' => 'boolean'
            ]);

            $user = Auth::user();
            $jenisSurat = JenisSurat::findOrFail($request->jenis_surat_id);
            $division = $user->division;

            // Generate next nomor urut
            $nextNomorUrut = $this->getNextNomorUrut($user->divisi_id, $request->jenis_surat_id, $request->tanggal_surat);
            
            if (!$nextNomorUrut) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Gagal generate nomor urut. Silakan coba lagi.']);
                }
                return back()->withErrors(['error' => 'Gagal generate nomor urut. Silakan coba lagi.']);
            }

            // Generate nomor surat
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%s/%04d',
                $nextNomorUrut,
                $division->kode_divisi,
                $jenisSurat->kode_jenis,
                $this->monthToRoman(date('n', strtotime($request->tanggal_surat))),
                date('Y', strtotime($request->tanggal_surat))
            );

            // Lock nomor urut for this user
            $this->lockNomorUrut($user->divisi_id, $request->jenis_surat_id, $nextNomorUrut, $user->id, $request->tanggal_surat);

            // Store data in session
            $sessionData = [
                'nomor_urut' => $nextNomorUrut,
                'nomor_surat' => $nomorSurat,
                'divisi_id' => $user->divisi_id,
                'jenis_surat_id' => $request->jenis_surat_id,
                'perihal' => $request->perihal,
                'tanggal_surat' => $request->tanggal_surat,
                'tanggal_diterima' => $request->tanggal_diterima ?: date('Y-m-d'),
                'is_private' => $request->has('is_private'),
                'generated_at' => now()
            ];
            
            session(['manual_surat_data' => $sessionData]);

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $sessionData
                ]);
            }

            return redirect()->route('surat.manual.generated');

        } catch (\Exception $e) {
            \Log::error('Error in manualGenerate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
            }
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Show generated nomor surat
     */
    public function showManualGenerated()
    {
        $data = session('manual_surat_data');
        
        if (!$data) {
            return redirect()->route('surat.manual.form')
                ->withErrors(['error' => 'Session expired. Silakan generate nomor surat kembali.']);
        }

        $division = Division::find($data['divisi_id']);
        $jenisSurat = JenisSurat::find($data['jenis_surat_id']);

        return view('surat.manual.generated', [
            'nomor_surat' => $data['nomor_surat'],
            'division_name' => $division->nama_divisi,
            'division_code' => $division->kode_divisi,
            'jenis_surat_name' => $jenisSurat->nama_jenis,
            'jenis_surat_code' => $jenisSurat->kode_jenis,
            'perihal' => $data['perihal'],
            'tanggal_surat' => $data['tanggal_surat'],
            'is_private' => $data['is_private']
        ]);
    }

    /**
     * Show upload form for manual mode
     */
    public function showManualUpload()
    {
        $data = session('manual_surat_data');
        
        if (!$data) {
            return redirect()->route('surat.manual.form')
                ->withErrors(['error' => 'Session expired. Silakan generate nomor surat kembali.']);
        }

        $division = Division::find($data['divisi_id']);
        $jenisSurat = JenisSurat::find($data['jenis_surat_id']);

        return view('surat.manual.upload', [
            'expected_nomor_surat' => $data['nomor_surat'],
            'division_name' => $division->nama_divisi,
            'jenis_surat_name' => $jenisSurat->nama_jenis,
            'perihal' => $data['perihal'],
            'tanggal_surat' => $data['tanggal_surat']
        ]);
    }

    /**
     * Handle file upload for manual mode
     */
    public function manualHandleUpload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
            ]);

            // Try to get data from session first (old flow)
            $data = session('manual_surat_data');
            
            // If no session data, get from current user's lock (new flow)
            if (!$data) {
                $user = Auth::user();
                $currentLock = NomorUrutLock::where('user_id', $user->id)
                    ->where('locked_until', '>', now())
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if (!$currentLock) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Session expired atau nomor surat sudah tidak berlaku. Silakan refresh halaman.'
                        ]);
                    }
                    return redirect()->route('surat.manual.form')
                        ->withErrors(['error' => 'Session expired atau nomor surat sudah tidak berlaku. Silakan refresh halaman.']);
                }
                
                // Reconstruct data from lock
                $jenisSurat = JenisSurat::find($currentLock->jenis_surat_id);
                $tanggalSurat = $currentLock->created_at->format('Y-m-d'); // Approximate from lock creation
                
                $nomorSurat = sprintf('%03d/%s/%s/INTENS/%s/%04d',
                    $currentLock->nomor_urut,
                    $user->division->kode_divisi,
                    $jenisSurat->kode_jenis,
                    $this->monthToRoman($currentLock->created_at->month),
                    $currentLock->created_at->year
                );
                
                $data = [
                    'nomor_urut' => $currentLock->nomor_urut,
                    'nomor_surat' => $nomorSurat,
                    'divisi_id' => $currentLock->divisi_id,
                    'jenis_surat_id' => $currentLock->jenis_surat_id,
                    'perihal' => 'Manual Upload', // Default since we don't store perihal in lock
                    'tanggal_surat' => $tanggalSurat,
                    'tanggal_diterima' => now()->format('Y-m-d'),
                    'is_private' => false,
                    'generated_at' => $currentLock->created_at
                ];
            }

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            // Store file
            $timestamp = date('Y-m-d_H-i-s');
            $user = Auth::user();
            $jenisSurat = JenisSurat::find($data['jenis_surat_id']);
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $descriptiveName = sprintf(
                'surat_manual_%s_%s_%s.%s',
                $user->division->kode_divisi,
                $jenisSurat->kode_jenis,
                $timestamp,
                $fileExtension
            );

            $filePath = $file->storeAs('letters', $descriptiveName);

            // Convert DOCX to PDF if needed
            if (in_array($fileExtension, ['doc', 'docx'])) {
                $fullPath = storage_path('app/' . $filePath);
                $convertedPdfPath = $this->convertWordToPdfWithLibreOffice($fullPath);
                
                if ($convertedPdfPath && file_exists($convertedPdfPath)) {
                    $pdfDescriptiveName = str_replace('.' . $fileExtension, '.pdf', $descriptiveName);
                    $newFilePath = 'letters/' . $pdfDescriptiveName;
                    
                    Storage::put($newFilePath, file_get_contents($convertedPdfPath));
                    unlink($convertedPdfPath);
                    Storage::delete($filePath);
                    
                    $filePath = $newFilePath;
                    $fileExtension = 'pdf';
                    $mimeType = 'application/pdf';
                    $fileSize = Storage::size($filePath);
                }
            }

            // Extract text for verification
            $extractedText = '';
            $ocrError = null;
            $extractionMethod = '';

            if ($fileExtension === 'pdf') {
                try {
                    $extractionMethod = 'PDF Parser';
                    $parser = new \Smalot\PdfParser\Parser();
                    $fullPath = storage_path('app/' . $filePath);
                    $pdf = $parser->parseFile($fullPath);
                    foreach ($pdf->getPages() as $page) {
                        $extractedText .= $page->getText() . ' ';
                    }
                } catch (\Exception $e) {
                    $ocrError = 'Error ekstraksi teks (PDF Parser): ' . $e->getMessage();
                }
            }

            // Verify nomor surat in the file
            $expectedNomorSurat = $data['nomor_surat'];
            $verificationResult = $this->verifyNomorSuratInText($extractedText, $expectedNomorSurat);

            if ($verificationResult['success']) {
                // Nomor surat sesuai - simpan ke database
                $surat = Surat::create([
                    'nomor_urut' => $data['nomor_urut'],
                    'nomor_surat' => $data['nomor_surat'],
                    'divisi_id' => $data['divisi_id'],
                    'jenis_surat_id' => $data['jenis_surat_id'],
                    'perihal' => $data['perihal'],
                    'tanggal_surat' => $data['tanggal_surat'],
                    'tanggal_diterima' => $data['tanggal_diterima'],
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'is_private' => $data['is_private'],
                    'uploaded_by' => Auth::id(),
                ]);

                // Increment counter
                $this->incrementNomorUrut($data['jenis_surat_id'], $data['tanggal_surat']);

                // Cleanup locks
                NomorUrutLock::where('divisi_id', $data['divisi_id'])
                    ->where('jenis_surat_id', $data['jenis_surat_id'])
                    ->where('nomor_urut', $data['nomor_urut'])
                    ->where('user_id', Auth::id())
                    ->delete();

                // Clear session
                session()->forget('manual_surat_data');

                $division = Division::find($data['divisi_id']);
                $jenisSurat = JenisSurat::find($data['jenis_surat_id']);

                // For AJAX requests, redirect to verification page
                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'redirect' => route('surat.manual.verification', [
                            'status' => 'success',
                            'surat_id' => $surat->id
                        ])
                    ]);
                }

                return redirect()->route('surat.manual.verification', [
                    'status' => 'success',
                    'surat_id' => $surat->id
                ]);
            } else {
                // Nomor surat tidak sesuai
                Storage::delete($filePath); // Hapus file yang gagal verifikasi

                // Store failed verification data in session for re-edit
                session([
                    'failed_verification' => [
                        'data' => $data,
                        'error_message' => $verificationResult['error'],
                        'expected_nomor_surat' => $expectedNomorSurat,
                        'found_nomor_surat' => $verificationResult['found_nomor'],
                        'extracted_text' => $extractedText,
                        'ocr_error' => $ocrError,
                        'original_filename' => $originalName
                    ]
                ]);

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'redirect' => route('surat.manual.verification', ['status' => 'failed'])
                    ]);
                }

                return redirect()->route('surat.manual.verification', ['status' => 'failed']);
            }

        } catch (\Exception $e) {
            \Log::error('Error in manualHandleUpload: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ]);
            }
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Verify nomor surat in extracted text
     */
    private function verifyNomorSuratInText($extractedText, $expectedNomorSurat)
    {
        if (empty($extractedText)) {
            return [
                'success' => false,
                'error' => 'Tidak dapat mengekstrak teks dari file. Pastikan file tidak rusak atau ter-password.',
                'found_nomor' => null
            ];
        }

        // Normalize text
        $normalizedText = preg_replace('/\s+/', ' ', $extractedText);
        
        // Clean expected nomor surat (remove spaces, normalize format)
        $cleanExpectedNomor = str_replace(' ', '', $expectedNomorSurat);
        
        // Look for various patterns of nomor surat
        $patterns = [
            '/Nomor\s*:?\s*(' . preg_quote($expectedNomorSurat, '/') . ')/i',
            '/Nomor\s*:?\s*(' . preg_quote($cleanExpectedNomor, '/') . ')/i',
            '/(' . preg_quote($expectedNomorSurat, '/') . ')/i',
            '/(' . preg_quote($cleanExpectedNomor, '/') . ')/i',
            // More flexible pattern - match format but with different numbers
            '/Nomor\s*:?\s*(\d{3}\/[A-Z]+\/[A-Z]+\/INTENS\/\d{2}\/\d{4})/i',
            '/(\d{3}\/[A-Z]+\/[A-Z]+\/INTENS\/\d{2}\/\d{4})/i'
        ];

        $foundNomor = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalizedText, $matches)) {
                $foundNomor = $matches[1];
                
                // Check if it matches exactly
                if (str_replace(' ', '', $foundNomor) === $cleanExpectedNomor) {
                    return [
                        'success' => true,
                        'found_nomor' => $foundNomor
                    ];
                }
            }
        }

        return [
            'success' => false,
            'error' => 'Nomor surat dalam file tidak sesuai dengan nomor yang di-generate. Pastikan Anda telah mengisi nomor surat dengan benar.',
            'found_nomor' => $foundNomor
        ];
    }

    // ================================= VERIFICATION PAGE =================================
    
    /**
     * tampilkan halaman verification manual upload
     */
    public function manualVerification(Request $request)
    {
        $status = $request->get('status');
        
        if ($status === 'success') {
            $suratId = $request->get('surat_id');
            $surat = Surat::with(['division', 'jenisSurat'])->find($suratId);
            
            if (!$surat) {
                return redirect()->route('surat.manual.form')
                    ->withErrors(['error' => 'Data surat tidak ditemukan']);
            }
            
            return view('surat.manual.verification', [
                'verification_success' => true,
                'surat' => $surat
            ]);
            
        } elseif ($status === 'failed') {
            $failedData = session('failed_verification');
            
            if (!$failedData) {
                return redirect()->route('surat.manual.form')
                    ->withErrors(['error' => 'Data verification tidak ditemukan']);
            }
            
            return view('surat.manual.verification', [
                'verification_success' => false,
                'failed_data' => $failedData
            ]);
        }
        
        return redirect()->route('surat.manual.form');
    }
    
    /**
     * tampilkan form re-edit untuk manual upload yang gagal
     */
    public function manualReEdit()
    {
        $failedData = session('failed_verification');
        
        if (!$failedData) {
            return redirect()->route('surat.manual.form')
                ->withErrors(['error' => 'Data tidak ditemukan. Silakan upload ulang.']);
        }
        
        $user = Auth::user();
        $jenisSurat = JenisSurat::where('divisi_id', $user->divisi_id)->get();
        
        return view('surat.manual.re_edit', [
            'jenisSurat' => $jenisSurat,
            'failedData' => $failedData,
            'formData' => $failedData['data']
        ]);
    }
    
    /**
     * handle re-upload setelah edit
     */
    public function manualReUpload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
                'divisi_id' => 'required|exists:divisions,id',
                'jenis_surat_id' => 'required|exists:jenis_surat,id',
                'perihal' => 'required|string|max:255',
                'tanggal_surat' => 'required|date',
                'tanggal_diterima' => 'nullable|date',
                'is_private' => 'nullable|boolean'
            ]);

            // buat data baru dari form yang udah di-edit
            $data = [
                'divisi_id' => $request->divisi_id,
                'jenis_surat_id' => $request->jenis_surat_id,
                'perihal' => $request->perihal,
                'tanggal_surat' => $request->tanggal_surat,
                'tanggal_diterima' => $request->tanggal_diterima ?: now()->format('Y-m-d'),
                'is_private' => $request->boolean('is_private')
            ];

            // generate nomor surat baru
            $jenisSurat = JenisSurat::find($data['jenis_surat_id']);
            $division = Division::find($data['divisi_id']);
            
            $nextNomorUrut = $this->getNextNomorUrut($data['divisi_id'], $data['jenis_surat_id'], $data['tanggal_surat']);
            
            $tanggalObj = new \DateTime($data['tanggal_surat']);
            $month = $this->monthToRoman($tanggalObj->format('n'));
            $year = $tanggalObj->format('Y');
            
            $nomorSurat = sprintf('%03d/%s/%s/INTENS/%s/%s',
                $nextNomorUrut,
                $division->kode_divisi,
                $jenisSurat->kode_jenis,
                $month,
                $year
            );
            
            $data['nomor_urut'] = $nextNomorUrut;
            $data['nomor_surat'] = $nomorSurat;

            // proses file upload yang sama seperti di manualHandleUpload
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            // simpan file
            $timestamp = date('Y-m-d_H-i-s');
            $user = Auth::user();
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $descriptiveName = sprintf(
                'surat_manual_%s_%s_%s.%s',
                $division->kode_divisi,
                $jenisSurat->kode_jenis,
                $timestamp,
                $fileExtension
            );

            $filePath = $file->storeAs('letters', $descriptiveName);

            // convert DOCX ke PDF kalau perlu
            if (in_array($fileExtension, ['doc', 'docx'])) {
                $fullPath = storage_path('app/' . $filePath);
                $convertedPdfPath = $this->convertWordToPdfWithLibreOffice($fullPath);
                
                if ($convertedPdfPath && file_exists($convertedPdfPath)) {
                    $pdfDescriptiveName = str_replace('.' . $fileExtension, '.pdf', $descriptiveName);
                    $newFilePath = 'letters/' . $pdfDescriptiveName;
                    
                    Storage::put($newFilePath, file_get_contents($convertedPdfPath));
                    unlink($convertedPdfPath);
                    Storage::delete($filePath);
                    
                    $filePath = $newFilePath;
                    $fileExtension = 'pdf';
                    $mimeType = 'application/pdf';
                    $fileSize = Storage::size($filePath);
                }
            }

            // ekstrak teks untuk verifikasi
            $extractedText = '';
            $ocrError = null;

            if ($fileExtension === 'pdf') {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $fullPath = storage_path('app/' . $filePath);
                    $pdf = $parser->parseFile($fullPath);
                    foreach ($pdf->getPages() as $page) {
                        $extractedText .= $page->getText() . ' ';
                    }
                } catch (\Exception $e) {
                    $ocrError = 'Error ekstraksi teks (PDF Parser): ' . $e->getMessage();
                }
            }

            // verifikasi nomor surat di file
            $verificationResult = $this->verifyNomorSuratInText($extractedText, $nomorSurat);

            if ($verificationResult['success']) {
                // verifikasi berhasil - simpan ke database
                $surat = Surat::create([
                    'nomor_urut' => $data['nomor_urut'],
                    'nomor_surat' => $data['nomor_surat'],
                    'divisi_id' => $data['divisi_id'],
                    'jenis_surat_id' => $data['jenis_surat_id'],
                    'perihal' => $data['perihal'],
                    'tanggal_surat' => $data['tanggal_surat'],
                    'tanggal_diterima' => $data['tanggal_diterima'],
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'is_private' => $data['is_private'],
                    'uploaded_by' => Auth::id(),
                ]);

                // increment counter
                $this->incrementNomorUrut($data['jenis_surat_id'], $data['tanggal_surat']);

                // clear session data
                session()->forget('failed_verification');

                return redirect()->route('surat.manual.verification', [
                    'status' => 'success',
                    'surat_id' => $surat->id
                ]);
                
            } else {
                // masih ga sesuai - hapus file dan kembali ke re-edit
                Storage::delete($filePath);
                
                // update session dengan error baru
                $failedData = session('failed_verification');
                $failedData['error_message'] = $verificationResult['error'];
                $failedData['expected_nomor_surat'] = $nomorSurat;
                $failedData['found_nomor_surat'] = $verificationResult['found_nomor'];
                $failedData['extracted_text'] = $extractedText;
                $failedData['ocr_error'] = $ocrError;
                $failedData['original_filename'] = $originalName;
                $failedData['data'] = $data; // update data dengan yang baru
                
                session(['failed_verification' => $failedData]);
                
                return redirect()->route('surat.manual.verification', ['status' => 'failed'])
                    ->withErrors(['verification' => 'File masih belum sesuai. Silakan periksa kembali nomor surat di file.']);
            }

        } catch (\Exception $e) {
            \Log::error('Error in manualReUpload: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Show manual verification page
     */
    public function showManualVerification(Request $request)
    {
        if ($request->has('success')) {
            return view('surat.manual.verification', [
                'verification_success' => true,
                'message' => 'Surat berhasil diupload dan disimpan!'
            ]);
        }
        
        return redirect()->route('surat.manual.form');
    }
}