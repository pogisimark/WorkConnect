<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkConnect - Your Gateway to Employment</title>
    <link rel="icon" href="assets/image/PESO Logo circle.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1a3876 0%, #2c5aa0 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Header */
        .header {
            background: rgba(26, 56, 118, 0.95);
            backdrop-filter: blur(10px);
            color: #fff;
            padding: 16px 32px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo {
            height: 50px;
        }

        .brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: #fff;
        }

        .nav-links {
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: #ffcb05;
            color: #1a3876;
        }

        /* Main Content */
        .main-content {
            padding-top: 100px;
            min-height: 100vh;
            position: relative;
        }

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .hero-section {
            text-align: center;
            padding: 60px 0;
            position: relative;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.4rem;
            color: #e3f0ff;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.5;
        }

        .action-buttons {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 60px;
        }

        .action-btn {
            background: #fff;
            color: #1a3876;
            border: none;
            padding: 20px 40px;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            min-width: 280px;
            justify-content: center;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.2);
            background: #ffcb05;
            color: #1a3876;
        }

        .action-btn i {
            font-size: 1.5rem;
        }

        .features-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin: 40px 0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ffcb05;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 15px;
        }

        .feature-description {
            color: #e3f0ff;
            line-height: 1.6;
        }

        /* Background Elements */
        .bg-elements {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            z-index: 1;
        }

        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 203, 5, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .bg-circle:nth-child(1) {
            width: 200px;
            height: 200px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .bg-circle:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .bg-circle:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Responsive Design */
        @media (max-width: 1366px) and (min-width: 1200px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .action-btn {
                padding: 18px 35px;
                font-size: 1.1rem;
                min-width: 260px;
            }
        }

        @media (max-width: 1366px) and (max-height: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .action-btn {
                padding: 16px 30px;
                font-size: 1rem;
                min-width: 240px;
            }
        }

        @media (max-width: 1023px) and (min-width: 768px) {
            .hero-title {
                font-size: 2.8rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .action-buttons {
                gap: 20px;
            }
            
            .action-btn {
                padding: 18px 32px;
                font-size: 1.1rem;
                min-width: 250px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 12px 20px;
            }
            
            .logo {
                height: 40px;
            }
            
            .brand {
                font-size: 1.5rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .main-content {
                padding-top: 80px;
            }
            
            .content-container {
                padding: 0 16px;
            }
            
            .hero-section {
                padding: 40px 0;
            }
            
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
                margin-bottom: 30px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 20px;
                align-items: center;
            }
            
            .action-btn {
                width: 100%;
                max-width: 300px;
                padding: 16px 24px;
                font-size: 1rem;
            }
            
            .features-section {
                padding: 30px 20px;
                margin: 30px 0;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .feature-card {
                padding: 25px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 10px 16px;
            }
            
            .logo {
                height: 35px;
            }
            
            .brand {
                font-size: 1.3rem;
            }
            
            .main-content {
                padding-top: 70px;
            }
            
            .content-container {
                padding: 0 12px;
            }
            
            .hero-section {
                padding: 30px 0;
            }
            
            .hero-title {
                font-size: 1.8rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
                margin-bottom: 25px;
            }
            
            .action-btn {
                padding: 14px 20px;
                font-size: 0.95rem;
                min-width: 250px;
            }
            
            .features-section {
                padding: 25px 16px;
                margin: 25px 0;
            }
            
            .feature-card {
                padding: 20px;
            }
            
            .feature-icon {
                font-size: 2.5rem;
            }
            
            .feature-title {
                font-size: 1.1rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(26, 56, 118, 0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #ffcb05;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="navbar">
            <div class="logo-brand">
                <img src="assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
                <span class="brand">WorkConnect</span>
            </div>
            <div class="nav-links">
                <a href="#home">Home</a>
                <a href="#features">Features</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-container">
        <!-- Home Section -->
        <section id="home" class="hero-section">
            <h1 class="hero-title">Welcome to WorkConnect</h1>
            <p class="hero-subtitle">
                Your comprehensive employment platform connecting job seekers with opportunities 
                and employers with the right talent. Powered by PESO services.
            </p>
            
            <div class="action-buttons">
                <a href="Employee/home.html" class="action-btn" id="jobSeekerBtn">
                    <i class="fas fa-user-tie"></i>
                    I'm Looking for a Job
                </a>
                <a href="Company/login.php" class="action-btn" id="companyBtn">
                    <i class="fas fa-briefcase"></i>
                    I'm an Employer
                </a>
                <a href="Employer/login.html" class="action-btn" id="employerBtn">
                    <i class="fas fa-building"></i>
                    I'm an Admin
                </a>
            </div>

            <div class="features-section" id="features">
                <h2 style="text-align: center; color: #fff; font-size: 2rem; margin-bottom: 20px;">
                    Why Choose WorkConnect?
                </h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="feature-title">Job Search</h3>
                        <p class="feature-description">
                            Find employment opportunities that match your skills and career goals 
                            with our comprehensive job search platform.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Talent Pool</h3>
                        <p class="feature-description">
                            Access a diverse pool of qualified candidates and connect with 
                            the right talent for your organization.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3 class="feature-title">PESO Services</h3>
                        <p class="feature-description">
                            Benefit from comprehensive employment services provided by 
                            the Public Employment Service Office.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        </div>

        <!-- Background Elements -->
        <div class="bg-elements">
            <div class="bg-circle"></div>
            <div class="bg-circle"></div>
            <div class="bg-circle"></div>
        </div>
    </main>

    <script>
        // Loading animation
        window.addEventListener('load', function() {
            // Hide loading immediately if page is already loaded
            document.getElementById('loading').style.display = 'none';
        });

        // Also hide loading on DOMContentLoaded for faster response
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.getElementById('loading').style.display = 'none';
            }, 100);
        });

        // Hide loading on page show (for back button navigation)
        window.addEventListener('pageshow', function(event) {
            document.getElementById('loading').style.display = 'none';
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Button hover effects
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px) scale(1.05)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Add click animation
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Add ripple effect
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add ripple animation CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Parallax effect for background elements
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelectorAll('.bg-circle');
            const speed = 0.5;

            parallax.forEach((element, index) => {
                const yPos = -(scrolled * speed * (index + 1));
                element.style.transform = `translateY(${yPos}px)`;
            });
        });

        // Feature cards animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Set initial state for feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
