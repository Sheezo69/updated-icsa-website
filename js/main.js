// ICSA Website - Main JavaScript

window.getAppBasePath = function() {
    const mainScript = Array.from(document.scripts).find((script) => {
        const src = script.getAttribute('src') || '';

        return /(?:^|\/)js\/main\.js(?:[?#].*)?$/i.test(src);
    });

    if (!mainScript) {
        return '';
    }

    try {
        const scriptUrl = new URL(mainScript.src, window.location.href);
        const basePath = scriptUrl.pathname.replace(/\/js\/main\.js$/i, '');

        return basePath === '/' ? '' : basePath.replace(/\/$/, '');
    } catch (error) {
        return '';
    }
};

window.resolveAppPath = function(path = '') {
    const basePath = window.getAppBasePath();
    const normalizedPath = String(path).replace(/^\/+/, '');

    if (!normalizedPath) {
        return basePath || '/';
    }

    return `${basePath}/${normalizedPath}`.replace(/\/{2,}/g, '/');
};

window.showSitePopup = function(message, type = 'info') {
    if (!message) return;

    const existingPopup = document.querySelector('.site-popup-backdrop');
    if (existingPopup) {
        existingPopup.remove();
    }

    const safeType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
    const autoCloseMs = 4500;
    let closeTimeout = null;
    let isClosing = false;
    const titleMap = {
        success: '',
        error: 'Something Went Wrong',
        warning: 'Notice',
        info: 'Message'
    };

    const backdrop = document.createElement('div');
    backdrop.className = 'site-popup-backdrop';

    const popup = document.createElement('div');
    popup.className = `site-popup site-popup-${safeType}`;
    popup.setAttribute('role', 'alertdialog');
    popup.setAttribute('aria-modal', 'true');
    popup.setAttribute('aria-live', 'assertive');
    popup.setAttribute('tabindex', '-1');

    if (safeType === 'success') {
        const imageCandidates = [
            'images/submission_success.png',
            'images/submission_success.jpg',
            'images/submission_success.jpeg',
            'images/submission_success.webp',
            'images/subbmission_success.png'
        ].map((path) => window.toAbsoluteUrl(window.resolveAppPath(path)));

        const visual = document.createElement('div');
        visual.className = 'site-popup-visual';

        const visualImg = document.createElement('img');
        visualImg.className = 'site-popup-image';
        visualImg.alt = 'Submission success';

        let imageIndex = 0;
        const loadNextImage = () => {
            if (imageIndex >= imageCandidates.length) {
                visual.remove();
                return;
            }
            visualImg.src = imageCandidates[imageIndex];
            imageIndex += 1;
        };

        visualImg.addEventListener('error', loadNextImage);
        loadNextImage();

        visual.appendChild(visualImg);
        popup.appendChild(visual);
    }

    const titleText = titleMap[safeType];

    const text = document.createElement('p');
    text.className = 'site-popup-message';
    text.textContent = String(message);

    const timer = document.createElement('div');
    timer.className = 'site-popup-timer';
    const timerFill = document.createElement('span');
    timerFill.className = 'site-popup-timer-fill';
    timerFill.style.animationDuration = `${autoCloseMs}ms`;
    timer.appendChild(timerFill);
    if (titleText) {
        const title = document.createElement('h3');
        title.className = 'site-popup-title';
        title.textContent = titleText;
        popup.appendChild(title);
    }
    popup.appendChild(text);
    popup.appendChild(timer);
    backdrop.appendChild(popup);
    document.body.appendChild(backdrop);

    const onKeyDown = (event) => {
        if (event.key === 'Escape') {
            closePopup();
        }
    };

    const closePopup = () => {
        if (isClosing) return;
        isClosing = true;
        if (closeTimeout) {
            clearTimeout(closeTimeout);
        }
        document.removeEventListener('keydown', onKeyDown);
        backdrop.classList.remove('is-visible');
        setTimeout(() => {
            backdrop.remove();
        }, 180);
    };

    backdrop.addEventListener('click', (event) => {
        if (event.target === backdrop) {
            closePopup();
        }
    });

    document.addEventListener('keydown', onKeyDown);

    requestAnimationFrame(() => {
        backdrop.classList.add('is-visible');
        timerFill.classList.add('is-running');
        popup.focus();
    });

    closeTimeout = setTimeout(closePopup, autoCloseMs);
};

window.resolveApiPath = function(filename) {
    if (!filename) return null;

    return window.resolveAppPath(`api/${filename}`);
};

window.toAbsoluteUrl = function(path) {
    if (!path) return null;
    try {
        return new URL(path, window.location.href).toString();
    } catch (error) {
        return path;
    }
};

window.getFriendlySubmitError = function(error, fallbackMessage = 'Unable to send message. Please try again.') {
    const rawMessage = error && typeof error.message === 'string' ? error.message : '';

    if (rawMessage.includes('Failed to fetch')) {
        return 'Cannot reach the form API. Open the website through your configured server URL and make sure PHP is serving the app.';
    }

    if (rawMessage.includes('expected pattern') || rawMessage.includes('did not match')) {
        return fallbackMessage;
    }

    return rawMessage || fallbackMessage;
};

window.submitApiForm = async function(endpoint, formData) {
    const response = await fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json'
        },
        body: formData
    });

    let data = {};
    try {
        data = await response.json();
    } catch (jsonError) {
        data = {};
    }

    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Unable to send message. Please try again.');
    }

    return data;
};

window.validateContactForm = function(form) {
    const nameInput = form.querySelector('input[name="name"]');
    const emailInput = form.querySelector('input[name="email"]');
    const phoneInput = form.querySelector('input[name="phone"]');

    const name = nameInput ? nameInput.value.trim() : '';
    const email = emailInput ? emailInput.value.trim() : '';
    const phone = phoneInput ? phoneInput.value.trim() : '';

    if (!name || !email || !phone) {
        return 'Name, email, and phone are required.';
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        return 'Please enter a valid email address.';
    }

    if (!/^[0-9+()\-\s]{7,40}$/.test(phone)) {
        return 'Please enter a valid phone number.';
    }

    return '';
};

let csrfTokenPromise = null;

window.fetchCsrfToken = async function() {
    if (csrfTokenPromise) {
        return csrfTokenPromise;
    }

    csrfTokenPromise = (async () => {
        const tokenEndpoint = window.toAbsoluteUrl(window.resolveApiPath('csrf-token.php'));
        const response = await fetch(tokenEndpoint, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store'
        });

        if (!response.ok) {
            throw new Error('Unable to initialize form security.');
        }

        const payload = await response.json();
        if (!payload || !payload.success || !payload.csrf_token) {
            throw new Error('Unable to initialize form security.');
        }

        return String(payload.csrf_token);
    })();

    try {
        return await csrfTokenPromise;
    } catch (error) {
        csrfTokenPromise = null;
        throw error;
    }
};

window.attachFormSecurity = async function(formData, form) {
    const token = await window.fetchCsrfToken();
    formData.set('csrf_token', token);
    formData.set('_token', token);
    formData.set('website', '');

    if (form) {
        let csrfInput = form.querySelector('input[name="csrf_token"]');
        if (!csrfInput) {
            csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            form.appendChild(csrfInput);
        }
        csrfInput.value = token;

        let laravelCsrfInput = form.querySelector('input[name="_token"]');
        if (!laravelCsrfInput) {
            laravelCsrfInput = document.createElement('input');
            laravelCsrfInput.type = 'hidden';
            laravelCsrfInput.name = '_token';
            form.appendChild(laravelCsrfInput);
        }
        laravelCsrfInput.value = token;

        let honeypotInput = form.querySelector('input[name="website"]');
        if (!honeypotInput) {
            honeypotInput = document.createElement('input');
            honeypotInput.type = 'text';
            honeypotInput.name = 'website';
            honeypotInput.tabIndex = -1;
            honeypotInput.autocomplete = 'off';
            honeypotInput.style.display = 'none';
            honeypotInput.setAttribute('aria-hidden', 'true');
            form.appendChild(honeypotInput);
        }
        honeypotInput.value = '';
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // Live footer year + short copyright format
    const currentYear = new Date().getFullYear();
    document.querySelectorAll('.footer-bottom p').forEach(p => {
        p.textContent = `\u00A9 ${currentYear} ICSA. All rights reserved.`;
    });

    // Top scroll progress bar
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    document.body.appendChild(progressBar);

    // Premium card tilt interaction (desktop only)
    const tiltableCards = document.querySelectorAll('.course-card, .category-card');
    const canTilt = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    if (canTilt) {
        tiltableCards.forEach(card => {
            card.classList.add('is-tiltable');

            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const rotateY = ((x / rect.width) - 0.5) * 8;
                const rotateX = (0.5 - (y / rect.height)) * 6;
                card.classList.add('interactive-glow');
                card.style.setProperty('--mx', `${x}px`);
                card.style.setProperty('--my', `${y}px`);
                card.style.transform = `perspective(900px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px)`;
            });

            card.addEventListener('mouseleave', () => {
                card.classList.remove('interactive-glow');
                card.style.transform = '';
            });
        });
    }

    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const nav = document.querySelector('.nav');
    
    if (menuToggle && nav) {
        menuToggle.setAttribute('aria-label', 'Toggle navigation menu');
        menuToggle.setAttribute('aria-expanded', 'false');

        const closeMenu = () => {
            nav.classList.remove('active');
            menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            menuToggle.setAttribute('aria-expanded', 'false');
        };

        menuToggle.addEventListener('click', () => {
            nav.classList.toggle('active');
            const isOpen = nav.classList.contains('active');
            menuToggle.innerHTML = nav.classList.contains('active') ? 
                '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
            menuToggle.setAttribute('aria-expanded', String(isOpen));
        });

        // Close menu when clicking on a link
        nav.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                closeMenu();
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', (event) => {
            if (!nav.classList.contains('active')) return;
            if (nav.contains(event.target) || menuToggle.contains(event.target)) return;
            closeMenu();
        });

        // Close menu on Escape
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && nav.classList.contains('active')) {
                closeMenu();
            }
        });

        // Reset menu state on desktop resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024 && nav.classList.contains('active')) {
                closeMenu();
            }
        });
    }

    // Course Filter on Courses Page
    const filterBtns = document.querySelectorAll('.filter-btn');
    const courseCards = document.querySelectorAll('.course-card');

    if (filterBtns.length > 0) {
        const applyCourseFilter = (filter) => {
            // Remove active from all buttons
            filterBtns.forEach(b => b.classList.remove('active'));

            // Activate matching button if found
            const activeBtn = Array.from(filterBtns).find(b => b.dataset.filter === filter) || filterBtns[0];
            activeBtn.classList.add('active');

            const activeFilter = activeBtn.dataset.filter;

            courseCards.forEach(card => {
                if (activeFilter === 'all' || card.dataset.category === activeFilter) {
                    card.style.display = 'block';
                    card.style.animation = 'fadeInUp 0.5s ease-out';
                } else {
                    card.style.display = 'none';
                }
            });
        };

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                applyCourseFilter(btn.dataset.filter);
            });
        });

        // Support direct category links like courses.html?category=it
        const categoryFromUrl = new URLSearchParams(window.location.search).get('category');
        if (categoryFromUrl) {
            applyCourseFilter(categoryFromUrl);
        }
    }

    // Smooth Scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
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

    // Header scroll effect
    const header = document.querySelector('.header');
    let lastScroll = 0;

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        const totalHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = totalHeight > 0 ? currentScroll / totalHeight : 0;
        progressBar.style.transform = `scaleX(${Math.max(0, Math.min(1, progress))})`;

        if (currentScroll > 100) {
            header.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
        } else {
            header.style.boxShadow = '0 1px 3px 0 rgb(0 0 0 / 0.1)';
        }

        lastScroll = currentScroll;
    });

    // Form validation helper
    window.validateForm = function(form) {
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.style.borderColor = 'var(--danger)';
                
                // Remove error styling on input
                input.addEventListener('input', function() {
                    this.style.borderColor = '';
                }, { once: true });
            }
        });

        return isValid;
    };

    // Scroll reveal via IntersectionObserver
    const setupReveal = () => {
        const autoTargets = document.querySelectorAll(
            '.course-card, .category-card, .feature-item, .testimonial-card, .value-card, .team-card, .stat-item, .section-header'
        );
        autoTargets.forEach(el => {
            if (!el.hasAttribute('data-reveal')) {
                el.setAttribute('data-reveal', '');
                const parent = el.parentElement;
                const sameClass = Array.from(parent.children).filter(c => c.classList[0] === el.classList[0]);
                const idx = sameClass.indexOf(el);
                if (idx >= 0 && idx < 5) el.setAttribute('data-delay', String(idx + 1));
            }
        });

        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        document.querySelectorAll('[data-reveal]').forEach(el => revealObserver.observe(el));
    };

    setupReveal();

    // Counter animation for stats
    const animateCounters = () => {
        const counters = document.querySelectorAll('.hero-stat-value, .stat-content h4');
        
        counters.forEach(counter => {
            const originalText = counter.innerText.trim();
            const target = parseInt(originalText);
            const suffix = originalText.replace(/[0-9]/g, '');
            if (!isNaN(target) && target > 0) {
                const duration = 2000;
                const step = target / (duration / 16);
                let current = 0;

                const updateCounter = () => {
                    current += step;
                    if (current < target) {
                        counter.innerText = Math.floor(current) + suffix;
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.innerText = target + suffix;
                    }
                };

                // Only animate when in view
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            updateCounter();
                            observer.unobserve(entry.target);
                        }
                    });
                });

                observer.observe(counter);
            }
        });
    };

    animateCounters();

    // FAQ Accordion
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        if (!question || !answer) return;

        if (item.classList.contains('open')) {
            answer.style.maxHeight = answer.scrollHeight + 'px';
        }

        question.addEventListener('click', () => {
            const isOpen = item.classList.contains('open');
            faqItems.forEach(i => {
                i.classList.remove('open');
                const a = i.querySelector('.faq-answer');
                if (a) a.style.maxHeight = '0';
            });
            if (!isOpen) {
                item.classList.add('open');
                answer.style.maxHeight = answer.scrollHeight + 'px';
            }
        });
    });

    // Image error fallback - graceful handling for missing course images
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function () {
            if (!this.dataset.errored) {
                this.dataset.errored = '1';
                this.style.background = 'var(--gray-200)';
                if (!this.style.minHeight) this.style.minHeight = '80px';
            }
        });
    });

    // Pre-fill course in contact form if URL has course parameter
    const urlParams = new URLSearchParams(window.location.search);
    const courseParam = urlParams.get('course');
    if (courseParam) {
        const courseSelect = document.querySelector('select[name="course"]');
        if (courseSelect) {
            courseSelect.value = courseParam;
        }
    }

    // Prime security token and ensure hidden fields exist on forms that post to /api/
    const apiForms = document.querySelectorAll('form[action*="api/"]');
    if (apiForms.length > 0 && window.location.protocol !== 'file:') {
        window.fetchCsrfToken()
            .then((token) => {
                apiForms.forEach((form) => {
                    let csrfInput = form.querySelector('input[name="csrf_token"]');
                    if (!csrfInput) {
                        csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = 'csrf_token';
                        form.appendChild(csrfInput);
                    }
                    csrfInput.value = token;

                    let laravelCsrfInput = form.querySelector('input[name="_token"]');
                    if (!laravelCsrfInput) {
                        laravelCsrfInput = document.createElement('input');
                        laravelCsrfInput.type = 'hidden';
                        laravelCsrfInput.name = '_token';
                        form.appendChild(laravelCsrfInput);
                    }
                    laravelCsrfInput.value = token;

                    let honeypotInput = form.querySelector('input[name="website"]');
                    if (!honeypotInput) {
                        honeypotInput = document.createElement('input');
                        honeypotInput.type = 'text';
                        honeypotInput.name = 'website';
                        honeypotInput.tabIndex = -1;
                        honeypotInput.autocomplete = 'off';
                        honeypotInput.style.display = 'none';
                        honeypotInput.setAttribute('aria-hidden', 'true');
                        form.appendChild(honeypotInput);
                    }
                    honeypotInput.value = '';
                });
            })
            .catch(() => {
                // Do not block page rendering; submission handlers will surface actionable errors.
            });
    }

    const contactForm = document.getElementById('contactForm');
    if (contactForm && !contactForm.dataset.bound) {
        contactForm.dataset.bound = 'true';
        const submitButton = contactForm.querySelector('button[type="submit"]');
        const defaultButtonHtml = submitButton ? submitButton.innerHTML : '';

        const setSubmitting = (isSubmitting) => {
            if (!submitButton) return;
            submitButton.disabled = isSubmitting;
            submitButton.setAttribute('aria-busy', String(isSubmitting));
            submitButton.innerHTML = isSubmitting
                ? '<i class="fas fa-spinner fa-spin"></i> Sending...'
                : defaultButtonHtml;
        };

        contactForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (window.location.protocol === 'file:') {
                showSitePopup('Open this page through your website URL, not directly as a local file.', 'warning');
                return;
            }

            const validationMessage = window.validateContactForm(contactForm);
            if (validationMessage) {
                showSitePopup(validationMessage, 'error');
                return;
            }

            const endpoint = window.toAbsoluteUrl(contactForm.getAttribute('action') || 'api/contact-submit.php');
            const formData = new FormData(contactForm);

            try {
                setSubmitting(true);
                await window.attachFormSecurity(formData, contactForm);
                await window.submitApiForm(endpoint, formData);
                showSitePopup('Thank you for your message! We will get back to you soon.', 'success');
                contactForm.reset();
            } catch (error) {
                const message = window.getFriendlySubmitError(error);
                showSitePopup(message, 'error');
            } finally {
                setSubmitting(false);
            }
        });
    }

    console.log('ICSA Website loaded successfully!');

    // Course pages: move full detail blocks into hero space
    const courseDetailContent = document.querySelector('.course-detail-content');
    const courseBlocks = document.querySelectorAll('.course-content-grid .course-block');
    if (courseDetailContent && courseBlocks.length >= 4 && !document.querySelector('.course-hero-details')) {
        const heroDetails = document.createElement('div');
        heroDetails.className = 'course-hero-details';

        Array.from(courseBlocks).slice(0, 4).forEach(block => {
            const card = document.createElement('article');
            card.className = 'course-hero-detail-card';
            card.innerHTML = block.innerHTML;
            heroDetails.appendChild(card);
        });

        courseDetailContent.appendChild(heroDetails);
        const lowerDetailsSection = document.querySelector('.course-content-section');
        if (lowerDetailsSection) {
            lowerDetailsSection.remove();
        }
    }

    // Course pages: fill "Interested in" heading with current course title
    const courseTitle = document.querySelector('.course-detail-content h1');
    const inquiryHeading = document.querySelector('.inquiry-info h2');
    if (courseTitle && inquiryHeading) {
        const title = courseTitle.textContent.trim();
        if (title) {
            inquiryHeading.textContent = `Interested in ${title}?`;
        }
    }
});

// Toast notification function
window.showToast = function(message, type = 'success') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 100px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? 'var(--success)' : 'var(--danger)'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-weight: 500;
        animation: slideInRight 0.3s ease-out;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }

    .site-popup-backdrop {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(15, 23, 42, 0.55);
        opacity: 0;
        transition: opacity 0.18s ease;
        z-index: 20000;
    }

    .site-popup-backdrop.is-visible {
        opacity: 1;
    }

    .site-popup {
        width: min(460px, 100%);
        background: #ffffff;
        border: 1px solid #dbe7f3;
        border-radius: 16px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.24);
        padding: 1.05rem 1.05rem 0.95rem;
        transform: translateY(12px) scale(0.98);
        opacity: 0;
        transition: transform 0.18s ease, opacity 0.18s ease;
    }

    .site-popup-backdrop.is-visible .site-popup {
        transform: translateY(0) scale(1);
        opacity: 1;
    }

    .site-popup-title {
        margin: 0 0 0.45rem;
        font-family: var(--font-heading);
        font-size: 1.05rem;
        line-height: 1.25;
        color: var(--primary);
    }

    .site-popup-visual {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 220px;
        height: 220px;
        margin: 0 auto 0.55rem;
        overflow: hidden;
    }

    .site-popup-image {
        width: 220px;
        height: 220px;
        object-fit: contain;
        transform: scale(1.7);
        transform-origin: center;
    }

    .site-popup-message {
        margin: 0;
        font-size: 0.97rem;
        line-height: 1.55;
        color: var(--gray-700);
    }

    .site-popup-success .site-popup-title {
        color: var(--success);
    }

    .site-popup-error .site-popup-title {
        color: var(--danger);
    }

    .site-popup-warning .site-popup-title {
        color: #a16207;
    }

    .site-popup-timer {
        margin-top: 0.75rem;
        height: 4px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }

    .site-popup-timer-fill {
        display: block;
        height: 100%;
        width: 100%;
        transform-origin: left center;
        background: var(--primary);
    }

    .site-popup-success .site-popup-timer-fill {
        background: var(--success);
    }

    .site-popup-error .site-popup-timer-fill {
        background: var(--danger);
    }

    .site-popup-warning .site-popup-timer-fill {
        background: #a16207;
    }

    .site-popup-timer-fill.is-running {
        animation-name: sitePopupTimer;
        animation-timing-function: linear;
        animation-fill-mode: forwards;
    }

    @keyframes sitePopupTimer {
        from {
            transform: scaleX(1);
        }
        to {
            transform: scaleX(0);
        }
    }

    @media (max-width: 480px) {
        .site-popup {
            border-radius: 14px;
            padding: 0.95rem 0.9rem 0.9rem;
        }

        .site-popup-visual {
            width: 180px;
            height: 180px;
        }

        .site-popup-image {
            width: 180px;
            height: 180px;
            transform: scale(1.65);
        }
    }

    .nav.active {
        display: flex;
        flex-direction: column;
        position: absolute;
        top: calc(100% + 0.45rem);
        left: 0;
        right: 0;
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius);
        padding: 0.75rem;
        box-shadow: var(--shadow-lg);
        z-index: 1000;
        max-height: calc(100vh - 110px);
        overflow-y: auto;
    }

    .nav.active .nav-link {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--gray-200);
    }

    .nav.active .nav-link:last-child {
        border-bottom: none;
    }

    @media (max-width: 1024px) {
        .nav {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .nav.active {
            padding: 0.6rem;
            max-height: calc(100vh - 90px);
        }
    }
`;
document.head.appendChild(style);
