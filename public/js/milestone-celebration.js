(() => {
    'use strict';

    const celebrationModal = document.querySelector('[data-milestone-celebration]');
    const confettiCanvas = celebrationModal?.querySelector('[data-milestone-confetti]');

    if (!celebrationModal || !confettiCanvas || typeof celebrationModal.showModal !== 'function') {
        return;
    }

    const celebrationPanel = celebrationModal.querySelector('.milestone-celebration-panel');
    const confettiContext = confettiCanvas.getContext('2d');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const confettiColors = ['#f4c542', '#25a460', '#e85d75', '#4d96ff', '#9b5de5', '#ff8c42'];
    let confettiPieces = [];
    let animationFrameId = null;
    let previousFrameTimestamp = 0;

    const randomNumberBetween = (minimum, maximum) => minimum + Math.random() * (maximum - minimum);

    const resizeConfettiCanvas = () => {
        const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
        const canvasWidth = celebrationModal.clientWidth;
        const canvasHeight = celebrationModal.clientHeight;

        confettiCanvas.width = Math.round(canvasWidth * pixelRatio);
        confettiCanvas.height = Math.round(canvasHeight * pixelRatio);
        confettiCanvas.style.width = `${canvasWidth}px`;
        confettiCanvas.style.height = `${canvasHeight}px`;
        confettiContext.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
    };

    const createConfettiPiece = (startsAboveViewport = false) => ({
        x: randomNumberBetween(0, celebrationModal.clientWidth),
        y: startsAboveViewport
            ? randomNumberBetween(-celebrationModal.clientHeight, -10)
            : randomNumberBetween(0, celebrationModal.clientHeight),
        width: randomNumberBetween(6, 12),
        height: randomNumberBetween(9, 17),
        color: confettiColors[Math.floor(Math.random() * confettiColors.length)],
        fallingSpeed: randomNumberBetween(90, 210),
        horizontalSpeed: randomNumberBetween(-45, 45),
        rotation: randomNumberBetween(0, Math.PI * 2),
        rotationSpeed: randomNumberBetween(-5, 5),
        oscillationOffset: randomNumberBetween(0, Math.PI * 2),
    });

    const resetConfettiPiece = (confettiPiece) => {
        Object.assign(confettiPiece, createConfettiPiece(true), {
            y: randomNumberBetween(-80, -10),
        });
    };

    const drawConfetti = (timestamp) => {
        const elapsedSeconds = previousFrameTimestamp === 0
            ? 0
            : Math.min((timestamp - previousFrameTimestamp) / 1000, 0.04);
        previousFrameTimestamp = timestamp;

        confettiContext.clearRect(0, 0, celebrationModal.clientWidth, celebrationModal.clientHeight);

        confettiPieces.forEach((confettiPiece) => {
            confettiPiece.y += confettiPiece.fallingSpeed * elapsedSeconds;
            confettiPiece.x += (
                confettiPiece.horizontalSpeed
                + Math.sin(timestamp / 450 + confettiPiece.oscillationOffset) * 18
            ) * elapsedSeconds;
            confettiPiece.rotation += confettiPiece.rotationSpeed * elapsedSeconds;

            if (
                confettiPiece.y > celebrationModal.clientHeight + 20
                || confettiPiece.x < -40
                || confettiPiece.x > celebrationModal.clientWidth + 40
            ) {
                resetConfettiPiece(confettiPiece);
            }

            confettiContext.save();
            confettiContext.translate(confettiPiece.x, confettiPiece.y);
            confettiContext.rotate(confettiPiece.rotation);
            confettiContext.fillStyle = confettiPiece.color;
            confettiContext.fillRect(
                -confettiPiece.width / 2,
                -confettiPiece.height / 2,
                confettiPiece.width,
                confettiPiece.height,
            );
            confettiContext.restore();
        });

        animationFrameId = window.requestAnimationFrame(drawConfetti);
    };

    const startConfettiAnimation = () => {
        resizeConfettiCanvas();

        if (prefersReducedMotion) {
            return;
        }

        const confettiPieceCount = Math.min(180, Math.max(90, Math.round(window.innerWidth / 8)));
        confettiPieces = Array.from({length: confettiPieceCount}, () => createConfettiPiece());
        previousFrameTimestamp = 0;
        animationFrameId = window.requestAnimationFrame(drawConfetti);
    };

    const stopConfettiAnimation = () => {
        if (animationFrameId !== null) {
            window.cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }
    };

    celebrationModal.addEventListener('click', (event) => {
        if (event.target.closest('[data-milestone-celebration-close]')) {
            celebrationModal.close();
            return;
        }

        if (!celebrationPanel.contains(event.target)) {
            celebrationModal.close();
        }
    });

    celebrationModal.addEventListener('close', stopConfettiAnimation);
    window.addEventListener('resize', resizeConfettiCanvas, {passive: true});

    celebrationModal.showModal();
    startConfettiAnimation();
})();
