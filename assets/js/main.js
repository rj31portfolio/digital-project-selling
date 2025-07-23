 // General JavaScript for the frontend

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Handle AJAX coupon validation
    const couponForm = document.getElementById('couponForm');
    if (couponForm) {
        couponForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const couponCode = document.getElementById('couponCode').value;
            const projectId = document.getElementById('projectId').value;
            
            fetch('validate-coupon.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `coupon_code=${encodeURIComponent(couponCode)}&project_id=${encodeURIComponent(projectId)}`
            })
            .then(response => response.json())
            .then(data => {
                const couponMessage = document.getElementById('couponMessage');
                if (data.success) {
                    couponMessage.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    // Update price display
                    document.getElementById('totalAmount').textContent = data.total_amount;
                    document.getElementById('discountAmount').textContent = data.discount_amount;
                } else {
                    couponMessage.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
    
    // Prevent right-click on preview images
    const previewImages = document.querySelectorAll('.preview-image');
    previewImages.forEach(img => {
        img.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
    });
    
    // Handle file upload preview
    const fileInputs = document.querySelectorAll('.file-input-preview');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const preview = document.getElementById(this.dataset.previewId);
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    });
});
