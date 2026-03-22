/**
 * Company portal: logout confirmation + loading spinner before redirect to logout.php.
 * Requires SweetAlert2 loaded before this script.
 */
window.showLogoutModal = function showLogoutModal() {
    var dd = document.getElementById('profileDropdown');
    if (dd) {
        dd.style.display = 'none';
    }
    if (typeof Swal === 'undefined') {
        if (window.confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
        return;
    }
    Swal.fire({
        title: 'Logout?',
        text: 'Are you sure you want to logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1a3876',
        cancelButtonColor: '#666',
        confirmButtonText: 'Yes, Logout',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(function (result) {
        if (!result.isConfirmed) {
            return;
        }
        Swal.fire({
            title: 'Logging out…',
            text: 'Please wait',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });
        window.location.href = 'logout.php';
    });
};
