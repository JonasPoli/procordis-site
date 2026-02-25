# Fluxo de Trabalho Antigravity: Gerente, Desenvolvedor e Tester

Este documento explica como utilizar o **Antigravity** para simular uma equipe completa de desenvolvimento (Gerente, Desenvolvedor e Tester) para o projeta Procordis.

O Antigravity é um agente de IA "full-stack" que pode assumir diferentes "personas" através de seus modos de operação. Abaixo descrevemos como orquestrar esses papéis.

## 1. O Agente Gerente (Role: Manager)
**Objetivo:** Planejamento, quebra de tarefas e acompanhamento do progresso.

No Antigravity, o papel de gerente é exercido através do **Modo de Planejamento (PLANNING)** e da gestão de artefatos.

### Como atuar como Gerente:
1.  **Criar/Atualizar o `task.md`**:
    *   O Gerente define o "backlog" do projeto no arquivo `task.md`.
    *   Quebre grandes funcionalidades em tarefas menores e executáveis.
    *   Exemplo: Em vez de "Fazer o site", use "Configurar Symfony", "Criar Entidade Notícia", "Criar Layout Base".
2.  **Criar Planos de Implementação (`implementation_plan.md`)**:
    *   Antes de qualquer código ser escrito, o Gerente deve criar um plano detalhado.
    *   Analise o documento `docs/procordis_documentacao.md`.
    *   Defina quais arquivos serão criados, quais bibliotecas serão instaladas e qual a lógica do sistema.
    *   Peça validação do usuário (você) antes de passar para o Desenvolvedor.
3.  **Coordenação**:
    *   O Gerente decide qual é a próxima tarefa prioritária e instrui a mudança para o modo de Execução.

---

## 2. O Agente Desenvolvedor (Role: Developer)
**Objetivo:** Execução técnica, escrita de código e implementação de funcionalidades.

Este papel é ativado quando mudamos para o **Modo de Execução (EXECUTION)**.

### Como atuar como Desenvolvedor:
1.  **Seguir o Plano**:
    *   O Desenvolvedor lê o `implementation_plan.md` aprovado pelo Gerente e o executa fielmente.
2.  **Escrever Código (`write_to_file`, `replace_file_content`)**:
    *   Criação de Controllers, Entidades, Templates Twig e arquivos CSS/JS.
    *   Configuração de serviços (AWS S3, WMailer, etc.).
3.  **Executar Comandos (`run_command`)**:
    *   Rodar `composer install`, `php bin/console make:entity`, `npm run build`, etc.
4.  **Resolução de Problemas**:
    *   Se encontrar um erro técnico, o Desenvolvedor tenta corrigir. Se o erro for de arquitetura, ele deve reportar ao Gerente (voltar ao Planning).

---

## 3. O Agente Tester (Role: Tester)
**Objetivo:** Verificação de qualidade, testes automatizados e validação visual.

Este papel é ativado no **Modo de Verificação (VERIFICATION)**.

### Como atuar como Tester:
1.  **Testes Automatizados**:
    *   Rodar PHPUnit ou testes do Symfony: `php bin/phpunit`.
    *   Verificar sintaxe e linting.
2.  **Verificação Visual (`browser_subagent`)**:
    *   O Tester pode abrir um navegador real para acessar o site localmente.
    *   Verificar se o layout está fiel ao design (TailwindCSS, responsividade).
    *   Testar fluxos de usuário (ex: preencher formulário de contato, fazer login no admin).
3.  **Relatório de Walkthrough (`walkthrough.md`)**:
    *   Após concluir uma tarefa, o Tester gera um artefato `walkthrough.md` com "provas" do funcionamento (screenshots, logs de sucesso).

---

## Exemplo de Ciclo de Trabalho (Workflow)

Para desenvolver o **CRUD de Notícias**, por exemplo:

1.  **GERENTE (Planning)**:
    *   Analisa os requisitos em `procordis_documentacao.md`.
    *   Atualiza `task.md`: `[ ] Implementar CRUD de Notícias`.
    *   Escreve `implementation_plan.md`: Detalha criação da Entidade `News`, Controller Admin, e Templates.

2.  **DESENVOLVEDOR (Execution)**:
    *   Roda `make:entity News`.
    *   Cria `NewsCrudController.php`.
    *   Cria templates Twig com Tailwind.
    *   Roda as migrations.

3.  **TESTER (Verification)**:
    *   Acessa `/admin/news` usando o navegador.
    *   Tenta criar uma notícia nova.
    *   Verifica se a imagem foi para o S3.
    *   Confirma se apareceu na listagem.
    *   Atualiza `task.md` para `[x] Implementar CRUD de Notícias`.

---

## Como Iniciar Agora?

Para começar o projeta Procordis seguindo este modelo, dê o seguinte comando para o Antigravity:

> "Atue como **Gerente**. Leia a documentação completa, crie o `task.md` inicial com todas as etapas do projeto e gere o primeiro `implementation_plan.md` para a configuração inicial do ambiente (Symfony + Tailwind + Banco de Dados)."
