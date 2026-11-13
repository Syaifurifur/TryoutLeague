<?php

declare(strict_types=1);

function xml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function paragraph(string $text, string $style = 'Normal', array $options = []): string
{
    $color = $options['color'] ?? null;
    $bold = $options['bold'] ?? false;
    $size = $options['size'] ?? null;
    $before = $options['before'] ?? null;
    $after = $options['after'] ?? 80;
    $shade = $options['shade'] ?? null;
    $border = $options['border'] ?? null;
    $keep = $options['keep'] ?? false;
    $indent = $options['indent'] ?? null;
    $pPr = '<w:pStyle w:val="'.$style.'"/><w:spacing w:after="'.$after.'"'.($before !== null ? ' w:before="'.$before.'"' : '').'/>';
    $pPr .= $keep ? '<w:keepNext/>' : '';
    $pPr .= $shade ? '<w:shd w:val="clear" w:color="auto" w:fill="'.$shade.'"/>' : '';
    $pPr .= $border ? '<w:pBdr><w:left w:val="single" w:sz="20" w:space="10" w:color="'.$border.'"/></w:pBdr>' : '';
    $pPr .= $indent ? '<w:ind w:left="'.$indent.'"/>' : '';
    $rPr = ($bold ? '<w:b/>' : '').($color ? '<w:color w:val="'.$color.'"/>' : '').($size ? '<w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/>' : '');

    return '<w:p><w:pPr>'.$pPr.'</w:pPr><w:r><w:rPr>'.$rPr.'</w:rPr><w:t xml:space="preserve">'.xml($text).'</w:t></w:r></w:p>';
}

function bullet(string $text): string
{
    return paragraph('•  '.$text, 'Normal', ['indent' => 280, 'after' => 65]);
}

function table(array $rows): string
{
    $xml = '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders><w:top w:val="single" w:sz="4" w:color="D7E2F0"/><w:left w:val="single" w:sz="4" w:color="D7E2F0"/><w:bottom w:val="single" w:sz="4" w:color="D7E2F0"/><w:right w:val="single" w:sz="4" w:color="D7E2F0"/><w:insideH w:val="single" w:sz="4" w:color="D7E2F0"/><w:insideV w:val="single" w:sz="4" w:color="D7E2F0"/></w:tblBorders><w:tblCellMar><w:top w:w="100" w:type="dxa"/><w:left w:w="120" w:type="dxa"/><w:bottom w:w="100" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tblCellMar></w:tblPr>';
    foreach ($rows as $rowIndex => $row) {
        $xml .= '<w:tr>';
        foreach ($row as $cellIndex => $cell) {
            $fill = $rowIndex === 0 ? '0B3B75' : ($cellIndex === 0 ? 'EDF4FD' : 'FFFFFF');
            $color = $rowIndex === 0 ? 'FFFFFF' : '1F2937';
            $bold = $rowIndex === 0 || $cellIndex === 0;
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="'.($cellIndex === 0 ? '1850' : '6700').'" w:type="dxa"/><w:shd w:val="clear" w:fill="'.$fill.'"/></w:tcPr>'.paragraph($cell, 'Normal', ['color' => $color, 'bold' => $bold, 'after' => 0]).'</w:tc>';
        }
        $xml .= '</w:tr>';
    }

    return $xml.'</w:tbl>'.paragraph('', 'Normal', ['after' => 40]);
}

$body = '';
$body .= paragraph('TKA JUARA  /  BANK SOAL', 'Eyebrow', ['color' => '0B63CE', 'bold' => true, 'size' => 18, 'after' => 120]);
$body .= paragraph('Template Import Bank Soal DOCX', 'Title', ['color' => '0A2548', 'bold' => true, 'size' => 42, 'after' => 100]);
$body .= paragraph('Panduan ringkas untuk menyiapkan soal pilihan ganda per mata pelajaran.', 'Subtitle', ['color' => '52647A', 'size' => 22, 'after' => 260]);
$body .= paragraph('PENTING  •  Mata pelajaran dipilih di layar Import DOCX. Jangan mencampur beberapa mata pelajaran dalam satu file.', 'Callout', ['color' => '0B3B75', 'bold' => true, 'shade' => 'EAF3FF', 'border' => '0B63CE', 'indent' => 180, 'after' => 220]);
$body .= paragraph('Cara menggunakan template', 'Heading1', ['color' => '0A2548', 'bold' => true, 'size' => 28, 'before' => 100, 'after' => 100, 'keep' => true]);
$body .= bullet('Buka menu Admin → Bank Soal, lalu pilih Import DOCX.');
$body .= bullet('Pilih satu mata pelajaran tujuan.');
$body .= bullet('Ganti contoh soal pada halaman berikut, lalu duplikasi blok untuk menambah soal.');
$body .= bullet('Pisahkan setiap soal dengan satu baris berisi tiga tanda hubung: ---');
$body .= bullet('Unggah file, periksa pratinjau, lalu impor soal yang valid.');
$body .= paragraph('Field yang dikenali', 'Heading1', ['color' => '0A2548', 'bold' => true, 'size' => 28, 'before' => 160, 'after' => 100, 'keep' => true]);
$body .= table([
    ['Field', 'Aturan'],
    ['Label SOAL:', 'Wajib. Teks pertanyaan; boleh berlanjut ke paragraf berikutnya.'],
    ['A. s.d. D.', 'Wajib. Opsi E boleh ditambahkan bila diperlukan.'],
    ['KUNCI:', 'Wajib. Isi satu huruf A, B, C, D, atau E.'],
    ['MATERI:', 'Wajib. Contoh: Aljabar, Teks Argumentasi, Vocabulary.'],
    ['LEVEL:', 'Wajib. Hanya Mudah, Sedang, atau Sulit.'],
    ['PEMBAHASAN:', 'Opsional. Penjelasan jawaban benar.'],
]);
$body .= paragraph('Catatan format', 'Heading2', ['color' => '0B3B75', 'bold' => true, 'size' => 22, 'before' => 120, 'after' => 80, 'keep' => true]);
$body .= paragraph('Gunakan teks biasa untuk label. Gambar, rumus berbentuk objek, tabel kompleks, header, dan footer tidak ikut dibaca sebagai bagian soal. Simpan kembali sebagai Word Document (*.docx).', 'Normal', ['color' => '52647A', 'after' => 80]);
$body .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
$body .= paragraph('CONTOH SIAP EDIT', 'Eyebrow', ['color' => '0B63CE', 'bold' => true, 'size' => 18, 'after' => 100]);
$body .= paragraph('Ganti isi blok di bawah ini', 'Heading1', ['color' => '0A2548', 'bold' => true, 'size' => 32, 'after' => 90]);
$body .= paragraph('Duplikasi seluruh blok sampai tanda --- untuk menambahkan soal berikutnya.', 'Normal', ['color' => '52647A', 'after' => 220]);
$body .= paragraph('SOAL: Nilai dari 3² + 4² adalah …', 'Question', ['color' => '0A2548', 'bold' => true, 'shade' => 'F5F8FC', 'border' => '0B63CE', 'indent' => 200, 'after' => 110]);
$body .= paragraph('A. 7', 'QuestionLine', ['indent' => 360, 'after' => 55]);
$body .= paragraph('B. 12', 'QuestionLine', ['indent' => 360, 'after' => 55]);
$body .= paragraph('C. 25', 'QuestionLine', ['indent' => 360, 'after' => 55]);
$body .= paragraph('D. 49', 'QuestionLine', ['indent' => 360, 'after' => 110]);
$body .= paragraph('KUNCI: C', 'QuestionMeta', ['color' => '075E54', 'bold' => true, 'indent' => 200, 'after' => 55]);
$body .= paragraph('MATERI: Aljabar', 'QuestionMeta', ['color' => '334155', 'bold' => true, 'indent' => 200, 'after' => 55]);
$body .= paragraph('LEVEL: Mudah', 'QuestionMeta', ['color' => '334155', 'bold' => true, 'indent' => 200, 'after' => 55]);
$body .= paragraph('PEMBAHASAN: 3² + 4² = 9 + 16 = 25.', 'QuestionMeta', ['color' => '334155', 'indent' => 200, 'after' => 90]);
$body .= paragraph('---', 'QuestionMeta', ['color' => '0B63CE', 'bold' => true, 'indent' => 200, 'after' => 220]);
$body .= paragraph('Checklist sebelum unggah', 'Heading1', ['color' => '0A2548', 'bold' => true, 'size' => 28, 'before' => 80, 'after' => 100, 'keep' => true]);
$body .= bullet('Setiap soal memiliki minimal opsi A–D dan tepat satu KUNCI.');
$body .= bullet('Huruf KUNCI sesuai dengan salah satu opsi yang tersedia.');
$body .= bullet('MATERI dan LEVEL terisi pada setiap blok.');
$body .= bullet('Tidak ada label yang diubah ejaannya.');
$body .= paragraph('Hasil impor masuk dengan status REVIEW agar tim akademik dapat melakukan pemeriksaan akhir sebelum soal digunakan pada paket ujian.', 'Callout', ['color' => '7C4A03', 'bold' => true, 'shade' => 'FFF7E6', 'border' => 'F59E0B', 'indent' => 180, 'before' => 140, 'after' => 80]);

$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><w:body>'.$body.'<w:sectPr><w:headerReference w:type="default" r:id="rId2"/><w:footerReference w:type="default" r:id="rId3"/><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="900" w:right="950" w:bottom="900" w:left="950" w:header="420" w:footer="420"/><w:cols w:space="720"/><w:docGrid w:linePitch="360"/></w:sectPr></w:body></w:document>';

$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos" w:eastAsia="Aptos" w:cs="Aptos"/><w:sz w:val="19"/><w:szCs w:val="19"/><w:color w:val="24364B"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="80" w:line="250" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>
<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style><w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/></w:style><w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/></w:style><w:style w:type="paragraph" w:styleId="Eyebrow"><w:name w:val="Eyebrow"/></w:style><w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:qFormat/></w:style><w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:qFormat/></w:style><w:style w:type="paragraph" w:styleId="Callout"><w:name w:val="Callout"/></w:style><w:style w:type="paragraph" w:styleId="Question"><w:name w:val="Question"/></w:style><w:style w:type="paragraph" w:styleId="QuestionLine"><w:name w:val="Question Line"/></w:style><w:style w:type="paragraph" w:styleId="QuestionMeta"><w:name w:val="Question Meta"/></w:style></w:styles>';

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/><Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/><Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
$docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header1.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/></Relationships>';
$header = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="8" w:space="6" w:color="0B63CE"/></w:pBdr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos"/><w:b/><w:color w:val="0B3B75"/><w:sz w:val="16"/></w:rPr><w:t>TKA JUARA</w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos"/><w:color w:val="64748B"/><w:sz w:val="16"/></w:rPr><w:t xml:space="preserve">  •  Panduan Import Bank Soal</w:t></w:r></w:p></w:hdr>';
$footer = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos"/><w:color w:val="94A3B8"/><w:sz w:val="16"/></w:rPr><w:t>Template DOCX  •  Halaman </w:t></w:r><w:fldSimple w:instr="PAGE"><w:r><w:rPr><w:color w:val="0B63CE"/><w:b/></w:rPr><w:t>1</w:t></w:r></w:fldSimple></w:p></w:ftr>';
$core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Template Import Bank Soal TKA</dc:title><dc:subject>Format DOCX bank soal</dc:subject><dc:creator>TKA Juara</dc:creator><cp:keywords>TKA, bank soal, DOCX</cp:keywords><dc:description>Template resmi untuk mengunggah bank soal TKA Juara.</dc:description><dcterms:created xsi:type="dcterms:W3CDTF">2026-09-04T00:00:00Z</dcterms:created></cp:coreProperties>';
$app = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>TKA Juara</Application><AppVersion>1.0</AppVersion></Properties>';

$outputDir = dirname(__DIR__).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'downloads';
if (! is_dir($outputDir) && ! mkdir($outputDir, 0777, true) && ! is_dir($outputDir)) {
    throw new RuntimeException('Gagal membuat direktori output.');
}
$output = $outputDir.DIRECTORY_SEPARATOR.'template-bank-soal-tka.docx';
$zip = new ZipArchive;
if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Gagal membuat DOCX.');
}
$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rootRels);
$zip->addFromString('word/document.xml', $document);
$zip->addFromString('word/styles.xml', $styles);
$zip->addFromString('word/header1.xml', $header);
$zip->addFromString('word/footer1.xml', $footer);
$zip->addFromString('word/_rels/document.xml.rels', $docRels);
$zip->addFromString('docProps/core.xml', $core);
$zip->addFromString('docProps/app.xml', $app);
$zip->close();

echo $output.PHP_EOL;
