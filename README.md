# Procordis Site

## Sistema Visual de Coração Batendo (Heartbeat)

O site possui um sistema visual de plano de fundo que simula o batimento de um coração, apresentando 3 círculos (blobs) que pulsam e flutuam suavemente na tela, implementados no Canvas.

### Arquivos Envolvidos
- **Script:** `public/js/heartbeat.js`
- **Elemento HTML:** Um elemento `<canvas>` com um `id="heartbeat-bg"`.

### Como Funciona

#### 1. Estrutura e Geometria
O sistema utiliza a API nativa do Web Canvas (`CanvasRenderingContext2D`) para renderizar e animar os visuais. A base geométrica consiste na função `drawOrganicHeart`, que desenha um "caminho" vetorial (utilizando curvas `bezierCurveTo`) formatado intencionalmente para lembrar a anatomia assimétrica de um coração humano real (com estruturas sugerindo os átrios e ventrículos), afastando-se do formato de "emoji de coração". O desenho é centralizado na tela.

#### 2. Cálculo Matemático do Batimento (Ritmo)
A mágica do sistema é o simulador `getHumanHeartBeat(t)`, em que a matemática responsável pelo pulso emula o formato de contração do miocárdio:
A função calcula um ciclo baseado na variável `bpmSpeed` (1.3) combinando duas curvas de função Exponencial de pico (`Math.exp` com fatores fixos):
- **Sístole/Batimento 1:** `Math.exp(-500 * Math.pow(cycle - 0.15, 2))` (Primeira contração do coração no ciclo de pulso).
- **Diástole/Batimento 2:** `1.5 * Math.exp(-500 * Math.pow(cycle - 0.30, 2))` (Tensão secundária de relaxamento e expansão do sangue na cavidade, um poquinho maior na curva de expansão).
- A velocidade geral de deslocamento é controlada pelas contantes `speed` (0.01) adicionado na variável do tempo a cada frame.

#### 3. Renderização, Flutuação (Drift) e Expansão
A função `animate` é o *Gameloop* do sistema, chamada de forma otimizada a 60 FPS com a API `requestAnimationFrame()`.
Em cada frame do sistema:
1. O canvas é limpo usando `clearRect()`.
2. O "pulso" global do estágio exato do batimento do coração é retornado calculando o tempo passado sobre o `bpmSpeed`.
3. Para cada um dos três círculos (blobs):
   - **Gradiante Radial Espacial:** Cada círculo é preenchido através de uma luz central no eixo `X, Y` que vai da cor de base até ser totalmente transparente e sutil (`rgba(0,0,0,0)`) nas bordas externas do tamanho da bolha de luz calculada.
   - **Movimento (Drift):** Um deslocamento constante de flutuação orgânica nos vértices X e Y adicionado por Seno e Cosseno (`Math.sin(time + blob.y) * 20` e `Math.cos()`), garantindo deriva (drift) aleatória entre os 3 círculos independentemente com base na posição inicial aleatória em loop infinito sobre o tempo.
   - **Expansão Dinâmica:** O círculo sofre variação de tamanho, onde esse tamanho de base é interpolado por um aditivo de espasmo físico, a `beatExpansion`, que no ápice do "batimento" cresce o raio para até *25%* o seu tamanho base inicial.

#### 4. Suporte a Tema (Dark mode fallback)
O script verifica de forma inteligente se a classe root tem a anotação `dark` via `document.documentElement.classList.contains('dark')` e ajusta todo o comportamento visual de luminosidade e mistura:

- **Modo Escuro (Dark Mode):** Utiliza cores brancas com alfa (`rgba(255, 255, 255, alpha)`) até uma refência opaca de cerca de 40%, blend mode `overlay` nativo do context, com renderização massiva do canvas a `0.8`, injetando glow na luz preta.
- **Modo Claro (Light Mode):** Utiliza cores base cinzas puras com pouca transparência (e.g., `'rgba(80, 80, 80, 0.5)'`), usando blendMode como `multiply` com opacidade contida de `0.4`, fornecendo uma sombra color burn amigável e limpa.
