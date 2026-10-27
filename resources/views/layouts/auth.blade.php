<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'QEAI One')</title>
  <meta name="robots" content="@yield('robots', 'noindex,nofollow')">
  <meta name="theme-color" content="#0B1220">
  <link rel="icon" href="{{ asset('img/logo.svg') }}" type="image/svg+xml">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  @php $primaryColor = \App\Models\Setting::safe('primary_color', '#6D3DF5') ?: '#6D3DF5'; @endphp
  <style>:root{--primary: {{ $primaryColor }}}</style>
</head>
<body class="auth-body">
  <div class="auth-split">
    <aside class="auth-aside">
      <div class="auth-glow auth-glow-1" aria-hidden="true"></div>
      <div class="auth-glow auth-glow-2" aria-hidden="true"></div>

      <img class="auth-logo" src="{{ asset('img/logo-light.svg') }}" alt="QEAI One" width="176" height="51">

      <div class="auth-aside-body">
        <p class="auth-kicker">CRM inteligente<br>para o seu negócio</p>
        <h1>Mais clientes.<br>Mais oportunidades.<br><span>Mais resultados.</span></h1>
        <p class="auth-lead">O QEAI One reúne CRM, atendimento, captação de leads e automações em um só lugar — para você vender mais e crescer com organização.</p>

        <ul class="auth-benefits">
          <li>
            <span class="auth-benefit-icon"><x-icon name="users" :size="20"/></span>
            <div><strong>Gestão de clientes</strong><small>Centralize leads e todo o relacionamento em um só lugar.</small></div>
          </li>
          <li>
            <span class="auth-benefit-icon"><x-icon name="bar-chart" :size="20"/></span>
            <div><strong>Mais vendas</strong><small>Acompanhe seu funil e identifique as melhores oportunidades.</small></div>
          </li>
          <li>
            <span class="auth-benefit-icon"><x-icon name="zap" :size="20"/></span>
            <div><strong>Automação inteligente</strong><small>Automatize tarefas e ganhe tempo para o que importa.</small></div>
          </li>
        </ul>
      </div>

      <div class="auth-trust">
        <span class="auth-trust-icon"><x-icon name="shield" :size="22"/></span>
        <div>
          <strong>Segurança e privacidade</strong>
          <small>Dados protegidos, auditoria completa e conformidade com a LGPD.</small>
        </div>
      </div>
    </aside>

    <main class="auth-main">
      <div class="auth-mobile-brand">
        <img src="{{ asset('img/logo.svg') }}" alt="QEAI One" width="148" height="43">
      </div>

      <div class="auth-card">
        <a class="auth-brand" href="{{ route('home') }}">
          <img src="{{ asset('img/logo.svg') }}" alt="QEAI One" width="164" height="48">
        </a>
        @yield('auth')
      </div>

      <p class="auth-foot">© {{ date('Y') }} QEAI One · Plataforma segura para empresas</p>
    </main>
  </div>

  <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
