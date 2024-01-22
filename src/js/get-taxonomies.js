function updateTaxonomiesDropdowns(form='') {
    var postType;
    if ( form === 'delete' ) {
        postType = document.getElementById('delete_post_type').value;
    } else {
        postType = document.getElementById('post_type').value;
    }

    // Create a new XMLHttpRequest object
    var xhr = new XMLHttpRequest();

    // Configure it to make a POST request to the WordPress AJAX endpoint
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

    // Define the callback function to handle the response
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var taxonomies = JSON.parse(xhr.responseText);

            if (form === 'delete' ) {
                updateTaxonomyDropdown('delete_taxonomy', taxonomies);
            } else {
                // Update origin and destination taxonomy dropdowns
                updateTaxonomyDropdown('origin_taxonomy', taxonomies);
                updateTaxonomyDropdown('destination_taxonomy', taxonomies);
            }
        }
    };

    // Send the request with the selected post type
    xhr.send('action=get_taxonomies_for_post_type&post_type=' + postType);
}

function updateTaxonomyDropdown(elementId, taxonomies) {
    var dropdown = document.getElementById(elementId);
    dropdown.innerHTML = '';

    for (var i = 0; i < taxonomies.length; i++) {
        var taxonomy = taxonomies[i];
        var option = document.createElement('option');
        option.value = taxonomy.slug;
        option.text = taxonomy.label + ' (' + taxonomy.slug + ')';
        dropdown.add(option);
    }
}

// Initialize dropdowns on page load
document.addEventListener('DOMContentLoaded', function () {
    updateTaxonomiesDropdowns();
});
