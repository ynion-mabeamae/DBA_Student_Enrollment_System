// Form toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtns = document.querySelectorAll('.toggle-btn');
            const formContainers = document.querySelectorAll('.form-container');
            
            toggleBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const formType = this.getAttribute('data-form');
                    toggleBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    formContainers.forEach(form => {
                        form.classList.remove('active');
                        if (form.id === formType + '-form') {
                            form.classList.add('active');
                        }
                    });
                });
            });
        });

        // Form toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtns = document.querySelectorAll('.toggle-btn');
            const formContainers = document.querySelectorAll('.form-container');
            
            toggleBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const formType = this.getAttribute('data-form');
                    toggleBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    formContainers.forEach(form => {
                        form.classList.remove('active');
                        if (form.id === formType + '-form') {
                            form.classList.add('active');
                        }
                    });
                });
            });

            // Create floating shapes
            createFloatingShapes();
            // Create pulse dots
            createPulseDots();
            // Create moving lines
            createMovingLines();
        });

        function createFloatingShapes() {
            const container = document.getElementById('floatingShapes');
            const shapes = ['circle', 'triangle', 'square'];
            const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57'];
            
            for (let i = 0; i < 15; i++) {
                const shape = document.createElement('div');
                const shapeType = shapes[Math.floor(Math.random() * shapes.length)];
                shape.className = `shape ${shapeType}`;
                
                // Random properties
                const size = Math.random() * 60 + 20;
                const left = Math.random() * 100;
                const top = Math.random() * 100;
                const duration = Math.random() * 10 + 5;
                const delay = Math.random() * 5;
                const color = colors[Math.floor(Math.random() * colors.length)];
                
                shape.style.width = `${size}px`;
                shape.style.height = `${size}px`;
                shape.style.left = `${left}%`;
                shape.style.top = `${top}%`;
                shape.style.animationDuration = `${duration}s`;
                shape.style.animationDelay = `${delay}s`;
                shape.style.backgroundColor = color;
                
                container.appendChild(shape);
            }
        }

        function createPulseDots() {
            const container = document.getElementById('pulseDots');
            
            for (let i = 0; i < 20; i++) {
                const dot = document.createElement('div');
                dot.className = 'dot';
                
                // Random properties
                const size = Math.random() * 8 + 2;
                const left = Math.random() * 100;
                const top = Math.random() * 100;
                const duration = Math.random() * 4 + 2;
                const delay = Math.random() * 3;
                
                dot.style.width = `${size}px`;
                dot.style.height = `${size}px`;
                dot.style.left = `${left}%`;
                dot.style.top = `${top}%`;
                dot.style.animationDuration = `${duration}s`;
                dot.style.animationDelay = `${delay}s`;
                
                container.appendChild(dot);
            }
        }

        function createMovingLines() {
            const container = document.getElementById('movingLines');
            
            for (let i = 0; i < 8; i++) {
                const line = document.createElement('div');
                line.className = 'line';
                
                // Random properties
                const width = Math.random() * 200 + 50;
                const height = Math.random() * 4 + 1;
                const top = Math.random() * 100;
                const duration = Math.random() * 30 + 20;
                const delay = Math.random() * 10;
                
                line.style.width = `${width}px`;
                line.style.height = `${height}px`;
                line.style.top = `${top}%`;
                line.style.animationDuration = `${duration}s`;
                line.style.animationDelay = `${delay}s`;
                
                container.appendChild(line);
            }
        }