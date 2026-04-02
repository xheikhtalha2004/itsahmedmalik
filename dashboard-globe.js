const dashboardMarkers = [
  { id: "islamabad", location: [33.6844, 73.0479] },
  { id: "london", location: [51.5072, -0.1276] },
  { id: "new-york", location: [40.7128, -74.006] },
  { id: "singapore", location: [1.3521, 103.8198] },
];

const dashboardArcs = [
  { from: [33.6844, 73.0479], to: [51.5072, -0.1276] },
  { from: [33.6844, 73.0479], to: [40.7128, -74.006] },
  { from: [33.6844, 73.0479], to: [1.3521, 103.8198] },
];

async function mountDashboardGlobe() {
  const canvas = document.querySelector("[data-dashboard-globe]");
  if (!(canvas instanceof HTMLCanvasElement)) return;

  try {
    const { default: createGlobe } = await import("https://esm.sh/cobe?bundle");
    const pointerState = { x: 0, y: 0, active: false };
    const dragOffset = { phi: 0, theta: 0 };
    let phiOffset = 0;
    let thetaOffset = 0;
    let isPaused = false;
    let phi = 0;
    let globe = null;
    let animationFrameId = 0;

    const getSize = () => Math.max(canvas.offsetWidth, 1);

    const handlePointerDown = (event) => {
      pointerState.x = event.clientX;
      pointerState.y = event.clientY;
      pointerState.active = true;
      isPaused = true;
      canvas.style.cursor = "grabbing";
    };

    const handlePointerMove = (event) => {
      if (!pointerState.active) return;

      dragOffset.phi = (event.clientX - pointerState.x) / 300;
      dragOffset.theta = (event.clientY - pointerState.y) / 1000;
    };

    const handlePointerUp = () => {
      if (pointerState.active) {
        phiOffset += dragOffset.phi;
        thetaOffset += dragOffset.theta;
      }

      pointerState.active = false;
      dragOffset.phi = 0;
      dragOffset.theta = 0;
      isPaused = false;
      canvas.style.cursor = "grab";
    };

    const handleResize = () => {
      if (!globe) return;
      const size = getSize();
      globe.update({ width: size, height: size });
    };

    const initGlobe = () => {
      if (globe || !canvas.offsetWidth) return;

      const size = getSize();

      globe = createGlobe(canvas, {
        devicePixelRatio: Math.min(window.devicePixelRatio || 1, 2),
        width: size,
        height: size,
        phi: 0,
        theta: 0.24,
        dark: 1,
        diffuse: 1.4,
        mapSamples: 16000,
        mapBrightness: 10,
        baseColor: [0.32, 0.34, 0.38],
        markerColor: [0.2, 0.8, 0.9],
        glowColor: [0.05, 0.08, 0.12],
        markerElevation: 0.02,
        markers: dashboardMarkers.map((marker) => ({
          id: marker.id,
          location: marker.location,
          size: 0.03,
        })),
        arcs: dashboardArcs,
        arcColor: [0.3, 0.85, 0.95],
        arcWidth: 0.55,
        arcHeight: 0.24,
        opacity: 0.7,
      });

      const animate = () => {
        if (!isPaused) {
          phi += 0.004;
        }

        globe.update({
          phi: phi + phiOffset + dragOffset.phi,
          theta: 0.24 + thetaOffset + dragOffset.theta,
        });

        animationFrameId = window.requestAnimationFrame(animate);
      };

      animate();
      canvas.classList.add("is-ready");
    };

    const resizeObserver = new ResizeObserver(() => {
      if (globe) {
        handleResize();
        return;
      }

      initGlobe();
    });

    canvas.addEventListener("pointerdown", handlePointerDown);
    window.addEventListener("pointermove", handlePointerMove, { passive: true });
    window.addEventListener("pointerup", handlePointerUp, { passive: true });
    window.addEventListener("resize", handleResize, { passive: true });
    resizeObserver.observe(canvas);
    initGlobe();

    const cleanup = () => {
      canvas.removeEventListener("pointerdown", handlePointerDown);
      window.removeEventListener("pointermove", handlePointerMove);
      window.removeEventListener("pointerup", handlePointerUp);
      window.removeEventListener("resize", handleResize);
      resizeObserver.disconnect();

      if (animationFrameId) {
        window.cancelAnimationFrame(animationFrameId);
      }

      if (globe) {
        globe.destroy();
      }
    };

    window.addEventListener("pagehide", cleanup, { once: true });
  } catch (error) {
    console.error("Dashboard globe failed to initialize.", error);
    canvas.classList.add("is-ready");
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", mountDashboardGlobe, { once: true });
} else {
  mountDashboardGlobe();
}
