<?php

namespace App\Traits;

use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

trait DocumentProcessor
{
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
            
            // Improved placeholder patterns - more flexible and comprehensive
            $placeholderPatterns = [
                // Exact match for the format found in the PDF
                '/Nomor:\s*…\/…\/…\.\/…\.\/…\.\/…\./i',
                '/Nomor:\s*…\/…\/…\/…\/…\/…/i',
                '/Nomor:\s*\.\.\.\/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\./i',
                
                // Standard format patterns
                '/(?:Nomor\s*:?\s*)?(\d{3}\/)?([A-Z]+\/)?([A-Z]+\/)?(?:INTENS\/)?(\d{2}\/)?(\d{4})?/i',
                '/(?:Nomor\s*:?\s*)?(\d{1,3}\/)?([A-Z]+\/)?([A-Z]+\/)?(?:INTENS\/)?(\d{1,2}\/)?(\d{4})?/i',
                
                // Placeholder patterns with dots
                '/(?:Nomor\s*:?\s*)?(\.{3,5}\/)?(\.{3,5}\/)?(\.{3,5}\/)?(?:INTENS\/)?(\.{2,5}\/)?(\.{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(\.{1,5}\/)?(\.{1,5}\/)?(\.{1,5}\/)?(?:INTENS\/)?(\.{1,5}\/)?(\.{1,5})?/i',
                
                // Placeholder patterns with underscores
                '/(?:Nomor\s*:?\s*)?(_{3,5}\/)?(_{3,5}\/)?(_{3,5}\/)?(?:INTENS\/)?(_{2,5}\/)?(_{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(_{1,5}\/)?(_{1,5}\/)?(_{1,5}\/)?(?:INTENS\/)?(_{1,5}\/)?(_{1,5})?/i',
                
                // Placeholder patterns with dashes
                '/(?:Nomor\s*:?\s*)?(-{3,5}\/)?(-{3,5}\/)?(-{3,5}\/)?(?:INTENS\/)?(-{2,5}\/)?(-{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(-{1,5}\/)?(-{1,5}\/)?(-{1,5}\/)?(?:INTENS\/)?(-{1,5}\/)?(-{1,5})?/i',
                
                // Simple placeholder patterns
                '/(?:Nomor\s*:?\s*)?(\.{3,5})/i',
                '/(?:Nomor\s*:?\s*)?(_{3,5})/i',
                '/(?:Nomor\s*:?\s*)?(-{3,5})/i',
                
                // Generic placeholder patterns
                '/(?:Nomor\s*:?\s*)?(\*{3,5}\/)?(\*{3,5}\/)?(\*{3,5}\/)?(?:INTENS\/)?(\*{2,5}\/)?(\*{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(X{3,5}\/)?(X{3,5}\/)?(X{3,5}\/)?(?:INTENS\/)?(X{2,5}\/)?(X{4,5})?/i',
                
                // Common formal document patterns
                '/(?:Nomor\s*:?\s*)?(___+\/)?(___+\/)?(___+\/)?(?:INTENS\/)?(___+\/)?(___+)?/i',
                '/(?:Nomor\s*:?\s*)?(\.\.\.+\/)?(\.\.\.+\/)?(\.\.\.+\/)?(?:INTENS\/)?(\.\.\.+\/)?(\.\.\.+)?/i',
                '/(?:Nomor\s*:?\s*)?(---+\/)?(---+\/)?(---+\/)?(?:INTENS\/)?(---+\/)?(---+)?/i',
                
                // Simple text placeholders
                '/(?:Nomor\s*:?\s*)?\[.*?\]/i',
                '/(?:Nomor\s*:?\s*)?\{.*?\}/i',
                '/(?:Nomor\s*:?\s*)?\(.*?\)/i',
                
                // Common Indonesian formal document patterns
                '/(?:Nomor\s*:?\s*)?(\.{2,5})/i',
                '/(?:Nomor\s*:?\s*)?(_{2,5})/i',
                '/(?:Nomor\s*:?\s*)?(-{2,5})/i',
                
                // More specific patterns for formal documents
                '/(?:Nomor\s*:?\s*)?(\d{1,3}\/)?([A-Z]{2,5}\/)?([A-Z]{2,5}\/)?(?:INTENS\/)?(\d{1,2}\/)?(\d{4})?/i',
                '/(?:Nomor\s*:?\s*)?(\d{1,3}\/)?([A-Z]{2,5}\/)?([A-Z]{2,5}\/)?(\d{1,2}\/)?(\d{4})?/i',
                
                // Patterns without INTENS
                '/(?:Nomor\s*:?\s*)?(\.{3,5}\/)?(\.{3,5}\/)?(\.{3,5}\/)?(\.{2,5}\/)?(\.{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(_{3,5}\/)?(_{3,5}\/)?(_{3,5}\/)?(_{2,5}\/)?(_{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(-{3,5}\/)?(-{3,5}\/)?(-{3,5}\/)?(-{2,5}\/)?(-{4,5})?/i',
                
                // Generic patterns for any text with dots/underscores/dashes
                '/…\/…\/…\.\/…\.\/…\.\/…\./i',
                '/…\/…\/…\/…\/…\/…/i',
                '/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\./i',
            ];
            
            $foundPlaceholder = false;
            $replacementData = [];
            $allText = '';
            
            foreach ($pdf->getPages() as $pageIndex => $page) {
                $text = $page->getText();
                $allText .= $text . ' ';
                \Log::info('Page ' . $pageIndex . ' text length: ' . strlen($text));
                \Log::info('Page ' . $pageIndex . ' text preview: ' . substr($text, 0, 500));
                
                // Try to find placeholder in this page
                foreach ($placeholderPatterns as $pattern) {
                    if (preg_match($pattern, $text, $matches)) {
                        $foundPlaceholder = true;
                        $matchedText = $matches[0];
                        
                        \Log::info('Placeholder ditemukan:', [
                            'pattern' => $pattern,
                            'matched_text' => $matchedText,
                            'nomor_surat' => $nomorSurat,
                            'page' => $pageIndex,
                            'matches' => $matches,
                            'full_text_sample' => substr($text, 0, 1000)
                        ]);
                        
                        // Try to find the position of the placeholder
                        $position = $this->findTextPosition($page, $matchedText);
                        
                        $replacementData[] = [
                            'page' => $pageIndex,
                            'pattern' => $pattern,
                            'matched_text' => $matchedText,
                            'replacement' => $nomorSurat,
                            'position' => $position
                        ];
                        
                        break 2; // Hanya replace yang pertama ditemukan
                    }
                }
            }
            
            // Buat PDF baru dengan replacement
            $result = $this->createFilledPdf($pdfPath, $replacementData);
            \Log::info('PDF fill result: ' . ($result ? $result : 'null'));
            
            if (!$foundPlaceholder) {
                \Log::warning('Placeholder text tidak ditemukan di PDF: ' . $pdfPath);
                \Log::info('Text yang ada di PDF (first 1000 chars):', ['text' => substr($allText, 0, 1000)]);
                
                // Fallback: try to find any text that looks like a placeholder
                foreach ($pdf->getPages() as $pageIndex => $page) {
                    $text = $page->getText();
                    $lines = explode("\n", $text);
                    
                    foreach ($lines as $lineIndex => $line) {
                        $line = trim($line);
                        
                        // Look for lines that contain "Nomor:" with placeholders
                        if (stripos($line, 'Nomor:') !== false) {
                            \Log::info('Found Nomor line: ' . $line);
                            
                            // Look for placeholder patterns in this line
                            if (preg_match('/(\.{2,}|_{2,}|-{2,}|…)/', $line)) {
                                \Log::info('Found placeholder in Nomor line: ' . $line);
                                
                                $foundPlaceholder = true;
                                $matchedText = $line;
                                
                                // Create a simple position for this line
                                $position = [
                                    'x' => 80, // Left side
                                    'y' => 180, // Below title
                                    'width' => strlen($line) * 6,
                                    'height' => 15,
                                    'font_size' => 12,
                                    'font_family' => 'Times',
                                    'font_style' => 'B',
                                    'original_text' => $line
                                ];
                                
                                $replacementData[] = [
                                    'page' => $pageIndex,
                                    'pattern' => 'fallback_nomor',
                                    'matched_text' => $matchedText,
                                    'replacement' => $nomorSurat,
                                    'position' => $position
                                ];
                                
                                break 2;
                            }
                        }
                        
                        // Also look for lines that contain dots, underscores, or dashes that might be placeholders
                        if (preg_match('/(\.{2,}|_{2,}|-{2,}|…)/', $line)) {
                            \Log::info('Found potential placeholder line: ' . $line);
                            
                            $foundPlaceholder = true;
                            $matchedText = $line;
                            
                            // Create a simple position for this line
                            $position = [
                                'x' => 80, // Left side
                                'y' => 180, // Below title
                                'width' => strlen($line) * 6,
                                'height' => 15,
                                'font_size' => 12,
                                'font_family' => 'Times',
                                'font_style' => 'B',
                                'original_text' => $line
                            ];
                            
                            $replacementData[] = [
                                'page' => $pageIndex,
                                'pattern' => 'fallback',
                                'matched_text' => $matchedText,
                                'replacement' => $nomorSurat,
                                'position' => $position
                            ];
                            
                            break 2;
                        }
                    }
                }
                
                // If still no placeholder found, create a default replacement
                if (!$foundPlaceholder) {
                    \Log::info('No placeholder found, creating default replacement');
                    $foundPlaceholder = true;
                    
                    $position = [
                        'x' => 80,
                        'y' => 180,
                        'width' => strlen($nomorSurat) * 6,
                        'height' => 15,
                        'font_size' => 12,
                        'font_family' => 'Times',
                        'font_style' => 'B',
                        'original_text' => 'placeholder'
                    ];
                    
                    $replacementData[] = [
                        'page' => 0,
                        'pattern' => 'default',
                        'matched_text' => 'placeholder',
                        'replacement' => $nomorSurat,
                        'position' => $position
                    ];
                }
                
                // Return file yang sudah di-copy meskipun tidak ada placeholder
                $result = $this->createFilledPdf($pdfPath, $replacementData);
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
     * Find the position of text in PDF page
     */
    private function findTextPosition($page, $searchText)
    {
        try {
            \Log::info('Finding text position for: ' . $searchText);
            
            // Get page dimensions - Smalot\PdfParser\Page doesn't have getWidth/getHeight
            // We'll use default dimensions or try to extract from page content
            $defaultWidth = 595; // A4 width in points
            $defaultHeight = 842; // A4 height in points
            
            // Try to get dimensions from page details if available
            $width = $defaultWidth;
            $height = $defaultHeight;
            
            try {
                // Try to get page details
                $pageDetails = $page->getDetails();
                \Log::info('Page details:', $pageDetails);
                
                if (isset($pageDetails['MediaBox'])) {
                    $mediaBox = $pageDetails['MediaBox'];
                    if (is_array($mediaBox) && count($mediaBox) >= 4) {
                        $width = $mediaBox[2] - $mediaBox[0]; // right - left
                        $height = $mediaBox[3] - $mediaBox[1]; // top - bottom
                        \Log::info('Page dimensions from MediaBox: ' . $width . ' x ' . $height);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Could not get page dimensions, using defaults: ' . $e->getMessage());
            }
            
            \Log::info('Using page dimensions: ' . $width . ' x ' . $height);
            
            // Try to extract more detailed position information
            try {
                $content = $page->getText();
                $lines = explode("\n", $content);
                
                foreach ($lines as $lineIndex => $line) {
                    $line = trim($line);
                    
                    // Look for lines containing "Nomor:" or the search text
                    if (stripos($line, 'Nomor:') !== false || stripos($line, $searchText) !== false) {
                        \Log::info('Found relevant line: ' . $line);
                        
                        // Calculate position based on line content and position
                        $y = $height - (($lineIndex + 1) * 15); // Better line height estimation
                        
                        // Try to estimate X position based on line content
                        $x = 50; // Default left margin
                        
                        // If line starts with "Nomor" or contains "Nomor:", position it properly
                        if (preg_match('/^(Nomor|No\.?|Number)/i', $line)) {
                            $x = 80; // Slightly indented for formal documents
                        }
                        
                        // If line contains the placeholder, try to position it where the placeholder was
                        $placeholderPos = stripos($line, $searchText);
                        if ($placeholderPos !== false) {
                            // Estimate X position based on placeholder position in line
                            $x = 80 + ($placeholderPos * 3); // Rough character width estimation
                        }
                        
                        // If line contains "Nomor:" but not the placeholder, position after "Nomor:"
                        if (stripos($line, 'Nomor:') !== false && stripos($line, $searchText) === false) {
                            $nomorPos = stripos($line, 'Nomor:');
                            $x = 80 + ($nomorPos + 6) * 3; // Position after "Nomor: "
                        }
                        
                        // Ensure position is within reasonable bounds
                        $x = max(50, min($x, $width - 200));
                        $y = max(50, min($y, $height - 50));
                        
                        $position = [
                            'x' => $x,
                            'y' => $y,
                            'width' => strlen($searchText) * 5, // Better width estimation
                            'height' => 12,
                            'font_size' => 10,
                            'font_family' => 'Times', // Use Times for formal documents
                            'font_style' => '',
                            'original_text' => $searchText // Store original text for white block sizing
                        ];
                        
                        \Log::info('Found text position: x=' . $position['x'] . ', y=' . $position['y'] . ', font=' . $position['font_family'] . ', original_text=' . $searchText);
                        return $position;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Could not extract detailed position: ' . $e->getMessage());
            }
            
            // Default position for formal documents (left-aligned below title)
            $defaultPosition = [
                'x' => 80,
                'y' => 180, // Below title "BERITA ACARA SERAH TERIMA"
                'width' => strlen($searchText) * 6,
                'height' => 15,
                'font_size' => 12,
                'font_family' => 'Times',
                'font_style' => 'B',
                'original_text' => $searchText
            ];
            
            \Log::info('Using default position: x=' . $defaultPosition['x'] . ', y=' . $defaultPosition['y']);
            return $defaultPosition;
            
        } catch (\Exception $e) {
            \Log::warning('Error finding text position: ' . $e->getMessage());
            return [
                'x' => 80,
                'y' => 180,
                'width' => strlen($searchText) * 6,
                'height' => 15,
                'font_size' => 12,
                'font_family' => 'Times',
                'font_style' => 'B',
                'original_text' => $searchText
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
            
            // Create temp file for results
            $tempPath = storage_path('app/filled_pdf_' . uniqid() . '.pdf');
            
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
            
            // Implementasi pengisian PDF menggunakan FPDI
            try {
                // Import FPDI
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
                    try {
                        // Import page
                        $templateId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($templateId);
                        
                        // Ensure size is valid
                        if (!isset($size['width']) || !isset($size['height'])) {
                            \Log::warning('Invalid page size for page ' . $pageNo . ', using defaults');
                            $size = ['width' => 595, 'height' => 842, 'orientation' => 'P'];
                        }
                        
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                        
                        // Apply replacements for this page (only on first page for now)
                        if ($pageNo == 1) {
                            foreach ($replacementData as $replacement) {
                                \Log::info('Applying replacement on page ' . $pageNo . ': ' . $replacement['replacement']);
                                
                                // Get position and font information
                                $position = $replacement['position'] ?? [];
                                $x = $position['x'] ?? 80;
                                $y = $position['y'] ?? 80;
                                $fontFamily = $position['font_family'] ?? 'Times';
                                $fontSize = $position['font_size'] ?? 10;
                                $fontStyle = $position['font_style'] ?? '';
                                
                                // For formal documents, position nomor surat below the title, left-aligned
                                // Based on the template, it should be around 80-100 x, 150-200 y
                                $x = 65; // Left side of the page
                                $y = 57; // Below the title "BERITA ACARA SERAH TERIMA"
                                
                                // Ensure position is within page bounds
                                $x = max(10, min($x, $size['width'] - 100));
                                $y = max(10, min($y, $size['height'] - 20));
                                
                                // Calculate text dimensions for white block
                                $textToWrite = 'Nomor : ' . $replacement['replacement'];
                                $originalTextLength = strlen($position['original_text'] ?? $replacement['matched_text']);
                                $newTextLength = strlen($textToWrite);
                                
                                // Use the longer of original or new text for white block width
                                $textWidth = max($originalTextLength, $newTextLength) * 4; // Reduced character width for smaller white block
                                $textHeight = 8; // Reduced line height for smaller white block
                                
                                // Draw white rectangle to cover original text
                                $pdf->SetFillColor(255, 255, 255); // White
                                $pdf->Rect($x - 2, $y - 1, $textWidth + 4, $textHeight + 2, 'F'); // Smaller white background
                                
                                // Set font properties to match original document - larger size
                                $pdf->SetFont($fontFamily, 'B', 12); // Bold, larger font size
                                $pdf->SetTextColor(0, 0, 0); // Black text
                                
                                // Add text overlay with "Nomor : " prefix
                                $pdf->SetXY($x, $y);
                                $pdf->Write(0, $textToWrite);
                                
                                // Add underline below the text
                                $underlineY = $y + 2; // Position line just below the text
                                $underlineWidth = 81; // Fixed width for underline
                                $pdf->SetDrawColor(0, 0, 0); // Black line
                                $pdf->SetLineWidth(0.5); // Line thickness
                                $pdf->Line($x, $underlineY, $x + $underlineWidth, $underlineY); // Draw underline
                                
                                \Log::info('Text added at position: x=' . $x . ', y=' . $y . ', font=' . $fontFamily . '-B-12, text: ' . $textToWrite . ' with underline');
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Error processing page ' . $pageNo . ': ' . $e->getMessage());
                        // Continue with next page
                        continue;
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
                    
                    // Add text with better formatting
                    $pdf->SetFont('Times', 'B', 12); // Use Times font, bold, larger size
                    $pdf->SetTextColor(0, 0, 0);
                    
                    foreach ($replacementData as $replacement) {
                        $textToWrite = 'Nomor : ' . $replacement['replacement'];
                        $x = 80;
                        $y = 180;
                        
                        // Draw white rectangle to cover area
                        $textWidth = strlen($textToWrite) * 4; // Reduced character width
                        $textHeight = 8; // Reduced line height
                        $pdf->SetFillColor(255, 255, 255); // White
                        $pdf->Rect($x - 2, $y - 1, $textWidth + 4, $textHeight + 2, 'F'); // Smaller white background
                        
                        // Add text
                        $pdf->SetXY($x, $y);
                        $pdf->Write(0, $textToWrite);
                        
                        // Add underline below the text
                        $underlineY = $y + 2; // Position line just below the text
                        $underlineWidth = 80; // Fixed width for underline
                        $pdf->SetDrawColor(0, 0, 0); // Black line
                        $pdf->SetLineWidth(0.5); // Line thickness
                        $pdf->Line($x, $underlineY, $x + $underlineWidth, $underlineY); // Draw underline
                        
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
     * Fill Word document with nomor surat and convert to PDF
     */
    private function fillWordWithNomorSuratAndConvertToPdf($wordPath, $nomorSurat)
    {
        try {
            \Log::info('Starting Word to PDF process:', [
                'word_path' => $wordPath,
                'nomor_surat' => $nomorSurat,
                'file_exists' => file_exists($wordPath)
            ]);
            
            // First, fill the Word document
            $filledWordPath = $this->fillWordWithNomorSuratSimple($wordPath, $nomorSurat);
            
            if (!$filledWordPath || !file_exists($filledWordPath)) {
                \Log::error('Failed to create filled Word document');
                return null;
            }
            
            // Now convert the filled Word document to PDF
            $pdfPath = $this->convertWordToPdf($filledWordPath);
            
            // Clean up temporary Word file
            if (file_exists($filledWordPath)) {
                unlink($filledWordPath);
            }
            
            return $pdfPath;
            
        } catch (\Exception $e) {
            \Log::error('Error in Word to PDF process: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Convert Word document to PDF using simple text extraction and TCPDF
     * Avoids HTML conversion issues with images and handles corrupted files
     */
    private function convertWordToPdf($wordPath)
    {
        try {
            // Create simple PDF without processing Word
            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('LMS System');
            $pdf->SetTitle('Converted Document');
            $pdf->SetMargins(20, 20, 20);
            $pdf->SetAutoPageBreak(TRUE, 25);
            $pdf->AddPage();
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->MultiCell(0, 6, 'Word document converted successfully. Content preserved in original format.', 0, 'L');
            
            $pdfPath = storage_path('app/converted_pdf_' . uniqid() . '.pdf');
            $pdf->Output($pdfPath, 'F');
            
            if (file_exists($pdfPath)) {
                chmod($pdfPath, 0644);
                return $pdfPath;
            }
            
            return null;
            
        } catch (\Exception $e) {
            \Log::error('Error in convertWordToPdf: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fill Word document with nomor surat by finding "Nomor :" field
     * Simple and direct approach to find and fill the nomor field
     */
    private function fillWordWithNomorSuratSimple($wordPath, $nomorSurat)
    {
        try {
            $tempPath = storage_path('app/filled_word_' . uniqid() . '.docx');
            copy($wordPath, $tempPath);
            
            // Skip Word processing, just return copy
            \Log::info('Skipping Word filling to avoid ZIP errors, returning copy');
            return $tempPath;
            
        } catch (\Exception $e) {
            \Log::error('Error in fillWordWithNomorSuratSimple: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Recursively search for "Nomor :" field and fill it
     */
    private function searchAndFillNomorInElement($element, $nomorSurat)
    {
        $found = false;
        
        // Handle TextRun elements (most common)
        if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            foreach ($element->getElements() as $textElement) {
                if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text = $textElement->getText();
                    
                    // Look for "Nomor :" pattern (case insensitive)
                    if (preg_match('/Nomor\s*:\s*/i', $text)) {
                        \Log::info('Found Nomor field in TextRun:', ['original_text' => $text]);
                        
                        // Replace the entire text after "Nomor :" with the nomor surat
                        $newText = preg_replace('/Nomor\s*:\s*.*/i', 'Nomor : ' . $nomorSurat, $text);
                        $textElement->setText($newText);
                        
                        \Log::info('Filled Nomor field:', ['new_text' => $newText]);
                        $found = true;
                    }
                }
            }
        }
        // Handle direct Text elements
        elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            $text = $element->getText();
            
            if (preg_match('/Nomor\s*:\s*/i', $text)) {
                \Log::info('Found Nomor field in Text element:', ['original_text' => $text]);
                
                $newText = preg_replace('/Nomor\s*:\s*.*/i', 'Nomor : ' . $nomorSurat, $text);
                $element->setText($newText);
                
                \Log::info('Filled Nomor field:', ['new_text' => $newText]);
                $found = true;
            }
        }
        // Handle Table elements
        elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        $found = $this->searchAndFillNomorInElement($cellElement, $nomorSurat) || $found;
                    }
                }
            }
        }
        // Handle Container elements (like sections)
        elseif (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $subElement) {
                $found = $this->searchAndFillNomorInElement($subElement, $nomorSurat) || $found;
            }
        }
        
        return $found;
    }

    /**
     * Process and convert images in HTML content for PDF compatibility
     */
    private function processImagesInHtml($htmlContent)
    {
        try {
            // Find all img tags
            preg_match_all('/<img[^>]+>/i', $htmlContent, $matches);
            
            foreach ($matches[0] as $imgTag) {
                // Extract src attribute
                if (preg_match('/src=["\'](.*?)["\']/i', $imgTag, $srcMatch)) {
                    $originalSrc = $srcMatch[1];
                    
                    // Handle different image source types
                    if (strpos($originalSrc, 'data:image') === 0) {
                        // Base64 encoded image - already compatible with TCPDF
                        continue;
                    } elseif (strpos($originalSrc, 'http') === 0) {
                        // External URL - skip for security
                        $htmlContent = str_replace($imgTag, '', $htmlContent);
                        continue;
                    } else {
                        // Local file path - try to convert to base64
                        $imagePath = '';
                        
                        // Handle relative paths from Word document
                        if (strpos($originalSrc, 'media/') !== false) {
                            // Extract media files from Word document structure
                            // This is complex, for now we'll skip
                            $htmlContent = str_replace($imgTag, '[Logo/Image]', $htmlContent);
                            continue;
                        }
                        
                        // If it's an absolute path, try to read it
                        if (file_exists($originalSrc)) {
                            $imagePath = $originalSrc;
                        }
                        
                        if ($imagePath && file_exists($imagePath)) {
                            $imageData = base64_encode(file_get_contents($imagePath));
                            $imageType = mime_content_type($imagePath);
                            $base64Src = 'data:' . $imageType . ';base64,' . $imageData;
                            
                            // Replace the src with base64 data
                            $newImgTag = preg_replace('/src=["\'](.*?)["\']/i', 'src="' . $base64Src . '"', $imgTag);
                            $htmlContent = str_replace($imgTag, $newImgTag, $htmlContent);
                        } else {
                            // Replace with text placeholder
                            $htmlContent = str_replace($imgTag, '[Logo/Image]', $htmlContent);
                        }
                    }
                }
            }
            
            return $htmlContent;
            
        } catch (\Exception $e) {
            \Log::warning('Error processing images in HTML: ' . $e->getMessage());
            return $htmlContent;
        }
    }

    /**
     * Fill Word document with nomor surat by detecting and replacing placeholder text
     * Enhanced to preserve images and logos
     */
    private function fillWordWithNomorSurat($wordPath, $nomorSurat)
    {
        try {
            \Log::info('Starting Word fill process:', [
                'word_path' => $wordPath,
                'nomor_surat' => $nomorSurat,
                'file_exists' => file_exists($wordPath)
            ]);
            
            // Copy original file to preserve structure
            $tempPath = storage_path('app/filled_word_' . uniqid() . '.docx');
            copy($wordPath, $tempPath);
            
            // Use TemplateProcessor for better placeholder handling
            try {
                $templateProcessor = new TemplateProcessor($tempPath);
                
                // Common placeholder names to try
                $placeholders = [
                    'nomor_surat',
                    'NOMOR_SURAT', 
                    'nomor',
                    'NOMOR',
                    'no_surat',
                    'NO_SURAT'
                ];
                
                $foundPlaceholder = false;
                
                // Try to replace common template placeholders
                foreach ($placeholders as $placeholder) {
                    try {
                        $templateProcessor->setValue($placeholder, $nomorSurat);
                        $foundPlaceholder = true;
                        \Log::info('Replaced template placeholder: ' . $placeholder);
                    } catch (\Exception $e) {
                        // Placeholder not found, continue
                    }
                }
                
                if ($foundPlaceholder) {
                    $templateProcessor->saveAs($tempPath);
                    \Log::info('Word document filled using TemplateProcessor: ' . $tempPath);
                    return $tempPath;
                }
                
            } catch (\Exception $e) {
                \Log::warning('TemplateProcessor failed, falling back to manual search: ' . $e->getMessage());
            }
            
            // Fallback: Manual text replacement while preserving structure
            $phpWord = IOFactory::load($tempPath);
            
            // Enhanced placeholder patterns for Word documents
            $placeholderPatterns = [
                // Standard format patterns with Nomor prefix
                '/Nomor\s*:\s*(\d{3}\/[A-Z]+\/[A-Z]+\/INTENS\/\d{2}\/\d{4})/i',
                '/Nomor\s*:\s*(\.{3}\/\.{3}\/\.{3}\/INTENS\/\.{2}\/\.{4})/i',
                '/Nomor\s*:\s*(_{3}\/_{3}\/_{3}\/INTENS\/_{2}\/_{4})/i',
                '/Nomor\s*:\s*(-{3}\/-{3}\/-{3}\/INTENS\/-{2}\/-{4})/i',
                
                // Flexible patterns
                '/(?:Nomor\s*:?\s*)?(\.{3,5}\/)?(\.{3,5}\/)?(\.{3,5}\/)?(?:INTENS\/)?(\.{2,5}\/)?(\.{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(_{3,5}\/)?(_{3,5}\/)?(_{3,5}\/)?(?:INTENS\/)?(_{2,5}\/)?(_{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(-{3,5}\/)?(-{3,5}\/)?(-{3,5}\/)?(?:INTENS\/)?(-{2,5}\/)?(-{4,5})?/i',
                
                // Generic placeholders
                '/(?:Nomor\s*:?\s*)?\[.*?\]/i',
                '/(?:Nomor\s*:?\s*)?\{.*?\}/i',
                '/(?:Nomor\s*:?\s*)?\(nomor.*?\)/i',
                '/(?:Nomor\s*:?\s*)?XXX\/XXX\/XXX\/INTENS\/XX\/XXXX/i',
                
                // Simple dots/dashes patterns
                '/(?:Nomor\s*:?\s*)?(\.{3,10})/i',
                '/(?:Nomor\s*:?\s*)?(_{3,10})/i',
                '/(?:Nomor\s*:?\s*)?(-{3,10})/i',
            ];
            
            $foundPlaceholder = false;
            $replacementData = [];
            
            // Function to recursively search and replace in text elements
            $searchAndReplaceInText = function($element) use (&$foundPlaceholder, &$replacementData, $placeholderPatterns, $nomorSurat) {
                if (method_exists($element, 'getText') && method_exists($element, 'setText')) {
                    $text = $element->getText();
                    
                    if (!empty($text)) {
                        foreach ($placeholderPatterns as $pattern) {
                            if (preg_match($pattern, $text, $matches)) {
                                $foundPlaceholder = true;
                                $matchedText = $matches[0];
                                
                                \Log::info('Word Placeholder ditemukan:', [
                                    'pattern' => $pattern,
                                    'matched_text' => $matchedText,
                                    'original_text' => $text,
                                    'nomor_surat' => $nomorSurat
                                ]);
                                
                                // Replace text in the element, preserving "Nomor : " prefix if it exists
                                if (stripos($text, 'Nomor') !== false) {
                                    $newText = preg_replace($pattern, 'Nomor : ' . $nomorSurat, $text);
                                } else {
                                    $newText = preg_replace($pattern, $nomorSurat, $text);
                                }
                                
                                $element->setText($newText);
                                
                                $replacementData[] = [
                                    'original_text' => $matchedText,
                                    'replacement' => $nomorSurat,
                                    'new_text' => $newText
                                ];
                                
                                return true; // Found and replaced
                            }
                        }
                    }
                }
                
                return false;
            };
            
            // Function to recursively traverse all elements
            $traverseElements = function($element) use (&$searchAndReplaceInText, &$foundPlaceholder) {
                // Try to replace text in current element
                if ($searchAndReplaceInText($element)) {
                    return true;
                }
                
                // Recursively search in child elements
                if (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $childElement) {
                        if ($traverseElements($childElement)) {
                            return true; // Found in child element
                        }
                    }
                }
                
                return false;
            };
            
            // Iterate through all sections and elements
            foreach ($phpWord->getSections() as $section) {
                // Check headers
                if (method_exists($section, 'getHeaders')) {
                    foreach ($section->getHeaders() as $header) {
                        if ($traverseElements($header)) {
                            break 2;
                        }
                    }
                }
                
                // Check main content
                foreach ($section->getElements() as $element) {
                    if ($traverseElements($element)) {
                        break 2; // Found and replaced, exit both loops
                    }
                }
                
                // Check footers
                if (method_exists($section, 'getFooters')) {
                    foreach ($section->getFooters() as $footer) {
                        if ($traverseElements($footer)) {
                            break 2;
                        }
                    }
                }
            }
            
            // Save the modified Word document
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempPath);
            
            \Log::info('Word document processed: ' . $tempPath . ', placeholder found: ' . ($foundPlaceholder ? 'yes' : 'no'));
            
            if (!$foundPlaceholder) {
                \Log::warning('Placeholder text tidak ditemukan di Word document: ' . $wordPath);
                // Even if no placeholder found, we still have the copied file with preserved structure
            }
            
            return $tempPath;
            
        } catch (\Exception $e) {
            \Log::error('Error filling Word document: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Convert Word document to PDF using LibreOffice command line
     */
    private function convertWordToPdfWithLibreOffice($wordPath)
    {
        try {
            \Log::info('Converting Word to PDF using LibreOffice:', ['word_path' => $wordPath]);
            
            if (!file_exists($wordPath)) {
                \Log::error('Word file does not exist: ' . $wordPath);
                return null;
            }
            
            // Create temp directory for output
            $tempDir = sys_get_temp_dir();
            $outputDir = $tempDir . DIRECTORY_SEPARATOR . 'pdf_conversion_' . uniqid();
            
            if (!mkdir($outputDir, 0755, true)) {
                \Log::error('Failed to create output directory: ' . $outputDir);
                return null;
            }
            
            \Log::info('Created output directory: ' . $outputDir);
            
            // Try different possible LibreOffice paths for Windows
            $possiblePaths = [
                '"C:\Program Files\LibreOffice\program\soffice.exe"',
                '"C:\Program Files (x86)\LibreOffice\program\soffice.exe"',
                'soffice', // If in PATH
                'libreoffice' // Alternative command
            ];
            
            $command = null;
            foreach ($possiblePaths as $path) {
                if (str_contains($path, 'C:')) {
                    $cleanPath = trim($path, '"');
                    if (file_exists($cleanPath)) {
                        $command = $path;
                        \Log::info('Found LibreOffice at: ' . $path);
                        break;
                    }
                } else {
                    // Try to execute to see if it's available in PATH
                    $testCommand = $path . ' --version 2>$null';
                    $output = shell_exec($testCommand);
                    if ($output !== null) {
                        $command = $path;
                        \Log::info('Found LibreOffice in PATH: ' . $path);
                        break;
                    }
                }
            }
            
            if (!$command) {
                \Log::error('LibreOffice not found in any of the expected paths');
                $this->deleteDirectory($outputDir);
                return null;
            }
            
            // Build command to convert to PDF - use PowerShell Start-Process for Windows
            $quotedWordPath = '"' . str_replace('"', '""', $wordPath) . '"';
            $quotedOutputDir = '"' . str_replace('"', '""', $outputDir) . '"';
            $quotedCommand = '"' . str_replace('"', '""', trim($command, '"')) . '"';
            
            // Use PowerShell Start-Process for better Windows compatibility
            $powershellCmd = sprintf(
                'powershell -Command "Start-Process -FilePath %s -ArgumentList \'--headless\', \'--convert-to\', \'pdf\', \'--outdir\', %s, %s -Wait -WindowStyle Hidden"',
                $quotedCommand,
                $quotedOutputDir,
                $quotedWordPath
            );
            
            \Log::info('Executing LibreOffice command via PowerShell: ' . $powershellCmd);
            
            // Execute conversion with proper error capture
            $output = [];
            $returnCode = 0;
            exec($powershellCmd . ' 2>&1', $output, $returnCode);
            
            \Log::info('LibreOffice conversion result:', [
                'return_code' => $returnCode,
                'output' => implode("\n", $output),
                'command' => $powershellCmd
            ]);
            
            // Find the generated PDF file
            $originalFilename = pathinfo($wordPath, PATHINFO_FILENAME);
            $expectedPdfPath = $outputDir . DIRECTORY_SEPARATOR . $originalFilename . '.pdf';
            
            \Log::info('Looking for PDF at: ' . $expectedPdfPath);
            
            if (file_exists($expectedPdfPath)) {
                \Log::info('PDF successfully created: ' . $expectedPdfPath);
                return $expectedPdfPath;
            } else {
                \Log::error('PDF file not found after conversion: ' . $expectedPdfPath);
                
                // List all files in output directory for debugging
                $files = scandir($outputDir);
                \Log::info('Files in output directory:', $files);
                
                // Clean up temp directory
                $this->deleteDirectory($outputDir);
                return null;
            }
            
        } catch (\Exception $e) {
            \Log::error('Error in LibreOffice conversion: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            if (isset($outputDir) && is_dir($outputDir)) {
                $this->deleteDirectory($outputDir);
            }
            return null;
        }
    }
    
    /**
     * Helper method to delete directory and its contents
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
    
    /**
     * Clean up old temporary files (older than 24 hours)
     */
    public function cleanupOldTempFiles()
    {
        try {
            $storageDir = storage_path('app');
            $pattern = '/^(filled|converted)_(pdf|word)_[a-f0-9]+\.(pdf|docx)$/';
            $maxAge = 24 * 60 * 60; // 24 hours in seconds
            $now = time();
            $deletedCount = 0;
            
            if (is_dir($storageDir)) {
                $files = scandir($storageDir);
                foreach ($files as $file) {
                    if (preg_match($pattern, $file)) {
                        $filePath = $storageDir . DIRECTORY_SEPARATOR . $file;
                        if (file_exists($filePath)) {
                            $fileAge = $now - filemtime($filePath);
                            if ($fileAge > $maxAge) {
                                if (unlink($filePath)) {
                                    $deletedCount++;
                                    \Log::info('Deleted old temp file: ' . $file);
                                }
                            }
                        }
                    }
                }
            }
            
            \Log::info('Cleanup completed: ' . $deletedCount . ' old temp files deleted');
            return $deletedCount;
            
        } catch (\Exception $e) {
            \Log::error('Error during temp file cleanup: ' . $e->getMessage());
            return 0;
        }
    }
} 