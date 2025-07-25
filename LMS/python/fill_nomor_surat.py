#!/usr/bin/env python3
import sys
import os
import traceback

def replace_in_word(input_path, output_pdf_path, nomor_surat):
    from docx import Document
    from docx2pdf import convert
    temp_docx = input_path.replace('.docx', '_filled.docx')
    doc = Document(input_path)
    for p in doc.paragraphs:
        if '.../.../.../.../.../...' in p.text:
            p.text = p.text.replace('.../.../.../.../.../...', nomor_surat)
    doc.save(temp_docx)
    convert(temp_docx, output_pdf_path)
    os.remove(temp_docx)

def replace_in_pdf(input_path, output_pdf_path, nomor_surat):
    import fitz  # PyMuPDF
    doc = fitz.open(input_path)
    replaced = False
    for page in doc:
        text_instances = page.search_for('.../.../.../.../.../...')
        for inst in text_instances:
            page.add_redact_annot(inst, fill=(1, 1, 1))
        if text_instances:
            page.apply_redactions()
            for inst in text_instances:
                page.insert_text(inst[:2], nomor_surat, fontsize=12, color=(0,0,0))
            replaced = True
    doc.save(output_pdf_path)
    return replaced

def main():
    if len(sys.argv) != 4:
        print('Usage: fill_nomor_surat.py <input_path> <output_pdf_path> <nomor_surat>', file=sys.stderr)
        sys.exit(1)
    input_path = sys.argv[1]
    output_path = sys.argv[2]
    nomor_surat = sys.argv[3]
    ext = os.path.splitext(input_path)[1].lower()
    try:
        if ext in ['.docx', '.doc']:
            replace_in_word(input_path, output_path, nomor_surat)
            print('OK')
        elif ext == '.pdf':
            replaced = replace_in_pdf(input_path, output_path, nomor_surat)
            if not replaced:
                print('Warning: Placeholder not found in PDF, overlay skipped.', file=sys.stderr)
            print('OK')
        else:
            print('Unsupported file type', file=sys.stderr)
            sys.exit(2)
    except Exception as e:
        print('Error:', str(e), file=sys.stderr)
        traceback.print_exc()
        sys.exit(3)

if __name__ == '__main__':
    main() 