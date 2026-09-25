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
                        <a href="{{ route('site.contact') }}" class="btn btn-white btn-lg">Contact Us</a>
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

    <!-- Interactive Learning Network -->
    <section class="public-neural section" id="learning-network" data-learning-network>
        <div class="public-neural__aurora" aria-hidden="true"></div>
        <div class="container public-neural__container">
            <div class="public-neural__intro">
                <div>
                    <span class="public-neural__eyebrow"><i class="fas fa-circle-nodes"></i> Interactive course explorer</span>
                    <h2>Find your path through the <span>ICSA learning network</span></h2>
                    <p>See how every program connects to practical skills, recognized certification, and your next career move. Select any signal to explore its route.</p>
                </div>
                <div class="public-neural__privacy"><span></span> Public learning paths · no personal data</div>
            </div>

            <div class="public-neural__filters" role="group" aria-label="Filter learning network">
                <button type="button" class="is-active" data-neural-filter="all" aria-pressed="true"><span></span> All paths</button>
                <button type="button" data-neural-filter="program" aria-pressed="false"><span></span> Programs</button>
                <button type="button" data-neural-filter="outcome" aria-pressed="false"><span></span> Outcomes</button>
            </div>

            <div class="public-neural__viewport" aria-label="Interactive map of ICSA learning paths">
                <div class="public-neural__stage">
                    <div class="public-neural__grid" aria-hidden="true"></div>
                    <div class="public-neural__orbit public-neural__orbit--one" aria-hidden="true"></div>
                    <div class="public-neural__orbit public-neural__orbit--two" aria-hidden="true"></div>
                    <svg class="public-neural__links" aria-hidden="true"></svg>

                    <button type="button" class="public-neural__node public-neural__node--entry" style="--node-x: 7%; --node-y: 50%;" data-node-id="explore" data-node-group="entry" data-node-title="Start exploring" data-node-kicker="Your journey" data-node-description="Begin with the skill, qualification, or career direction you want to build." data-node-action="Browse every course" data-node-url="#courses" aria-label="Explore all ICSA learning paths">
                        <span class="public-neural__pulse"></span><span class="public-neural__core"><i class="fas fa-compass"></i></span><strong>Start Here</strong><small>EXPLORE</small>
                    </button>

                    <button type="button" class="public-neural__node public-neural__node--program" style="--node-x: 30%; --node-y: 10%;" data-node-id="it" data-node-group="program" data-node-title="IT &amp; Technical" data-node-kicker="{{ $categoryCounts['it'] }} learning options" data-node-description="Build practical ability across software, networking, programming, and modern technical tools." data-node-action="Explore IT courses" data-node-url="{{ route('site.home', ['category' => 'it']) }}#courses">
                        <span class="public-neural__pulse"></span><span class="public-neural__core"><i class="fas fa-code"></i></span><strong>IT &amp; Technical</strong><small>{{ $categoryCounts['it'] }} COURSES</small>
                    </button>
                    <button type="button" class="public-neural__node public-neural__node--program" style="--node-x: 30%; --node-y: 26%;" data-node-id="diploma" data-node-group="program" data-node-title="UK Diplomas" data-node-kicker="{{ $categoryCounts['diploma'] }} diploma programs" data-node-description="Follow an internationally focused qualification path in business, technology, healthcare, and more." data-node-action="Explore UK diplomas" data-node-url="{{ route('site.home', ['category' => 'diploma']) }}#courses">
                        <span class="public-neural__pulse"></span><span class="public-neural__core"><i class="fas fa-award"></i></span><strong>UK Diplomas</strong><small>{{ $categoryCounts['diploma'] }} PROGRAMS</small>
                    </button>
                    <button type="button" class="public-neural__node public-neural__node--program" style="--node-x: 30%; --node-y: 42%;" data-node-id="language" data-node-group="program" data-node-title="Languages" data-node-kicker="{{ $categoryCounts['language'] }} language courses" data-node-description="Strengthen communication for study, work, travel, and everyday professional confidence." data-node-action="Explore language courses" data-node-url="{{ route('site.home', ['category' => 'language']) }}#courses">
                        <span class="public-neural__pulse"></span><span class="public-neural__core"><i class="fas fa-language"></i></span><strong>Languages</strong><small>{{ $categoryCounts['language'] }} COURSES</small>
                    </button>
                    <button type="button" class="public-neural__node public-neural__node--program" style="--node-x: 30%; --node-y: 58%;" data-node-id="healthcare" data-node-group="program" data-node-title="Healthcare" data-node-kicker="{{ $categoryCounts['nursing'] }} healthcare courses" data-node-description="Develop patient-centered knowledge and hands-on skills for care-focused roles." data-node-action="Explore healthcare" data-node-url="{{ route('site.home', ['category' => 'nursing']) }}#courses">
                        <span class="public-neural__pulse"></span><span class="public-neural__core"><i class="fas fa-heart-pulse"></i></span><strong>Healthcare</strong><small>{{ $categoryCounts['nursing'] }} COURSES</small>
                    </button>
                    <button type="button" class="public-neural__node public-neural__node--program" style="--node-x: 30%; --node-y: 74%;" data-node-id="design" data-node-group="program" data-node-title="Design &amp; Multimedia" data-node-kicker="{{ $categoryCounts['design'] }} creative courses" data-node-description="Turn ideas into visual work through design, 3D, motion, editing, and multimedia production." data-node-action="Explore design courses" data-node-url="{{ route('site.home', ['category' => 'design']) }}#courses">
                        <span class="public-neural__pulse"></span><span class="public-neural__core"><i class="fas fa-pen-ruler"></i></span><strong>Design</strong><small>{{ $categoryCounts['design'] }} COURSES</small>
                    </button>
                    <button type="button" class="public-neural__node public-neural__node--program" style="--node-x: 30%; --node-y: 90%;" data-node-id="short" data-node-group="program" data-node-title="Short Skills" data-node-kicker="{{ $categoryCounts['short-skills'] }} focused courses" data-node-description="Add a practical, job-ready skill through focused training designed to fit a busy schedule." data-node-action="Explore short courses" data-node-url="{{ route('site.home', ['category' => 'short-skills']) }}#courses">
                        <span class="public-neural__pulse"></span><span class="public-neural__core"><i class="fas fa-bolt"></i></span><strong>Short Skills</strong><small>{{ $categoryCounts['short-skills'] }} COURSES</small>
                    </button>

                    <button type="button" class="public-neural__node public-neural__node--hub is-selected" style="--node-x: 55%; --node-y: 50%;" data-node-id="hub" data-node-group="hub" data-node-title="ICSA Learning Hub" data-node-kicker="{{ $courseTotal }}+ professional courses" data-node-description="Every route combines guided learning, practical experience, and support from ICSA instructors in Kuwait." data-node-action="See all courses" data-node-url="#courses" aria-current="true">
                        <span class="public-neural__pulse"></span><span class="public-neural__core"><i class="fas fa-graduation-cap"></i></span><strong>ICSA Hub</strong><small>LEARN · PRACTICE</small>
                    </button>

                    <button type="button" class="public-neural__node public-neural__node--outcome" style="--node-x: 79%; --node-y: 24%;" data-node-id="skills" data-node-group="outcome" data-node-title="Practical Skills" data-node-kicker="Learn by doing" data-node-description="Build usable knowledge through guided exercises, projects, labs, and instructor support." data-node-action="Find a practical course" data-node-url="#courses">
                        <span class="public-neural__pulse"></span><span class="public-neural__core"><i class="fas fa-screwdriver-wrench"></i></span><strong>Build Skills</strong><small>PRACTICAL</small>
                    </button>
                    <button type="button" class="public-neural__node public-neural__node--outcome" style="--node-x: 79%; --node-y: 50%;" data-node-id="certificate" data-node-group="outcome" data-node-title="Recognized Certification" data-node-kicker="Prove your progress" data-node-description="Complete your chosen program and leave with evidence of the professional development you achieved." data-node-action="View qualification paths" data-node-url="#courses">
                        <span class="public-neural__pulse"></span><span class="public-neural__core"><i class="fas fa-certificate"></i></span><strong>Get Certified</strong><small>ACHIEVEMENT</small>
                    </button>
                    <button type="button" class="public-neural__node public-neural__node--outcome" style="--node-x: 79%; --node-y: 76%;" data-node-id="career" data-node-group="outcome" data-node-title="Career Growth" data-node-kicker="Turn learning into momentum" data-node-description="Use stronger skills and qualifications to prepare for new responsibilities and opportunities." data-node-action="Plan your next step" data-node-url="{{ route('site.contact') }}">
                        <span class="public-neural__pulse"></span><span class="public-neural__core"><i class="fas fa-arrow-trend-up"></i></span><strong>Career Growth</strong><small>NEXT STEP</small>
                    </button>

                    <button type="button" class="public-neural__node public-neural__node--finish" style="--node-x: 95%; --node-y: 50%;" data-node-id="enroll" data-node-group="finish" data-node-title="Start your enrollment" data-node-kicker="Ready when you are" data-node-description="Talk with the ICSA team to choose the right schedule, course, and enrollment route." data-node-action="Contact ICSA" data-node-url="{{ route('site.contact') }}">
                        <span class="public-neural__pulse"></span><span class="public-neural__core"><i class="fas fa-paper-plane"></i></span><strong>Enroll</strong><small>BEGIN</small>
                    </button>
                </div>
            </div>

            <div class="public-neural__inspector" aria-live="polite">
                <div class="public-neural__inspector-icon"><i class="fas fa-graduation-cap" data-neural-inspector-icon></i></div>
                <div class="public-neural__inspector-copy">
                    <span data-neural-inspector-kicker>{{ $courseTotal }}+ professional courses</span>
                    <h3 data-neural-inspector-title>ICSA Learning Hub</h3>
                    <p data-neural-inspector-description>Every route combines guided learning, practical experience, and support from ICSA instructors in Kuwait.</p>
                </div>
                <a class="public-neural__inspector-action" href="#courses" data-neural-inspector-action>See all courses <i class="fas fa-arrow-right"></i></a>
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

                <a href="{{ route('site.home', ['category' => 'nursing']) }}#courses" class="category-card">
                    <img class="category-photo" src="{{ asset('images/category-healthcare.jpg') }}" alt="Students learning nursing and healthcare">
                    <h3>Nursing &amp; Healthcare</h3>
                    <p>Build practical healthcare knowledge and professional skills for patient-centered careers.</p>
                    <span class="category-courses">{{ $categoryCounts['nursing'] }} Courses</span>
                </a>

                <a href="{{ route('site.home', ['category' => 'design']) }}#courses" class="category-card">
                    <img class="category-photo" src="{{ asset('images/category-design-multimedia.jpg') }}" alt="Students learning design and multimedia production">
                    <h3>Design &amp; Multimedia</h3>
                    <p>Develop creative skills in graphic design, 3D modeling, video editing, and multimedia production.</p>
                    <span class="category-courses">{{ $categoryCounts['design'] }} Courses</span>
                </a>

                <a href="{{ route('site.home', ['category' => 'short-skills']) }}#courses" class="category-card">
                    <img class="category-photo" src="{{ asset('images/category-short-skill-courses.jpg') }}" alt="Students attending a practical short skill course">
                    <h3>Short Skill Courses</h3>
                    <p>Build practical, job-ready skills through focused short courses and hands-on training.</p>
                    <span class="category-courses">{{ $categoryCounts['short-skills'] }} Courses</span>
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
                <div class="course-search home-course-search" role="search">
                    <div class="course-search-input-container">
                        <input type="search" id="courseSearch" class="course-search-input" placeholder="Search courses..." autocomplete="off" aria-label="Search courses">
                        <span class="course-search-icon" aria-hidden="true">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 5H20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 8H17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 11.5C21 16.75 16.75 21 11.5 21C6.25 21 2 16.75 2 11.5C2 6.25 6.25 2 11.5 2" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M22 22L20 20" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </div>
                    <p class="course-search-status" id="courseSearchStatus" aria-live="polite"></p>
                </div>
            </div>

            <div class="filter-buttons home-course-filter">
                <button type="button" class="filter-btn active" data-filter="all" aria-pressed="true">All Courses</button>
                <button type="button" class="filter-btn" data-filter="it" aria-pressed="false">IT & Technical</button>
                <button type="button" class="filter-btn" data-filter="diploma" aria-pressed="false">UK Diploma Programs</button>
                <button type="button" class="filter-btn" data-filter="language" aria-pressed="false">Language & Professional</button>
                <button type="button" class="filter-btn" data-filter="nursing" aria-pressed="false">Nursing & Healthcare</button>
                <button type="button" class="filter-btn" data-filter="design" aria-pressed="false">Design & Multimedia</button>
                <button type="button" class="filter-btn" data-filter="short-skills" aria-pressed="false">Short Skill Courses</button>
            </div>

            <div class="courses-grid" id="coursesGrid">
                @forelse ($courses as $course)
                    @php($searchText = implode(' ', array_filter([
                        $course['title'] ?? '',
                        $course['slug'] ?? '',
                        $course['badge'] ?? '',
                        $course['listing_category_label'] ?? '',
                        $course['description'] ?? '',
                        $course['overview'] ?? '',
                        $course['learning_outcomes'] ?? '',
                        $course['target_audience'] ?? '',
                        $course['careers'] ?? '',
                        $course['duration'] ?? '',
                        $course['certification'] ?? '',
                    ])))
                    <article class="course-card" data-category="{{ implode(',', $course['listing_categories']) }}" data-search="{{ $searchText }}">
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
                    <a href="{{ route('site.contact') }}" class="btn btn-outline btn-lg" style="border-color: var(--primary-dark); color: var(--primary-dark);">Contact Us</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
@endsection
