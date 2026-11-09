# galeria-de-noticias Specification

## Purpose
Cada notícia tem uma galeria de imagens e vídeos do YouTube, gerenciada no painel por Jornalistas e Administradores e exibida na página da notícia como slideshow com lightbox. Implementado em `Admin/NewsGalleryController` (ações AJAX), `Service/Gallery/*` (`NewsGalleryManager`, `GalleryImageProcessor`, `GalleryStorage`, `YouTubeClient`, `YouTubeUrlParser`), `Entity/NewsGalleryItem`, `EventListener/GalleryFileCleanupListener` e `templates/news/_gallery.html.twig`. Os arquivos ficam em `public/images/news-gallery/{id da notícia}/`, fora do git.

## Requirements
### Requirement: Ações do painel protegidas
O sistema SHALL aceitar as ações da galeria (`/admin/news/{id}/gallery/...`) só de Jornalista ou Administrador logado e com o token CSRF `admin_ajax` no cabeçalho `X-CSRF-Token`. Sem o token, a ação MUST responder 403 sem alterar nada. Item de outra notícia MUST responder 404 ("Item não encontrado nesta notícia.").

#### Scenario: Token vencido
- **WHEN** o jornalista envia uma imagem com a sessão expirada
- **THEN** a resposta é 403 com "Sua sessão expirou ou o formulário ficou desatualizado. Recarregue a página e tente novamente."

### Requirement: Envio de imagens
O sistema SHALL aceitar uma imagem por requisição, só nos formatos JPG, PNG ou WebP, com no máximo 15 MB ou o limite de upload do PHP, o que for menor, e até 50 megapixels. Imagem fora dessas regras MUST ser recusada com a mensagem do motivo, sem gravar nada. A imagem aceita SHALL entrar no fim da galeria, ativa.

#### Scenario: Formato não aceito
- **WHEN** o jornalista envia um GIF
- **THEN** a resposta é "Formato não aceito. Envie imagens JPG, PNG ou WebP."

#### Scenario: Foto com resolução muito alta
- **WHEN** o jornalista envia uma foto com mais de 50 megapixels
- **THEN** a resposta pede para reduzir a foto, informando as dimensões e o limite

#### Scenario: Arquivo maior que o limite do servidor
- **WHEN** o arquivo passa do post_max_size do PHP e chega sem conteúdo
- **THEN** a resposta pede uma imagem com no máximo o limite efetivo, em MB

### Requirement: Versões geradas da imagem
O sistema SHALL gravar de cada imagem enviada as versões thumb (lado maior até 400 px), medium (até 1200 px) e large (até 2400 px), em WebP quando o servidor suporta, ou em JPG/PNG conforme o original. A orientação EXIF SHALL ser aplicada e os metadados removidos antes de redimensionar. O nome do arquivo SHALL ser o nome original em slug (até 40 caracteres) mais um sufixo aleatório.

#### Scenario: Foto de celular deitada
- **WHEN** o jornalista envia uma foto JPG com orientação EXIF de 90 graus
- **THEN** as versões gravadas ficam em pé, como a foto foi tirada

### Requirement: Vídeos do YouTube
O sistema SHALL reconhecer o ID do vídeo nos links youtube.com/watch, youtu.be, /shorts, /embed, /live, /v, m., music. e youtube-nocookie.com, com ou sem https e parâmetros extras. Antes de gravar, SHALL consultar o oEmbed do YouTube; vídeo privado, sem permissão de incorporação ou inexistente MUST ser recusado com a mensagem do motivo. A legenda em branco SHALL receber o título do vídeo, e a capa SHALL ser baixada (maxres, sd ou hq), sem as faixas pretas, em thumb e medium.

#### Scenario: Vídeo que não permite incorporação
- **WHEN** o jornalista cola o link de um vídeo cujo dono não permite exibir em outros sites
- **THEN** a resposta é "Este vídeo é privado ou o dono não permite que ele seja exibido em outros sites."
- **AND** nada é gravado

### Requirement: Pré-visualização dos links colados
O sistema SHALL pré-visualizar até 20 links colados de uma vez (separados por linha, espaço, vírgula ou ponto e vírgula), sem gravar nada, mostrando para cada um o ID, o título, a capa, o erro e se o vídeo já está na galeria. Links repetidos na colagem SHALL aparecer uma vez só. A gravação do vídeo MUST NOT conferir duplicidade: o aviso de repetido existe só na pré-visualização.

#### Scenario: Vídeo já na galeria
- **WHEN** o jornalista cola o link de um vídeo que já está na galeria desta notícia
- **THEN** a pré-visualização marca o vídeo como repetido

### Requirement: Legenda, ordem e Ativo dos itens
O sistema SHALL permitir editar a legenda (até 500 caracteres; vazia vira sem legenda), reordenar os itens arrastando e ligar ou desligar cada item. Na reordenação, IDs de outra notícia SHALL ser ignorados e os itens não enviados SHALL ir para o fim.

#### Scenario: Legenda longa
- **WHEN** o jornalista grava uma legenda com mais de 500 caracteres
- **THEN** a resposta é "A legenda pode ter no máximo 500 caracteres."

### Requirement: Exclusão apaga os arquivos
O sistema SHALL apagar do disco todas as versões de um item depois que a exclusão for gravada no banco, tanto ao excluir o item quanto ao excluir a notícia inteira, e SHALL remover a pasta da notícia quando ela ficar vazia. Se a gravação de um item novo falhar, os arquivos já gerados MUST ser apagados.

#### Scenario: Notícia excluída
- **WHEN** o jornalista exclui uma notícia com 10 itens na galeria
- **THEN** os itens saem do banco e os arquivos deles saem de `public/images/news-gallery/{id}/`

### Requirement: Galeria na página da notícia
O sistema SHALL mostrar na página pública da notícia só os itens ativos, na ordem do painel, num slideshow com lightbox. O vídeo SHALL abrir no lightbox pelo youtube-nocookie.com, e o iframe só é criado quando o slide do vídeo fica ativo. Sem itens ativos, a galeria não aparece.

#### Scenario: Item desligado
- **WHEN** o jornalista desliga um item da galeria
- **THEN** o item some da página pública da notícia e continua no painel
