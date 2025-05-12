document.addEventListener('DOMContentLoaded', function() {
    // Add sparkle effect to all buttons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const sparkle = document.createElement('div');
            sparkle.className = 'sparkle';
            sparkle.style.left = (e.clientX - this.getBoundingClientRect().left) + 'px';
            sparkle.style.top = (e.clientY - this.getBoundingClientRect().top) + 'px';
            this.appendChild(sparkle);
            
            setTimeout(() => {
                sparkle.remove();
            }, 600);
        });
    });

    // Add sparkle effect to all links
    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function(e) {
            const sparkle = document.createElement('div');
            sparkle.className = 'sparkle';
            sparkle.style.left = (e.clientX - this.getBoundingClientRect().left) + 'px';
            sparkle.style.top = (e.clientY - this.getBoundingClientRect().top) + 'px';
            this.appendChild(sparkle);
            
            setTimeout(() => {
                sparkle.remove();
            }, 600);
        });
    });
}); 