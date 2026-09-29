<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\InviteAcceptController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\OrganizationPanelController;
use App\Http\Controllers\OrganizationRoleController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PlatformSettingsController;
use App\Http\Controllers\SecurityController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('show');
    Route::get('/banco', [InstallController::class, 'database'])->name('database');
    Route::post('/banco', [InstallController::class, 'databaseStore']);
    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/admin', [InstallController::class, 'adminStore']);
    Route::get('/identidade', [InstallController::class, 'identity'])->name('identity');
    Route::post('/identidade', [InstallController::class, 'identityStore']);
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/esqueci-senha', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/esqueci-senha', [PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/redefinir-senha/{token}', [PasswordResetController::class, 'show'])->name('password.reset');
    Route::post('/redefinir-senha', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::get('/2fa', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
Route::post('/2fa', [TwoFactorController::class, 'verify'])->name('two-factor.verify');

Route::get('/verificar-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')->name('verification.verify');
Route::post('/reverificar-email', [EmailVerificationController::class, 'send'])
    ->middleware('auth')->name('verification.send');

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Convites (público)
Route::get('/convite/{token}', [InviteAcceptController::class, 'show'])->name('invite.show');
Route::post('/convite/{token}', [InviteAcceptController::class, 'accept']);
Route::get('/convite/{token}/nao-reconheco', [InviteAcceptController::class, 'reportForm'])->name('invite.report.form');
Route::post('/convite/{token}/nao-reconheco', [InviteAcceptController::class, 'report'])->name('invite.report');

// Área do membro (usuário da organização)
Route::middleware(['auth', 'installed', 'member'])->prefix('app')->name('member.')->group(function () {
    Route::get('/', [MemberController::class, 'index'])->name('dashboard');
});

// Painel da organização (escopado por organização e permissão)
Route::middleware(['auth', 'installed', 'org'])
    ->prefix('app/organizacoes/{organization}')
    ->name('member.org.')
    ->group(function () {
        Route::get('/', [OrganizationPanelController::class, 'show'])->name('show');
        Route::get('logs', [OrganizationController::class, 'logs'])->name('logs');

        Route::post('membros', [OrganizationMemberController::class, 'store'])->name('members.store');
        Route::put('membros/{membership}', [OrganizationMemberController::class, 'update'])->name('members.update');
        Route::delete('membros/{membership}', [OrganizationMemberController::class, 'destroy'])->name('members.destroy');

        Route::post('convites', [InviteController::class, 'store'])->name('invites.store');
        Route::delete('convites/{invite}', [InviteController::class, 'destroy'])->name('invites.destroy');

        Route::post('grupos', [OrganizationRoleController::class, 'store'])->name('roles.store');
        Route::put('grupos/{role}', [OrganizationRoleController::class, 'update'])->name('roles.update');
        Route::delete('grupos/{role}', [OrganizationRoleController::class, 'destroy'])->name('roles.destroy');
    });

Route::middleware(['auth', 'installed', 'root'])->prefix('painel')->name('panel.')->group(function () {
    Route::get('/', function () {
        return view('dashboard', [
            'reportedInvites' => \App\Models\AuditLog::where('action', 'invite.reported')->count(),
        ]);
    })->name('dashboard');

    Route::get('logs', [AuditController::class, 'index'])->name('logs.index');

    Route::get('seguranca', [SecurityController::class, 'index'])->name('security.index');
    Route::put('seguranca/senha', [SecurityController::class, 'updatePassword'])->name('security.password');
    Route::post('seguranca/2fa', [SecurityController::class, 'enableTwoFactor'])->name('security.2fa.start');
    Route::post('seguranca/2fa/confirmar', [SecurityController::class, 'confirmTwoFactor'])->name('security.2fa.confirm');
    Route::post('seguranca/2fa/desativar', [SecurityController::class, 'disableTwoFactor'])->name('security.2fa.disable');

    Route::get('organizacoes', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::post('organizacoes', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('organizacoes/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::get('organizacoes/{organization}/logs', [OrganizationController::class, 'logs'])->name('organizations.logs');
    Route::put('organizacoes/{organization}/plano', [OrganizationController::class, 'updatePlan'])->name('organizations.plan');
    Route::put('organizacoes/{organization}/recursos', [\App\Http\Controllers\OrganizationFeatureController::class, 'update'])->name('organizations.features');

    Route::post('organizacoes/{organization}/membros', [OrganizationMemberController::class, 'store'])->name('members.store');
    Route::put('organizacoes/{organization}/membros/{membership}', [OrganizationMemberController::class, 'update'])->name('members.update');
    Route::delete('organizacoes/{organization}/membros/{membership}', [OrganizationMemberController::class, 'destroy'])->name('members.destroy');

    Route::post('organizacoes/{organization}/convites', [InviteController::class, 'store'])->name('invites.store');
    Route::delete('organizacoes/{organization}/convites/{invite}', [InviteController::class, 'destroy'])->name('invites.destroy');

    Route::post('organizacoes/{organization}/grupos', [OrganizationRoleController::class, 'store'])->name('roles.store');
    Route::put('organizacoes/{organization}/grupos/{role}', [OrganizationRoleController::class, 'update'])->name('roles.update');
    Route::delete('organizacoes/{organization}/grupos/{role}', [OrganizationRoleController::class, 'destroy'])->name('roles.destroy');

    Route::get('configuracoes', [PlatformSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('configuracoes', [PlatformSettingsController::class, 'update'])->name('settings.update');
    Route::post('configuracoes/email-teste', [PlatformSettingsController::class, 'testEmail'])->name('settings.test');

    Route::get('planos', [PlanController::class, 'index'])->name('plans.index');
    Route::post('planos', [PlanController::class, 'store'])->name('plans.store');
    Route::put('planos/{plan}', [PlanController::class, 'update'])->name('plans.update');
    Route::delete('planos/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');
});
