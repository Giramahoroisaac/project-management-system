<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="landing-header">
        <nav class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-project-diagram"></i>
                ProjectFlow
            </a>
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            </ul>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Streamline Your Project Management</h1>
                <p>Welcome to ProjectFlow, your comprehensive solution for efficient project management and collaboration. Organize, track, and deliver projects with ease using our intuitive platform.</p>
                <a href="login.php" class="cta-button">
                    <i class="fas fa-rocket"></i> Get Started
                </a>
            </div>
            <div class="hero-image">
                <img src="assets/project-management.jpg" alt="Project Management Illustration" 
                     onerror="this.src='https://via.placeholder.com/600x400?text=Project+Management'">
            </div>
        </div>
    </section>

    <section class="features">
        <div class="features-container">
            <div class="feature-card">
                <i class="fas fa-tasks"></i>
                <h3>Project Tracking</h3>
                <p>Keep track of your projects' progress in real-time. Monitor deadlines, milestones, and team performance all in one place.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-users"></i>
                <h3>Team Collaboration</h3>
                <p>Work seamlessly with your team members. Share documents, communicate effectively, and stay aligned with project goals.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-chart-line"></i>
                <h3>Progress Analytics</h3>
                <p>Get detailed insights into your project performance with comprehensive analytics and reporting tools.</p>
            </div>
        </div>
    </section>

    <script>
    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Add active class to current nav item
    const currentLocation = location.href;
    const menuItems = document.querySelectorAll('.nav-menu a');
    menuItems.forEach(item => {
        if(item.href === currentLocation) {
            item.classList.add('active');
        }
    });
    </script>
</body>
</html>
