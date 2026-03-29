<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Muestra el panel maestro de configuración del sistema.
     */
    public function index()
    {
        return view('config.index');
    }
}
