# Blueprint de Estrutura: Sistema para Clonagem (Template 2025)

Este documento descreve minuciosamente a arquitetura, o design e as mecânicas do projeto atual, servindo como guia técnico definitivo para a criação de um clone do sistema para outra entidade.

---

## 1. ARQUITETURA E STACK TÉCNICA

O sistema é construído sobre o **Symfony 7.2**, utilizando uma abordagem moderna de frontend desacoplado via **Tailwind CSS**, **Alpine.js** e **AssetMapper**.

### Componentes Chave:
- **Backend**: Symfony (PHP 8.2+), Doctrine ORM.
- **Estilização**: Tailwind CSS (Utility-first com suporte a Dark Mode nativo).
- **Interatividade**: Alpine.js (para sliders, modais e lógica reativa leve).
- **Ícones**: Lucide Icons (SVGs dinâmicos).
- **Animações**: AOS (Animate On Scroll) e keyframes customizados no Tailwind.
- **Uploads**: VichUploaderBundle para gestão de mídia.

---

## 2. ÁREA ADMINISTRATIVA (PAINEL DE CONTROLE)

O painel administrativo utiliza uma estética **Glassmorphism-Modern**, com foco em usabilidade e performance.

### 2.1 Sidebar (Menu Lateral)
- **Estruturação**: Localizada em `templates/admin/base_admin.html.twig`.
- **Lógica**: Identifica a rota ativa (`app.request.get('_route')`) para destacar os itens selecionados.
- **Categorização**: Dividida em "Gestão de Conteúdo", "Leads" e "Sistema".
- **Botões**: Utilizam classes utilitárias do Tailwind para efeitos de hover e anéis de foco (`ring-1`).

### 2.2 Dashboard
- **Localização**: `templates/admin/dashboard/index.html.twig`.
- **Layout**: Grid responsivo (1 col mobile, 2 col tablet, 4 col desktop).
- **Cards de Estatísticas**:
  - Fundo translúcido (`glass-panel`).
  - Ícone temático com fundo colorido suave (bg-blue-100/50, etc.).
  - Contador dinâmico vindo dos Repositories.
  - Link de "Acesso Rápido" para o respectivo CRUD.
- **Seção de Acesso Rápido**: Grid de botões com ícones para criação direta de novos conteúdos (Notícias, Serviços).

### 2.3 CRUDs (Padrão de Interface)
- **Tabelas**: Estilizadas com bordas suaves, cabeçalhos em caixa alta (`uppercase`) e botões de ação com ícones Lucide.
- **Formulários**: Campos com classes `form-input` e `form-textarea`.
- **Editor**: Integração com TinyMCE para campos de conteúdo HTML (classes `.editor-html`).

---

## 3. AUTENTICAÇÃO E SEGURANÇA

### 3.1 Login
- **Rota**: `/login`.
- **Controller**: `SecurityController::login`.
- **Template**: `templates/security/login.html.twig` (herda de `layouts/auth.html.twig`).
- **Mecânica**: Utiliza o componente Security do Symfony com firewall configurado em `security.yaml`. Suporta CSRF protection.

### 3.2 Esqueci a Senha
- **Estrutura Atual**: O sistema está preparado para integração com o `SymfonyCasts ResetPasswordBundle`.
- **Recomendação para Clone**: Implementar o fluxo de token de e-mail utilizando as rotas padrão do bundle para maior segurança.

---

## 4. SISTEMA DE TEMAS (LIGHT & DARK MODE)

A implementação de cores é baseada em **Variáveis CSS** e na configuração `darkMode: 'class'` do Tailwind.

### 4.1 Mecânica de Troca (`theme-toggle.js`)
- **Funcionamento**: O script alterna a classe `.dark` no elemento `<html>`.
- **Persistência**: O estado (light/dark) é salvo no `localStorage.getItem('theme')`.
- **Botão Toggle**: Presente tanto na Home quanto no Admin, alternando entre ícones de sol e lua.

### 4.2 Definição de Cores (`tailwind.config.js`)
```javascript
// Exemplo de mapeamento de cores
colors: {
  background: "hsl(var(--background))",
  foreground: "hsl(var(--foreground))",
  primary: {
    DEFAULT: "hsl(var(--primary))",
    foreground: "hsl(var(--primary-foreground))",
  },
  // Cores específicas para tons médicos/saúde
  medical: {
    blue: "#0ea5e9",
    heading: "#1e293b",
    text: "#64748b",
  }
}
```

---

## 5. ESTRUTURA DA HOME (FRONTEND PÚBLICO)

A Home é estruturada em seções independentes e repetíveis.

### 5.1 Hero Slider
- **Tecnologia**: Alpine.js para movimentação e transições.
- **Mecânica**: Loop automático de banners cadastrados no Admin.
- **Filtros**: Sobreposição de gradiente dinâmico para garantir legibilidade dos textos sobre as imagens.

### 5.2 Tipos de Cards e Boxes
- **Cards de Info**: Grid de 3 colunas sob o Hero (Horários, Equipe, Contato). Fundo sólido em destaque (`bg-primary`).
- **Cards de Serviços**: Grid de 4 colunas. Design "Clean" com ícones grandes e sombra suave ao hover.
- **Flip-Cards (Especialidades)**: Cards interativos que giram 180º ao hover, revelando descrição curta e link.
- **Corpo Clínico**: Cards com imagem vertical (aspect ratio 4/5) e overlay informativo no hover.

### 5.3 Tipografia
- **Títulos (Headings)**: Montserrat (Séria e moderna).
- **Corpo (Body)**: Lato (Leitura clara e fluida).
- **Dashboard/Admin**: Jakarta Plus Sans.

---

## 6. COMPONENTES GLOBAIS

### 6.1 Menu (Header)
- **Desktop**: Navegação horizontal com links que mudam de cor ao scroll e rotas ativas.
- **Mobile**: Menu lateral (slide-in) com toggle via Alpine.js (`x-data="{ isMenuOpen: false }"`).

### 6.2 Rodapé (Footer)
- **Estrutura**: 4 colunas (Sobre, Links Rápidos, Especialidades, Horário).
- **Newsletter**: Form com submissão assíncrona (Fetch API) integrada com SweetAlert2 para feedback.

---

## 7. MASTER PROMPT PARA CRIAÇÃO DO NOVO PROJETO (CLONE)

> [!TIP]
> Use o prompt abaixo como base para iniciar a criação do novo sistema após configurar o ambiente Symfony base.

**"Preciso criar um clone do sistema atual para a entidade [NOME_ENTIDADE]. Mantenha a seguinte estrutura técnica: Symfony 7.2 com Tailwind CSS e Alpine.js.
1. Design System: Utilize o padrão de Variáveis CSS para cores. Defina a cor primária como [COR].
2. Admin: Implemente o Dashboard com grid de 4 colunas para estatísticas e área de acesso rápido. O tema deve ter suporte a Dark Mode com persistência em localStorage.
3. Home: Estruture a página com Hero Slider (Alpine), seção de 3 info-cards sob o banner, grid de serviços de 4 colunas e flip-cards para especialidades.
4. Auth: Configure o login padrão redirecionando para /admin.
5. Fonts: Use Montserrat para headers e Lato para o corpo do texto.
6. Preparação: Crie as entidades básicas (News, Service, Specialty, Doctor, HomeBanner, AboutPage) seguindo o padrão VichUploader do projeto original."**

---

*Documentação gerada em: 20/12/2025*
