# Documentação Técnica: Sistema Base & Projeto Procordis

> [!NOTE]
> Este documento está organizado em duas partes principais:
> 1.  **Arquitetura e Padrões (Geral):** Tecnologias, frameworks e padrões reutilizáveis em qualquer projeto da agência.
> 2.  **Especificações do Projeto (Procordis):** Detalhes exclusivos, regras de negócio e conteúdo deste projeto.

---

# PARTE 1: ARQUITETURA, TECNOLOGIAS E PADRÕES (GERAL)
*Tudo descrito nesta seção compõe a base tecnológica reutilizável do sistema.*

## 1. Tecnologias & Stack
A base do sistema utiliza uma arquitetura moderna e otimizada para performance máxima (PageSpeed 100/100).

| Área | Tecnologia | Detalhes |
|-----|------------|----------|
| **Backend** | **Symfony 7.2** | Estrutura central da aplicação. |
| **PHP** | **8.2+** | Linguagem base. |
| **Front-end** | **TailwindCSS v3** | Framework CSS utility-first. |
| **Componentes** | **Flowbite Blocks** | Biblioteca de componentes UI pré-construídos. |
| **ORM** | **Doctrine** | Camada de abstração de banco de dados. |
| **Templates** | **Twig** | Motor de templates para renderização server-side. |
| **Acessibilidade** | **WAI-ARIA** | Padrões de acessibilidade web. |
| **Animações** | **AOS.js** | Biblioteca leve para animações "On Scroll". |

## 2. Arquitetura do Sistema
### 2.1 Estrutura de Pastas (Symfony Padrão)
```txt
src/
 ├── Controller/      # Controladores de rotas
 ├── Entity/          # Entidades do Banco de Dados
 ├── Repository/      # Consultas ao Banco (Doctrine)
 ├── Service/         # Regras de Negócio e Serviços Reutilizáveis
 ├── Security/        # Lógica de Autenticação
 ├── Form/            # Classes de Formulários
templates/            # Arquivos Twig (.html.twig)
public/               # Assets públicos (imagens, builds css/js)
config/               # Configurações do Symfony
```

### 2.2 Requisitos de Performance
Para garantir a nota 100/100 no PageSpeed/Lighthouse:
1.  **Imagens:** Uso exclusivo de formatos modernos (**WebP**) e dimensionamento correto.
2.  **Lazy Loading:** Carregamento diferido para imagens e iframes.
3.  **Scripts:** JS mínimo e carregamento assíncrono/deferido.
4.  **CSS:** Build purgado do TailwindCSS para remover estilos não utilizados.
5.  **Cache:** Implementação de cache HTTP e uso de CDN para assets estáticos.
6.  **Fontes:** Preconnect e display swap para otimização de webfonts.

## 3. Sistema de SEO (Três Camadas)
O sistema implementa uma arquitetura robusta de SEO dividida em três níveis de especificidade.

### Camada 1: Tags Globais
Gerenciadas pela entidade `GlobalTags`.
*   **Função:** Inserir scripts e metadados presentes em *todas* as páginas.
*   **Campos:** `ga4` (Google Analytics), `tagsGoogleAds`, `pixelMetaAds`.
*   **Implementação:** Injetado automaticamente no `base.html.twig` via `TemplateService`.

### Camada 2: SEO de Páginas Estáticas
Gerenciadas pela entidade `PageSeo`.
*   **Função:** Definir títulos e descrições para rotas fixas (Home, Quem Somos, Contato).
*   **Campos:** `homePageTitle`, `homePageDescription`, `aboutPageTitle`, etc.
*   **Uso:** `{{ templateService.pageSeo.homePageTitle }}`

### Camada 3: SEO Dinâmico (Conteúdo)
Gerenciado dentro de cada entidade de conteúdo (Ex: `News`, `Service`).
*   **Campos Padrão:**
    *   `seoTitle` (Título otimizado, max 60 chars)
    *   `seoDescription` (Meta description, max 160 chars)
    *   `slug` (URL amigável gerada automaticamente)
    *   `canonicalUrl` (Para evitar conteúdo duplicado)
    *   `imageAlt` (Texto alternativo para acessibilidade/SEO)
    *   `isNoIndex` (Checkbox para ocultar do Google)

## 4. TemplateService
Um serviço central (`src/Service/TemplateService.php`) para **injetar dados globais** em todos os templates Twig, evitando repetição nos Controllers.

*   **Métodos Principais:**
    *   `globalTags()`: Retorna as tags de rastreamento.
    *   `pageSeo()`: Retorna os textos de SEO estático.
    *   `generalData()`: Retorna dados da empresa (Telefone, Endereço, Social).

## 5. Sistema de Imagens (Arquivos)
Integração completa para upload, armazenamento e processamento.

*   **Upload:** **VichUploaderBundle**. Gerencia o envio e vinculação do arquivo com a Entidade.
*   **Armazenamento:** **Flysystem + AWS S3**. Os arquivos são enviados diretamente para a nuvem, não ocupando espaço no servidor da aplicação.
*   **Processamento:** **LiipImagineBundle**. Cria miniaturas e versões otimizadas dinamicamente.
    *   *Filtros Padrão:* `news_thumb`, `news_list_thumb`, `news_large`, `admin_thumb`.

## 6. Segurança e Autenticação
### 6.1 Acesso Administrativo
*   **Entidade:** `User`.
*   **Role Obrigatória:** `ROLE_ADMIN` é exigida para acessar `/admin`.
*   **Login:** Formulário padrão do Symfony Security. Redirecionamento automático para Dashboard após sucesso.

### 6.2 Comando para Criar Administrador
Ferramenta via terminal para criar usuários iniciais.
```bash
php bin/console app:admin-user
```
*Fluxo:* Lista usuários existentes -> Solicita e-mail/senha -> Cria usuário com privilégios administrativos.

### 6.3 Esqueci Minha Senha
Fluxo seguro de recuperação de conta.
1.  Usuário solicita reset via e-mail.
2.  Sistema gera token único e envia link (via **WMailer**).
3.  Usuário define nova senha.
4.  Template de e-mail customizado em Twig.

## 7. Design System Administrativo ("Liquid Glass")
Um padrão visual premium desenvolvido para áreas administrativas.

*   **Conceito:** Estética moderna utilizando *Glassmorphism* (efeito de vidro fosco) e gradientes suaves (*Mesh Gradients*).
*   **Configuração Global:** O tema é definido em `config/packages/twig.yaml` apontando para `form/tailwind_glass_theme.html.twig`.
*   **Formulários Personalizados:**
    *   Inputs com fundo translúcido e `backdrop-blur`.
    *   **Acessibilidade:** Bordas reforçadas (`border-slate-400`) para garantir contraste adequado fora do estado de foco.
*   **Dashboard Padrão:**
    *   Cards com efeito de vidro.
    *   Visão rápida de métricas (Contagens, Últimos registros).

## 8. Padrões de Desenvolvimento
### 8.2 Enum System
O uso de Enums (PHP 8.1+) é mandatório para campos de seleção fixa, localizados em `src/Entity/Enum/`.
Exemplo:
```php
enum LanguageEnum: int {
    case PORTUGUESE = 1;
    // ...
}
```

### 8.3 Data Seeding (Massa de Dados)
Antes de iniciar a dinamização dos layouts, é **obrigatório** popular o banco de dados.
**Como Rodar:**
1.  Instalar dependências (se necessário):
    ```bash
    composer require --dev doctrine/doctrine-fixtures-bundle fakerphp/faker
    ```
2.  Rodar o comando de carga:
    ```bash
    php bin/console doctrine:fixtures:load
    ```
    *(Responda `yes` para purgar o banco antigo)*.

*   **Regra:** Inserir pelo menos **100 registros** para entidades principais e 20-50 para secundárias.
*   **Contém:** Serviços, Médicos, Notícias (com paginação), Dados Gerais.

---

# PARTE 2: ESPECIFICAÇÕES DO PROJETO (PROCORDIS)
*Conteúdo detalhado, regras de negócio e definições exclusivas para o cliente Procordis.*

## 9. Visão Geral do Projeto
Desenvolvimento do novo portal para a **Associação Procórdis – Ambulatório Cardíaco de Araraquara**.
*   **Objetivo:** Modernizar a presença digital, oferecer agendamento simplificado e fornecer conteúdo educativo.
*   **Referência Visual (Template):** "Medical Clinic" (Template Help).
*   **Fidelidade Visual:** O layout deve seguir rigorosamente os mockups aprovados e descritos nos documentos de layout (`docs/layouts`). Implementação via TailwindCSS.

## 10. Estrutura de Conteúdo do Site
Páginas essenciais definidas no **Relatório RDS-250903-1**:

1.  **Home:** Hero section, Destaques de Serviços, Últimas Notícias.
2.  **Quem Somos:** História, Missão, Visão, Valores, Galeria da Equipe.
3.  **Serviços e Especialidades:** Listagem completa de exames e consultas.
4.  **Notícias e Educação:** Blog de saúde cardíaca (Detalhe + Listagem).
5.  **Corpo Clínico:** Perfil dos médicos.
6.  **Transparência:** Documentos e prestação de contas.
7.  **Contato:** Formulário, Mapa, Endereços, Telefones.
8.  **Páginas Auxiliares:** FAQ, Pesquisa, Agendamento (Link externo/integrado).

## 11. Funcionalidades Específicas
### 11.1 Gestão de Notícias (CMS Avançado)
Funcionalidade inspirada no sistema *Siatec*, portada para este projeto.
*   **Editor:** Integração com **TinyMCE** para conteúdo rico.
*   **Automação:** Geração automática de `slug` via JS no admin.
*   **SEO Dedicado:** Campos específicos (`seoTitle`, `canonicalUrl`, `isNoIndex`) na edição de notícias.

### 11.2 Dashboard Personalizado
O painel inicial administrativo exibe métricas vitais da clínica:
*   Total de Notícias publicadas.
*   Total de Serviços ativos.
*   Solicitações de contato recentes.
*   *(Opcional)* Gráficos de acesso.

## 12. Geração de Assets e Imagens
> [!IMPORTANT]
> **Diretrizes de Mídia:**
> *   Utilizar ferramentas de IA para gerar imagens exclusivas e livres de direitos, mantendo o contexto médico profissional.
> *   Não utilizar placeholders "Lorem Ipsum" ou imagens genéricas de bancos gratuitos na versão final.

## 13. Arquivos e Entregáveis
*   **Logo:** `procordis_logo.svg` (Vetorizado).
*   **Código Fonte:** Repositório Git completo.
*   **Documentação:** Este arquivo (`docs/procordis_documentacao.md`).

---
**Fim da Documentação**
*(Atualizado automaticamente)*
