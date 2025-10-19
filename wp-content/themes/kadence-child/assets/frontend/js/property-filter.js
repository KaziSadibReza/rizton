jQuery(document).ready(function ($) {
  // Tab functionality
  $(".tab-link").on("click", function (e) {
    e.preventDefault();

    // Remove active class from all tabs
    $(".tab-link").removeClass("active");

    // Add active class to clicked tab
    $(this).addClass("active");

    // Update hidden input value
    const filterType = $(this).data("filter-type");
    $("#filter-type-input").val(filterType);
  });

  // Form submission handler
  $("#property-filter-form").on("submit", function (e) {
    // Let the form submit normally to redirect to archive page
    // The Elementor widget will automatically filter based on URL parameters
  });

  // Initialize the form with current URL parameters
  initializeFormFromURL();

  function initializeFormFromURL() {
    const urlParams = new URLSearchParams(window.location.search);

    // Set filter type tab
    const propertyFor = urlParams.get("property_for");
    if (propertyFor) {
      $(".tab-link").removeClass("active");
      $(`.tab-link[data-filter-type="${propertyFor}"]`).addClass("active");
      $("#filter-type-input").val(propertyFor);
    }

    // Set location input
    const location = urlParams.get("location");
    if (location) {
      $("#filter-location").val(location);
    }

    // Set property type select
    const propertyType = urlParams.get("property_type");
    if (propertyType) {
      $("#filter-property-type").val(propertyType);
    }
  }
});
