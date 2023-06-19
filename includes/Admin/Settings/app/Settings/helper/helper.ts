// Fetch data from API
export const fetchDataFromAPI = async (body) => {
    const response = await fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(body).toString(),
    });
    return response;
};

// Active social profile tab
export const activeSocialTab = () => {
    history.pushState(null, null, window.location.href.split("&")[0]);
    const selectSocialProfileTab = document.querySelectorAll('[data-key="layout_social_profile"]');
    const selectSocialProfileSection = document.getElementById('layout_social_profile');
    selectSocialProfileSection.classList.add('wprf-active');
    selectSocialProfileTab.forEach((element) => {
        element.classList.add('wprf-active-nav');
    });
}