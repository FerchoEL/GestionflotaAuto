<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Flota | Junta Directiva</title>
    <style>
        :root {
            --bg: #07121d;
            --bg-2: #0d2235;
            --panel: rgba(8, 24, 38, 0.82);
            --panel-strong: rgba(10, 31, 48, 0.94);
            --line: rgba(255, 255, 255, 0.12);
            --text: #f2f6fb;
            --muted: #9eb0c3;
            --gold: #f2b64d;
            --cyan: #67dafc;
            --green: #70e0a3;
            --danger: #ff8f7a;
            --shadow: 0 30px 80px rgba(0, 0, 0, 0.36);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            background:
                linear-gradient(145deg, rgba(4, 12, 21, 0.96), rgba(8, 21, 34, 0.88)),
                url('/images/flota-hero.jpg') center/cover no-repeat fixed;
            color: var(--text);
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
        }

        body {
            overflow: hidden;
        }

        .presentation {
            position: relative;
            min-height: 100vh;
        }

        .toolbar {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 30;
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: rgba(6, 17, 28, 0.76);
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow);
        }

        .toolbar button {
            border: 0;
            border-radius: 999px;
            padding: 10px 14px;
            font: inherit;
            font-weight: 700;
            color: var(--text);
            background: rgba(255, 255, 255, 0.08);
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease, opacity 0.2s ease;
        }

        .toolbar button:hover {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.14);
        }

        .toolbar button:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
        }

        .counter {
            min-width: 62px;
            text-align: center;
            font-size: 14px;
            color: var(--muted);
        }

        .slides {
            height: 100vh;
            overflow: hidden;
        }

        .track {
            height: 100%;
            transition: transform 0.55s ease;
        }

        .slide {
            height: 100vh;
            padding: 34px;
        }

        .frame {
            position: relative;
            height: 100%;
            max-width: 1440px;
            margin: 0 auto;
            padding: 38px 42px;
            border-radius: 30px;
            overflow: auto;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background:
                radial-gradient(circle at top right, rgba(103, 218, 252, 0.18), transparent 26%),
                radial-gradient(circle at left bottom, rgba(112, 224, 163, 0.14), transparent 24%),
                linear-gradient(145deg, rgba(5, 15, 26, 0.92), rgba(10, 27, 42, 0.86));
            box-shadow: var(--shadow);
        }

        .frame::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(90deg, rgba(5, 14, 25, 0.82) 0%, rgba(5, 14, 25, 0.55) 48%, rgba(5, 14, 25, 0.28) 100%);
        }

        .content {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 28px;
        }

        .hero {
            grid-template-columns: 1.15fr 0.85fr;
            align-items: start;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }

        .brand img {
            width: 64px;
            height: auto;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--gold);
            background: rgba(242, 182, 77, 0.12);
            border: 1px solid rgba(242, 182, 77, 0.22);
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        .title {
            margin-top: 18px;
            max-width: 760px;
            font-size: clamp(40px, 4.8vw, 70px);
            line-height: 0.98;
            letter-spacing: -0.045em;
        }

        .lead {
            margin-top: 18px;
            max-width: 760px;
            font-size: clamp(18px, 1.55vw, 24px);
            line-height: 1.5;
            color: var(--muted);
        }

        .thesis {
            margin-top: 24px;
            max-width: 740px;
            padding-left: 18px;
            border-left: 4px solid var(--cyan);
            font-size: clamp(22px, 1.95vw, 30px);
            line-height: 1.28;
            font-weight: 700;
        }

        .card,
        .mini-card,
        .pillar,
        .stat,
        .message,
        .kpi {
            border: 1px solid var(--line);
            background: var(--panel);
            backdrop-filter: blur(10px);
            border-radius: 22px;
        }

        .card {
            padding: 22px;
        }

        .side-stack {
            display: grid;
            gap: 18px;
        }

        .label {
            margin-bottom: 12px;
            font-size: 13px;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 800;
        }

        .metric {
            font-size: clamp(40px, 4vw, 58px);
            font-weight: 800;
            line-height: 0.95;
            letter-spacing: -0.05em;
        }

        .subtext {
            margin-top: 10px;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.5;
        }

        .bullet-list {
            display: grid;
            gap: 14px;
        }

        .bullet {
            padding-top: 14px;
            border-top: 1px solid var(--line);
        }

        .bullet:first-child {
            padding-top: 0;
            border-top: 0;
        }

        .bullet strong {
            display: block;
            margin-bottom: 6px;
            font-size: 18px;
        }

        .bullet span {
            color: var(--muted);
            line-height: 1.45;
        }

        .message {
            padding: 24px;
            background: linear-gradient(180deg, rgba(13, 40, 61, 0.95), rgba(7, 23, 37, 0.92));
        }

        .message p:first-child {
            margin-bottom: 10px;
            font-size: clamp(26px, 2.3vw, 34px);
            font-weight: 800;
            line-height: 1.12;
        }

        .message p:last-child {
            font-size: clamp(19px, 1.45vw, 23px);
            line-height: 1.45;
            color: var(--text);
        }

        .footer-band {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 6px;
        }

        .mini-card {
            padding: 16px 18px;
            background: linear-gradient(90deg, rgba(103, 218, 252, 0.12), rgba(112, 224, 163, 0.12));
        }

        .mini-card strong {
            color: var(--green);
        }

        .grid-2,
        .grid-3,
        .grid-4 {
            display: grid;
            gap: 18px;
        }

        .grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .grid-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .pillar {
            padding: 20px;
        }

        .pillar h3 {
            margin-bottom: 10px;
            font-size: 21px;
        }

        .pillar p {
            color: var(--muted);
            line-height: 1.55;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: end;
        }

        .section-head h2 {
            font-size: clamp(32px, 3vw, 48px);
            letter-spacing: -0.04em;
            line-height: 1;
        }

        .section-head p {
            max-width: 680px;
            color: var(--muted);
            line-height: 1.5;
            font-size: 18px;
        }

        .stat {
            padding: 22px;
        }

        .stat .number {
            font-size: clamp(28px, 3vw, 46px);
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .stat .caption {
            margin-top: 10px;
            color: var(--muted);
            line-height: 1.45;
        }

        .kpi {
            padding: 22px;
            background: rgba(255, 255, 255, 0.05);
        }

        .kpi h3 {
            margin-bottom: 8px;
            font-size: 19px;
        }

        .kpi p {
            color: var(--muted);
            line-height: 1.5;
        }

        .quote {
            padding: 26px;
            border-radius: 24px;
            border: 1px solid rgba(103, 218, 252, 0.2);
            background: linear-gradient(145deg, rgba(10, 32, 48, 0.95), rgba(6, 18, 30, 0.92));
        }

        .quote p:first-child {
            font-size: clamp(24px, 2.2vw, 34px);
            font-weight: 800;
            line-height: 1.18;
        }

        .quote p:last-child {
            margin-top: 12px;
            color: var(--muted);
            line-height: 1.55;
            font-size: 18px;
        }

        .risk {
            padding: 20px;
            border-radius: 20px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.05);
        }

        .risk strong {
            display: block;
            margin-bottom: 8px;
            font-size: 18px;
        }

        .risk span {
            color: var(--muted);
            line-height: 1.45;
        }

        .accent-gold {
            color: var(--gold);
        }

        .accent-cyan {
            color: var(--cyan);
        }

        .accent-danger {
            color: var(--danger);
        }

        @media (max-width: 1180px) {
            body {
                overflow: auto;
            }

            .toolbar {
                left: 14px;
                right: 14px;
                top: auto;
                bottom: 14px;
                justify-content: center;
            }

            .slides {
                height: auto;
                overflow: visible;
            }

            .track {
                height: auto;
                transform: none !important;
                transition: none;
            }

            .slide {
                height: auto;
                min-height: auto;
                padding: 18px 12px;
            }

            .frame {
                height: auto;
                min-height: auto;
                padding: 28px 20px 90px;
                overflow: visible;
                border-radius: 24px;
            }

            .hero,
            .grid-2,
            .grid-3,
            .grid-4,
            .footer-band {
                grid-template-columns: 1fr;
            }

            .section-head {
                align-items: start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main class="presentation" data-presentation>
        <div class="toolbar">
            <button type="button" data-prev>Anterior</button>
            <div class="counter"><span data-current>1</span> / <span data-total>4</span></div>
            <button type="button" data-next>Siguiente</button>
        </div>

        <div class="slides">
            <div class="track" data-track>
                <section class="slide" data-slide>
                    <div class="frame">
                        <div class="content hero">
                            <div>
                                <div class="brand">
                                    <img src="/images/logokp.png" alt="Logo KP">
                                    <div>
                                        <div style="font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; color: var(--muted);">Presentación Ejecutiva</div>
                                        <div style="font-size: 22px; font-weight: 800;">Plataforma Integral de Flota</div>
                                    </div>
                                </div>

                                <div class="eyebrow">Propuesta de valor para Junta Directiva</div>
                                <h1 class="title">Convertimos la flota en una operación controlada, visible y financieramente predecible.</h1>
                                <p class="lead">
                                    El sistema concentra gestión de vehículos, combustible, fondeo operativo y financiero, alertas automáticas, trazabilidad por responsable, control documental y reportes ejecutivos exportables en una sola plataforma.
                                </p>
                                <div class="thesis">
                                    Pasamos de reaccionar a fugas, vencimientos y faltantes de combustible a dirigir la operación con datos, alertas y disciplina financiera.
                                </div>
                            </div>

                            <div class="side-stack">
                                <div class="card">
                                    <div class="label">Tesis de inversión</div>
                                    <div class="metric">1 sola plataforma</div>
                                    <p class="subtext">Centraliza combustible, fondeo, documentos, alertas y desempeño de la flota para eliminar operación dispersa y decisiones tardías.</p>
                                </div>

                                <div class="card">
                                    <div class="label">Qué resuelve hoy</div>
                                    <div class="bullet-list">
                                        <div class="bullet">
                                            <strong>Disponibilidad de unidades</strong>
                                            <span>Reduce el riesgo de paro por falta de saldo operativo o financiero.</span>
                                        </div>
                                        <div class="bullet">
                                            <strong>Cumplimiento y auditoría</strong>
                                            <span>Ordena vencimientos, responsables y evidencias documentales.</span>
                                        </div>
                                        <div class="bullet">
                                            <strong>Rentabilidad por operación</strong>
                                            <span>Detecta desviaciones de rendimiento y enfoca correctivos donde realmente se pierde dinero.</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="message">
                                    <p>No estamos comprando software.</p>
                                    <p>Estamos instalando una capa de control sobre <span class="accent-gold">disponibilidad, combustible, cumplimiento y trazabilidad financiera</span>.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="slide" data-slide>
                    <div class="frame">
                        <div class="content">
                            <div class="section-head">
                                <div>
                                    <div class="eyebrow">Diapositiva 2</div>
                                    <h2>Qué vendemos realmente</h2>
                                </div>
                                <p>El valor no está en registrar datos. El valor está en darle a la dirección una operación más gobernable, menos expuesta y con mayor capacidad de reacción.</p>
                            </div>

                            <div class="grid-2">
                                <div class="pillar">
                                    <h3>Control operativo en tiempo real</h3>
                                    <p>Seguimiento por vehículo, departamento, localidad, responsable, chofer y tarjeta para saber qué está ocurriendo y dónde intervenir primero.</p>
                                </div>
                                <div class="pillar">
                                    <h3>Blindaje del gasto en combustible</h3>
                                    <p>Monitoreo de litros, importes, rendimiento real, saldo disponible y reposición pendiente para reducir fuga y compras sin control.</p>
                                </div>
                                <div class="pillar">
                                    <h3>Menor riesgo de interrupción</h3>
                                    <p>Alertas de rendimiento, fondeo y vencimiento documental para anticipar incidentes que detienen unidades o exponen a sanciones.</p>
                                </div>
                                <div class="pillar">
                                    <h3>Decisiones con evidencia</h3>
                                    <p>Dashboards y exportables convierten la operación diaria en información ejecutiva y seguimiento auditable.</p>
                                </div>
                            </div>

                            <div class="quote">
                                <p>La plataforma convierte una flota difícil de supervisar en una operación con señales claras, responsables visibles y decisiones sustentadas.</p>
                                <p>Para la junta, eso significa menos dependencia de reportes manuales y más capacidad para exigir desempeño, disciplina y continuidad operativa.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="slide" data-slide>
                    <div class="frame">
                        <div class="content">
                            <div class="section-head">
                                <div>
                                    <div class="eyebrow">Diapositiva 3</div>
                                    <h2>Beneficio directivo por frente</h2>
                                </div>
                                <p>La plataforma ataca cuatro dolores del consejo: gasto, continuidad, cumplimiento y trazabilidad financiera.</p>
                            </div>

                            <div class="grid-4">
                                <div class="stat">
                                    <div class="number accent-gold">Combustible</div>
                                    <div class="caption">Mayor control sobre consumo, rendimiento y reposiciones para cerrar espacios de fuga operativa.</div>
                                </div>
                                <div class="stat">
                                    <div class="number accent-cyan">Fondeo</div>
                                    <div class="caption">Visibilidad de saldo operativo y financiero por unidad para sostener disponibilidad sin improvisación.</div>
                                </div>
                                <div class="stat">
                                    <div class="number accent-danger">Documentos</div>
                                    <div class="caption">Prevención de vencimientos, exposición regulatoria y unidades detenidas por incumplimiento.</div>
                                </div>
                                <div class="stat">
                                    <div class="number" style="color: var(--green);">Alertas</div>
                                    <div class="caption">Notificación automática a responsables y administración para acelerar la corrección.</div>
                                </div>
                            </div>

                            <div class="grid-2">
                                <div class="kpi">
                                    <h3>Resultado para Finanzas</h3>
                                    <p>Menos dispersión en el control del gasto, mejor trazabilidad por tarjeta y visibilidad temprana de reposiciones necesarias.</p>
                                </div>
                                <div class="kpi">
                                    <h3>Resultado para Operaciones</h3>
                                    <p>Más continuidad de servicio, menos sorpresas por faltantes y foco en unidades con desviaciones reales.</p>
                                </div>
                                <div class="kpi">
                                    <h3>Resultado para Cumplimiento</h3>
                                    <p>Mayor orden documental y capacidad para actuar antes del vencimiento y no después de la contingencia.</p>
                                </div>
                                <div class="kpi">
                                    <h3>Resultado para Dirección</h3>
                                    <p>Una sola versión de la verdad para supervisar el negocio móvil con criterio ejecutivo.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="slide" data-slide>
                    <div class="frame">
                        <div class="content">
                            <div class="section-head">
                                <div>
                                    <div class="eyebrow">Diapositiva 4</div>
                                    <h2>Cierre comercial para la junta</h2>
                                </div>
                                <p>El mensaje final debe pedir respaldo para adopción, gobierno y seguimiento, no solo aprobación tecnológica.</p>
                            </div>

                            <div class="grid-3">
                                <div class="risk">
                                    <strong>Sin plataforma</strong>
                                    <span>Seguimos reaccionando tarde, conciliando datos dispersos y asumiendo pérdidas invisibles en combustible y disponibilidad.</span>
                                </div>
                                <div class="risk">
                                    <strong>Con plataforma</strong>
                                    <span>Instalamos un modelo de control continuo con responsables, alertas y tableros que hacen visible el costo de no actuar.</span>
                                </div>
                                <div class="risk">
                                    <strong>Decisión recomendada</strong>
                                    <span>Respaldar la adopción institucional, fijar KPIs de dirección y usar la plataforma como estándar corporativo de gestión de flota.</span>
                                </div>
                            </div>

                            <div class="message">
                                <p>Esta inversión no compite contra otros sistemas.</p>
                                <p>Compite contra <span class="accent-danger">la fuga operativa, el riesgo de paro, la exposición documental y la falta de visibilidad directiva</span>.</p>
                            </div>

                            <div class="footer-band">
                                <div class="mini-card">
                                    <strong>Resultado esperado:</strong> menos fuga operativa, menos riesgo regulatorio y mayor velocidad de decisión.
                                </div>
                                <div class="mini-card">
                                    <strong>Siguiente paso:</strong> definir KPIs del consejo, plan de adopción y revisiones ejecutivas mensuales sobre la plataforma.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        (() => {
            const root = document.querySelector('[data-presentation]');
            if (!root) return;

            const slides = Array.from(root.querySelectorAll('[data-slide]'));
            const track = root.querySelector('[data-track]');
            const prev = root.querySelector('[data-prev]');
            const next = root.querySelector('[data-next]');
            const current = root.querySelector('[data-current]');
            const total = root.querySelector('[data-total]');
            const compactMode = () => window.innerWidth <= 1180;

            let index = 0;
            total.textContent = String(slides.length);

            const render = () => {
                current.textContent = String(index + 1);
                prev.disabled = index === 0;
                next.disabled = index === slides.length - 1;

                if (compactMode()) {
                    slides[index].scrollIntoView({ behavior: 'smooth', block: 'start' });
                    return;
                }

                track.style.transform = `translateY(-${index * 100}vh)`;
            };

            prev.addEventListener('click', () => {
                if (index > 0) {
                    index -= 1;
                    render();
                }
            });

            next.addEventListener('click', () => {
                if (index < slides.length - 1) {
                    index += 1;
                    render();
                }
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' || event.key === 'PageDown' || event.key === 'ArrowRight') {
                    event.preventDefault();
                    if (index < slides.length - 1) {
                        index += 1;
                        render();
                    }
                }

                if (event.key === 'ArrowUp' || event.key === 'PageUp' || event.key === 'ArrowLeft') {
                    event.preventDefault();
                    if (index > 0) {
                        index -= 1;
                        render();
                    }
                }
            });

            let wheelLock = false;
            window.addEventListener('wheel', (event) => {
                if (compactMode()) return;
                if (wheelLock) return;
                if (Math.abs(event.deltaY) < 24) return;

                wheelLock = true;
                setTimeout(() => {
                    wheelLock = false;
                }, 550);

                if (event.deltaY > 0 && index < slides.length - 1) {
                    index += 1;
                    render();
                } else if (event.deltaY < 0 && index > 0) {
                    index -= 1;
                    render();
                }
            }, { passive: true });

            let startY = 0;
            window.addEventListener('touchstart', (event) => {
                startY = event.touches[0]?.clientY || 0;
            }, { passive: true });

            window.addEventListener('touchend', (event) => {
                if (compactMode()) return;
                const endY = event.changedTouches[0]?.clientY || 0;
                const delta = startY - endY;

                if (Math.abs(delta) < 60) return;

                if (delta > 0 && index < slides.length - 1) {
                    index += 1;
                    render();
                } else if (delta < 0 && index > 0) {
                    index -= 1;
                    render();
                }
            }, { passive: true });

            window.addEventListener('resize', render);
            render();
        })();
    </script>
</body>
</html>
