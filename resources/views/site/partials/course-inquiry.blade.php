<section class='inquiry-section'>
<div class='container'>
            <div class='inquiry-grid'>
                <div class='inquiry-info'>
                    <span class='section-label'>Course Inquiry</span>
                    <h2>Interested in This Course?</h2>
                    <p>Submit your details and our admissions team will contact you with schedules, fees, and enrollment guidance.</p>
                    <a href="{{ route('site.home') }}#contact" class='btn btn-primary' style='margin-top: 1rem;'>Contact Us</a>
                </div>
                <form class='inquiry-form course-contact-form' method='post' action="{{ route('api.contact') }}">
                    @csrf
                    <h3 style='margin-bottom:0.5rem;color:var(--primary);'>Send Us a Message</h3>
                    <p style='margin-bottom:1.5rem;color:var(--gray-600);'>Fill out the form below and we'll get back to you as soon as possible.</p>
                    <div class='form-row'>
                        <div class='form-group'><label class='form-label'>Full Name *</label><input type='text' class='form-input' name='name' required></div>
                        <div class='form-group'><label class='form-label'>Email Address *</label><input type='email' class='form-input' name='email' required></div>
                    </div>
                    <div class='form-row'>
                        <div class='form-group'><label class='form-label'>Phone Number *</label><input type='text' class='form-input' name='phone' inputmode='tel' required></div>
                        <div class='form-group'>
                            <label class='form-label'>Course *</label>
                            <select class='form-select' name='course' required>
                                <option value='' disabled>Select a course</option>
                                <optgroup label='IT & Technical'>
                                    <option value='computer-secretarial'>Computer Secretarial</option>
                                    <option value='office-management'>Office Management</option>
                                    <option value='graphics-designing'>Graphics Designing</option>
                                    <option value='multimedia-motion-graphics'>Multimedia & Motion Graphics</option>
                                    <option value='web-designing'>Web Designing</option>
                                    <option value='programming-web-development'>Programming & Web Development</option>
                                    <option value='pc-networking'>PC, Laptop Maintenance & Networking</option>
                                    <option value='autocad-2d-3d'>AutoCAD 2D & 3D</option>
                                    <option value='3d-studio-max'>3D Studio Max</option>
                                    <option value='revit'>Revit</option>
                                    <option value='sketchup'>SketchUp</option>
                                </optgroup>
                                <optgroup label='UK Diploma Programs'>
                                    <option value='uk-diploma-strategic-management'>UK Diploma in Strategic Management & Leadership</option>
                                    <option value='uk-diploma-health-social-care'>UK Diploma in Health & Social Care</option>
                                    <option value='uk-diploma-information-technology'>UK Diploma in Information Technology</option>
                                    <option value='uk-diploma-accounting-finance'>UK Diploma in Accounting & Finance</option>
                                    <option value='uk-diploma-hospitality-tourism'>UK Diploma in Hospitality & Tourism Management</option>
                                    <option value='uk-diploma-business-management'>UK Diploma in Business Management</option>
                                    <option value='uk-diploma-entrepreneurship'>UK Diploma in Business Innovation & Entrepreneurship</option>
                                </optgroup>
                                <optgroup label='Language & Professional'>
                                    <option value='ielts-preparation'>IELTS Preparation</option>
                                    <option value='english-enhancement'>English Enhancement</option>
                                    <option value='arabic-learning'>Arabic Learning</option>
                                    <option value='airline-ticketing'>Airline Ticketing & Travel Agent</option>
                                </optgroup>
                            </select>
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
