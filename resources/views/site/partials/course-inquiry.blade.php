<section id='reserve-a-spot' class='inquiry-section' style='scroll-margin-top: 100px;'>
<div class='container'>
            <div class='inquiry-grid'>
                <div class="course-inquiry-column">
                    <div class='inquiry-info'>
                        <span class='section-label'>Course Highlights</span>
                        <h3 class="course-learning-title">What Will You Learn?</h3>
                        <ul class='course-inquiry-outcomes'>
                            @forelse (($course['learning_outcome_items'] ?? $course['highlight_items'] ?? []) as $item)
                                <li style='gap: 0;'><i class='fas fa-check' style='margin-right: 1rem;'></i><span>{{ $item }}</span></li>
                            @empty
                                <li style='gap: 0;'><i class='fas fa-check' style='margin-right: 1rem;'></i><span>Practical skills designed for your career goals</span></li>
                            @endforelse
                        </ul>
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
