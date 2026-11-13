import fs from 'node:fs/promises';
import { SpreadsheetFile, Workbook } from '@oai/artifact-tool';

const outputDir = 'C:/xampp/htdocs/TKA_APPS/public/downloads';
const previewDir = 'C:/xampp/htdocs/TKA_APPS/storage/qa/spreadsheet-templates';
await fs.mkdir(outputDir, { recursive: true });
await fs.mkdir(previewDir, { recursive: true });

function styleSheet(sheet, range, widths) {
    sheet.showGridLines = false;
    sheet.freezePanes.freezeRows(4);
    sheet.getRange(range).format.font = { name: 'Arial', size: 10, color: '#24364B' };
    sheet.getRange('A1:N1').format.font = { name: 'Arial', size: 16, bold: true, color: '#0A2548' };
    sheet.getRange('A2:N2').format.font = { name: 'Arial', size: 10, italic: true, color: '#64748B' };
    sheet.getRange('A4:N4').format = { fill: '#0B3B75', font: { name: 'Arial', size: 10, bold: true, color: '#FFFFFF' }, wrapText: true, verticalAlignment: 'center' };
    sheet.getRange('A4:N20').format.borders = { insideHorizontal: { style: 'thin', color: '#D7E2F0' }, bottom: { style: 'thin', color: '#D7E2F0' } };
    widths.forEach(([column, width]) => { sheet.getRange(`${column}:${column}`).format.columnWidth = width; });
    sheet.getRange('A4:N20').format.autofitRows();
}

async function participantTemplate() {
    const wb = Workbook.create();
    const sheet = wb.worksheets.add('Peserta');
    sheet.getRange('A1:J1').merge();
    sheet.getRange('A1').values = [['Template Import Peserta TKA']];
    sheet.getRange('A2:J2').merge();
    sheet.getRange('A2').values = [['Jangan mengubah nama kolom. NPSN atau sekolah diperlukan jika sekolah default tidak dipilih saat unggah.']];
    sheet.getRange('A4:J6').values = [
        ['nama', 'email', 'no_hp', 'nisn', 'npsn', 'sekolah', 'kelas', 'provinsi', 'kota', 'password'],
        ['Budi Santoso', 'budi@example.sch.id', '081234567890', '0012345678', '20100101', 'SMAN 1 Bekasi', 'XII', 'Jawa Barat', 'Kota Bekasi', 'TkaAman123!'],
        ['', '', '', '', '', '', '', '', '', ''],
    ];
    sheet.getRange('G5:G500').dataValidation = { rule: { type: 'list', values: ['XI', 'XII', 'Alumni'] } };
    styleSheet(sheet, 'A1:J20', [['A', 24], ['B', 28], ['C', 18], ['D', 18], ['E', 16], ['F', 28], ['G', 12], ['H', 20], ['I', 22], ['J', 18]]);
    const preview = await wb.render({ sheetName: 'Peserta', range: 'A1:J8', scale: 1.2, format: 'png' });
    await fs.writeFile(`${previewDir}/peserta.png`, new Uint8Array(await preview.arrayBuffer()));
    const check = await wb.inspect({ kind: 'table', sheetId: 'Peserta', range: 'A1:J6', include: 'values,formulas', tableMaxRows: 8, tableMaxCols: 12 });
    const file = await SpreadsheetFile.exportXlsx(wb);
    await file.save(`${outputDir}/template-import-peserta.xlsx`);
    return check.ndjson;
}

async function questionTemplate() {
    const wb = Workbook.create();
    const sheet = wb.worksheets.add('Soal');
    sheet.getRange('A1:N1').merge();
    sheet.getRange('A1').values = [['Template Import Bank Soal TKA']];
    sheet.getRange('A2:N2').merge();
    sheet.getRange('A2').values = [['Kunci dapat berisi satu huruf (C) atau beberapa huruf dipisahkan koma (A,C). Level: Mudah, Sedang, atau Sulit.']];
    sheet.getRange('A4:N6').values = [
        ['soal', 'a', 'b', 'c', 'd', 'e', 'kunci', 'materi', 'submateri', 'level', 'tipe', 'pembahasan', 'tags', 'catatan'],
        ['Nilai dari 3² + 4² adalah …', '7', '12', '25', '49', '', 'C', 'Aljabar', 'Operasi bilangan', 'Mudah', 'single_choice', '3² + 4² = 9 + 16 = 25.', 'numerasi,aljabar', 'Hapus baris contoh sebelum mengisi data final.'],
        ['Pilih bilangan prima.', '2', '4', '5', '8', '', 'A,C', 'Bilangan', 'Bilangan prima', 'Sedang', 'multiple_choice', 'Dua dan lima adalah bilangan prima.', 'numerasi', ''],
    ];
    sheet.getRange('J5:J500').dataValidation = { rule: { type: 'list', values: ['Mudah', 'Sedang', 'Sulit'] } };
    sheet.getRange('K5:K500').dataValidation = { rule: { type: 'list', values: ['single_choice', 'multiple_choice', 'matrix'] } };
    styleSheet(sheet, 'A1:N20', [['A', 42], ['B', 18], ['C', 18], ['D', 18], ['E', 18], ['F', 18], ['G', 14], ['H', 20], ['I', 22], ['J', 14], ['K', 20], ['L', 42], ['M', 24], ['N', 36]]);
    const preview = await wb.render({ sheetName: 'Soal', range: 'A1:N7', scale: 1.1, format: 'png' });
    await fs.writeFile(`${previewDir}/soal.png`, new Uint8Array(await preview.arrayBuffer()));
    const check = await wb.inspect({ kind: 'table', sheetId: 'Soal', range: 'A1:N6', include: 'values,formulas', tableMaxRows: 8, tableMaxCols: 16 });
    const errors = await wb.inspect({ kind: 'match', searchTerm: '#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!', options: { useRegex: true, maxResults: 50 }, summary: 'formula error scan' });
    const file = await SpreadsheetFile.exportXlsx(wb);
    await file.save(`${outputDir}/template-import-soal.xlsx`);
    return `${check.ndjson}\n${errors.ndjson}`;
}

console.log(await participantTemplate());
console.log(await questionTemplate());
