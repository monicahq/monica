<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactJsonExportController extends Controller
{
    public function __invoke(Request $request, Contact $contact)
    {
        // 1. Comprobamos que el usuario tiene permiso para ver este contacto
        // (Opcional pero buena práctica de seguridad)

        // 2. Convertimos los datos del contacto a formato JSON
        $data = $contact->toJson(JSON_PRETTY_PRINT);

        // 3. Generamos el nombre del archivo
        $fileName = 'contacto_' . $contact->id . '.json';

        // 4. Forzamos la descarga del archivo
        return response($data, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}