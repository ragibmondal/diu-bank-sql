<?php
require_once 'config/config.php';

if(is_logged_in()) {
    redirect('user/dashboard.php');
}

if(is_admin_logged_in()) {
    redirect('admin/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daffodil Banking System - Modern Digital Banking</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="assets/fonts/inter.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow-x: hidden;
        }
        
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .navbar-glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: white !important;
            text-decoration: none;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            padding: 8px 16px !important;
            border-radius: 25px;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
            transform: translateY(-2px);
        }
        
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            padding: 0;
        }
        
        .hero-content {
            z-index: 2;
            color: white;
        }
        
        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.4rem;
            font-weight: 400;
            opacity: 0.95;
            margin-bottom: 3rem;
            line-height: 1.6;
        }
        
        .btn-modern {
            padding: 16px 32px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-modern:hover::before {
            left: 100%;
        }
        
        .btn-primary-modern {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #667eea;
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.3);
        }
        
        .btn-primary-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 255, 255, 0.4);
            color: #667eea;
        }
        
        .btn-secondary-modern {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .btn-secondary-modern:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-3px);
            color: white;
        }
        
        .features-section {
            background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
            padding: 100px 0;
            position: relative;
        }
        
        .section-title {
            font-size: 3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
        }
        
        .section-subtitle {
            font-size: 1.2rem;
            color: #718096;
            margin-bottom: 4rem;
        }
        
        .feature-card {
            background: white;
            border-radius: 24px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 2rem;
            color: white;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 16px;
        }
        
        .feature-text {
            color: #718096;
            line-height: 1.6;
        }
        
        .stats-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 80px 0;
            color: white;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .cta-section {
            background: #2d3748;
            padding: 80px 0;
            color: white;
            text-align: center;
        }
        
        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .cta-text {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .btn-modern {
                padding: 14px 28px;
                font-size: 1rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
        
        .fade-in-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="particle" style="width: 10px; height: 10px; left: 10%; top: 20%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 15px; height: 15px; left: 20%; top: 80%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 8px; height: 8px; left: 60%; top: 30%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 12px; height: 12px; left: 80%; top: 70%; animation-delay: 1s;"></div>
        <div class="particle" style="width: 20px; height: 20px; left: 90%; top: 10%; animation-delay: 3s;"></div>
    </div>

    <nav class="navbar navbar-expand-lg fixed-top navbar-glass">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-university me-2"></i>
                Daffodil Bank
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Customer Login
                    </a>
                    <a class="nav-link" href="register.php">
                        <i class="fas fa-user-plus me-1"></i>Register
                    </a>
                    <a class="nav-link" href="admin/login.php">
                        <i class="fas fa-shield-alt me-1"></i>Admin Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content fade-in-up">
                    <h1 class="hero-title">The Future of Banking is Here</h1>
                    <p class="hero-subtitle">Experience next-generation digital banking with advanced security, seamless transfers, and intelligent financial management.</p>
                    <div class="d-flex flex-column flex-md-row gap-3">
                        <a href="register.php" class="btn-modern btn-primary-modern">
                            <i class="fas fa-rocket"></i>
                            Start Banking Today
                        </a>
                        <a href="login.php" class="btn-modern btn-secondary-modern">
                            <i class="fas fa-sign-in-alt"></i>
                            Customer Login
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 fade-in-up" style="animation-delay: 0.3s;">
                    <div class="text-center">
                        <div style="position: relative; display: inline-block;">
                            <i class="fas fa-mobile-alt" style="font-size: 15rem; color: rgba(255,255,255,0.1);"></i>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                <i class="fas fa-university" style="font-size: 4rem; color: white;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 stat-item fade-in-up">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Happy Customers</div>
                </div>
                <div class="col-md-3 stat-item fade-in-up" style="animation-delay: 0.1s;">
                    <div class="stat-number">৳100M+</div>
                    <div class="stat-label">Transactions Processed</div>
                </div>
                <div class="col-md-3 stat-item fade-in-up" style="animation-delay: 0.2s;">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-label">Uptime Guarantee</div>
                </div>
                <div class="col-md-3 stat-item fade-in-up" style="animation-delay: 0.3s;">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Customer Support</div>
                </div>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title fade-in-up">Revolutionary Banking Features</h2>
                <p class="section-subtitle fade-in-up">Discover the tools that make banking effortless and secure</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6 fade-in-up">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Bank-Grade Security</h3>
                        <p class="feature-text">Advanced encryption, multi-factor authentication, and real-time fraud monitoring protect your financial data 24/7.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 fade-in-up" style="animation-delay: 0.1s;">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3 class="feature-title">Instant Transfers</h3>
                        <p class="feature-text">Send money anywhere, anytime with lightning-fast transfers that complete in seconds, not days.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 fade-in-up" style="animation-delay: 0.2s;">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Smart Analytics</h3>
                        <p class="feature-text">Get insights into your spending patterns with intelligent analytics and personalized financial recommendations.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 fade-in-up" style="animation-delay: 0.3s;">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="feature-title">Mobile First</h3>
                        <p class="feature-text">Experience seamless banking on any device with our responsive design and intuitive user interface.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 fade-in-up" style="animation-delay: 0.4s;">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3 class="feature-title">24/7 Support</h3>
                        <p class="feature-text">Our dedicated support team is always available to help you with any questions or concerns.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 fade-in-up" style="animation-delay: 0.5s;">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h3 class="feature-title">Global Access</h3>
                        <p class="feature-text">Access your accounts from anywhere in the world with our secure, cloud-based banking platform.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <div class="fade-in-up">
                <h2 class="cta-title">Ready to Experience Modern Banking?</h2>
                <p class="cta-text">Join thousands of satisfied customers who trust Daffodil Bank for their financial needs.</p>
                <a href="register.php" class="btn-modern btn-primary-modern">
                    <i class="fas fa-rocket"></i>
                    Open Your Account Now
                </a>
            </div>
        </div>
    </section>

    <footer style="background: #1a202c; color: white; padding: 60px 0 30px;">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 style="font-weight: 600; margin-bottom: 20px;">
                        <i class="fas fa-university me-2"></i>Daffodil Bank
                    </h5>
                    <p style="color: #a0aec0; line-height: 1.6;">
                        Leading the future of digital banking with innovative solutions and unmatched security.
                    </p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 style="font-weight: 600; margin-bottom: 15px;">Services</h6>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 8px;"><a href="#" style="color: #a0aec0; text-decoration: none;">Personal Banking</a></li>
                        <li style="margin-bottom: 8px;"><a href="#" style="color: #a0aec0; text-decoration: none;">Business Banking</a></li>
                        <li style="margin-bottom: 8px;"><a href="#" style="color: #a0aec0; text-decoration: none;">Online Banking</a></li>
                        <li style="margin-bottom: 8px;"><a href="#" style="color: #a0aec0; text-decoration: none;">Mobile Banking</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 style="font-weight: 600; margin-bottom: 15px;">Support</h6>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 8px;"><a href="#" style="color: #a0aec0; text-decoration: none;">Help Center</a></li>
                        <li style="margin-bottom: 8px;"><a href="#" style="color: #a0aec0; text-decoration: none;">Contact Us</a></li>
                        <li style="margin-bottom: 8px;"><a href="#" style="color: #a0aec0; text-decoration: none;">Security</a></li>
                        <li style="margin-bottom: 8px;"><a href="#" style="color: #a0aec0; text-decoration: none;">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6 style="font-weight: 600; margin-bottom: 15px;">Get Started</h6>
                    <p style="color: #a0aec0; margin-bottom: 20px;">Ready to join the banking revolution?</p>
                    <a href="register.php" class="btn-modern btn-primary-modern">
                        <i class="fas fa-arrow-right"></i>
                        Open Account
                    </a>
                </div>
            </div>
            <hr style="border-color: #2d3748; margin: 40px 0 20px;">
            <div class="text-center">
                <p style="color: #a0aec0; margin: 0;">&copy; 2024 Daffodil Banking System. All rights reserved. Built with ❤️ for modern banking.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in-up').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>