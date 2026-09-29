document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("account_type").addEventListener("change", function() {
        let accountType = this.value;
        let accountNumberField = document.getElementById("account_num");

        if (accountType) {
            fetch(`chartAccounts_logic.php?account_type=${accountType}`)
                .then(response => response.text())
                .then(data => {
                    accountNumberField.value = data;
                })
                .catch(error => console.error("Error fetching account number:", error));
        } else {
            accountNumberField.value = "";
        }
    });

//This is for showing each accounts section
    const links = document.querySelectorAll('.section-link');
    const sections = document.querySelectorAll('.account-section');

    links.forEach(link => {
        link.addEventListener('click', e => {
            const target = link.getAttribute('href'); //This is for getting the href/account-section of each nav

            if (target.startsWith('#')) {
                e.preventDefault();

                sections.forEach(section => section.style.display = 'none');

                const section = document.querySelector(target);
                if (section) section.style.display = 'block';
                links.forEach(nav => nav.classList.remove('active'));
                link.classList.add('active');
            }
        });
    });

    // This is for redirecting the success modal to its corresponding section
    const sectionID = window.location.hash;

    if (sectionID) {
        const targetSection = document.querySelector(sectionID);
        const targetLink = document.querySelector(`.section-link[href="${sectionID}"]`);

        if (targetSection && targetLink) {
            // Hide all sections first
            document.querySelectorAll('.account-section').forEach(section => section.style.display = 'none');

            // Show the matching section
            targetSection.style.display = 'block';

            // Highlight the correct nav item
            document.querySelectorAll('.section-link').forEach(link => link.classList.remove('active'));
            targetLink.classList.add('active');
        }
    } else {
        // If no section ID, show the first one by default
        const firstSection = document.querySelector('.account-section');
        if (firstSection) firstSection.style.display = 'block';
    }
    
});