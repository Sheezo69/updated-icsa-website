@extends('layouts.site')

@section('title', 'Contact Us | ICSA - International Institute of Computer Science and Administration')
@section('description', 'Contact ICSA Kuwait for course inquiries, enrollment information, and more. We are here to help you start your learning journey.')
@php($showHeaderLogin = false)

@section('content')
<!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Contact Us</h1>
            <p>Get in touch with us for course inquiries, enrollment information, or any questions you may have.</p>
            <div class="breadcrumb">
                <a href="{{ route('site.home') }}">Home</a>
                <span>/</span>
                <span>Contact</span>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="section faq-section">
        <div class="container">
            <div class="contact-grid">
                <!-- Contact Info -->
                <div class="contact-info-card cic-info-card">
                    <h3>Get In Touch</h3>

                    <div class="cic-hours-box">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Working Hours</strong>
                            <span>Fri – Wed: 9:00 AM – 7:00 PM &nbsp;|&nbsp; <em>Thu: Off</em></span>
                        </div>
                    </div>

                    <div class="cic-section-label">Our Branches</div>
                    <div class="cic-branches">
                        <div class="cic-branch">
                            <div class="cic-branch-header">
                                <span class="cic-flag">🇰🇼</span>
                                <div>
                                    <strong>Maliya — Kuwait City</strong>
                                    <p>8th Floor, Panasonic Tower, Fahad Al Salem Street, Kuwait City</p>
                                    <!-- Full address: 8th Floor, Panasonic Tower, Fahad Al Salem Street, Kuwait City -->
                                </div>
                            </div>
                            <div class="cic-branch-actions">
                                <a href="https://wa.me/96597674076" target="_blank" rel="noopener" class="cic-action-btn cic-wa">
                                    <i class="fab fa-whatsapp"></i> +965 9767 4076
                                </a>
                            </div>
                        </div>

                        <div class="cic-branch">
                            <div class="cic-branch-header">
                                <span class="cic-flag">🇰🇼</span>
                                <div>
                                    <strong>Mahboula — Kuwait</strong>
                                    <p>Block 2, St. 20, Near Oxygen Gym</p>
                                    <!-- Full address: Mahboula Block 2, St. 20 Building 163, Next to Oxygen Gym -->
                                </div>
                            </div>
                            <div class="cic-branch-actions">
                                <a href="https://wa.me/96590921623" target="_blank" rel="noopener" class="cic-action-btn cic-wa">
                                    <i class="fab fa-whatsapp"></i> +965 9092 1623
                                </a>
                            </div>
                        </div>

                        <div class="cic-branch">
                            <div class="cic-branch-header">
                                <span class="cic-flag">🇦🇪</span>
                                <div>
                                    <strong>Dubai — UAE</strong>
                                    <p>Rolex Twin Towers, Baniyas Road, Deira</p>
                                    <!-- Full address: ICSA UAE - ICSA Education Support Services, Room 207, Office 12, Blue Imperial Business Center, 2nd Floor, Rolex Twin Towers, Baniyas Road, Deira, Dubai -->
                                </div>
                            </div>
                            <div class="cic-branch-actions">
                                <a href="tel:+971507901248" class="cic-action-btn cic-phone">
                                    <i class="fas fa-phone"></i> +971 50 790 1248
                                </a>
                            </div>
                        </div>

                        <div class="cic-branch">
                            <div class="cic-branch-header">
                                <span class="cic-flag">🇬🇧</span>
                                <div>
                                    <strong>London — UK</strong>
                                    <p>College House, 17 King Edwards Rd, Ruislip</p>
                                    <!-- Full address: 2nd Floor, College House, 17 King Edwards Road, Ruislip, London HA4 7AE -->
                                </div>
                            </div>
                            <div class="cic-branch-actions">
                                <a href="tel:+447467927776" class="cic-action-btn cic-phone">
                                    <i class="fas fa-phone"></i> +44 7467 927776
                                </a>
                            </div>
                        </div>

                        <div class="cic-branch">
                            <div class="cic-branch-header">
                                <span class="cic-flag">🇵🇰</span>
                                <div>
                                    <strong>Pakistan</strong>
                                    <p>Villa N.2, Sethi Colony, Rahwali</p>
                                    <!-- Full address: Villa N.2, Sethi Colony, Rahwali, Gujranwala Cantt, Gujranwala, Pakistan -->
                                </div>
                            </div>
                            <div class="cic-branch-actions">
                                <a href="tel:+923255655508" class="cic-action-btn cic-phone">
                                    <i class="fas fa-phone"></i> +92 325 5655508
                                </a>
                                <a href="mailto:nascollegeoflondon@gmail.com" class="cic-action-btn cic-email">
                                    <i class="fas fa-envelope"></i> PK Email
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="cic-section-label cic-section-label-spaced">General Contact</div>
                    <div class="cic-global">
                        <a href="tel:+96522467301" class="cic-global-item">
                            <span class="cic-global-icon"><i class="fas fa-phone"></i></span>
                            <div>
                                <span class="cic-global-label">Telephone</span>
                                <span class="cic-global-val">+965 2246 7301</span>
                            </div>
                        </a>
                        <a href="mailto:admin@icsa.us" class="cic-global-item">
                            <span class="cic-global-icon"><i class="fas fa-envelope"></i></span>
                            <div>
                                <span class="cic-global-label">Email</span>
                                <span class="cic-global-val">admin@icsa.us</span>
                            </div>
                        </a>
                    </div>

                    <div class="contact-social cic-social">
                        <h4>Follow Us</h4>
                        <div class="contact-social-links">
                            <a href="https://www.facebook.com/KuwaitICSA" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="https://www.instagram.com/icsakuwait/" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="https://www.tiktok.com/@icsa.international" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                            <a href="https://www.youtube.com/@ICSAInternational" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Contact Form + Panasonic Map -->
                <div class="cic-form-and-map">
                    <div class="inquiry-form cic-form-card" style="margin: 0;">
                        <h3 style="margin-bottom: 0.5rem; color: var(--primary);">Send Us a Message</h3>
                        <p style="margin-bottom: 1.5rem; color: var(--gray-600);">Fill out the form below and we'll get back to you as soon as possible.</p>
                        
                        <form id="contactForm" method="post" action="{{ route('api.contact') }}" novalidate>
                            @csrf
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Full Name </label>
                                    <input type="text" class="form-input" name="name" placeholder="Your full name" autocomplete="name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email Address </label>
                                    <input type="email" class="form-input" name="email" placeholder="your@email.com" autocomplete="email" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Phone Number </label>
                                    <input type="text" class="form-input" name="phone" inputmode="tel" autocomplete="tel" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Course Interest</label>
                                    <select class="form-select" name="course">
                                        <option value="">Select a course (optional)</option>
                                        <optgroup label="IT & Technical">
                                            <option value="computer-secretarial">Computer Secretarial</option>
                                            <option value="office-management">Office Management</option>
                                            <option value="graphics-designing">Graphics Designing</option>
                                            <option value="multimedia-motion-graphics">Multimedia & Motion Graphics</option>
                                            <option value="web-designing">Web Designing</option>
                                            <option value="programming-web-development">Programming & Web Development</option>
                                            <option value="pc-networking">PC, Laptop Maintenance & Networking</option>
                                            <option value="autocad-2d-3d">AutoCAD 2D & 3D</option>
                                            <option value="3d-studio-max">3D Studio Max</option>
                                            <option value="revit">Revit</option>
                                            <option value="sketchup">SketchUp</option>
                                        </optgroup>
                                        <optgroup label="UK Diploma Programs">
                                            <option value="uk-diploma-strategic-management">UK Diploma in Strategic Management & Leadership</option>
                                            <option value="uk-diploma-health-social-care">UK Diploma in Health & Social Care</option>
                                            <option value="uk-diploma-information-technology">UK Diploma in Information Technology</option>
                                            <option value="uk-diploma-accounting-finance">UK Diploma in Accounting & Finance</option>
                                            <option value="uk-diploma-hospitality-tourism">UK Diploma in Hospitality & Tourism Management</option>
                                            <option value="uk-diploma-business-management">UK Diploma in Business Management</option>
                                            <option value="uk-diploma-entrepreneurship">UK Diploma in Business Innovation & Entrepreneurship</option>
                                        </optgroup>
                                        <optgroup label="Language & Professional">
                                            <option value="ielts-preparation">IELTS Preparation</option>
                                            <option value="english-enhancement">English Enhancement</option>
                                            <option value="arabic-learning">Arabic Learning</option>
                                            <option value="airline-ticketing">Airline Ticketing & Travel Agent</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Subject</label>
                                <select class="form-select" name="subject">
                                    <option value="general">General Inquiry</option>
                                    <option value="enrollment">Course Enrollment</option>
                                    <option value="pricing">Pricing Information</option>
                                    <option value="schedule">Schedule Inquiry</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Message</label>
                                <textarea class="form-textarea" name="message" placeholder="How can we help you?"></textarea>
                            </div>

                            <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="display:none;">

                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </form>
                    </div>

                    <!-- Panasonic / Kuwait City map right under the form -->
                    <div class="cic-map-box">
                        <div class="cic-map-label">
                            <span class="cic-map-title">Kuwait</span>
                        </div>
                        <div class="map-container cic-under-form-map">
                            <iframe src="https://www.google.com/maps?q=Panasonic%20Tower%2C%2016%20Fahad%20Al-Salem%20Street%2C%20Kuwait&output=embed" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section (remaining branches) -->
    <section class="section" style="padding-top: 0;">
        <div class="container">
            <div class="map-grid">
                <div class="cic-map-box">
                    <div class="cic-map-label">
                        <span class="cic-map-title">Kuwait</span>
                    </div>
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps?q=ICSA%20Mahboula%20(International%20Institute%20of%20Computer%20Science%20and%20Administration)%2C%20Kuwait&output=embed" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
                <div class="cic-map-box">
                    <div class="cic-map-label">
                        <span class="cic-map-title">Dubai</span>
                    </div>
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps?q=Blue%20Imperial%20Business%20Center%2C%20Rolex%20Twin%20Towers%2C%20Baniyas%20Road%2C%20Deira%2C%20Dubai&output=embed" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
                <div class="cic-map-box">
                    <div class="cic-map-label">
                        <span class="cic-map-title">London</span>
                    </div>
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps?q=2nd%20Floor%2C%20College%20House%2C%2017%20King%20Edwards%20Road%2C%20Ruislip%2C%20London%20HA4%207AE&output=embed" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
                <div class="cic-map-box">
                    <div class="cic-map-label">
                        <span class="cic-map-title">Pakistan</span>
                    </div>
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps?q=Villa%20N.2%2C%20Sethi%20Colony%2C%20Rahwali%2C%20Gujranwala%20Cantt%2C%20Gujranwala%2C%20Pakistan&output=embed" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-label" style="font-size:30px; color:#c9a227; opacity:1; text-shadow:0 3px 12px rgba(0,0,0,0.35);">FAQ</span>
                <h2 class="section-title" style="font-size:30px; color:#ffffff; opacity:1; text-shadow:0 3px 12px rgba(0,0,0,0.35);">Frequently Asked Questions</h2>
            </div>

            <div class="faq-list">
                <div class="faq-item open">
                    <div class="faq-question">
                        <h4><i class="fas fa-question-circle faq-icon-q"></i> How do I enroll in a course?</h4>
                        <i class="fas fa-chevron-down faq-chevron"></i>
                    </div>
                    <div class="faq-answer">
                        <p>You can enroll by visiting our center, calling us, filling out the contact form, or sending us a message on WhatsApp. Our team will guide you through the enrollment process.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4><i class="fas fa-question-circle faq-icon-q"></i> What are the payment options?</h4>
                        <i class="fas fa-chevron-down faq-chevron"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We offer flexible payment options including full payment and installment plans. Contact us for detailed pricing information for your chosen course.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4><i class="fas fa-question-circle faq-icon-q"></i> Do you provide certificates?</h4>
                        <i class="fas fa-chevron-down faq-chevron"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, all our courses come with completion certificates. Our UK Diploma programs provide internationally recognized qualifications.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4><i class="fas fa-question-circle faq-icon-q"></i> What are the class timings?</h4>
                        <i class="fas fa-chevron-down faq-chevron"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We offer flexible class schedules including morning, afternoon, and evening batches to accommodate working professionals and students.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4><i class="fas fa-question-circle faq-icon-q"></i> Do you provide career guidance?</h4>
                        <i class="fas fa-chevron-down faq-chevron"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, we provide guidance on resume building, interview preparation, and professional skill development to help you succeed after completing your course.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
@endsection
