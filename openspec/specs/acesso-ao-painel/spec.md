# acesso-ao-painel Specification

## Purpose
Controla quem entra no painel `/admin` e o que cada usuário pode fazer. Há dois tipos de acesso: Administrador (`ROLE_ADMIN`, acesso total) e Jornalista (`ROLE_JORNALISTA`, só notícias, categorias de notícias e galeria). Implementado em `config/packages/security.yaml` (login por formulário e `access_control`), `Admin/UserController`, `Form/UserType`, `Entity/User` e no comando `app:admin-user`.

## Requirements
### Requirement: Login para o painel
O sistema SHALL exigir login por e-mail e senha (formulário em `/login`, com CSRF) para qualquer URL sob `/admin`. Um usuário já logado que abre `/login` SHALL ser levado ao painel.

#### Scenario: Visitante sem login
- **WHEN** um visitante não logado abre `/admin/news/`
- **THEN** ele é redirecionado para `/login`

### Requirement: Perfil Jornalista restrito às notícias
O sistema SHALL permitir ao Jornalista somente as URLs `/admin/news/...` (inclui a galeria) e `/admin/news-category/...`. Todas as demais URLs sob `/admin` MUST responder 403 para o Jornalista, inclusive envios POST de formulários do administrador.

#### Scenario: Jornalista na área de notícias
- **WHEN** um Jornalista abre `/admin/news/`, `/admin/news/new` ou `/admin/news-category/`
- **THEN** a página é exibida

#### Scenario: Jornalista em área de administrador
- **WHEN** um Jornalista abre `/admin/service/`, `/admin/usuarios/` ou `/admin/general-data/edit`, ou envia POST para `/admin/usuarios/novo`
- **THEN** a resposta é 403

### Requirement: Painel inicial por perfil
O sistema SHALL mostrar em `/admin/` a visão geral (contagem de notícias e serviços) só ao Administrador; o Jornalista SHALL ser redirecionado para a lista de notícias. O menu lateral do Jornalista SHALL mostrar apenas as áreas de notícias.

#### Scenario: Jornalista abre o painel inicial
- **WHEN** um Jornalista abre `/admin/`
- **THEN** ele é redirecionado para `/admin/news/`

### Requirement: Administrador tem acesso total
O sistema SHALL dar ao Administrador acesso a todas as áreas do painel, inclusive as do Jornalista, pela hierarquia `ROLE_ADMIN` → `ROLE_JORNALISTA` → `ROLE_USER`.

#### Scenario: Administrador nas notícias
- **WHEN** um Administrador abre `/admin/news/` ou `/admin/usuarios/`
- **THEN** a página é exibida

### Requirement: Cadastro de usuários do painel
O sistema SHALL permitir só ao Administrador criar, editar e excluir usuários em `/admin/usuarios`. O cadastro SHALL pedir e-mail, tipo de acesso (Administrador ou Jornalista, padrão Jornalista) e senha de no mínimo 8 caracteres digitada duas vezes. Na edição, a senha em branco SHALL manter a senha atual.

#### Scenario: Senhas diferentes
- **WHEN** o administrador cadastra um usuário com a confirmação de senha diferente da senha
- **THEN** o cadastro é recusado com "As senhas informadas não conferem."

#### Scenario: Troca do tipo de acesso
- **WHEN** o administrador muda um Jornalista para Administrador
- **THEN** o usuário passa a ter `ROLE_ADMIN` no lugar de `ROLE_JORNALISTA`, e outras roles que ele tinha são mantidas

### Requirement: Sempre existe um administrador
O sistema MUST NOT permitir excluir o único administrador nem rebaixá-lo a Jornalista. O usuário logado MUST NOT excluir a si mesmo nem mudar o próprio tipo de acesso (o campo aparece bloqueado).

#### Scenario: Rebaixar o único administrador
- **WHEN** o administrador tenta mudar para Jornalista o único usuário com `ROLE_ADMIN`
- **THEN** a gravação é recusada com "Este é o único administrador. Cadastre outro administrador antes de alterar o tipo deste usuário."

#### Scenario: Excluir a si mesmo
- **WHEN** o administrador logado tenta excluir o próprio usuário
- **THEN** nada é excluído e aparece "Você não pode excluir o seu próprio usuário."

### Requirement: Usuário pela linha de comando
O sistema SHALL oferecer o comando `app:admin-user <email> <senha>`, que cria o usuário ou, se o e-mail já existe, troca a senha e o tipo de acesso. Sem opção o tipo é Administrador; com `--jornalista` é Jornalista.

#### Scenario: E-mail já cadastrado
- **WHEN** o comando roda com o e-mail de um Jornalista existente e sem `--jornalista`
- **THEN** o usuário passa a ser Administrador, com a nova senha
