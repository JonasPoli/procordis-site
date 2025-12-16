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
        canvasOpacity = '0.4';
    }

    // Aplicar estilos ao Canvas
    canvas.style.mixBlendMode = blendMode;
    canvas.style.opacity = canvasOpacity;

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

    function animate() {
        ctx.clearRect(0, 0, width, height);

        time += speed;
        const pulse = getHumanHeartBeat(time);

        blobs.forEach(blob => {
            const x = width * blob.x;
            const y = height * blob.y;

            const driftX = Math.sin(time + blob.y) * 20;
            const driftY = Math.cos(time + blob.x) * 20;

            const baseRadius = Math.min(width, height) * blob.size;
            const beatExpansion = baseRadius * 0.25 * pulse;
            const currentRadius = baseRadius + beatExpansion;

            const gradient = ctx.createRadialGradient(
                x + driftX, y + driftY, 0,
                x + driftX, y + driftY, currentRadius
            );
            gradient.addColorStop(0, blob.color);
            gradient.addColorStop(1, 'rgba(0,0,0,0)');

            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(x + driftX, y + driftY, currentRadius, 0, Math.PI * 2);
            ctx.fill();
        });

        requestAnimationFrame(animate);
    }

    window.addEventListener('resize', resize);
    resize();
    animate();
}
