// 3D Sky Simulator using Three.js
class SkySimulator {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.stars = [];
        this.planets = [];
        this.constellationLines = [];
        this.labels = [];
        this.raycaster = new THREE.Raycaster();
        this.mouse = new THREE.Vector2();
        this.selectedObject = null;

        this.init();
        this.animate();
        this.setupEventListeners();
    }

    init() {
        // Create scene
        this.scene = new THREE.Scene();

        // Create camera
        this.camera = new THREE.PerspectiveCamera(
            75,
            this.canvas.clientWidth / this.canvas.clientHeight,
            0.1,
            10000
        );
        this.camera.position.set(0, 0, 100);

        // Create renderer
        this.renderer = new THREE.WebGLRenderer({
            canvas: this.canvas,
            antialias: true,
            alpha: true
        });
        this.renderer.setSize(this.canvas.clientWidth, this.canvas.clientHeight);
        this.renderer.setPixelRatio(window.devicePixelRatio);

        // Create controls
        this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.05;
        this.controls.minDistance = 50;
        this.controls.maxDistance = 500;

        // Create celestial sphere
        this.createCelestialSphere();

        // Create starfield background
        this.createStarfield();

        // Add ambient light
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        this.scene.add(ambientLight);

        // Add point light
        const pointLight = new THREE.PointLight(0xffffff, 1);
        pointLight.position.set(0, 0, 0);
        this.scene.add(pointLight);

        // Handle window resize
        window.addEventListener('resize', () => this.onWindowResize(), false);
    }

    createCelestialSphere() {
        const geometry = new THREE.SphereGeometry(800, 64, 64);
        const material = new THREE.MeshBasicMaterial({
            color: 0x000033,
            side: THREE.BackSide,
            transparent: true,
            opacity: 0.3
        });
        const sphere = new THREE.Mesh(geometry, material);
        this.scene.add(sphere);
    }

    createStarfield() {
        const starGeometry = new THREE.BufferGeometry();
        const starCount = 5000;
        const positions = new Float32Array(starCount * 3);

        for (let i = 0; i < starCount * 3; i += 3) {
            // Random position on sphere
            const radius = 700 + Math.random() * 100;
            const theta = Math.random() * Math.PI * 2;
            const phi = Math.acos(2 * Math.random() - 1);

            positions[i] = radius * Math.sin(phi) * Math.cos(theta);
            positions[i + 1] = radius * Math.sin(phi) * Math.sin(theta);
            positions[i + 2] = radius * Math.cos(phi);
        }

        starGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

        const starMaterial = new THREE.PointsMaterial({
            color: 0xffffff,
            size: 1,
            transparent: true,
            opacity: 0.8
        });

        const starfield = new THREE.Points(starGeometry, starMaterial);
        this.scene.add(starfield);
    }

    // Convert astronomical coordinates to Cartesian
    astronomicalToCartesian(altitude, azimuth, distance = 200) {
        const altRad = THREE.MathUtils.degToRad(altitude);
        const azRad = THREE.MathUtils.degToRad(azimuth);

        // Convert to Cartesian (azimuth measured from North, clockwise)
        const x = distance * Math.cos(altRad) * Math.sin(azRad);
        const y = distance * Math.sin(altRad);
        const z = -distance * Math.cos(altRad) * Math.cos(azRad);

        return new THREE.Vector3(x, y, z);
    }

    // Add Sun
    addSun(sunData) {
        this.removeCelestialObject('sun');

        if (sunData.visible) {
            const geometry = new THREE.SphereGeometry(8, 32, 32);
            const material = new THREE.MeshBasicMaterial({
                color: 0xffff00,
                emissive: 0xffaa00,
                emissiveIntensity: 1
            });
            const sun = new THREE.Mesh(geometry, material);

            const position = this.astronomicalToCartesian(sunData.altitude, sunData.azimuth);
            sun.position.copy(position);
            sun.userData = { type: 'sun', name: 'Güneş', data: sunData };

            this.scene.add(sun);

            // Add sun glow
            const glowGeometry = new THREE.SphereGeometry(12, 32, 32);
            const glowMaterial = new THREE.MeshBasicMaterial({
                color: 0xffff00,
                transparent: true,
                opacity: 0.3
            });
            const glow = new THREE.Mesh(glowGeometry, glowMaterial);
            glow.position.copy(position);
            this.scene.add(glow);
        }
    }

    // Add Moon
    addMoon(moonData) {
        this.removeCelestialObject('moon');

        if (moonData.visible) {
            const geometry = new THREE.SphereGeometry(5, 32, 32);
            const material = new THREE.MeshStandardMaterial({
                color: 0xcccccc,
                emissive: 0x444444,
                roughness: 0.8
            });
            const moon = new THREE.Mesh(geometry, material);

            const position = this.astronomicalToCartesian(moonData.altitude, moonData.azimuth);
            moon.position.copy(position);
            moon.userData = { type: 'moon', name: 'Ay', data: moonData };

            this.scene.add(moon);

            // Add label
            this.addLabel(`Ay (${moonData.phase_name})`, position);
        }
    }

    // Add Planet
    addPlanet(name, planetData) {
        this.removeCelestialObject(name);

        if (planetData && planetData.visible) {
            const colors = {
                'Mercury': 0x8c7853,
                'Venus': 0xffc649,
                'Mars': 0xff6347,
                'Jupiter': 0xd4a373,
                'Saturn': 0xfad5a5
            };

            const sizes = {
                'Mercury': 2,
                'Venus': 4,
                'Mars': 3,
                'Jupiter': 6,
                'Saturn': 5
            };

            const geometry = new THREE.SphereGeometry(sizes[name] || 3, 32, 32);
            const material = new THREE.MeshStandardMaterial({
                color: colors[name] || 0xffffff,
                emissive: colors[name] || 0xffffff,
                emissiveIntensity: 0.3
            });
            const planet = new THREE.Mesh(geometry, material);

            const position = this.astronomicalToCartesian(planetData.altitude, planetData.azimuth);
            planet.position.copy(position);
            planet.userData = { type: 'planet', name: name, data: planetData };

            this.scene.add(planet);
            this.planets.push(planet);

            // Add label
            this.addLabel(name, position);
        }
    }

    // Add Star
    addStar(starData) {
        if (starData.visible) {
            // Size based on magnitude (brighter = larger)
            const size = Math.max(0.5, 5 - starData.magnitude);

            const geometry = new THREE.SphereGeometry(size, 16, 16);
            const color = this.getStarColor(starData.spectral_type);
            const material = new THREE.MeshBasicMaterial({
                color: color,
                emissive: color,
                emissiveIntensity: 0.5
            });
            const star = new THREE.Mesh(geometry, material);

            const position = this.astronomicalToCartesian(starData.altitude, starData.azimuth, 250);
            star.position.copy(position);
            star.userData = { type: 'star', name: starData.name_tr || starData.name_common, data: starData };

            this.scene.add(star);
            this.stars.push(star);

            // Add label for bright stars
            if (starData.magnitude < 2) {
                this.addLabel(starData.name_tr || starData.name_common, position);
            }
        }
    }

    // Get star color based on spectral type
    getStarColor(spectralType) {
        if (!spectralType) return 0xffffff;

        const type = spectralType.charAt(0);
        const colors = {
            'O': 0x9bb0ff,  // Blue
            'B': 0xaabfff,  // Blue-white
            'A': 0xcad7ff,  // White
            'F': 0xf8f7ff,  // Yellow-white
            'G': 0xfff4e8,  // Yellow (like Sun)
            'K': 0xffd2a1,  // Orange
            'M': 0xffaa77   // Red
        };

        return colors[type] || 0xffffff;
    }

    // Add text label
    addLabel(text, position) {
        // Using CSS2DRenderer would be better, but for simplicity, we'll skip labels in WebGL
        // In production, use THREE.CSS2DRenderer for better text rendering
    }

    // Remove celestial object
    removeCelestialObject(type) {
        const objectsToRemove = [];
        this.scene.traverse((object) => {
            if (object.userData && object.userData.type === type) {
                objectsToRemove.push(object);
            }
        });
        objectsToRemove.forEach(obj => this.scene.remove(obj));
    }

    // Clear all celestial objects
    clearAll() {
        this.stars.forEach(star => this.scene.remove(star));
        this.planets.forEach(planet => this.scene.remove(planet));
        this.stars = [];
        this.planets = [];
    }

    // Setup event listeners
    setupEventListeners() {
        this.canvas.addEventListener('click', (event) => this.onMouseClick(event), false);
        this.canvas.addEventListener('mousemove', (event) => this.onMouseMove(event), false);
    }

    // Mouse click handler
    onMouseClick(event) {
        const rect = this.canvas.getBoundingClientRect();
        this.mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        this.mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

        this.raycaster.setFromCamera(this.mouse, this.camera);
        const intersects = this.raycaster.intersectObjects(this.scene.children, true);

        if (intersects.length > 0) {
            const object = intersects[0].object;
            if (object.userData && object.userData.type) {
                this.onObjectClick(object.userData);
            }
        }
    }

    // Mouse move handler
    onMouseMove(event) {
        const rect = this.canvas.getBoundingClientRect();
        this.mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        this.mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

        this.raycaster.setFromCamera(this.mouse, this.camera);
        const intersects = this.raycaster.intersectObjects(this.scene.children, true);

        const tooltip = document.getElementById('object-tooltip');

        if (intersects.length > 0) {
            const object = intersects[0].object;
            if (object.userData && object.userData.name) {
                tooltip.textContent = object.userData.name;
                tooltip.style.left = event.clientX + 'px';
                tooltip.style.top = event.clientY + 'px';
                tooltip.classList.remove('hidden');
                this.canvas.style.cursor = 'pointer';
            } else {
                tooltip.classList.add('hidden');
                this.canvas.style.cursor = 'default';
            }
        } else {
            tooltip.classList.add('hidden');
            this.canvas.style.cursor = 'default';
        }
    }

    // Object click callback
    onObjectClick(objectData) {
        if (window.app) {
            window.app.showObjectDetails(objectData);
        }
    }

    // Handle window resize
    onWindowResize() {
        this.camera.aspect = this.canvas.clientWidth / this.canvas.clientHeight;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(this.canvas.clientWidth, this.canvas.clientHeight);
    }

    // Animation loop
    animate() {
        requestAnimationFrame(() => this.animate());
        this.controls.update();
        this.renderer.render(this.scene, this.camera);
    }
}
