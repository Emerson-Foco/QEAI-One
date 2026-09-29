<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Support\EnvFile;
use App\Support\Installer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InstallController extends Controller
{
    private function guard(): void
    {
        if (Installer::installed()) {
            abort(404);
        }
    }

    public function index()
    {
        $this->guard();
        return view('install', ['step' => 'requirements', 'requirements' => Installer::requirements(), 'requirementsOk' => Installer::requirementsOk()]);
    }

    public function database(Request $request)
    {
        $this->guard();
        $connected = false;
        $error = null;
        try {
            DB::connection()->getPdo();
            $connected = true;
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
        }
        return view('install', [
            'step' => 'database',
            'connected' => $connected,
            'error' => $error,
            'values' => [
                'host' => config('database.connections.mysql.host', '127.0.0.1'),
                'port' => config('database.connections.mysql.port', '3306'),
                'database' => config('database.connections.mysql.database', ''),
                'username' => config('database.connections.mysql.username', ''),
                'password' => '',
            ],
        ]);
    }

    public function databaseStore(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $pdo = new \PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $data['host'], $data['port'], $data['database']),
                $data['username'],
                (string) ($data['password'] ?? ''),
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors(['database' => 'Não foi possível conectar: ' . $exception->getMessage()]);
        }

        EnvFile::set(base_path('.env'), [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['host'],
            'DB_PORT' => (string) $data['port'],
            'DB_DATABASE' => $data['database'],
            'DB_USERNAME' => $data['username'],
            'DB_PASSWORD' => (string) ($data['password'] ?? ''),
        ]);

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $data['host'],
            'database.connections.mysql.port' => (string) $data['port'],
            'database.connections.mysql.database' => $data['database'],
            'database.connections.mysql.username' => $data['username'],
            'database.connections.mysql.password' => (string) ($data['password'] ?? ''),
        ]);
        DB::purge('mysql');

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $exception) {
            return back()->withErrors(['database' => 'Conectado, mas as migrações falharam: ' . $exception->getMessage()]);
        }

        return redirect()->route('install.admin');
    }

    public function admin()
    {
        $this->guard();
        if (User::where('is_root_admin', true)->exists()) {
            return redirect()->route('install.identity');
        }
        return view('install', ['step' => 'admin']);
    }

    public function adminStore(Request $request)
    {
        $this->guard();

        if (User::where('is_root_admin', true)->exists()) {
            return redirect()->route('install.identity')->with('install_message', 'O ADM Root já foi criado. Continue definindo a identidade.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'max:72', 'confirmed'],
        ]);

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            return redirect()->route('install.database')->withErrors(['database' => 'Conecte o banco antes de criar o ADM.']);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        // A flag de ADM Root é definida explicitamente (fora do mass assignment).
        // O ADM é criado durante a instalação, em servidor próprio: e-mail considerado verificado.
        $user->is_root_admin = true;
        $user->email_verified_at = now();
        $user->save();

        return redirect()->route('install.identity')->with('install_message', 'ADM Root criado com sucesso. Agora defina a identidade da plataforma.');
    }

    public function identity()
    {
        $this->guard();
        if (! User::where('is_root_admin', true)->exists()) {
            return redirect()->route('install.admin');
        }
        return view('install', [
            'step' => 'identity',
            'values' => [
                'company_name' => Setting::get('company_name', 'QEAI One'),
                'company_legal_name' => Setting::get('company_legal_name', ''),
                'company_document' => Setting::get('company_document', ''),
                'contact_email' => Setting::get('contact_email', ''),
                'primary_color' => Setting::get('primary_color', '#6D3DF5'),
                'smtp_host' => Setting::get('smtp_host', 'smtp.hostinger.com'),
                'smtp_port' => Setting::get('smtp_port', '465'),
                'smtp_encryption' => Setting::get('smtp_encryption', 'ssl'),
                'smtp_username' => Setting::get('smtp_username', ''),
                'smtp_from_name' => Setting::get('smtp_from_name', 'QEAI One'),
            ],
        ]);
    }

    public function identityStore(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'company_legal_name' => ['nullable', 'string', 'max:180'],
            'company_document' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:180'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'smtp_encryption' => ['nullable', 'in:ssl,tls'],
            'smtp_username' => ['nullable', 'email', 'max:180'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:100'],
        ]);

        foreach ([
            'company_name', 'company_legal_name', 'company_document', 'contact_email',
            'primary_color', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_from_name',
        ] as $key) {
            Setting::put($key, (string) ($data[$key] ?? ''));
        }
        if (! empty($data['smtp_password'])) {
            Setting::put('smtp_password', $data['smtp_password'], true);
        }

        Installer::markInstalled();

        return redirect()->route('login')->with('status', 'Instalação concluída. Entre com seu e-mail e senha.');
    }
}
