/**
 * SweetAlert2 Initialization
 * Configure global settings for SweetAlert2
 */

// Configure SweetAlert2 defaults
if (typeof Swal !== 'undefined') {
    Swal.mixin({
        confirmButtonColor: '#4CAF50',
        cancelButtonColor: '#f44336',
        buttonsStyling: true,
        customClass: {
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-outline'
        }
    });
}

