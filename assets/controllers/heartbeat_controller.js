import { Controller } from '@hotwired/stimulus';

// === CONFIGURAÇÕES DO RITMO ===
const SPEED = 0.01;
const BPM_SPEED = 1.3;

// Configurações por tema
const THEMES = {
    // MODO DARK: luz branca (overlay)
    dark: {
        colors: [
            'rgba(255, 255, 255, 0.4)',
            'rgba(255, 255, 255, 0.3)',
            'rgba(255, 255, 255, 0.15)',
        ],
        blendMode: 'overlay',
        canvasOpacity: '0.8',
    },
    // MODO LIGHT: cinza (multiply)
    light: {
        colors: [
            'rgba(80, 80, 80, 0.5)',
            'rgba(120, 120, 120, 0.4)',
            'rgba(180, 180, 180, 0.3)',
        ],
        blendMode: 'multiply',
        canvasOpacity: '0.3',
    },
};

function getHumanHeartBeat(t) {
    const cycle = (t * BPM_SPEED) % 1;
    const beat1 = Math.exp(-500 * Math.pow(cycle - 0.15, 2));
    const beat2 = 1.5 * Math.exp(-500 * Math.pow(cycle - 0.30, 2));

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

    // O gradiente preenche o path
    const gradient = ctx.createRadialGradient(0, 15, 0, 0, 15, 90);
    gradient.addColorStop(0, color);
    gradient.addColorStop(1, 'rgba(0,0,0,0)');

    ctx.fillStyle = gradient;
    ctx.fill();

    ctx.restore();
}

/*
 * Coração batendo no fundo do site público (<canvas data-controller="heartbeat">). Ver README.md.
 *
 * A animação começa quando o canvas entra na página e para (requestAnimationFrame e listener de
 * resize removidos) quando ele sai, então não acumula loops nem listeners entre navegações.
 */
export default class extends Controller {
    connect() {
        const canvas = this.element;
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }

        // Detectar tema
        const { colors, blendMode, canvasOpacity } = document.documentElement.classList.contains('dark')
            ? THEMES.dark
            : THEMES.light;

        // Aplicar estilos ao canvas (o blur fica aqui em vez de depender de uma classe fixa do Twig)
        canvas.style.mixBlendMode = blendMode;
        canvas.style.opacity = canvasOpacity;
        canvas.style.filter = 'blur(10px)';

        let width;
        let height;
        let time = 0;

        this.resize = () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        };

        const animate = () => {
            ctx.clearRect(0, 0, width, height);

            time += SPEED;
            const pulse = getHumanHeartBeat(time);

            // Base no centro da tela para um "coração" único e forte
            const centerX = width * 0.5;
            const centerY = height * 0.45;

            // O coração principal recebe movimento orgânico (drift)
            const driftX = Math.sin(time * 0.5) * 15;
            const driftY = Math.cos(time * 0.3) * 15;

            const baseSize = Math.min(width, height) * 0.0035;

            drawOrganicHeart(ctx, centerX + driftX, centerY + driftY, baseSize, pulse, colors[0]);

            this.frame = window.requestAnimationFrame(animate);
        };

        window.addEventListener('resize', this.resize);
        this.resize();
        animate();
    }

    disconnect() {
        window.cancelAnimationFrame(this.frame);
        if (this.resize) {
            window.removeEventListener('resize', this.resize);
        }
    }
}
