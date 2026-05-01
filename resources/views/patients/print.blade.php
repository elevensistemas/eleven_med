<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historia Clínica - {{ $patient->first_name }} {{ $patient->last_name }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11pt; color: #000; padding: 20px; line-height: 1.5; background: #eee; }
        .document-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .logo { font-size: 26pt; font-weight: bold; letter-spacing: 2px; margin-bottom: 2px; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 10px;}
        .logo svg { width: 32px; height: 32px; }
        .logo-subtitle { font-size: 10pt; letter-spacing: 5px; text-transform: uppercase; color: #555;}
        .patient-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .patient-details { width: 45%; }
        .patient-notes { width: 50%; padding-left: 20px; font-size: 10.5pt; }
        .visit-item { border-top: 1px solid #000; padding-top: 15px; margin-bottom: 25px; page-break-inside: avoid; }
        .visit-header { display: flex; justify-content: space-between; font-weight: bold; margin-bottom: 10px; font-size: 11pt; }
        .visit-content { display: flex; flex-direction: column; gap: 6px; margin-left: 5px; }
        .row-data { display: flex; }
        .label { width: 110px; font-weight: bold; }
        .value { flex: 1; white-space: pre-line; }
        .refraccion-box { align-self: flex-end; width: 280px; margin-top: 8px; margin-right: 20px; }
        .refraccion-row { display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 2px; }
        .refraccion-label { width: 80px; text-align: right; font-weight: bold; }
        .refraccion-value { width: 120px; text-align: left; }
        @media print {
            body { padding: 0; background: #fff; }
            .document-container { padding: 0; box-shadow: none; max-width: 100%; margin: 0; }
            .print-btn { display: none; }
            @page { margin: 1.5cm; }
        }
        .print-btn { position: fixed; top: 20px; right: 20px; padding: 12px 24px; font-size: 12pt; cursor: pointer; background: #222; color: #fff; border: none; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); font-weight: bold;}
        .print-btn:hover { background: #000; transform: scale(1.05); }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Imprimir Documento</button>

    <div class="document-container">
        <div class="header">
            <div class="logo">
                <svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                  <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                  <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                </svg>
                CORTALEZZI
            </div>
            <div class="logo-subtitle">Visión</div>
        </div>

        <div class="patient-info">
            <div class="patient-details">
                <h3 style="margin:0 0 5px 0; text-transform: capitalize;">Paciente: {{ strtolower($patient->first_name . ' ' . $patient->last_name) }}</h3>
                <p style="margin:0;"><strong>D.N.I.:</strong> {{ $patient->dni }}</p>
                @if($patient->date_of_birth)<p style="margin:0;"><strong>Edad:</strong> {{ \Carbon\Carbon::parse($patient->date_of_birth)->age }} años</p>@endif
                @if($patient->obra_social)<p style="margin:0;"><strong>C. Médica:</strong> {{ $patient->obra_social }} {{ $patient->plan ? ' - '.$patient->plan : '' }}</p>@endif
            </div>
            <div class="patient-notes">
                @if($patient->medical_notes)
                    <strong>Antecedentes:</strong><br>
                    <div style="white-space: pre-line;">{{ $patient->medical_notes }}</div>
                @endif
            </div>
        </div>

        @if($patient->surgeries && $patient->surgeries->count() > 0)
            <div style="margin-bottom: 20px; border: 1px solid #000; padding: 10px;">
                <h4 style="margin: 0 0 10px 0; text-transform: uppercase;">Historial Quirúrgico</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($patient->surgeries->sortByDesc('surgery_date') as $surg)
                        <li style="margin-bottom: 5px;">
                            <strong>{{ $surg->surgery_date->format('d/m/Y') }} - Ojo {{ $surg->eye }}:</strong> {{ $surg->notes }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="visits-container">
            @forelse($visits as $visit)
                <div class="visit-item">
                    <div class="visit-header">
                        <div>Visita: {{ $visit->created_at->format('d-M-Y') }}</div>
                        <div>Médico: {{ $visit->doctor->name ?? 'Staff' }}</div>
                    </div>
                    
                    <div class="visit-content">
                        @if($visit->motivo_consulta)
                            <div class="row-data">
                                <div class="label">Motivo:</div>
                                <div class="value">{{ $visit->motivo_consulta }}</div>
                            </div>
                        @endif

                        @if($visit->diagnostico)
                            <div class="row-data">
                                <div class="label">Diagnóstico:</div>
                                <div class="value">{{ $visit->diagnostico }}</div>
                            </div>
                        @endif

                        @if($visit->tratamiento_oftalmologico || $visit->av_od_lejos || $visit->av_oi_lejos || $visit->av_od_cerca || $visit->av_oi_cerca)
                            <div class="row-data">
                                <div class="label">Tratamiento:</div>
                                <div class="value">
                                    @if($visit->tratamiento_oftalmologico)
                                        <div>{{ $visit->tratamiento_oftalmologico }}</div>
                                    @endif
                                </div>
                            </div>
                            
                            @if($visit->av_od_lejos || $visit->av_oi_lejos || $visit->av_od_cerca || $visit->av_oi_cerca)
                                <div class="refraccion-box">
                                    @if($visit->av_od_lejos)<div class="refraccion-row"><div class="refraccion-label">OD Lejos:</div><div class="refraccion-value">{{ $visit->av_od_lejos }}</div></div>@endif
                                    @if($visit->av_oi_lejos)<div class="refraccion-row"><div class="refraccion-label">OI Lejos:</div><div class="refraccion-value">{{ $visit->av_oi_lejos }}</div></div>@endif
                                    @if($visit->av_od_cerca)<div class="refraccion-row"><div class="refraccion-label">OD Cerca:</div><div class="refraccion-value">{{ $visit->av_od_cerca }}</div></div>@endif
                                    @if($visit->av_oi_cerca)<div class="refraccion-row"><div class="refraccion-label">OI Cerca:</div><div class="refraccion-value">{{ $visit->av_oi_cerca }}</div></div>@endif
                                </div>
                            @endif
                        @endif

                        @if($visit->pio || $visit->bmc || $visit->obi || $visit->otros_examen || $visit->antecedentes_oftalmologicos)
                            <div style="margin-top: 10px; border-top: 1px dotted #ccc; padding-top: 5px;">
                                <div class="row-data">
                                    <div class="label">Examen:</div>
                                    <div class="value">
                                        @if($visit->antecedentes_oftalmologicos)<div style="margin-bottom:2px;"><strong>Ant. Oftalm.:</strong> {{ $visit->antecedentes_oftalmologicos }}</div>@endif
                                        @if($visit->pio)<div style="margin-bottom:2px;"><strong>PIO:</strong> {{ $visit->pio }}</div>@endif
                                        @if($visit->bmc)<div style="margin-bottom:2px;"><strong>BMC:</strong> {{ $visit->bmc }}</div>@endif
                                        @if($visit->obi)<div style="margin-bottom:2px;"><strong>OBI:</strong> {{ $visit->obi }}</div>@endif
                                        @if($visit->otros_examen)<div style="margin-bottom:2px;"><strong>Otros:</strong> {{ $visit->otros_examen }}</div>@endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p style="text-align: center; font-style: italic; color: #666; margin-top: 40px;">No hay registro de visitas clínicas previas para este paciente.</p>
            @endforelse
        </div>
    </div>
    
    <script>
        window.addEventListener('load', function() {
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
