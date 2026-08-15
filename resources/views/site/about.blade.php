@extends('layouts.site')

@section('title', 'About Us | ICSA - International Institute of Computer Science and Administration')
@section('description', 'Learn about ICSA Kuwait - a leading educational institution offering professional courses in IT, Business, and Languages for over 24 years.')
@php($showHeaderLogin = false)

@section('content')
<!-- About Hero -->
    <section class="about-hero">
        <div class="container">
            <h1>About ICSA</h1>
            <p>Empowering students with quality education and professional skills since 2002. We are committed to shaping the future of education in Kuwait.</p>
        </div>
    </section>

    <!-- Our Story -->
    <section class="section about-story">
        <div class="container">
            <div class="about-story-grid">
                <div class="about-story-content">
                    <span class="section-label">Our Story</span>
                    <h2>Building Careers, Transforming Lives</h2>
                    <p class="about-story-lead">ICSA (International Institute of Computer Science and Administration) was founded with a vision to provide quality professional education in Kuwait. Over the past 24 years, we have grown to become one of the leading educational institutions in the region.</p>
                    <p>Our mission is to bridge the gap between education and industry by offering practical, hands-on training that prepares our students for real-world challenges. We believe that education should be accessible, affordable, and most importantly, relevant to today's job market.</p>
                    <p>With a team of experienced instructors, state-of-the-art facilities, and a comprehensive curriculum, we have helped thousands of students achieve their career goals and build successful futures.</p>
                </div>
                <div class="about-story-image">
                    <img src="{{ asset('images/hero-icsa-campus.jpg') }}" alt="ICSA Campus">
                </div>
            </div>
            <div class="values-grid about-values-grid">
                <div class="value-card">
                    <i class="fas fa-bullseye"></i>
                    <h4>Our Mission</h4>
                    <p>To provide quality education that empowers students to achieve their professional goals.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-eye"></i>
                    <h4>Our Vision</h4>
                    <p>To be the leading educational institution in Kuwait, recognized for excellence.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-heart"></i>
                    <h4>Our Values</h4>
                    <p>Integrity, Excellence, Innovation, and Student Success drive everything we do.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-users"></i>
                    <h4>Our Community</h4>
                    <p>A diverse learning environment that fosters growth and collaboration.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="section" style="background: var(--primary); color: white;">
        <div class="container">
            <div class="stats-bar-content" style="grid-template-columns: repeat(4, 1fr);">
                <div class="stat-item" style="border-color: rgba(255,255,255,0.2);">
                    <div class="stat-icon" style="background: rgba(255,255,255,0.1); color: var(--accent);">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h4 style="color: white;">24</h4>
                        <p style="color: var(--gray-300);">Years of Excellence</p>
                    </div>
                </div>
                <div class="stat-item" style="border-color: rgba(255,255,255,0.2);">
                    <div class="stat-icon" style="background: rgba(255,255,255,0.1); color: var(--accent);">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-content">
                        <h4 style="color: white;">18000+</h4>
                        <p style="color: var(--gray-300);">Graduates</p>
                    </div>
                </div>
                <div class="stat-item" style="border-color: rgba(255,255,255,0.2);">
                    <div class="stat-icon" style="background: rgba(255,255,255,0.1); color: var(--accent);">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h4 style="color: white;">20+</h4>
                        <p style="color: var(--gray-300);">Professional Courses</p>
                    </div>
                </div>
                <div class="stat-item" style="border: none;">
                    <div class="stat-icon" style="background: rgba(255,255,255,0.1); color: var(--accent);">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-content">
                        <h4 style="color: white;">50+</h4>
                        <p style="color: var(--gray-300);">Expert Instructors</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="section why-choose">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Why ICSA</span>
                <h2 class="section-title">Why Students Choose Us</h2>
                <p class="section-subtitle">We are committed to providing the best learning experience for our students</p>
            </div>

            <div class="features-list" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="feature-content">
                        <h4>Expert Instructors</h4>
                        <p>Learn from industry professionals with years of real-world experience. Our instructors are passionate about teaching and dedicated to student success.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <div class="feature-content">
                        <h4>Modern Facilities</h4>
                        <p>State-of-the-art computer labs and classrooms equipped with the latest technology to enhance your learning experience.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <div class="feature-content">
                        <h4>Hands-on Training</h4>
                        <p>Practical, project-based learning that prepares you for real-world challenges. Theory meets practice in every course.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="feature-content">
                        <h4>Recognized Certificates</h4>
                        <p>Earn certificates that are widely recognized. Our UK Diploma programs are internationally accredited.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="feature-content">
                        <h4>Career Development Support</h4>
                        <p>Receive guidance on resume building, interview preparation, and long-term career planning.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="feature-content">
                        <h4>Small Batch Sizes</h4>
                        <p>Personalized attention with small class sizes. Every student gets the support they need to succeed.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="section testimonials">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Testimonials</span>
                <h2 class="section-title">What Our Students Say</h2>
            </div>

            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"This is Randy, finally after 8 months of study hard at ICSA Kuwait, I finished my short course which is &quot;ComSec.&quot; Thanks to Sir &quot;Ryan Guese&quot; for sharing your knowledge to us especially on me. You are so kind, great leader and very professional instructor. I highly recommend ICSA because all the instructors there are very professional and good. Keep up the good work, guys."</p>
                    <div class="testimonial-author">
                        <img src="{{ asset('images/Mr. Randy Paguia.png') }}" alt="Mr. Randy Paguia" class="testimonial-avatar">
                        <div class="testimonial-info">
                            <h4>Mr. Randy Paguia</h4>
                            <p>Computer Secretarial Graduate</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"I have learned so much from this institute! It provides a wide variety of short courses to choose from that can help build skills for more job opportunities. Other than that, the work-study balance is manageable here! Attendance is flexible and easy to work with, so there isn't much to worry about in terms of schedules."</p>
                    <div class="testimonial-author">
                        <img src="{{ asset('images/Ms. Millen Glow.png') }}" alt="Ms. Millen Glow" class="testimonial-avatar">
                        <div class="testimonial-info">
                            <h4>Ms. Millen Glow</h4>
                            <p>AutoCAD 2D &amp; 3D Course Graduate</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"I really appreciate the flexibility of this course. It works well with my busy schedule, and the expectations were clear and upfront. The training materials, video exercises, and format were presented effectively. Homework, assignments, and quizzes are reasonable, and the instructors are approachable too. Thank you, ICSA."</p>
                    <div class="testimonial-author">
                        <img src="{{ asset('images/Ms. Katherine Regner.png') }}" alt="Ms. Katherine Regner" class="testimonial-avatar testimonial-avatar-katherine">
                        <div class="testimonial-info">
                            <h4>Ms. Katherine Regner</h4>
                            <p>Graphics Designing Course Graduate</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Your Journey?</h2>
                <p>Join thousands of successful graduates who have transformed their careers with ICSA.</p>
                <div class="cta-buttons">
                    <a href="{{ route('site.home') }}#courses" class="btn btn-secondary btn-lg">Explore Courses</a>
                    <a href="{{ route('site.home') }}#contact" class="btn btn-outline btn-lg" style="border-color: var(--primary-dark); color: var(--primary-dark);">Contact Us</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
@endsection
