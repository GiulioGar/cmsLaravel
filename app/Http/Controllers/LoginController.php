<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class LoginController extends Controller
{
    // Mostra il form di login
    public function showLoginForm()
    {
        return view('login');
    }

    // Gestisce la richiesta di login
    public function login(Request $request)
    {
        // Validazione dei campi obbligatori
        $request->validate([
            'name'     => 'required',
            'password' => 'required',
        ]);

        // Recupera l'utente in base al campo name
        $user = User::where('name', $request->input('name'))->first();

        // Confronta la password (usando md5) se l'utente esiste
        if ($user && $user->password === md5($request->input('password'))) {
            // Salva l'ID dell'utente in sessione (o altri dati se necessario)
           $request->session()->put('user', $user->id);
            $request->session()->put('user_name', $user->name);

            // Reindirizza al dashboard (o altra pagina protetta)
            return redirect()->route('index');
        }

        // Se le credenziali non sono valide, ritorna indietro con un messaggio di errore
        return redirect()->back()->withErrors(['msg' => 'Credenziali non valide']);
    }

    // Effettua il logout rimuovendo i dati dalla sessione
    public function logout(Request $request)
    {
        $request->session()->flush();
         $request->session()->invalidate();
         $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
