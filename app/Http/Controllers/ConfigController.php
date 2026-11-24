<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Patient;

class ConfigController extends Controller
{
    /**
     * Muestra el panel maestro de configuración del sistema.
     */
    public function index()
    {
        $obrasSociales = \App\Models\ObraSocial::orderBy('name')->get();
        
        // Cargar Memorias de IA
        $aiMemories = [];
        $memoryDir = storage_path('app/ai_memory');
        if (file_exists($memoryDir)) {
            $files = \Illuminate\Support\Facades\File::files($memoryDir);
            foreach ($files as $file) {
                if (preg_match('/^(\d+)_memory\.txt$/', $file->getFilename(), $matches)) {
                    $userId = $matches[1];
                    $user = \App\Models\User::find($userId);
                    if ($user) {
                        $chatContent = [];
                        $chatPath = storage_path('app/ai_chats/' . $userId . '_chat.json');
                        if (file_exists($chatPath)) {
                            $chatContent = json_decode(file_get_contents($chatPath), true) ?? [];
                        }
                        
                        $aiMemories[] = [
                            'user' => $user,
                            'content' => file_get_contents($file->getPathname()),
                            'chat_history' => array_reverse($chatContent), // Reverse to show newest first or newest at bottom
                            'size' => round($file->getSize() / 1024, 2) . ' KB',
                            'last_modified' => \Carbon\Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i')
                        ];
                    }
                }
            }
        }
        
        // Cargar chats de usuarios que quizas NO tengan memoria RAG pero sí tengan chats
        $chatDir = storage_path('app/ai_chats');
        if (file_exists($chatDir)) {
            $files = \Illuminate\Support\Facades\File::files($chatDir);
            foreach ($files as $file) {
                if (preg_match('/^(\d+)_chat\.json$/', $file->getFilename(), $matches)) {
                    $userId = $matches[1];
                    // Si ya está en aiMemories, lo salteamos
                    if (!collect($aiMemories)->contains(function($m) use ($userId) { return $m['user']->id == $userId; })) {
                        $user = \App\Models\User::find($userId);
                        if ($user) {
                            $aiMemories[] = [
                                'user' => $user,
                                'content' => 'Ninguna regla o preferencia personal guardada todavía.',
                                'chat_history' => array_reverse(json_decode(file_get_contents($file->getPathname()), true) ?? []),
                                'size' => round($file->getSize() / 1024, 2) . ' KB',
                                'last_modified' => \Carbon\Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i')
                            ];
                        }
                    }
                }
            }
        }

        return view('config.index', compact('obrasSociales', 'aiMemories'));
    }

    /**
     * Limpia la memoria de un bot específico.
     */
    public function clearAiMemory(\App\Models\User $user)
    {
        $memoryFilePath = storage_path('app/ai_memory/' . $user->id . '_memory.txt');
        $chatFilePath = storage_path('app/ai_chats/' . $user->id . '_chat.json');
        
        if (file_exists($memoryFilePath)) {
            unlink($memoryFilePath);
        }
        if (file_exists($chatFilePath)) {
            unlink($chatFilePath);
        }
        
        return back()->with('success', 'Memoria y Chat IA vaciados para el usuario: ' . $user->name);
    }

    /**
     * Exporta la base consolidada de pacientes a CSV compatible con Excel (Windows y Mac) con TODOS los datos clínicos.
     */
    public function exportPatientsToExcel()
    {
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=Pacientes_Historia_Completa_' . date('Ymd_His') . '.csv',
            'Expires'             => '0',
            'Pragma'              => 'public'
        ];

        $patients = Patient::with(['visits.doctor', 'surgeries.doctor', 'studies'])->orderBy('last_name')->get();

        $callback = function() use ($patients) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 para lectura directa en Excel (Windows y Mac)
            fputs($file, "\xEF\xBB\xBF");
            
            fputcsv($file, [
                'Nro Carpeta',
                'ID',
                'DNI',
                'Apellido',
                'Nombre',
                'Fecha Nacimiento',
                'Edad',
                'Obra Social / Cobertura',
                'Plan',
                'Nro Afiliado / Cobertura',
                'Derivado / Recomendado por',
                'Telefono',
                'Email',
                'Direccion',
                'Profesion',
                'Condicion IVA',
                'Consultas Completas (Fecha, Medico, Motivo, Diagnostico, PIO, BMC, OBI, Anteojos/AV OD-OI Lejos/Cerca, Antecedentes y Medicacion)',
                'Cirugias (Fecha, Ojo OD/OI, Procedimiento/LIO y Cirujano)',
                'Estudios Cargados',
                'Comentarios Medicos Internos'
            ], ';');

            foreach ($patients as $pt) {
                $carpeta = $pt->code ?: sprintf('%06d', $pt->id);

                // Formatear todas las consultas con absolutamente todas las áreas
                $visitsText = collect($pt->visits)->sortByDesc('created_at')->map(function ($visit) {
                    $date = $visit->created_at ? $visit->created_at->format('d/m/Y H:i') : '-';
                    $doc = $visit->doctor->name ?? 'Staff';
                    $parts = ["[{$date} - Dr. {$doc}]"];
                    if ($visit->motivo_consulta) $parts[] = "Motivo: {$visit->motivo_consulta}";
                    if ($visit->diagnostico) $parts[] = "Diagnóstico: {$visit->diagnostico}";
                    if ($visit->pio) $parts[] = "PIO: {$visit->pio}";
                    if ($visit->bmc) $parts[] = "BMC: {$visit->bmc}";
                    if ($visit->obi) $parts[] = "OBI: {$visit->obi}";
                    if ($visit->otros_examen) $parts[] = "Otros: {$visit->otros_examen}";
                    if ($visit->av_od_lejos || $visit->av_oi_lejos) {
                        $parts[] = "AV/Anteojos Lejos -> OD: " . ($visit->av_od_lejos ?: '-') . " | OI: " . ($visit->av_oi_lejos ?: '-');
                    }
                    if ($visit->av_od_cerca || $visit->av_oi_cerca) {
                        $parts[] = "AV/Anteojos Cerca -> OD: " . ($visit->av_od_cerca ?: '-') . " | OI: " . ($visit->av_oi_cerca ?: '-');
                    }
                    if ($visit->antecedentes_oftalmologicos) $parts[] = "Ant. Oftalmológicos: {$visit->antecedentes_oftalmologicos}";
                    if ($visit->tratamiento_oftalmologico) $parts[] = "Medicación/Trat. Oftalm.: {$visit->tratamiento_oftalmologico}";
                    if ($visit->antecedentes_generales) $parts[] = "Ant. Generales: {$visit->antecedentes_generales}";
                    if ($visit->tratamientos_generales) $parts[] = "Trat. Generales: {$visit->tratamientos_generales}";
                    return implode(' | ', $parts);
                })->implode("\n\n");

                // Formatear Cirugías incluyendo OJO (OD / OI), fecha, procedimiento/notas y cirujano
                $surgeriesText = collect($pt->surgeries)->sortByDesc('surgery_date')->map(function ($surg) {
                    $date = $surg->surgery_date ? \Carbon\Carbon::parse($surg->surgery_date)->format('d/m/Y') : '-';
                    $ojo = $surg->eye ?: 'S/E';
                    $doc = $surg->doctor ? " (Cirujano: {$surg->doctor->name})" : '';
                    return "[{$date}] OJO: {$ojo} | QX / Detalle: " . ($surg->notes ?: '-') . $doc;
                })->implode("\n\n");

                // Formatear Estudios
                $studiesText = collect($pt->studies)->sortByDesc('created_at')->map(function ($st) {
                    $date = $st->created_at ? $st->created_at->format('d/m/Y') : '-';
                    return "[{$date}] " . ($st->study_type ?: 'Estudio') . ": " . ($st->original_name ?: '') . ($st->notes ? " ({$st->notes})" : '');
                })->implode(" | ");

                $age = $pt->date_of_birth ? \Carbon\Carbon::parse($pt->date_of_birth)->age . ' años' : 'S/D';
                $dob = $pt->date_of_birth ? \Carbon\Carbon::parse($pt->date_of_birth)->format('d/m/Y') : 'S/D';

                fputcsv($file, [
                    $carpeta,
                    $pt->id,
                    $pt->dni,
                    $pt->last_name,
                    $pt->first_name,
                    $dob,
                    $age,
                    $pt->obra_social ?: 'Particular',
                    $pt->plan ?: '-',
                    $pt->affiliate_number ?: '-',
                    $pt->recommendation ?: '-',
                    $pt->phone ?: '-',
                    $pt->email ?: '-',
                    $pt->address ?: '-',
                    $pt->profession ?: '-',
                    $pt->iva_condition ?: '-',
                    $visitsText ?: 'Sin consultas',
                    $surgeriesText ?: 'Sin cirugías',
                    $studiesText ?: 'Sin estudios',
                    $pt->medical_notes ?: ''
                ], ';');
            }
            
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Genera un Backup Clínico Completo en formato Excel Nativo Multi-Hoja (.xlsx)
     * 100% compatible con doble clic en macOS (Apple Numbers / Excel) y Windows (Excel).
     */
    public function exportFullBackupXlsx()
    {
        $patients = Patient::orderBy('last_name')->get();
        $visits = \App\Models\Visit::with(['patient', 'doctor'])->orderBy('created_at', 'desc')->get();
        $surgeries = \App\Models\PatientSurgery::with(['patient', 'doctor'])->orderBy('surgery_date', 'desc')->get();
        $appointments = \App\Models\Appointment::with(['patient', 'doctor'])->orderBy('date', 'desc')->orderBy('time', 'desc')->get();
        $assignments = \App\Models\PatientAssignment::with(['patient', 'doctor'])->orderBy('created_at', 'desc')->get();

        // Hoja 1: Pacientes y Coberturas
        $sheet1 = [[
            'Nro Carpeta', 'ID', 'DNI', 'Apellido', 'Nombre', 'Fecha Nacimiento', 'Edad',
            'Obra Social / Cobertura', 'Plan', 'Nro Cobertura / Afiliado', 'Derivado / Recomendado por',
            'Teléfono', 'Email', 'Dirección', 'Profesión', 'Condición IVA', 'Nro Siniestro',
            'Comentarios Médicos (Internos)', 'Fecha Alta Ficha'
        ]];
        foreach ($patients as $p) {
            $sheet1[] = [
                $p->code ?: sprintf('%06d', $p->id),
                (string)$p->id,
                (string)($p->dni ?? ''),
                (string)($p->last_name ?? ''),
                (string)($p->first_name ?? ''),
                $p->date_of_birth ? \Carbon\Carbon::parse($p->date_of_birth)->format('d/m/Y') : '',
                $p->date_of_birth ? (string)\Carbon\Carbon::parse($p->date_of_birth)->age : '',
                (string)($p->obra_social ?: 'Particular'),
                (string)($p->plan ?? ''),
                (string)($p->affiliate_number ?? ''),
                (string)($p->recommendation ?? ''),
                (string)($p->phone ?? ''),
                (string)($p->email ?? ''),
                (string)($p->address ?? ''),
                (string)($p->profession ?? ''),
                (string)($p->iva_condition ?? ''),
                (string)($p->nro_siniestro ?? ''),
                (string)($p->medical_notes ?? ''),
                $p->created_at ? $p->created_at->format('d/m/Y H:i') : '',
            ];
        }

        // Hoja 2: Consultas Completas (con PIO, Anteojos/AV, BMC, OBI, Antecedentes y Tratamientos)
        $sheet2 = [[
            'ID Consulta', 'Fecha y Hora', 'Nro Carpeta', 'DNI', 'Paciente', 'Obra Social', 'Nro Cobertura',
            'Médico Actuante', 'Estado', 'Motivo de Consulta', 'Diagnóstico',
            'PIO (Presión Intraocular)', 'BMC (Biomicroscopía)', 'OBI (Oftalmoscopía)', 'Otros Hallazgos / Estudios',
            'Anteojos / AV OD Lejos', 'Anteojos / AV OI Lejos', 'Anteojos / AV OD Cerca', 'Anteojos / AV OI Cerca',
            'Antecedentes Oftalmológicos', 'Medicación / Tratamiento Oftalmológico',
            'Antecedentes Generales', 'Tratamientos Generales'
        ]];
        foreach ($visits as $v) {
            $p = $v->patient;
            $sheet2[] = [
                (string)$v->id,
                $v->created_at ? $v->created_at->format('d/m/Y H:i') : '',
                $p ? ($p->code ?: sprintf('%06d', $p->id)) : '',
                (string)($p->dni ?? ''),
                $p ? ($p->last_name . ', ' . $p->first_name) : 'Paciente Eliminado',
                (string)($p->obra_social ?? 'Particular'),
                (string)($p->affiliate_number ?? ''),
                (string)($v->doctor->name ?? 'Staff'),
                $v->is_open ? 'Abierta' : 'Cerrada',
                (string)($v->motivo_consulta ?? ''),
                (string)($v->diagnostico ?? ''),
                (string)($v->pio ?? ''),
                (string)($v->bmc ?? ''),
                (string)($v->obi ?? ''),
                (string)($v->otros_examen ?? ''),
                (string)($v->av_od_lejos ?? ''),
                (string)($v->av_oi_lejos ?? ''),
                (string)($v->av_od_cerca ?? ''),
                (string)($v->av_oi_cerca ?? ''),
                (string)($v->antecedentes_oftalmologicos ?? ''),
                (string)($v->tratamiento_oftalmologico ?? ''),
                (string)($v->antecedentes_generales ?? ''),
                (string)($v->tratamientos_generales ?? ''),
            ];
        }

        // Hoja 3: Cirugías (con Ojo OD / OI explícito)
        $sheet3 = [[
            'ID Cirugía', 'Fecha Cirugía', 'Ojo Operado (OD / OI)', 'Procedimiento / LIO / Notas Quirúrgicas',
            'Cirujano', 'Nro Carpeta', 'DNI', 'Paciente', 'Obra Social', 'Nro Cobertura'
        ]];
        foreach ($surgeries as $s) {
            $p = $s->patient;
            $sheet3[] = [
                (string)$s->id,
                $s->surgery_date ? \Carbon\Carbon::parse($s->surgery_date)->format('d/m/Y') : '',
                (string)($s->eye ?: 'S/E'),
                (string)($s->notes ?? ''),
                (string)($s->doctor->name ?? 'Staff'),
                $p ? ($p->code ?: sprintf('%06d', $p->id)) : '',
                (string)($p->dni ?? ''),
                $p ? ($p->last_name . ', ' . $p->first_name) : 'Paciente Eliminado',
                (string)($p->obra_social ?? 'Particular'),
                (string)($p->affiliate_number ?? ''),
            ];
        }

        // Hoja 4: Turnos y Agenda
        $sheet4 = [[
            'ID Turno', 'Fecha', 'Hora', 'Paciente', 'DNI', 'Obra Social', 'Nro Cobertura',
            'Médico', 'Motivo', 'Notas Secretaría', 'Estado'
        ]];
        foreach ($appointments as $a) {
            $p = $a->patient;
            $sheet4[] = [
                (string)$a->id,
                $a->date ? \Carbon\Carbon::parse($a->date)->format('d/m/Y') : '',
                $a->time ? substr($a->time, 0, 5) : '',
                $p ? ($p->last_name . ', ' . $p->first_name) : 'Paciente Eliminado',
                (string)($p->dni ?? ''),
                (string)($p->obra_social ?? 'Particular'),
                (string)($p->affiliate_number ?? ''),
                (string)($a->doctor->name ?? ''),
                (string)($a->reason ?? ''),
                (string)($a->notes ?? ''),
                (string)($a->status ?? ''),
            ];
        }

        // Hoja 5: Tiempos de Sala de Espera y Atención
        $sheet5 = [[
            'Fecha', 'Paciente', 'DNI', 'Etapa / Actividad', 'Hora Inicio', 'Hora Fin',
            'Minutos Transcurridos', 'Médico', 'Observaciones'
        ]];
        foreach ($assignments as $as) {
            $p = $as->patient;
            $mins = ($as->started_at && $as->ended_at) ? max(0, $as->started_at->diffInMinutes($as->ended_at)) : '';
            $sheet5[] = [
                $as->created_at ? $as->created_at->format('d/m/Y') : '',
                $p ? ($p->last_name . ', ' . $p->first_name) : 'Paciente Eliminado',
                (string)($p->dni ?? ''),
                (string)($as->event_type ?? ''),
                $as->started_at ? $as->started_at->format('H:i') : '',
                $as->ended_at ? $as->ended_at->format('H:i') : '',
                (string)$mins,
                (string)($as->doctor->name ?? ''),
                (string)($as->notes ?? ''),
            ];
        }

        $sheets = [
            'Pacientes_Fichas'      => $sheet1,
            'Consultas_Completas'   => $sheet2,
            'Cirugias_OD_OI'        => $sheet3,
            'Turnos_Agenda'         => $sheet4,
            'Tiempos_Sala_Espera'   => $sheet5,
        ];

        $tmpFile = tempnam(sys_get_temp_dir(), 'elevenmed_xlsx_');
        $zip = new \ZipArchive();
        if ($zip->open($tmpFile, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) !== true) {
            return $this->exportPatientsToExcel();
        }

        // 1. [Content_Types].xml
        $sheetOverrides = '';
        $idx = 1;
        foreach ($sheets as $name => $_) {
            $sheetOverrides .= '<Override PartName="/xl/worksheets/sheet' . $idx . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $idx++;
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats.package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $sheetOverrides
            . '</Types>');

        // 2. _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        // 3. xl/workbook.xml & xl/_rels/workbook.xml.rels
        $workbookSheets = '';
        $workbookRels = '';
        $idx = 1;
        foreach ($sheets as $sheetTitle => $_) {
            $safeTitle = htmlspecialchars(mb_substr($sheetTitle, 0, 31), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $workbookSheets .= '<sheet name="' . $safeTitle . '" sheetId="' . $idx . '" r:id="rId' . $idx . '"/>';
            $workbookRels .= '<Relationship Id="rId' . $idx . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $idx . '.xml"/>';
            $idx++;
        }
        $workbookRels .= '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $workbookSheets . '</sheets>'
            . '</workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $workbookRels
            . '</Relationships>');

        // 4. xl/styles.xml (Header bold + clean font for Excel & Apple Numbers)
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E3C72"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '</cellXfs>'
            . '</styleSheet>');

        // 5. Worksheets
        $colName = function (int $colIdx): string {
            $str = '';
            while ($colIdx >= 0) {
                $str = chr(65 + ($colIdx % 26)) . $str;
                $colIdx = intdiv($colIdx, 26) - 1;
            }
            return $str;
        };

        $idx = 1;
        foreach ($sheets as $_ => $rows) {
            $xmlRows = '';
            foreach ($rows as $rIdx => $row) {
                $rowNum = $rIdx + 1;
                $styleAttr = ($rIdx === 0) ? ' s="1"' : '';
                $xmlCells = '';
                foreach ($row as $cIdx => $cellVal) {
                    $ref = $colName($cIdx) . $rowNum;
                    // Strip invalid XML control chars
                    $cleanVal = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$cellVal);
                    $escaped = htmlspecialchars($cleanVal, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $xmlCells .= '<c r="' . $ref . '" t="inlineStr"' . $styleAttr . '><is><t xml:space="preserve">' . $escaped . '</t></is></c>';
                }
                $xmlRows .= '<row r="' . $rowNum . '">' . $xmlCells . '</row>';
            }
            $zip->addFromString('xl/worksheets/sheet' . $idx . '.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<sheetData>' . $xmlRows . '</sheetData>'
                . '</worksheet>');
            $idx++;
        }

        $zip->close();

        $filename = 'Backup_Completo_ElevenMed_' . date('Y-m-d_His') . '.xlsx';
        return response()->download($tmpFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Genera y descarga una copia de seguridad íntegra de la Base de Datos (SQL nativo).
     */
    public function backupDatabase()
    {
        $databaseName = config('database.connections.mysql.database');
        $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
        $keyName = 'Tables_in_' . $databaseName;

        $response = new StreamedResponse(function () use ($tables, $keyName, $databaseName) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "-- ELEVEN MED / SISTEMA CORTALEZZI\n");
            fwrite($handle, "-- Resguardo Completo de Base de Datos\n");
            fwrite($handle, "-- Base de datos: {$databaseName}\n");
            fwrite($handle, "-- Fecha y Hora: " . date('d/m/Y H:i:s') . "\n");
            fwrite($handle, "-- --------------------------------------------------------\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

            foreach ($tables as $tableObj) {
                $tableName = $tableObj->$keyName ?? array_values((array)$tableObj)[0];

                // Create table schema
                $createRes = \Illuminate\Support\Facades\DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createSql = $createRes[0]->{'Create Table'} ?? null;

                if ($createSql) {
                    fwrite($handle, "-- --------------------------------------------------------\n");
                    fwrite($handle, "-- Estructura de tabla para `{$tableName}`\n");
                    fwrite($handle, "-- --------------------------------------------------------\n");
                    fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                    fwrite($handle, $createSql . ";\n\n");
                }

                // Dump records
                $count = \Illuminate\Support\Facades\DB::table($tableName)->count();
                if ($count > 0) {
                    fwrite($handle, "-- Volcado de datos para `{$tableName}` ({$count} registros)\n");
                    
                    \Illuminate\Support\Facades\DB::table($tableName)->chunk(200, function($rows) use ($handle, $tableName) {
                        foreach ($rows as $row) {
                            $rowArray = (array)$row;
                            $columns = array_map(fn($col) => "`{$col}`", array_keys($rowArray));
                            $values = array_map(function($val) {
                                if (is_null($val)) return 'NULL';
                                return "'" . addslashes((string)$val) . "'";
                            }, array_values($rowArray));

                            $insertSql = "INSERT INTO `{$tableName}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                            fwrite($handle, $insertSql);
                        }
                    });
                    fwrite($handle, "\n");
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);
        });

        $filename = 'Backup_ElevenMed_' . date('Y-m-d_His') . '.sql';
        $response->headers->set('Content-Type', 'application/sql');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    /**
     * Guarda una nueva obra social
     */
    public function storeObraSocial(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:obra_socials,name',
            'color' => 'nullable|string|max:7'
        ]);
        \App\Models\ObraSocial::create([
            'name' => $request->name,
            'color' => $request->color ?? '#5e6ad2'
        ]);
        // Limpiar cache potencial
        \Illuminate\Support\Facades\Cache::forget('os_color_' . md5($request->name));
        return back()->with('success', 'Obra Social / Cobertura creada exitosamente.');
    }

    /**
     * Elimina una obra social
     */
    public function destroyObraSocial(\App\Models\ObraSocial $obraSocial)
    {
        $obraSocial->delete();
        return back()->with('success', 'Obra Social eliminada exitosamente.');
    }

    /**
     * Carga o actualiza los pacientes históricos de muestra del Viejo Eleven con todo su historial clínico.
     */
    public function importLegacySample()
    {
        $res = \App\Services\LegacyElevenImporter::provisionClinicUsersAndSample();
        $cnt = count($res['sample_patients'] ?? []);
        return back()->with('success', "Se integraron exitosamente {$cnt} pacientes históricos del Viejo Eleven con todas sus consultas y cirugías previas.");
    }
}
