<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();

Route::get('/run-migrations', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== 'Trinitotolueno2015') {
        abort(403, 'Unauthorized action.');
    }
    
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return response()->json([
            'message' => 'Migrations executed successfully',
            'output' => \Illuminate\Support\Facades\Artisan::output()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
})->withoutMiddleware('web');

Route::get('/run-seeders', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== 'Trinitotolueno2015') {
        abort(403, 'Unauthorized action.');
    }
    
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        return response()->json([
            'message' => 'Seeders executed successfully',
            'output' => \Illuminate\Support\Facades\Artisan::output()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
})->withoutMiddleware('web');

Route::get('/run-cortalezzi-setup', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== 'Trinitotolueno2015') {
        abort(403, 'Unauthorized action.');
    }
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrateOut = \Illuminate\Support\Facades\Artisan::output();
        $setupOut = \App\Services\LegacyElevenImporter::provisionClinicUsersAndSample();
        return response()->json([
            'success' => true,
            'migrations' => $migrateOut,
            'setup' => $setupOut,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getFile() . ':' . $e->getLine(),
        ], 500);
    }
})->withoutMiddleware('web');

// Outbound Cloud Bridge for Clinic PC WhatsApp Connector (.BAT -> Hostinger without port forwarding)
Route::post('/api/whatsapp-bridge/sync', [App\Http\Controllers\WhatsappConfigController::class, 'bridgeSync'])->withoutMiddleware('web');

// Fallback direct access to patient studies to prevent Apache 403 Forbidden on shared hostings
Route::get('/storage/patients/{patient}/studies/{filename}', function ($patient, $filename) {
    $study = \App\Models\PatientStudy::where('patient_id', $patient)
        ->where('file_path', 'like', "%$filename")
        ->first();
    if ($study) {
        return app(\App\Http\Controllers\PatientStudyController::class)->show($study);
    }
    abort(404, 'Estudio médico no encontrado.');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/api/dashboard/chart-data', [App\Http\Controllers\HomeController::class, 'chartData'])->name('api.dashboard.chart_data');
    Route::post('/mi-perfil/cambiar-password', [App\Http\Controllers\UserController::class, 'changeOwnPassword'])->name('profile.changePassword');
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::post('users/{user}/reset-password', [App\Http\Controllers\UserController::class, 'resetPassword'])->name('users.resetPassword');
    Route::get('/configuracion', [App\Http\Controllers\ConfigController::class, 'index'])->name('config.index');
    Route::post('/configuracion/import-legacy-sample', [App\Http\Controllers\ConfigController::class, 'importLegacySample'])->name('config.import.legacySample');
    Route::post('/configuracion/obras-sociales', [App\Http\Controllers\ConfigController::class, 'storeObraSocial'])->name('config.obras_sociales.store');
    Route::delete('/configuracion/obras-sociales/{obraSocial}', [App\Http\Controllers\ConfigController::class, 'destroyObraSocial'])->name('config.obras_sociales.destroy');
    Route::delete('/configuracion/ai-memory/{user}', [App\Http\Controllers\ConfigController::class, 'clearAiMemory'])->name('config.ai_memory.clear');
    Route::get('/configuracion/export-patients', [App\Http\Controllers\ConfigController::class, 'exportPatientsToExcel'])->name('config.export.patients');
    Route::get('/configuracion/backup-xlsx', [App\Http\Controllers\ConfigController::class, 'exportFullBackupXlsx'])->name('config.backup.xlsx');
    Route::get('/configuracion/backup-database', [App\Http\Controllers\ConfigController::class, 'backupDatabase'])->name('config.backup.database');
    
    // WhatsApp Configuration & Web Gateway
    Route::get('/configuracion/whatsapp', [App\Http\Controllers\WhatsappConfigController::class, 'index'])->name('whatsapp.index');
    Route::post('/configuracion/whatsapp', [App\Http\Controllers\WhatsappConfigController::class, 'store'])->name('whatsapp.store');
    Route::get('/configuracion/whatsapp/status', [App\Http\Controllers\WhatsappConfigController::class, 'status'])->name('whatsapp.status');
    Route::get('/configuracion/whatsapp/qr', [App\Http\Controllers\WhatsappConfigController::class, 'qr'])->name('whatsapp.qr');
    Route::post('/configuracion/whatsapp/disconnect', [App\Http\Controllers\WhatsappConfigController::class, 'disconnect'])->name('whatsapp.disconnect');
    Route::post('/configuracion/whatsapp/test-send', [App\Http\Controllers\WhatsappConfigController::class, 'testSend'])->name('whatsapp.testSend');
    Route::post('/configuracion/whatsapp/send-batch', [App\Http\Controllers\WhatsappConfigController::class, 'sendManualBatch'])->name('whatsapp.sendBatch');
    Route::get('/configuracion/whatsapp/history', [App\Http\Controllers\WhatsappConfigController::class, 'history'])->name('whatsapp.history');
    Route::delete('/configuracion/whatsapp/history', [App\Http\Controllers\WhatsappConfigController::class, 'clearHistory'])->name('whatsapp.clearHistory');
    Route::get('/configuracion/whatsapp/download-connector', [App\Http\Controllers\WhatsappConfigController::class, 'downloadConnector'])->name('whatsapp.downloadConnector');
    
    Route::resource('patients', App\Http\Controllers\PatientController::class);
    Route::post('patients/{patient}/quick-update', [App\Http\Controllers\PatientController::class, 'quickUpdate'])->name('patients.quickUpdate');
    // Medical Studies specific routes
    Route::post('patients/{patient}/studies', [App\Http\Controllers\PatientStudyController::class, 'store'])->name('patient.studies.store');
    Route::get('studies/{patientStudy}/view', [App\Http\Controllers\PatientStudyController::class, 'show'])->name('studies.show');
    Route::get('studies/{patientStudy}/download', [App\Http\Controllers\PatientStudyController::class, 'download'])->name('studies.download');
    Route::put('studies/{patientStudy}', [App\Http\Controllers\PatientStudyController::class, 'update'])->name('studies.update');
    Route::delete('studies/{patientStudy}', [App\Http\Controllers\PatientStudyController::class, 'destroy'])->name('studies.destroy');

    // Comments specific route
    Route::post('patients/{patient}/comments', [App\Http\Controllers\PatientController::class, 'storeComment'])->name('patient.comments.store');

    // Surgeries specific routes
    Route::post('patients/{patient}/surgeries', [App\Http\Controllers\PatientSurgeryController::class, 'store'])->name('patient.surgeries.store');
    Route::delete('surgeries/{patientSurgery}', [App\Http\Controllers\PatientSurgeryController::class, 'destroy'])->name('surgeries.destroy');

    // Phase 20: Global Omnibar Search
    Route::get('/global-search', [App\Http\Controllers\PatientController::class, 'globalSearch'])->name('global.search');

    // Phase 21: Live Patient Search API
    Route::get('/api/patients/search', [App\Http\Controllers\PatientController::class, 'apiSearch']);

    // Clinical Visits routes
    Route::post('patients/{patient}/photo', [App\Http\Controllers\PatientController::class, 'uploadPhoto'])->name('patients.uploadPhoto');
    Route::post('patients/{patient}/photo-base64', [App\Http\Controllers\PatientController::class, 'uploadBase64Photo'])->name('patients.uploadBase64Photo');
    Route::delete('patients/{patient}/photo', [App\Http\Controllers\PatientController::class, 'removePhoto'])->name('patients.removePhoto');
    Route::post('patients/{patient}/analyze', [App\Http\Controllers\PatientController::class, 'analyzeHistory'])->name('patients.analyze');
    Route::get('patients/{patient}/visits/create', [App\Http\Controllers\VisitController::class, 'create'])->name('patient.visits.create');
    Route::post('patients/{patient}/visits', [App\Http\Controllers\VisitController::class, 'store'])->name('patient.visits.store');
    Route::get('visits/{visit}/edit', [App\Http\Controllers\VisitController::class, 'edit'])->name('visits.edit');
    Route::put('visits/{visit}', [App\Http\Controllers\VisitController::class, 'update'])->name('visits.update');
    Route::post('visits/{visit}/toggle-status', [App\Http\Controllers\VisitController::class, 'toggleStatus'])->name('visits.toggleStatus');
    Route::get('patients/{patient}/history/print', [App\Http\Controllers\PatientController::class, 'printHistory'])->name('patients.history.print');
    
    // Patient Flow Console (Phase 15 Pivot)
    Route::get('console', [App\Http\Controllers\ConsoleController::class, 'index'])->name('console.index');
    Route::post('console/assignments', [App\Http\Controllers\ConsoleController::class, 'store'])->name('console.assignments.store');
    Route::post('console/assignments/{patient}/append', [App\Http\Controllers\ConsoleController::class, 'append'])->name('console.assignments.append');
    Route::post('console/transition/{patient}', [App\Http\Controllers\ConsoleController::class, 'transition'])->name('console.assignments.transition');
    Route::post('console/activity/{patient}', [App\Http\Controllers\ConsoleController::class, 'toggleActivity'])->name('console.assignments.toggleActivity');
    Route::post('console/assignments/{patient}/notes', [App\Http\Controllers\ConsoleController::class, 'updateNotes'])->name('console.assignments.notes');
    Route::post('console/finish-all', [App\Http\Controllers\ConsoleController::class, 'finishAll'])->name('console.finishAll');

    // Medical Agenda & Interactive Calendar
    Route::get('agenda', [App\Http\Controllers\AppointmentController::class, 'index'])->name('agenda.index');
    // Phase 16 & 17 Split View APIs
    Route::get('api/appointments/availability', [App\Http\Controllers\AppointmentController::class, 'getMonthAvailability'])->name('api.agenda.availability');
    Route::get('api/appointments/slots', [App\Http\Controllers\AppointmentController::class, 'getDaySlots'])->name('api.agenda.slots');
    Route::get('api/appointments/nearest', [App\Http\Controllers\AppointmentController::class, 'getNearestSlots'])->name('api.agenda.nearest');
    Route::post('api/appointments', [App\Http\Controllers\AppointmentController::class, 'store'])->name('agenda.store');
    Route::match(['post', 'patch'], 'api/appointments/{appointment}/reschedule', [App\Http\Controllers\AppointmentController::class, 'reschedule'])->name('agenda.reschedule');
    Route::post('api/appointments/{appointment}/send-whatsapp', [App\Http\Controllers\AppointmentController::class, 'sendWhatsappNow'])->name('agenda.send_whatsapp');
    Route::patch('api/appointments/{appointment}/no-show', [App\Http\Controllers\AppointmentController::class, 'markNoShow'])->name('agenda.no_show');
    Route::delete('api/appointments/{appointment}', [App\Http\Controllers\AppointmentController::class, 'destroy'])->name('agenda.destroy');
    Route::get('api/patients/{id}/habits', [App\Http\Controllers\PatientController::class, 'getHabits']);
    // Internal Chat & Real-Time Sync (Intercomunicador)
    Route::get('api/messages/poll/incoming', [App\Http\Controllers\MessageController::class, 'pollIncoming']);
    Route::get('api/messages/{user}', [App\Http\Controllers\MessageController::class, 'getHistory']);
    Route::post('api/messages', [App\Http\Controllers\MessageController::class, 'store']);
    Route::post('api/messages/read', [App\Http\Controllers\MessageController::class, 'markRead']);
    Route::get('api/notifications/poll', [App\Http\Controllers\NotificationController::class, 'poll']);

    // Agenda Settings (Doctor Blocks)
    Route::get('agenda/settings', [App\Http\Controllers\AgendaSettingsController::class, 'index'])->name('agenda.settings');
    Route::get('api/agenda/config/{doctorId}', [App\Http\Controllers\AgendaSettingsController::class, 'getConfig']);
    Route::delete('agenda/settings/config/{doctorId}', [App\Http\Controllers\AgendaSettingsController::class, 'destroyConfig'])->name('agenda.config.destroy');
    Route::post('agenda/settings/config', [App\Http\Controllers\AgendaSettingsController::class, 'storeConfig'])->name('agenda.config.store');
    Route::post('agenda/settings/blocks', [App\Http\Controllers\AgendaSettingsController::class, 'storeBlock'])->name('agenda.blocks.store');
    Route::delete('agenda/settings/blocks/{block}', [App\Http\Controllers\AgendaSettingsController::class, 'destroyBlock'])->name('agenda.blocks.destroy');
    Route::delete('agenda/settings/blocks-doctor/{doctor}', [App\Http\Controllers\AgendaSettingsController::class, 'destroyDoctorBlocks'])->name('agenda.blocks.destroy_doctor');
    Route::post('api/agenda/blocks', [App\Http\Controllers\AppointmentController::class, 'storeBlock'])->name('api.agenda.blocks.store');
    Route::post('api/agenda/blocks/unblock-range', [App\Http\Controllers\AppointmentController::class, 'unblockRange'])->name('api.agenda.blocks.unblock_range');
    Route::delete('api/agenda/blocks/{block}', [App\Http\Controllers\AppointmentController::class, 'destroyBlock'])->name('api.agenda.blocks.destroy');
    Route::post('api/agenda/special-opening', [App\Http\Controllers\AppointmentController::class, 'storeSpecialOpening'])->name('api.agenda.special_opening.store');
    Route::delete('api/agenda/special-opening', [App\Http\Controllers\AppointmentController::class, 'removeSpecialOpening'])->name('api.agenda.special_opening.destroy');

    // Duplicate Patients Detector & Merger
    Route::get('patients-duplicates', [App\Http\Controllers\PatientController::class, 'duplicates'])->name('patients.duplicates');
    Route::post('patients-duplicates/merge', [App\Http\Controllers\PatientController::class, 'mergeDuplicates'])->name('patients.duplicates.merge');
    // Chat IT (Asistente Médico IA)
    Route::get('/chat-it', [App\Http\Controllers\ChatItController::class, 'index'])->name('chatit.index');
    Route::post('/chat-it/ask', [App\Http\Controllers\ChatItController::class, 'ask'])->name('chatit.ask');

    // Módulo de Noticias Oftalmológicas
    Route::get('/noticias', [App\Http\Controllers\NewsController::class, 'index'])->name('news.index');
    Route::get('/noticias/refresh', [App\Http\Controllers\NewsController::class, 'refresh'])->name('news.refresh');
    Route::get('/noticias/favoritos', [App\Http\Controllers\NewsController::class, 'favorites'])->name('news.favorites');
    Route::post('/noticias/favorito', [App\Http\Controllers\NewsController::class, 'saveFavorite'])->name('news.saveFavorite');
    Route::post('/noticias/favorito/remove', [App\Http\Controllers\NewsController::class, 'removeFavorite'])->name('news.removeFavorite');
});
