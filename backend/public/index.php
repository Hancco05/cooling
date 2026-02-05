<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cooling System - Control de Temperatura Inteligente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #4facfe;
            --secondary: #00f2fe;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --success: #00b894;
            --danger: #ff6b6b;
        }

        body {
            background: linear-gradient(135deg, var(--dark) 0%, #16213e 100%);
            color: var(--light);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Header y Navegación */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 5%;
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 2px solid var(--primary);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .logo i {
            font-size: 2rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: var(--light);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .nav-links a:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        .cta-button {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 30px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(79, 172, 254, 0.3);
        }

        /* Hero Section */
        .hero {
            padding: 10rem 5% 5rem;
            display: flex;
            align-items: center;
            gap: 4rem;
            min-height: 100vh;
        }

        .hero-content {
            flex: 1;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            color: #b0b7c3;
            line-height: 1.6;
            max-width: 600px;
        }

        .hero-image {
            flex: 1;
            position: relative;
        }

        .hero-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Features */
        .features {
            padding: 5rem 5%;
            background: rgba(255, 255, 255, 0.05);
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            font-size: 2.5rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary);
        }

        .feature-card i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        /* Login Modal */
        .login-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: var(--dark);
            padding: 3rem;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            position: relative;
            border: 2px solid var(--primary);
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: var(--light);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .login-tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            color: #b0b7c3;
            transition: all 0.3s;
        }

        .tab.active {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #b0b7c3;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: white;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 2rem;
            background: rgba(0, 0, 0, 0.3);
            margin-top: 5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero {
                flex-direction: column;
                text-align: center;
                padding-top: 8rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-snowflake"></i>
            <span>Cooling System</span>
        </div>
        <div class="nav-links">
            <a href="#home">Inicio</a>
            <a href="#features">Características</a>
            <a href="#about">Nosotros</a>
            <a href="#contact">Contacto</a>
        </div>
        <button class="cta-button" id="loginBtn">
            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
        </button>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Control Inteligente de Temperatura</h1>
            <p>Monitorea y controla todos tus sistemas de refrigeración desde una sola plataforma. Ahorra energía, previene fallas y optimiza el rendimiento.</p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button class="cta-button" id="startBtn">
                    <i class="fas fa-play"></i> Comenzar Ahora
                </button>
                <button class="cta-button" style="background: transparent; border: 2px solid var(--primary);">
                    <i class="fas fa-video"></i> Ver Demo
                </button>
            </div>
        </div>
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Dashboard Cooling">
        </div>
    </section>

    <!-- Features -->
    <section class="features" id="features">
        <h2 class="section-title">Características Principales</h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-thermometer-half"></i>
                <h3>Monitoreo en Tiempo Real</h3>
                <p>Seguimiento constante de temperatura y humedad en todos tus dispositivos.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-bell"></i>
                <h3>Alertas Inteligentes</h3>
                <p>Notificaciones instantáneas cuando se detectan anomalías o fallas.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-chart-line"></i>
                <h3>Reportes Detallados</h3>
                <p>Análisis de consumo energético y tendencias de temperatura.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-mobile-alt"></i>
                <h3>Control Remoto</h3>
                <p>Gestiona tus sistemas desde cualquier dispositivo con conexión a internet.</p>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="login-modal" id="loginModal">
        <div class="login-container">
            <button class="close-modal" id="closeModal">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="login-tabs">
                <div class="tab active" data-tab="login">Iniciar Sesión</div>
                <div class="tab" data-tab="register">Registrarse</div>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="tab-content active">
                <div class="form-group">
                    <label for="loginEmail"><i class="fas fa-envelope"></i> Correo Electrónico</label>
                    <input type="email" id="loginEmail" placeholder="usuario@ejemplo.com" required>
                </div>
                <div class="form-group">
                    <label for="loginPassword"><i class="fas fa-lock"></i> Contraseña</label>
                    <input type="password" id="loginPassword" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="rememberMe"> Recordar sesión
                    </label>
                </div>
                <button type="submit" class="cta-button" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Ingresar
                </button>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="#" style="color: var(--primary);">¿Olvidaste tu contraseña?</a>
                </div>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="tab-content">
                <div class="form-group">
                    <label for="regName"><i class="fas fa-user"></i> Nombre Completo</label>
                    <input type="text" id="regName" placeholder="Juan Pérez" required>
                </div>
                <div class="form-group">
                    <label for="regEmail"><i class="fas fa-envelope"></i> Correo Electrónico</label>
                    <input type="email" id="regEmail" placeholder="usuario@ejemplo.com" required>
                </div>
                <div class="form-group">
                    <label for="regPassword"><i class="fas fa-lock"></i> Contraseña</label>
                    <input type="password" id="regPassword" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label for="regConfirmPassword"><i class="fas fa-lock"></i> Confirmar Contraseña</label>
                    <input type="password" id="regConfirmPassword" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label for="userType"><i class="fas fa-user-tag"></i> Tipo de Usuario</label>
                    <select id="userType" style="width: 100%; padding: 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 10px; color: white;">
                        <option value="user">Usuario Normal</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <button type="submit" class="cta-button" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Crear Cuenta
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>🧊 Cooling System © 2024 - Control Inteligente de Temperatura</p>
        <p style="margin-top: 1rem; color: #b0b7c3;">
            <i class="fas fa-phone"></i> +1 234 567 890 | 
            <i class="fas fa-envelope"></i> info@coolingsystem.com
        </p>
    </footer>

    <script>
        // Elementos DOM
        const loginBtn = document.getElementById('loginBtn');
        const startBtn = document.getElementById('startBtn');
        const loginModal = document.getElementById('loginModal');
        const closeModal = document.getElementById('closeModal');
        const tabs = document.querySelectorAll('.tab');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        // Abrir modal de login
        loginBtn.addEventListener('click', () => {
            loginModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });

        startBtn.addEventListener('click', () => {
            loginModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });

        // Cerrar modal
        closeModal.addEventListener('click', () => {
            loginModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });

        // Cerrar al hacer clic fuera
        loginModal.addEventListener('click', (e) => {
            if (e.target === loginModal) {
                loginModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Cambiar pestañas
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.getAttribute('data-tab');
                
                // Remover active de todas
                tabs.forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Agregar active al seleccionado
                tab.classList.add('active');
                document.getElementById(`${tabName}Form`).classList.add('active');
            });
        });

        // Manejar login
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            
            // Simulación de login (más tarde se conectará a API real)
            if (email && password) {
                // Mostrar loading
                const btn = loginForm.querySelector('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
                btn.disabled = true;
                
                // Simular petición a API
                setTimeout(() => {
                    // Aquí iría la llamada real a tu API PHP
                    // Por ahora simulamos éxito
                    if (email.includes('admin')) {
                        // Redirigir a dashboard admin
                        window.location.href = '/dashboard-admin.html';
                    } else {
                        // Redirigir a dashboard usuario
                        window.location.href = '/dashboard-user.html';
                    }
                    
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 1500);
            }
        });

        // Manejar registro
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const name = document.getElementById('regName').value;
            const email = document.getElementById('regEmail').value;
            const password = document.getElementById('regPassword').value;
            const confirmPassword = document.getElementById('regConfirmPassword').value;
            const userType = document.getElementById('userType').value;
            
            if (password !== confirmPassword) {
                alert('Las contraseñas no coinciden');
                return;
            }
            
            // Mostrar loading
            const btn = registerForm.querySelector('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando cuenta...';
            btn.disabled = true;
            
            // Simular creación de cuenta
            setTimeout(() => {
                alert(`✅ Cuenta creada exitosamente!\n\nBienvenido ${name}\nTipo: ${userType === 'admin' ? 'Administrador' : 'Usuario'}`);
                
                // Cambiar a pestaña de login
                tabs[0].click();
                
                // Limpiar formulario
                registerForm.reset();
                
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 1500);
        });

        // Efecto de escritura en el título
        const title = document.querySelector('.hero h1');
        const originalText = title.textContent;
        title.textContent = '';
        
        let i = 0;
        function typeWriter() {
            if (i < originalText.length) {
                title.textContent += originalText.charAt(i);
                i++;
                setTimeout(typeWriter, 50);
            }
        }
        
        // Iniciar efecto después de 1 segundo
        setTimeout(typeWriter, 1000);
    </script>
</body>
</html>