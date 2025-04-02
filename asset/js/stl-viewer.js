  /**
   * Creates the scene, camera and renderer.
   * Sets up lighting and loads the STL model to display on screen.
   */
  let scene, camera, renderer, controls;

  // Function to initialize scene
  function init(modelUrl, options = {}) {
    // Set default options
    const config = {
      backgroundColor: options.backgroundColor || '#ffffff',
      autoRotate: options.autoRotate !== undefined ? options.autoRotate : true,
      showGrid: options.showGrid !== undefined ? options.showGrid : false
    };
    
    // Convert hex color to Three.js color value
    const bgColor = new THREE.Color(config.backgroundColor);
    
    // Create scene
    scene = new THREE.Scene();
    
    // Add grid if enabled
    if (config.showGrid) {
      // Create a grid helper
      const gridSize = 100;
      const gridDivisions = 20;
      const gridHelper = new THREE.GridHelper(gridSize, gridDivisions, 0x00ff00, 0x00ff00);
      // Rotate the grid to be horizontal (XZ plane)
      gridHelper.position.y = -20; // Position slightly below the model
      scene.add(gridHelper);
    }

    // Configure camera
    camera = new THREE.PerspectiveCamera(
      60, // Field of view
      window.innerWidth / window.innerHeight, // Aspect ratio
      0.1, // Near clipping plane
      1000 // Far clipping plane
    );
    camera.position.set(0, 0, 100);

    // Create renderer and add it to the document
    renderer = new THREE.WebGLRenderer({ antialias: true });
    
    // Get the specific container
    const container = document.querySelector('.media-render');

    // Use container dimensions instead of window
    renderer.setSize(container.clientWidth, container.clientHeight);

    // Set background color from configuration
    renderer.setClearColor(bgColor);
    // document.body.appendChild(renderer.domElement);
document.querySelector('.media-render').appendChild(renderer.domElement);
    

    // Add orbit controls to manipulate the camera
    controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true; // Add inertia to controls
    controls.dampingFactor = 0.25;
    controls.screenSpacePanning = false;
    controls.maxDistance = 500;
    
    // Set auto-rotation based on configuration
    controls.autoRotate = config.autoRotate;
    controls.autoRotateSpeed = 1.0;
    
    // Log the auto-rotate setting for debugging
    console.log('STL Viewer: Auto-rotate is ' + (config.autoRotate ? 'enabled' : 'disabled'));

    // Add lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambientLight);
  
    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(0, 50, 50).normalize();
    scene.add(directionalLight);

    // Load the STL file
    const loader = new THREE.STLLoader();
    loader.load(
      modelUrl, // Path to your STL file
      function (geometry) {
        // Hide loading message
        const loadingElement = document.getElementById('loading');
        if (loadingElement) {
          loadingElement.style.display = 'none';
        }
          
        // Create a material with better appearance
        const material = new THREE.MeshPhongMaterial({ 
          color: 0x999999,
          specular: 0x111111,
          shininess: 200
        });
        const mesh = new THREE.Mesh(geometry, material);
  
        // Center the model
        geometry.computeBoundingBox();
        const boundingBox = geometry.boundingBox;
        const center = new THREE.Vector3();
        boundingBox.getCenter(center);
        mesh.position.set(-center.x, -center.y, -center.z);
          
        // Adjust scale to fit view
        const size = new THREE.Vector3();
        boundingBox.getSize(size);
        const maxDim = Math.max(size.x, size.y, size.z);
        const scale = 50 / maxDim;
        mesh.scale.set(scale, scale, scale);
  
        scene.add(mesh);
          
        // Adjust camera to see the complete model
        const distance = 100;
        camera.position.set(distance, distance, distance);
        camera.lookAt(0, 0, 0);
        controls.update();
      },
      // Progress function (optional)
      function (xhr) {
        if (xhr.lengthComputable) {
          const percentComplete = Math.round(xhr.loaded / xhr.total * 100);
          document.getElementById('loading').textContent = 
            `Loading STL model: ${percentComplete}%`;
          
          // Hide the loading message when it reaches 100%
          if (percentComplete === 100) {
            document.getElementById('loading').style.display = 'none';
          }
        }
      },
      // Error function (optional)
      function (error) {
        document.getElementById('loading').textContent = 
          'Error loading STL model. Please verify the file exists.';
        document.getElementById('loading').style.backgroundColor = 'rgba(255,0,0,0.7)';
        console.error('Error loading STL', error);
      }
    );

    // Ajustamos la escena cuando la ventana cambia de tamaño
    window.addEventListener('resize', onWindowResize, false);
  }

  // Function to adjust the renderer to the window size
function onWindowResize() {
    const container = document.querySelector('.media-render');
    camera.aspect = container.clientWidth / container.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(container.clientWidth, container.clientHeight);
}

  // Animation function: renders each frame
  function animate() {
    requestAnimationFrame(animate);
    controls.update(); // Update controls in each frame
    renderer.render(scene, camera);
  }

document.addEventListener('DOMContentLoaded', () => {
    // Get URL and configuration from data attributes
    const loadingElement = document.getElementById('loading');
    if (!loadingElement) {
        console.error('Loading element not found');
        return;
    }
    
    const stl_path = loadingElement.dataset.stlUrl;
    const backgroundColor = loadingElement.dataset.backgroundColor || '#ffffff';
    const autoRotate = loadingElement.dataset.autoRotate === 'true';
    const showGrid = loadingElement.dataset.showGrid === 'true';
    
    // Verify we have a valid URL
    if (!stl_path) {
        console.error('STL model URL not found');
        document.getElementById('loading').textContent = 
            'Error: Could not load the model';
        return;
    }

    // Initialize with configuration
    init(stl_path, {
        backgroundColor: backgroundColor,
        autoRotate: autoRotate,
        showGrid: showGrid
    });
    animate();
});
