const canvas = document.getElementById('heartbeat-bg');
// Ensure canvas exists before context
if (canvas) {
    const ctx = canvas.getContext('2d');

    let width, height;
    let time = 0;

    // === CONFIGURAÇÕES DO RITMO ===
    const speed = 0.01;
    const bpmSpeed = 1.3;

    // Detectar Tema
    const isDark = document.documentElement.classList.contains('dark');

    // Configurações por Tema
    let colors, blendMode, canvasOpacity;

    if (isDark) {
        // MODO DARK: Luz Branca (Overlay)
        colors = [
            'rgba(255, 255, 255, 0.4)',   // Mais forte (era 0.15)
            'rgba(255, 255, 255, 0.3)',
            'rgba(255, 255, 255, 0.15)'
        ];
        blendMode = 'overlay';
        canvasOpacity = '0.8'; // Aumentado significativamente
    } else {
        // MODO LIGHT: Cinza (Multiply)
        colors = [
            'rgba(80, 80, 80, 0.5)',      // Mais escuro e opaco
            'rgba(120, 120, 120, 0.4)',
            'rgba(180, 180, 180, 0.3)'
        ];
        blendMode = 'multiply';
        canvasOpacity = '0.3';
    }

    // Aplicar estilos ao Canvas
    canvas.style.mixBlendMode = blendMode;
    canvas.style.opacity = canvasOpacity;
    // Adiciona o blur aumentado em vez de depender de uma classe fixa do Twig
    canvas.style.filter = 'blur(10px)';

    const blobs = [
        { x: 0.3, y: 0.4, size: 0.2, color: colors[0] },
        { x: 0.7, y: 0.3, size: 0.3, color: colors[1] },
        { x: 0.5, y: 0.7, size: 0.3, color: colors[2] }
    ];

    function resize() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    }

    function getHumanHeartBeat(t) {
        let cycle = (t * bpmSpeed) % 1;
        let beat1 = Math.exp(-500 * Math.pow(cycle - 0.15, 2));
        let beat2 = 1.5 * Math.exp(-500 * Math.pow(cycle - 0.30, 2));
        return beat1 + beat2;
    }

    function drawOrganicHeart(ctx, x, y, size, pulse, color) {
        ctx.save();
        ctx.translate(x, y);

        // A escala base aumenta levemente de acordo com o pulso
        const scale = size * (1 + pulse * 0.15);
        ctx.scale(scale, scale);

        // Path que imita o formato orgânico de um coração humano (mais cônico, levemente inclinado)
        // Valores de curva desenhados para não serem um "Coração Emoji", mas simétricos de órgão
        ctx.beginPath();
        ctx.moveTo(0, -20); // Topo médio

        // Cúspide Superior Direita (Átrio direito/Aorta área)
        ctx.bezierCurveTo(30, -35, 60, -10, 50, 20);

        // Descida do Ventrículo Direito até o Ápice Inferior
        ctx.bezierCurveTo(40, 50, 15, 80, -10, 90);

        // Subida do Ventrículo Esquerdo
        ctx.bezierCurveTo(-45, 75, -60, 40, -50, 10);

        // Cúspide Superior Esquerda (Átrio esquerdo)
        ctx.bezierCurveTo(-40, -20, -15, -30, 0, -20);

        ctx.closePath();

        // O Gradiente continua o mesmo conceito, preenchendo o path
        const gradient = ctx.createRadialGradient(0, 15, 0, 0, 15, 90);
        gradient.addColorStop(0, color);
        gradient.addColorStop(1, 'rgba(0,0,0,0)');

        ctx.fillStyle = gradient;
        ctx.fill();

        ctx.restore();
    }

    function animate() {
        ctx.clearRect(0, 0, width, height);

        time += speed;
        const pulse = getHumanHeartBeat(time);

        // Daremos base no centro da tela para um "coração" único e forte
        const centerX = width * 0.5;
        const centerY = height * 0.45;

        // O coração principal recebe movimento orgânico (drift)
        const driftX = Math.sin(time * 0.5) * 15;
        const driftY = Math.cos(time * 0.3) * 15;

        const baseSize = Math.min(width, height) * 0.0035;

        drawOrganicHeart(
            ctx,
            centerX + driftX,
            centerY + driftY,
            baseSize,
            pulse,
            colors[0]
        );

        requestAnimationFrame(animate);
    }

    window.addEventListener('resize', resize);
    resize();
    animate();
}
