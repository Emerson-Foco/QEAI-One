@extends('layouts.app')

@section('title', 'QEAI One — CRM e atendimento para empresas')
@section('body_class', 'landing')

@section('content')
<div class="landing-hero">
  <div class="container">
    <nav class="landing-nav">
      <a href="{{ route('home') }}"><img src="{{ asset('img/logo-light.svg') }}" alt="QEAI One" width="158" height="46"></a>
      <div class="landing-links">
        <a href="#recursos">Recursos</a>
        <a href="#como-funciona">Como funciona</a>
        <a href="{{ route('login') }}">Entrar</a>
        <a class="btn" href="{{ route('register') }}">Criar conta</a>
      </div>
    </nav>

    <div class="landing-hero-inner">
      <div>
        <span class="landing-kicker"><x-icon name="sparkles" :size="13"/> CRM inteligente para o seu negócio</span>
        <h1>Mais clientes.<br>Mais oportunidades.<br><span>Mais resultados.</span></h1>
        <p>O QEAI One reúne CRM, captação de leads, atendimento e automações em uma plataforma simples — para sua equipe vender mais sem se perder em planilhas.</p>
        <div class="btn-row" style="margin-top:22px">
          <a class="btn" href="{{ route('register') }}">Criar conta grátis <x-icon name="arrow-right" :size="17"/></a>
          <a class="btn secondary" href="{{ route('login') }}">Já tenho conta</a>
        </div>
        <div class="landing-stats">
          <div><strong>Multiempresa</strong><small>cada empresa com sua própria base</small></div>
          <div><strong>LGPD</strong><small>auditoria e controle de acesso</small></div>
          <div><strong>Sem instalação</strong><small>acesse pelo navegador</small></div>
        </div>
      </div>

      <div class="landing-preview" aria-hidden="true">
        <div class="landing-mock">
          <div class="landing-mock-head"><i></i><i></i><i></i><span>app.qeai.com.br</span></div>
          <div class="landing-mock-kpis">
            <div class="landing-mock-kpi"><small>LEADS</small><strong>1.248</strong></div>
            <div class="landing-mock-kpi"><small>OPORTUNIDADES</small><strong>320</strong></div>
            <div class="landing-mock-kpi"><small>CLIENTES</small><strong>128</strong></div>
          </div>
          <div class="landing-mock-bars">
            <i style="height:34%"></i><i style="height:52%"></i><i style="height:44%"></i><i style="height:68%"></i>
            <i style="height:58%"></i><i style="height:82%"></i><i style="height:72%"></i><i style="height:94%"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<section class="landing-section" id="recursos">
  <div class="container">
    <div class="landing-section-head">
      <span class="pill">Tudo em um só lugar</span>
      <h2>O essencial para organizar e crescer</h2>
      <p>Módulos que se conectam, com uma interface pensada para quem precisa de praticidade no dia a dia.</p>
    </div>
    <div class="feature-grid">
      <article class="feature-card">
        <span class="feature-icon"><x-icon name="users" :size="21"/></span>
        <h3>CRM e funil de vendas</h3>
        <p>Leads, empresas, oportunidades e tarefas organizados em um funil visual, com campos personalizados para o seu negócio.</p>
      </article>
      <article class="feature-card">
        <span class="feature-icon"><x-icon name="filter" :size="21"/></span>
        <h3>Captação de leads</h3>
        <p>Formulários próprios, página pública e API para receber leads do site, de campanhas e de qualquer integração.</p>
      </article>
      <article class="feature-card">
        <span class="feature-icon"><x-icon name="message" :size="21"/></span>
        <h3>Atendimento unificado</h3>
        <p>Chat do site, e-mail e WhatsApp em uma única caixa de entrada, com histórico completo por contato.</p>
      </article>
      <article class="feature-card">
        <span class="feature-icon"><x-icon name="zap" :size="21"/></span>
        <h3>Automação e integrações</h3>
        <p>Webhooks de entrada e saída, chaves de API e conexão com N8N, Zapier ou Make para automatizar processos.</p>
      </article>
      <article class="feature-card">
        <span class="feature-icon"><x-icon name="share" :size="21"/></span>
        <h3>Redes sociais e anúncios</h3>
        <p>Planeje publicações, acompanhe contas de anúncio e visualize investimento, CTR, CPC e conversões.</p>
      </article>
      <article class="feature-card">
        <span class="feature-icon"><x-icon name="shield" :size="21"/></span>
        <h3>Segurança e governança</h3>
        <p>Permissões por grupo, convites com consentimento, autenticação em dois fatores e trilhas de auditoria.</p>
      </article>
    </div>
  </div>
</section>

<section class="landing-section alt" id="como-funciona">
  <div class="container">
    <div class="landing-section-head">
      <span class="pill">Simples de começar</span>
      <h2>Pronto para usar em três passos</h2>
      <p>Sem instalação, sem servidor e sem treinamento longo.</p>
    </div>
    <div class="feature-grid">
      <article class="feature-card">
        <span class="feature-icon"><x-icon name="building" :size="21"/></span>
        <h3>1. Crie sua conta</h3>
        <p>Cadastre sua empresa e seu usuário. Você entra como proprietário e já pode configurar a equipe.</p>
      </article>
      <article class="feature-card">
        <span class="feature-icon"><x-icon name="settings" :size="21"/></span>
        <h3>2. Ajuste o seu jeito de vender</h3>
        <p>Crie campos, etapas do funil, formulários e convide sua equipe com permissões específicas.</p>
      </article>
      <article class="feature-card">
        <span class="feature-icon"><x-icon name="trending-up" :size="21"/></span>
        <h3>3. Acompanhe os resultados</h3>
        <p>Use o dashboard para ver leads, oportunidades, tarefas e a evolução do seu funil em tempo real.</p>
      </article>
    </div>
  </div>
</section>

<section class="landing-cta">
  <div class="container">
    <h2>Comece a organizar sua operação hoje</h2>
    <p>Crie sua organização e teste o QEAI One com a sua equipe.</p>
    <a class="btn" href="{{ route('register') }}">Criar conta grátis <x-icon name="arrow-right" :size="17"/></a>
  </div>
</section>

<footer class="landing-footer">
  <div class="container">
    <span>© {{ date('Y') }} QEAI One. Todos os direitos reservados.</span>
    <span>
      <a href="{{ route('login') }}">Entrar</a> ·
      <a href="{{ route('register') }}">Criar conta</a> ·
      <a href="https://qeai.com.br/privacidade" target="_blank" rel="noopener">Privacidade</a>
    </span>
  </div>
</footer>
@endsection
