@extends('layouts.site')

@section('title', 'ICSA - International Institute of Computer Science and Administration | Kuwait')
@section('description', 'ICSA offers professional courses in IT, UK Diploma Programs, and Language Training in Kuwait. Enroll now for Computer Secretarial, Graphics Design, Web Development, and more.')
@php($showHeaderLogin = true)

@section('content')
<!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-pattern"></div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">Build Your Future with <span>Professional Education</span></h1>
                    <p class="hero-description">Join Kuwait's leading institute for Computer Science, Administration, and Professional Development. Explore every course from one streamlined page.</p>
                    <div class="hero-buttons">
                        <a href="#courses" class="btn btn-primary btn-lg">Explore Courses</a>
                        <a href="#contact" class="btn btn-white btn-lg">Contact Us</a>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <span class="hero-stat-value">{{ $courseTotal }}+</span>
                            <span class="hero-stat-label">Professional Courses</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-value">18000+</span>
                            <span class="hero-stat-label">Graduates</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-value">24+</span>
                            <span class="hero-stat-label">Years Experience</span>
                        </div>
                    </div>
                </div>          
                <div class="hero-image">
                    <img src="{{ asset('images/hero-icsa-campus.jpg') }}" alt="ICSA Students Learning">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Bar -->
    <section class="stats-bar">
        <div class="container">
            <div class="stats-bar-content">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <div class="stat-content">
                            <h4>{{ $categoryCounts['it'] }}</h4>
                        <p>IT & Technical Courses</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="stat-content">
                            <h4>{{ $categoryCounts['diploma'] }}</h4>
                        <p>UK Diploma Programs</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-language"></i>
                    </div>
                    <div class="stat-content">
                            <h4>{{ $categoryCounts['language'] }}</h4>
                        <p>Language Courses</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h4>100%</h4>
                        <p>Student Satisfaction</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Accreditation Partners -->
    <section class="partners-strip">
        <div class="container">
            <div class="partners-strip-content">
                <p class="partners-strip-label">Accredited &amp; Recognized By</p>
                <div class="partners-marquee">
                    <div class="partners-marquee-track">
                        <div class="partners-marquee-group">
                            <img src="{{ asset('images/athe.png') }}" alt="ATHE">
                            <img src="{{ asset('images/athe white.png') }}" alt="ATHE">
                            <img src="{{ asset('images/pearson.png') }}" alt="Pearson">
                            <img src="{{ asset('images/ielts.png') }}" alt="IELTS">
                            <img src="{{ asset('images/qualifi.png') }}" alt="Qualifi">
                            <img src="{{ asset('images/wes.png') }}" alt="WES">
                            <img src="{{ asset('images/amca.png') }}" alt="AMCA">
                            <img src="{{ asset('images/cpd.png') }}" alt="CPD">
                            <img src="{{ asset('images/british-council.png') }}" alt="British Council">
                            <img src="{{ asset('images/visaync.png') }}" alt="Visaync">
                            <img src="{{ asset('images/icsa-London.png') }}" alt="ICSA International College of London">
                            <img src="{{ asset('images/Layer2.png') }}" alt="CAP College Association">
                        </div>
                        <div class="partners-marquee-group" aria-hidden="true">
                            <img src="{{ asset('images/athe.png') }}" alt="">
                            <img src="{{ asset('images/athe white.png') }}" alt="">
                            <img src="{{ asset('images/pearson.png') }}" alt="">
                            <img src="{{ asset('images/ielts.png') }}" alt="">
                            <img src="{{ asset('images/qualifi.png') }}" alt="">
                            <img src="{{ asset('images/wes.png') }}" alt="">
                            <img src="{{ asset('images/amca.png') }}" alt="">
                            <img src="{{ asset('images/cpd.png') }}" alt="">
                            <img src="{{ asset('images/british-council.png') }}" alt="">
                            <img src="{{ asset('images/visaync.png') }}" alt="">
                            <img src="{{ asset('images/icsa-London.png') }}" alt="">
                            <img src="{{ asset('images/Layer2.png') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="section why-choose" id="about">
        <div class="container">
            <div class="why-choose-grid">
                <div class="why-choose-image">
                    <img src="{{ asset('images/section-learning-environment.jpg') }}" alt="ICSA Learning Environment">
                </div>
                <div class="why-choose-content">
                    <span class="section-label">Why Choose ICSA</span>
                    <h2>Your Success is Our Priority</h2>
                    <p>At ICSA, we are committed to providing quality education that prepares you for the real world. Our experienced instructors, modern facilities, and industry-relevant curriculum ensure you get the best learning experience.</p>
                    
                    <div class="features-list">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="feature-content">
                                <h4>Expert Instructors</h4>
                                <p>Learn from industry professionals with years of real-world experience.</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-laptop"></i>
                            </div>
                            <div class="feature-content">
                                <h4>Modern Facilities</h4>
                                <p>State-of-the-art computer labs and learning environments.</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div class="feature-content">
                                <h4>Career Guidance</h4>
                                <p>Get practical guidance on building your professional profile and planning your career path.</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div class="feature-content">
                                <h4>Recognized Certificates</h4>
                                <p>Earn certificates that are widely recognized for academic and professional growth.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Course Categories -->
    <section class="section home-categories">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Our Programs</span>
                <h2 class="section-title">Explore Our Course Categories</h2>
                <p class="section-subtitle">Choose from our wide range of professional courses designed to help you succeed in your career</p>
            </div>

            <div class="categories-grid">
                <a href="{{ route('site.home', ['category' => 'it']) }}#courses" class="category-card">
                    <img class="category-photo" src="{{ asset('images/category-it.jpg') }}" alt="Laptop with code on screen">
                    <h3>IT & Technical</h3>
                    <p>Master modern technology with courses in programming, design, networking, and software development.</p>
                    <span class="category-courses">{{ $categoryCounts['it'] }} Courses</span>
                </a>

                <a href="{{ route('site.home', ['category' => 'diploma']) }}#courses" class="category-card">
                    <img class="category-photo" src="{{ asset('images/category-uk.jpg') }}" alt="United Kingdom flag and Big Ben">
                    <h3>UK Diploma Programs</h3>
                    <p>Internationally recognized qualifications in business, management, IT, healthcare, and more.</p>
                    <span class="category-courses">{{ $categoryCounts['diploma'] }} Programs</span>
                </a>

                <a href="{{ route('site.home', ['category' => 'language']) }}#courses" class="category-card">
                    <img class="category-photo" src="{{ asset('images/category-language.jpg') }}" alt="Student studying in a classroom">
                    <h3>Language & Professional</h3>
                    <p>Enhance your communication skills with two English courses and one Arabic course.</p>
                    <span class="category-courses">{{ $categoryCounts['language'] }} Courses</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Courses -->
    <section class="section featured-courses" id="courses">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Course Catalog</span>
                <h2 class="section-title">All Courses</h2>
                <p class="section-subtitle">Browse every ICSA program from the homepage and filter by category.</p>
            </div>

            <div class="filter-buttons home-course-filter">
                <button class="filter-btn active" data-filter="all">All Courses</button>
                <button class="filter-btn" data-filter="it">IT & Technical</button>
                <button class="filter-btn" data-filter="diploma">UK Diploma Programs</button>
                <button class="filter-btn" data-filter="language">Language & Professional</button>
            </div>

            <div class="courses-grid" id="coursesGrid">
                @forelse ($courses as $course)
                    <article class="course-card" data-category="{{ $course['listing_category'] }}">
                        <div class="course-image">
                            @if ($course['listing_image_url'])
                                <img src="{{ $course['listing_image_url'] }}" alt="{{ $course['title'] }}" loading="lazy">
                            @else
                                <div class="course-image-placeholder">
                                    <i class="fas fa-graduation-cap"></i>
                                    <p>{{ $course['title'] }}</p>
                                </div>
                            @endif
                        </div>
                        <div class="course-content">
                            <span class="course-category">{{ $course['badge'] ?: $course['listing_category_label'] }}</span>
                            <h3 class="course-title">{{ $course['title'] }}</h3>
                            <p class="course-description">{{ $course['description'] ?: 'Professional training at ICSA Kuwait.' }}</p>
                            <div class="course-meta">
                                @if ($course['duration'] !== '')
                                    <span class="course-meta-item"><i class="fas fa-clock"></i> {{ $course['duration'] }}</span>
                                @endif
                                @if ($course['certification'] !== '')
                                    <span class="course-meta-item"><i class="fas fa-signal"></i> {{ $course['certification'] }}</span>
                                @endif
                            </div>
                            <div class="course-footer">
                                <span class="course-price">{{ $course['price'] }}</span>
                                <a href="{{ route('site.course', $course['slug']) }}" class="btn btn-secondary btn-sm">View Details</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="course-card">
                        <div class="course-content">
                            <h3 class="course-title">No Courses Found</h3>
                            <p class="course-description">Courses added from the admin portal will appear here.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="section testimonials">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Testimonials</span>
                <h2 class="section-title">What Our Students Say</h2>
                <p class="section-subtitle">Hear from our graduates who have transformed their careers with ICSA</p>
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
                <h2>Ready to Start Your Learning Journey?</h2>
                <p>Join thousands of successful graduates who have transformed their careers with ICSA. Enroll today and take the first step towards a brighter future.</p>
                <div class="cta-buttons">
                    <a href="#courses" class="btn btn-secondary btn-lg">Browse Courses</a>
                    <a href="#contact" class="btn btn-outline btn-lg" style="border-color: var(--primary-dark); color: var(--primary-dark);">Contact Us</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
@endsection
