<section class='inquiry-section'>
<div class='container'>
            <div class='inquiry-grid'>
                <div class="course-inquiry-column">
                    <div class='inquiry-info'>
                        <span class='section-label'>Course Inquiry</span>
                        <h2>Interested in This Course?</h2>
                        <p>Submit your details and our admissions team will contact you with schedules, fees, and enrollment guidance.</p>
                        <a href="{{ route('site.home') }}#contact" class='btn btn-primary' style='margin-top: 1rem;'>Contact Us</a>
                    </div>
                    @include('site.partials.course-testimonials')
                </div>
                <form class='inquiry-form course-contact-form' method='post' action="{{ route('api.contact') }}">
                    @csrf
                    <h3 style='margin-bottom:0.5rem;color:var(--primary);'>Reserve a Spot</h3>
                    <p style='margin-bottom:1.5rem;color:var(--gray-600);'>Fill out the form below and we'll get back to you as soon as possible.</p>
                    <div class='form-row'>
                        <div class='form-group'><label class='form-label'>Full Name *</label><input type='text' class='form-input' name='name' required></div>
                        <div class='form-group'><label class='form-label'>Email Address *</label><input type='email' class='form-input' name='email' required></div>
                    </div>
                    <div class='form-row'>
                        <div class='form-group'><label class='form-label'>Phone Number *</label><input type='text' class='form-input' name='phone' inputmode='tel' required></div>
                        <div class='form-group'>
                            <label class='form-label'>Course *</label>
                            <input type='text' class='form-input' value='{{ $course['title'] }}' readonly aria-label='Selected course'>
                            <input type='hidden' name='course' value='{{ $course['slug'] }}'>
                        </div>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Subject</label>
                        <select class='form-select' name='subject'>
                            <option value='general'>General Inquiry</option>
                            <option value='enrollment'>Course Enrollment</option>
                            <option value='pricing'>Pricing Information</option>
                            <option value='schedule'>Schedule Inquiry</option>
                            <option value='other'>Other</option>
                        </select>
                    </div>
                    <div class='form-group'><label class='form-label'>Message</label><textarea class='form-textarea' name='message' placeholder='How can we help you?'></textarea></div>
                    <input type='text' name='website' tabindex='-1' autocomplete='off' aria-hidden='true' style='display:none;'>
                    <button type='submit' class='btn btn-primary' style='width:100%;'>
                        <i class='fas fa-paper-plane'></i> Send Message
                    </button>
                </form>
            </div>
        </div>
</section>
