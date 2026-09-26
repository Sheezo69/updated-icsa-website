(() => {
    const config = window.ICSA_GALAXY;
    const root = document.querySelector('[data-galaxy-root]');
    const canvas = root?.querySelector('[data-galaxy-canvas]');
    if (!config || !root || !canvas) return;

    const context = canvas.getContext('2d', { alpha: true });
    const viewport = root.querySelector('[data-galaxy-viewport]');
    const cursorCard = root.querySelector('[data-galaxy-cursor-card]');
    const statusRegion = root.querySelector('[data-galaxy-status]');
    const toast = root.querySelector('[data-galaxy-toast]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const world = { width: 1600, height: 900 };
    const camera = { x: 0, y: 0, zoom: 1 };
    const categoryPositions = [
        [410, 245], [790, 175], [1185, 270], [1190, 640], [790, 720], [405, 635],
    ];
    let payload = config.payload;
    let entities = [];
    let selected = null;
    let hovered = null;
    let dragged = null;
    let dragOffset = { x: 0, y: 0 };
    let panStart = null;
    let activeFilter = 'all';
    let replayDays = 0;
    let animationFrame = null;
    let lastTimestamp = 0;
    let lastPaint = 0;
    let fetching = false;
    let toastTimer = null;

    const hash = (value) => {
        let number = 2166136261;
        for (let index = 0; index < String(value).length; index += 1) {
            number ^= String(value).charCodeAt(index);
            number = Math.imul(number, 16777619);
        }
        return Math.abs(number >>> 0);
    };

    const deterministic = (seed, min, max) => min + ((hash(seed) % 10000) / 10000) * (max - min);
    const hexToRgba = (hex, alpha) => {
        const value = hex.replace('#', '');
        const number = parseInt(value.length === 3 ? value.split('').map((part) => part + part).join('') : value, 16);
        return `rgba(${(number >> 16) & 255},${(number >> 8) & 255},${number & 255},${alpha})`;
    };
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]));

    const buildEntities = () => {
        const built = [];
        payload.categories.forEach((category, categoryIndex) => {
            const [x, y] = categoryPositions[categoryIndex] || [800, 450];
            built.push({ ...category, type: 'category', x, y, radius: 34 + Math.min(13, category.demand * 2), color: category.color });
            category.courses.forEach((course, courseIndex) => {
                const angle = ((Math.PI * 2) / Math.max(1, category.courses.length)) * courseIndex + deterministic(course.slug, -.16, .16);
                const orbit = 76 + (courseIndex % 3) * 34 + deterministic(`${course.slug}:orbit`, -8, 8);
                built.push({
                    ...course,
                    type: 'course',
                    categoryId: category.id,
                    x: x + Math.cos(angle) * orbit,
                    y: y + Math.sin(angle) * orbit,
                    orbitX: x,
                    orbitY: y,
                    radius: 7 + Math.min(7, course.demand * 1.5),
                    color: category.color,
                });
            });
        });

        payload.staff.forEach((staff, index) => {
            const spacing = Math.min(190, 960 / Math.max(1, payload.staff.length));
            built.push({ ...staff, type: 'staff', x: 800 + (index - (payload.staff.length - 1) / 2) * spacing, y: 840, radius: 23, color: '#34d399' });
        });

        payload.inquiries.forEach((inquiry, index) => {
            const matchedCourse = built.find((entity) => entity.type === 'course' && normalize(entity.title) === normalize(inquiry.course));
            const baseX = matchedCourse ? matchedCourse.x : 800;
            const baseY = matchedCourse ? matchedCourse.y : 450;
            const angle = deterministic(`${inquiry.id}:angle`, 0, Math.PI * 2);
            const distance = deterministic(`${inquiry.id}:distance`, 58, 150);
            built.push({
                ...inquiry,
                type: 'inquiry',
                x: baseX + Math.cos(angle) * distance,
                y: baseY + Math.sin(angle) * distance,
                homeX: baseX + Math.cos(angle) * distance,
                homeY: baseY + Math.sin(angle) * distance,
                radius: 9 + Math.min(5, inquiry.score / 25),
                color: inquiry.neglected ? '#fb7185' : '#fb923c',
                phase: deterministic(inquiry.id, 0, Math.PI * 2),
            });
        });

        payload.stars.forEach((star) => {
            const angle = deterministic(`${star.id}:star-angle`, 0, Math.PI * 2);
            const distance = deterministic(`${star.id}:star-distance`, 150, 690);
            built.push({ ...star, type: 'star', x: 800 + Math.cos(angle) * distance, y: 450 + Math.sin(angle) * distance * .52, radius: deterministic(star.id, 1.4, 3.4), color: '#ddd6fe' });
        });
        entities = built;
        renderObjectList();
    };

    function normalize(value) {
        return String(value || '').toLowerCase().replace(/-/g, ' ').replace(/\s+/g, ' ').trim();
    }

    const visibleForTimeline = (entity) => {
        if (!['inquiry', 'star'].includes(entity.type) || replayDays === 0) return true;
        if (!entity.timestamp) return false;
        const age = Math.floor((payload.generated_at - entity.timestamp) / 86400);
        return Math.abs(age - replayDays) <= 1;
    };

    const isVisible = (entity) => (activeFilter === 'all' || entity.type === activeFilter || entity.type === 'category') && visibleForTimeline(entity);

    const resize = () => {
        const rect = viewport.getBoundingClientRect();
        const ratio = Math.min(1.5, window.devicePixelRatio || 1);
        canvas.width = Math.max(1, Math.round(rect.width * ratio));
        canvas.height = Math.max(1, Math.round(rect.height * ratio));
        canvas.style.width = `${rect.width}px`;
        canvas.style.height = `${rect.height}px`;
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        draw(performance.now(), false);
    };

    const baseScale = () => Math.min(viewport.clientWidth / world.width, viewport.clientHeight / world.height) * camera.zoom;
    const worldToScreen = (x, y) => {
        const scale = baseScale();
        return { x: viewport.clientWidth / 2 + (x - world.width / 2 + camera.x) * scale, y: viewport.clientHeight / 2 + (y - world.height / 2 + camera.y) * scale, scale };
    };
    const screenToWorld = (x, y) => {
        const scale = baseScale();
        return { x: (x - viewport.clientWidth / 2) / scale + world.width / 2 - camera.x, y: (y - viewport.clientHeight / 2) / scale + world.height / 2 - camera.y };
    };

    const drawBackground = (width, height, time) => {
        context.clearRect(0, 0, width, height);
        const gradient = context.createRadialGradient(width * .5, height * .48, 0, width * .5, height * .48, Math.max(width, height) * .65);
        gradient.addColorStop(0, 'rgba(13,48,79,.42)');
        gradient.addColorStop(.46, 'rgba(4,20,40,.24)');
        gradient.addColorStop(1, 'rgba(1,8,20,0)');
        context.fillStyle = gradient;
        context.fillRect(0, 0, width, height);
        for (let index = 0; index < 125; index += 1) {
            const x = deterministic(`bgx:${index}`, 0, width);
            const y = deterministic(`bgy:${index}`, 0, height);
            const alpha = .18 + deterministic(`bga:${index}`, 0, .48) * (reducedMotion ? 1 : .72 + Math.sin(time / 1100 + index) * .28);
            context.fillStyle = `rgba(151,205,255,${alpha})`;
            context.beginPath();
            context.arc(x, y, deterministic(`bgr:${index}`, .35, 1.35), 0, Math.PI * 2);
            context.fill();
        }
    };

    const drawConnections = () => {
        const categories = entities.filter((entity) => entity.type === 'category' && isVisible(entity));
        const core = worldToScreen(800, 450);
        categories.forEach((category) => {
            const point = worldToScreen(category.x, category.y);
            context.strokeStyle = hexToRgba(category.color, .12);
            context.lineWidth = 1;
            context.setLineDash([4, 12]);
            context.beginPath();
            context.moveTo(core.x, core.y);
            context.quadraticCurveTo((core.x + point.x) / 2, Math.min(core.y, point.y) - 22, point.x, point.y);
            context.stroke();
        });
        context.setLineDash([]);
        entities.filter((entity) => entity.type === 'course' && isVisible(entity)).forEach((course) => {
            const center = worldToScreen(course.orbitX, course.orbitY);
            const point = worldToScreen(course.x, course.y);
            context.strokeStyle = hexToRgba(course.color, hovered?.id === course.id || selected?.id === course.id ? .5 : .105);
            context.lineWidth = hovered?.id === course.id || selected?.id === course.id ? 1.4 : .7;
            context.beginPath(); context.moveTo(center.x, center.y); context.lineTo(point.x, point.y); context.stroke();
        });
    };

    const drawCore = (time) => {
        const point = worldToScreen(800, 450);
        const pulse = reducedMotion ? 0 : Math.sin(time / 900) * 4;
        const gradient = context.createRadialGradient(point.x, point.y, 4, point.x, point.y, 62 + pulse);
        gradient.addColorStop(0, 'rgba(255,255,255,.98)'); gradient.addColorStop(.1, 'rgba(65,239,225,.95)'); gradient.addColorStop(.32, 'rgba(38,133,235,.4)'); gradient.addColorStop(1, 'rgba(38,133,235,0)');
        context.fillStyle = gradient; context.beginPath(); context.arc(point.x, point.y, 64 + pulse, 0, Math.PI * 2); context.fill();
        context.strokeStyle = 'rgba(82,235,229,.34)'; context.lineWidth = 1; context.beginPath(); context.arc(point.x, point.y, 82 + pulse * .6, 0, Math.PI * 2); context.stroke();
    };

    const drawEntity = (entity, time) => {
        if (!isVisible(entity)) return;
        let x = entity.x;
        let y = entity.y;
        if (entity.type === 'inquiry' && !dragged && !reducedMotion) {
            x += Math.cos(time / 1150 + entity.phase) * 5;
            y += Math.sin(time / 900 + entity.phase) * 4;
        }
        const point = worldToScreen(x, y);
        const radius = Math.max(2, entity.radius * Math.max(.72, point.scale));
        const active = selected?.id === entity.id || hovered?.id === entity.id;
        context.save();
        context.globalAlpha = activeFilter === 'all' || activeFilter === entity.type || entity.type === 'category' ? 1 : .12;

        if (entity.type === 'star') {
            const twinkle = reducedMotion ? 1 : .65 + Math.sin(time / 500 + hash(entity.id)) * .35;
            context.shadowBlur = 10; context.shadowColor = entity.color; context.fillStyle = hexToRgba(entity.color, twinkle);
            context.beginPath(); context.arc(point.x, point.y, radius, 0, Math.PI * 2); context.fill(); context.restore(); return;
        }

        if (entity.type === 'inquiry') {
            context.strokeStyle = hexToRgba(entity.color, active ? .95 : .55); context.lineWidth = active ? 2.4 : 1.4;
            context.beginPath(); context.moveTo(point.x - radius * 3.6, point.y + radius * 1.8); context.lineTo(point.x - radius * .5, point.y + radius * .2); context.stroke();
        }

        if (entity.type === 'staff') {
            context.translate(point.x, point.y); context.rotate(-Math.PI / 2);
            context.shadowBlur = active ? 26 : 13; context.shadowColor = entity.color; context.fillStyle = '#0c4239'; context.strokeStyle = entity.color; context.lineWidth = active ? 2.5 : 1.4;
            context.beginPath(); context.moveTo(radius * 1.15, 0); context.lineTo(-radius * .75, -radius * .68); context.lineTo(-radius * .35, 0); context.lineTo(-radius * .75, radius * .68); context.closePath(); context.fill(); context.stroke();
            context.rotate(Math.PI / 2); context.translate(-point.x, -point.y);
        } else {
            const gradient = context.createRadialGradient(point.x - radius * .28, point.y - radius * .28, 1, point.x, point.y, radius * 1.25);
            gradient.addColorStop(0, '#ffffff'); gradient.addColorStop(.15, entity.color); gradient.addColorStop(.72, hexToRgba(entity.color, .28)); gradient.addColorStop(1, 'rgba(4,16,31,.9)');
            context.fillStyle = gradient; context.shadowBlur = active ? 30 : (entity.type === 'category' ? 20 : 10); context.shadowColor = entity.color; context.strokeStyle = active ? '#fff' : hexToRgba(entity.color, .85); context.lineWidth = active ? 2.4 : 1;
            context.beginPath(); context.arc(point.x, point.y, radius, 0, Math.PI * 2); context.fill(); context.stroke();
            if (entity.type === 'category') {
                context.strokeStyle = hexToRgba(entity.color, .28); context.setLineDash([3, 6]); context.beginPath(); context.arc(point.x, point.y, radius + 10, 0, Math.PI * 2); context.stroke(); context.setLineDash([]);
            }
        }

        if (active || entity.type === 'category' || entity.type === 'staff') {
            context.shadowBlur = 0; context.textAlign = 'center'; context.fillStyle = active ? '#fff' : '#bfd2e8'; context.font = `${active ? 700 : 600} ${entity.type === 'category' ? 11 : 9}px Inter, sans-serif`;
            context.fillText(entity.label || entity.name || entity.title, point.x, point.y + radius + 17);
        }
        context.restore();
    };

    const draw = (timestamp = 0, continuous = true) => {
        if (continuous && timestamp - lastPaint < 32) {
            animationFrame = requestAnimationFrame(draw);
            return;
        }
        lastPaint = timestamp;
        lastTimestamp = timestamp;
        const width = viewport.clientWidth;
        const height = viewport.clientHeight;
        drawBackground(width, height, timestamp);
        drawConnections();
        drawCore(timestamp);
        entities.filter((entity) => entity.type === 'star').forEach((entity) => drawEntity(entity, timestamp));
        entities.filter((entity) => entity.type !== 'star').forEach((entity) => drawEntity(entity, timestamp));
        if (continuous && !reducedMotion && !document.hidden) animationFrame = requestAnimationFrame(draw);
    };

    const hitTest = (screenX, screenY, types = null) => {
        return [...entities].reverse().find((entity) => {
            if (!isVisible(entity) || (types && !types.includes(entity.type))) return false;
            const point = worldToScreen(entity.x, entity.y);
            const radius = Math.max(10, entity.radius * point.scale) + (entity.type === 'star' ? 5 : 7);
            return Math.hypot(screenX - point.x, screenY - point.y) <= radius;
        }) || null;
    };

    const pointerPosition = (event) => {
        const rect = canvas.getBoundingClientRect();
        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    };

    const selectEntity = (entity) => {
        selected = entity;
        const visual = root.querySelector('[data-galaxy-inspector-visual]');
        const icon = root.querySelector('[data-galaxy-inspector-icon]');
        const title = root.querySelector('[data-galaxy-inspector-title]');
        const type = root.querySelector('[data-galaxy-inspector-type]');
        const copy = root.querySelector('[data-galaxy-inspector-copy]');
        const metrics = root.querySelector('[data-galaxy-inspector-metrics]');
        const action = root.querySelector('[data-galaxy-inspector-action]');
        const details = inspectorDetails(entity);
        visual.className = `galaxy-inspector-visual is-${entity.type}`;
        visual.style.setProperty('--object-color', entity.color || '#5eead4');
        icon.className = details.icon;
        title.textContent = details.title;
        type.textContent = details.type;
        copy.textContent = details.copy;
        metrics.innerHTML = details.metrics.map(([value, label]) => `<div><strong>${escapeHtml(value)}</strong><span>${escapeHtml(label)}</span></div>`).join('');
        action.href = details.url || '#';
        action.firstChild.textContent = `${details.action} `;
        action.hidden = !details.url;
        draw(lastTimestamp, false);
    };

    const inspectorDetails = (entity) => {
        if (entity.type === 'category') return { icon: 'fas fa-solar-panel', type: 'COURSE SYSTEM', title: entity.label, copy: `${entity.courses.length} course planets orbit this learning system, carrying ${entity.demand} current demand signals.`, metrics: [[entity.courses.length, 'Courses'], [entity.demand, 'Signals']], action: 'Explore system', url: entity.courses[0]?.url };
        if (entity.type === 'course') return { icon: 'fas fa-earth-americas', type: 'COURSE PLANET', title: entity.title, copy: `${entity.duration}. Drag an inquiry comet onto this planet to mark the lead qualified for this course.`, metrics: [[entity.demand, 'Demand'], [entity.duration, 'Duration']], action: 'Open course', url: entity.url };
        if (entity.type === 'inquiry') return { icon: 'fas fa-meteor', type: entity.neglected ? 'NEGLECTED SIGNAL' : 'INQUIRY COMET', title: entity.name, copy: `${entity.course} · ${entity.assignee ? `Assigned to ${entity.assignee}` : 'Waiting for assignment'}. Drag this comet onto a staff ship or course planet.`, metrics: [[entity.score, 'Intent'], [entity.stage.replace('_', ' '), 'Stage']], action: 'Open inquiry', url: entity.url };
        if (entity.type === 'staff') return { icon: 'fas fa-shuttle-space', type: 'STAFF COMMAND SHIP', title: entity.name, copy: `This team member currently owns ${entity.active} active inquiry signals. Drop a comet here to assign it instantly.`, metrics: [[entity.active, 'Active'], [entity.qualified, 'Qualified']], action: 'View workload', url: entity.url };
        return { icon: 'fas fa-star', type: 'ENROLLMENT STAR', title: entity.name, copy: `${entity.course}. This permanent star represents a successfully resolved enrollment journey.`, metrics: [['Complete', 'Journey'], ['Enrolled', 'Status']], action: 'View inquiries', url: null };
    };

    const renderObjectList = () => {
        const list = root.querySelector('[data-galaxy-object-list]');
        const visible = entities.filter((entity) => ['inquiry', 'staff'].includes(entity.type) && isVisible(entity)).slice(0, 18);
        root.querySelector('[data-galaxy-object-count]').textContent = visible.length;
        list.innerHTML = visible.length ? visible.map((entity) => `<button type="button" data-galaxy-object="${escapeHtml(entity.id)}"><i class="fas ${entity.type === 'inquiry' ? 'fa-meteor' : 'fa-shuttle-space'}"></i><span><strong>${escapeHtml(entity.name)}</strong><small>${escapeHtml(entity.type === 'inquiry' ? entity.course : `${entity.active} active signals`)}</small></span><b></b></button>`).join('') : '<p>No matching live objects.</p>';
    };

    const showToast = (message, isError = false) => {
        toast.querySelector('i').className = `fas ${isError ? 'fa-triangle-exclamation' : 'fa-check'}`;
        toast.querySelector('span').textContent = message;
        toast.classList.toggle('is-error', isError);
        toast.hidden = false;
        statusRegion.textContent = message;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.hidden = true, 3500);
    };

    const performAction = async (inquiry, target) => {
        const body = target.type === 'staff'
            ? { action: 'assign_staff', staff_id: target.record_id }
            : { action: 'qualify_course', course_slug: target.slug };
        showToast(target.type === 'staff' ? `Routing ${inquiry.name} to ${target.name}…` : `Qualifying ${inquiry.name} for ${target.title}…`);
        try {
            const response = await fetch(config.actionUrl.replace('__ID__', inquiry.record_id), {
                method: 'PATCH', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(body),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'The command could not be saved.');
            payload = data.snapshot;
            buildEntities();
            updateStats();
            selected = null;
            showToast(data.message);
        } catch (error) {
            buildEntities();
            showToast(error.message || 'The command could not be saved.', true);
        }
    };

    const updateStats = () => Object.entries(payload.stats).forEach(([key, value]) => {
        const element = root.querySelector(`[data-galaxy-stat="${key}"]`);
        if (element) element.textContent = value;
    });

    canvas.addEventListener('pointerdown', (event) => {
        const pointer = pointerPosition(event);
        const entity = hitTest(pointer.x, pointer.y);
        canvas.setPointerCapture(event.pointerId);
        if (entity?.type === 'inquiry') {
            dragged = entity;
            const worldPoint = screenToWorld(pointer.x, pointer.y);
            dragOffset = { x: entity.x - worldPoint.x, y: entity.y - worldPoint.y };
            canvas.classList.add('is-dragging-object');
        } else {
            panStart = { ...pointer, cameraX: camera.x, cameraY: camera.y };
            canvas.classList.add('is-panning');
            if (entity) selectEntity(entity);
        }
    });
    canvas.addEventListener('pointermove', (event) => {
        const pointer = pointerPosition(event);
        if (dragged) {
            const worldPoint = screenToWorld(pointer.x, pointer.y);
            dragged.x = worldPoint.x + dragOffset.x; dragged.y = worldPoint.y + dragOffset.y;
            hovered = hitTest(pointer.x, pointer.y, ['staff', 'course']);
        } else if (panStart && event.buttons) {
            const scale = baseScale();
            camera.x = panStart.cameraX + (pointer.x - panStart.x) / scale;
            camera.y = panStart.cameraY + (pointer.y - panStart.y) / scale;
        } else {
            hovered = hitTest(pointer.x, pointer.y);
            canvas.style.cursor = hovered?.type === 'inquiry' ? 'grab' : (hovered ? 'pointer' : 'crosshair');
            if (hovered) {
                cursorCard.hidden = false;
                cursorCard.style.transform = `translate(${pointer.x + 16}px, ${pointer.y + 16}px)`;
                cursorCard.innerHTML = `<strong>${escapeHtml(hovered.label || hovered.name || hovered.title)}</strong><span>${escapeHtml(hovered.type)}</span>`;
            } else cursorCard.hidden = true;
        }
        if (reducedMotion) draw(lastTimestamp, false);
    });
    const finishPointer = (event) => {
        if (dragged) {
            const pointer = pointerPosition(event);
            const target = hitTest(pointer.x, pointer.y, ['staff', 'course']);
            const inquiry = dragged;
            dragged = null; hovered = null;
            canvas.classList.remove('is-dragging-object');
            if (target) performAction(inquiry, target); else buildEntities();
        }
        panStart = null;
        canvas.classList.remove('is-panning');
        try { canvas.releasePointerCapture(event.pointerId); } catch (_) {}
    };
    canvas.addEventListener('pointerup', finishPointer);
    canvas.addEventListener('pointercancel', finishPointer);
    canvas.addEventListener('wheel', (event) => {
        event.preventDefault();
        camera.zoom = Math.min(2.4, Math.max(.72, camera.zoom * (event.deltaY > 0 ? .92 : 1.08)));
        if (reducedMotion) draw(lastTimestamp, false);
    }, { passive: false });

    root.addEventListener('click', (event) => {
        const filter = event.target.closest('[data-galaxy-filter]');
        if (filter) {
            activeFilter = filter.dataset.galaxyFilter;
            root.querySelectorAll('[data-galaxy-filter]').forEach((button) => button.classList.toggle('is-active', button === filter));
            renderObjectList(); if (reducedMotion) draw(lastTimestamp, false); return;
        }
        const zoom = event.target.closest('[data-galaxy-zoom]')?.dataset.galaxyZoom;
        if (zoom) {
            if (zoom === 'reset') Object.assign(camera, { x: 0, y: 0, zoom: 1 });
            else camera.zoom = Math.min(2.4, Math.max(.72, camera.zoom * (zoom === 'in' ? 1.18 : .84)));
            if (reducedMotion) draw(lastTimestamp, false); return;
        }
        const objectButton = event.target.closest('[data-galaxy-object]');
        if (objectButton) {
            const entity = entities.find((item) => item.id === objectButton.dataset.galaxyObject);
            if (entity) selectEntity(entity); return;
        }
        if (event.target.closest('[data-galaxy-fullscreen]')) {
            root.classList.toggle('is-command-mode');
            document.body.classList.toggle('galaxy-command-open', root.classList.contains('is-command-mode'));
            const button = root.querySelector('[data-galaxy-fullscreen]');
            button.querySelector('i').className = `fas ${root.classList.contains('is-command-mode') ? 'fa-compress' : 'fa-expand'}`;
            button.querySelector('span').textContent = root.classList.contains('is-command-mode') ? 'Exit command mode' : 'Command mode';
            setTimeout(resize, 80);
        }
    });

    root.querySelector('[data-galaxy-timeline]').addEventListener('input', (event) => {
        replayDays = 30 - Number(event.target.value);
        root.querySelector('[data-galaxy-time-label]').textContent = replayDays === 0 ? 'Live universe' : `${replayDays} days ago`;
        renderObjectList(); if (reducedMotion) draw(lastTimestamp, false);
    });

    const refresh = async () => {
        if (fetching || dragged || document.hidden) return;
        fetching = true;
        try {
            const response = await fetch(config.snapshotUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store' });
            if (!response.ok) return;
            const fresh = await response.json();
            root.querySelector('[data-galaxy-updated]').textContent = 'Synced just now';
            if (fresh.version !== payload.version) { payload = fresh; buildEntities(); updateStats(); }
        } catch (_) {} finally { fetching = false; }
    };

    new ResizeObserver(resize).observe(viewport);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && animationFrame) cancelAnimationFrame(animationFrame);
        else { refresh(); if (!reducedMotion) animationFrame = requestAnimationFrame(draw); }
    });
    buildEntities(); updateStats(); resize();
    if (!reducedMotion) animationFrame = requestAnimationFrame(draw);
    window.setInterval(refresh, 12000);
})();
