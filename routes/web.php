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

Route::middleware('auth')->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::post('users/{user}/reset-password', [App\Http\Controllers\UserController::class, 'resetPassword'])->name('users.resetPassword');
    Route::get('/configuracion', [App\Http\Controllers\ConfigController::class, 'index'])->name('config.index');
    Route::post('/configuracion/obras-sociales', [App\Http\Controllers\ConfigController::class, 'storeObraSocial'])->name('config.obras_sociales.store');
    Route::delete('/configuracion/obras-sociales/{obraSocial}', [App\Http\Controllers\ConfigController::class, 'destroyObraSocial'])->name('config.obras_sociales.destroy');
    Route::delete('/configuracion/ai-memory/{user}', [App\Http\Controllers\ConfigController::class, 'clearAiMemory'])->name('config.ai_memory.clear');
    Route::get('/configuracion/export-patients', [App\Http\Controllers\ConfigController::class, 'exportPatientsToExcel'])->name('config.export.patients');
    
    // WhatsApp API Configuration
    Route::get('/configuracion/whatsapp', [App\Http\Controllers\WhatsappConfigController::class, 'index'])->name('whatsapp.index');
    Route::post('/configuracion/whatsapp', [App\Http\Controllers\WhatsappConfigController::class, 'store'])->name('whatsapp.store');
    
    Route::resource('patients', App\Http\Controllers\PatientController::class);
    // Medical Studies specific routes
    Route::post('patients/{patient}/studies', [App\Http\Controllers\PatientStudyController::class, 'store'])->name('patient.studies.store');
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
    Route::get('patients/{patient}/history/print', [App\Http\Controllers\PatientController::class, 'printHistory'])->name('patients.history.print');
    
    // Patient Flow Console (Phase 15 Pivot)
    Route::get('console', [App\Http\Controllers\ConsoleController::class, 'index'])->name('console.index');
    Route::post('console/assignments', [App\Http\Controllers\ConsoleController::class, 'store'])->name('console.assignments.store');
    Route::post('console/assignments/{patient}/append', [App\Http\Controllers\ConsoleController::class, 'append'])->name('console.assignments.append');
    Route::post('console/transition/{patient}', [App\Http\Controllers\ConsoleController::class, 'transition'])->name('console.assignments.transition');
    Route::post('console/finish-all', [App\Http\Controllers\ConsoleController::class, 'finishAll'])->name('console.finishAll');

    // Medical Agenda & Interactive Calendar
    Route::get('agenda', [App\Http\Controllers\AppointmentController::class, 'index'])->name('agenda.index');
    // Phase 16 & 17 Split View APIs
    Route::get('api/appointments/availability', [App\Http\Controllers\AppointmentController::class, 'getMonthAvailability'])->name('api.agenda.availability');
    Route::get('api/appointments/slots', [App\Http\Controllers\AppointmentController::class, 'getDaySlots'])->name('api.agenda.slots');
    Route::get('api/appointments/nearest', [App\Http\Controllers\AppointmentController::class, 'getNearestSlots'])->name('api.agenda.nearest');
    Route::post('api/appointments', [App\Http\Controllers\AppointmentController::class, 'store'])->name('agenda.store');
    Route::patch('api/appointments/{appointment}/no-show', [App\Http\Controllers\AppointmentController::class, 'markNoShow'])->name('agenda.no_show');
    Route::delete('api/appointments/{appointment}', [App\Http\Controllers\AppointmentController::class, 'destroy'])->name('agenda.destroy');
    Route::get('api/patients/{id}/habits', [App\Http\Controllers\PatientController::class, 'getHabits']);
    // Internal Chat & Real-Time Sync
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
